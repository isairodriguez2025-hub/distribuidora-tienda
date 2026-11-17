<?php

declare(strict_types=1);

namespace App\Modules\Notificaciones;

use PDO;

/**
 * Cola de notificaciones con preferencias por usuario (RN-12).
 *
 * Se encola siempre; el envío real lo hace el worker. Así un pedido no se cae
 * porque el servidor de correo esté lento, y un fallo de envío se puede
 * reintentar sin repetir la lógica de negocio.
 */
final class NotificacionService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $directorioCorreos,
    ) {
    }

    /**
     * Encola una notificación respetando las preferencias del usuario.
     *
     * @param string[] $canales canales deseados: correo, push, bandeja
     * @return int cantidad de notificaciones encoladas
     */
    public function encolar(
        int $usuarioId,
        string $tipo,
        string $asunto,
        string $cuerpo,
        ?string $enlace = null,
        array $canales = ['bandeja', 'correo'],
    ): int {
        $permitidos = $this->canalesPermitidos($usuarioId, $tipo);
        $encoladas = 0;

        foreach ($canales as $canal) {
            if (!in_array($canal, $permitidos, true)) {
                continue;
            }

            $this->pdo->prepare(
                "INSERT INTO notificaciones (usuario_id, canal, tipo, asunto, cuerpo, enlace, estado)
                 VALUES (?,?,?,?,?,?,'pendiente')"
            )->execute([$usuarioId, $canal, $tipo, mb_substr($asunto, 0, 180), $cuerpo, $enlace]);

            $encoladas++;
        }

        return $encoladas;
    }

    /**
     * Canales que el usuario acepta para este tipo de evento.
     *
     * @return string[]
     */
    private function canalesPermitidos(int $usuarioId, string $tipo): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT correo, push FROM preferencias_notificacion WHERE usuario_id = ? AND tipo = ? LIMIT 1'
        );
        $stmt->execute([$usuarioId, $tipo]);
        $pref = $stmt->fetch();

        // Sin preferencia guardada se usan los canales por omisión.
        if ($pref === false) {
            return ['bandeja', 'correo', 'push'];
        }

        $canales = ['bandeja'];

        if ((int) $pref['correo'] === 1) {
            $canales[] = 'correo';
        }

        if ((int) $pref['push'] === 1) {
            $canales[] = 'push';
        }

        if ((int) ($pref['sms'] ?? 0) === 1) {
            $canales[] = 'sms';
        }

        return $canales;
    }

    /**
     * Envía las notificaciones pendientes.
     *
     * @return array{enviadas:int, fallidas:int}
     */
    public function enviarPendientes(int $limite = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT n.*, u.email, u.telefono FROM notificaciones n JOIN usuarios u ON u.id = n.usuario_id
             WHERE n.estado = 'pendiente' ORDER BY n.created_at LIMIT ?"
        );
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();

        $enviadas = 0;
        $fallidas = 0;

        foreach ($stmt->fetchAll() ?: [] as $n) {
            // La bandeja es la propia tabla: estar ahí ya es estar entregada.
            if ($n['canal'] === 'bandeja') {
                $this->marcar((int) $n['id'], 'enviado');
                $enviadas++;
                continue;
            }

            if ($n['canal'] === 'correo') {
                $ok = $this->enviarCorreo((string) $n['email'], (string) $n['asunto'], (string) $n['cuerpo'], (string) $n['id']);
                $this->marcar((int) $n['id'], $ok ? 'enviado' : 'fallido', $ok ? null : 'No se pudo entregar al servidor de correo');
                $ok ? $enviadas++ : $fallidas++;
                continue;
            }

            if ($n['canal'] === 'sms') {
                $telefono = trim((string) ($n['telefono'] ?? ''));

                if ($telefono === '') {
                    $this->marcar((int) $n['id'], 'fallido', 'El usuario no tiene teléfono registrado');
                    $fallidas++;
                    continue;
                }

                $resultado = $this->sms()->enviar($telefono, (string) $n['cuerpo']);
                $this->marcar(
                    (int) $n['id'],
                    $resultado['ok'] ? 'enviado' : 'fallido',
                    $resultado['simulado'] ? 'SIMULADO · ' . $resultado['mensaje'] : ($resultado['ok'] ? null : $resultado['mensaje']),
                );
                $resultado['ok'] ? $enviadas++ : $fallidas++;
                continue;
            }

            // Push sin librería VAPID instalada: se deja pendiente, no se finge enviado.
            $this->marcar((int) $n['id'], 'fallido', 'Push no configurado en este entorno');
            $fallidas++;
        }

        return ['enviadas' => $enviadas, 'fallidas' => $fallidas];
    }

    private function sms(): HttpsmsClient
    {
        return HttpsmsClient::desdeEntorno();
    }

    private function marcar(int $id, string $estado, ?string $error = null): void
    {
        $this->pdo->prepare('UPDATE notificaciones SET estado = ?, error = ?, enviado_at = NOW() WHERE id = ?')
            ->execute([$estado, $error, $id]);
    }

    /**
     * Correo transaccional.
     *
     * Con MAIL_DRIVER=log (desarrollo) se escribe un archivo por mensaje en
     * storage/correos. Con MAIL_DRIVER=mail se usa la función mail() del sistema.
     * Así el flujo se prueba completo sin depender de un SMTP externo.
     */
    private function enviarCorreo(string $destino, string $asunto, string $cuerpo, string $id): bool
    {
        $driver = $_ENV['MAIL_DRIVER'] ?? 'log';

        if ($driver === 'mail' && function_exists('mail')) {
            $cabeceras = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";

            return @mail($destino, $asunto, $cuerpo, $cabeceras);
        }

        if (!is_dir($this->directorioCorreos)) {
            @mkdir($this->directorioCorreos, 0775, true);
        }

        $html = '<!doctype html><meta charset="utf-8"><h2>' . htmlspecialchars($asunto, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<div style="font-family:system-ui;line-height:1.6">' . $cuerpo . '</div>';

        $archivo = $this->directorioCorreos . '/correo-' . $id . '-' . hash('sha256', $destino . microtime(true)) . '.html';
        $encabezado = "Para: {$destino}\nAsunto: {$asunto}\nFecha: " . date('c') . "\n\n";

        return file_put_contents($archivo, $encabezado . $html) !== false;
    }

    /** Notificaciones de la bandeja de un usuario. */
    public function bandeja(int $usuarioId, int $limite = 30): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM notificaciones WHERE usuario_id = ? AND canal = 'bandeja'
             ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function sinLeer(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND canal = 'bandeja' AND estado <> 'leido'"
        );
        $stmt->execute([$usuarioId]);

        return (int) $stmt->fetchColumn();
    }

    public function marcarLeidas(int $usuarioId): void
    {
        $this->pdo->prepare(
            "UPDATE notificaciones SET estado = 'leido' WHERE usuario_id = ? AND canal = 'bandeja' AND estado <> 'leido'"
        )->execute([$usuarioId]);
    }
}
