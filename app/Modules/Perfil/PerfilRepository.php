<?php

declare(strict_types=1);

namespace App\Modules\Perfil;

use PDO;

final class PerfilRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Crea el perfil que corresponde al rol, si no existe. */
    public function asegurarPerfil(int $usuarioId, string $rol, string $nombreNegocio): void
    {
        $tabla = match ($rol) {
            'comercio'   => 'comercios_perfil',
            'mayorista'  => 'mayoristas_perfil',
            'repartidor' => 'repartidores_perfil',
            default      => null,
        };

        if ($tabla === null) {
            return;
        }

        $stmt = $this->pdo->prepare("SELECT id FROM {$tabla} WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);

        if ($stmt->fetchColumn() !== false) {
            return;
        }

        if ($tabla === 'comercios_perfil') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO comercios_perfil (usuario_id, nombre_negocio) VALUES (?, ?)'
            );
            $stmt->execute([$usuarioId, $nombreNegocio]);

            return;
        }

        if ($tabla === 'mayoristas_perfil') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO mayoristas_perfil (uuid, usuario_id, nombre_comercial) VALUES (UUID(), ?, ?)'
            );
            $stmt->execute([$usuarioId, $nombreNegocio]);

            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO repartidores_perfil (usuario_id) VALUES (?)');
        $stmt->execute([$usuarioId]);
    }

    public function perfil(int $usuarioId, string $rol): ?array
    {
        $tabla = match ($rol) {
            'comercio'   => 'comercios_perfil',
            'mayorista'  => 'mayoristas_perfil',
            'repartidor' => 'repartidores_perfil',
            default      => null,
        };

        if ($tabla === null) {
            return null;
        }

        // Se traen también los datos de la cuenta: la ficha de "Mi cuenta"
        // muestra el correo y el nombre aunque el perfil del rol esté vacío.
        $stmt = $this->pdo->prepare(
            "SELECT p.*, u.email, u.telefono, u.nombre, u.avatar_ruta
               FROM {$tabla} p
               JOIN usuarios u ON u.id = p.usuario_id
              WHERE p.usuario_id = ?
              LIMIT 1"
        );
        $stmt->execute([$usuarioId]);
        $fila = $stmt->fetch();

        return $fila !== false ? $fila : null;
    }

    public function guardarDireccion(int $usuarioId, array $datos): int
    {
        $this->pdo->beginTransaction();

        try {
            // Solo una dirección principal por usuario.
            if (!empty($datos['es_principal'])) {
                $stmt = $this->pdo->prepare('UPDATE direcciones SET es_principal = 0 WHERE usuario_id = ?');
                $stmt->execute([$usuarioId]);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO direcciones
                 (usuario_id, alias, calle, colonia, ciudad, estado_mx, cp, latitud, longitud, referencias, es_principal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $usuarioId,
                $datos['alias'] ?? null,
                $datos['calle'],
                $datos['colonia'] ?? null,
                $datos['ciudad'],
                $datos['estado_mx'] ?? null,
                $datos['cp'] ?? null,
                $datos['latitud'],
                $datos['longitud'],
                $datos['referencias'] ?? null,
                !empty($datos['es_principal']) ? 1 : 0,
            ]);

            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();

            return $id;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }

    public function listarDirecciones(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM direcciones WHERE usuario_id = ? AND deleted_at IS NULL ORDER BY es_principal DESC, id DESC'
        );
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll() ?: [];
    }

    /** ¿La coordenada cae en alguna zona de cobertura activa? */
    public function dentroDeCobertura(float $lat, float $lng): bool
    {
        $stmt = $this->pdo->query('SELECT poligono FROM zonas_cobertura WHERE activa = 1');
        $zonas = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        foreach ($zonas as $json) {
            $poligono = json_decode((string) $json, true);

            if (is_array($poligono) && \App\Core\Geo::puntoEnPoligono($lat, $lng, $poligono)) {
                return true;
            }
        }

        return false;
    }

    // ---------------------------------------------------------------- cuenta

    public function emailEnUso(string $email, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM usuarios WHERE email = ? AND id <> ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([mb_strtolower(trim($email)), $usuarioId]);

        return (bool) $stmt->fetchColumn();
    }

    /** Datos mínimos de la cuenta para la pantalla de edición. */
    public function cuenta(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, telefono, password_hash FROM usuarios WHERE id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$usuarioId]);

        return $stmt->fetch() ?: [];
    }

    public function actualizarCuenta(int $usuarioId, string $rol, string $nombre, string $email): void
    {
        $this->pdo->prepare('UPDATE usuarios SET email = ?, nombre = ? WHERE id = ?')
            ->execute([mb_strtolower(trim($email)), $nombre, $usuarioId]);

        // El repartidor no tiene nombre en su perfil: su identidad es la cuenta.
        if ($rol === 'repartidor') {
            return;
        }

        $tabla = $rol === 'mayorista' ? 'mayoristas_perfil' : 'comercios_perfil';
        $columna = $rol === 'mayorista' ? 'nombre_comercial' : 'nombre_negocio';

        $stmt = $this->pdo->prepare("SELECT 1 FROM {$tabla} WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);

        if ($stmt->fetchColumn() === false) {
            $this->pdo->prepare("INSERT INTO {$tabla} (usuario_id, uuid, {$columna}) VALUES (?, UUID(), ?)")
                ->execute([$usuarioId, $nombre]);

            return;
        }

        $this->pdo->prepare("UPDATE {$tabla} SET {$columna} = ? WHERE usuario_id = ?")
            ->execute([$nombre, $usuarioId]);
    }

    public function cambiarPassword(int $usuarioId, string $hash): void
    {
        $this->pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')
            ->execute([$hash, $usuarioId]);
    }

    /** Una dirección principal por usuario; se reutiliza la existente. */
    public function guardarPrincipal(int $usuarioId, array $d): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM direcciones WHERE usuario_id = ? AND deleted_at IS NULL ORDER BY es_principal DESC, id ASC LIMIT 1'
        );
        $stmt->execute([$usuarioId]);
        $id = $stmt->fetchColumn();

        if ($id === false) {
            $this->guardarDireccion($usuarioId, $d + ['es_principal' => true]);

            return;
        }

        $this->pdo->prepare(
            'UPDATE direcciones
                SET alias = ?, calle = ?, colonia = ?, ciudad = ?, estado_mx = ?, cp = ?, referencias = ?
              WHERE id = ? AND usuario_id = ?'
        )->execute([
            (string) ($d['alias'] ?? ''),
            (string) ($d['calle'] ?? ''),
            (string) ($d['colonia'] ?? ''),
            (string) ($d['ciudad'] ?? ''),
            (string) ($d['estado_mx'] ?? ''),
            (string) ($d['cp'] ?? ''),
            (string) ($d['referencias'] ?? ''),
            (int) $id,
            $usuarioId,
        ]);
    }

    // ------------------------------------------------------------ privacidad

    public function preferencias(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT notificaciones, sms, correo, ubicacion FROM preferencias_usuario WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        $fila = $stmt->fetch();

        if ($fila === false) {
            return ['notificaciones' => 1, 'sms' => 1, 'correo' => 1, 'ubicacion' => 1];
        }

        return [
            'notificaciones' => (int) $fila['notificaciones'],
            'sms'            => (int) $fila['sms'],
            'correo'         => (int) $fila['correo'],
            'ubicacion'      => (int) $fila['ubicacion'],
        ];
    }

    public function guardarPreferencias(int $usuarioId, array $p): void
    {
        $this->pdo->prepare(
            'INSERT INTO preferencias_usuario (usuario_id, notificaciones, sms, correo, ubicacion) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE notificaciones = VALUES(notificaciones), sms = VALUES(sms),
                                     correo = VALUES(correo), ubicacion = VALUES(ubicacion)'
        )->execute([
            $usuarioId,
            !empty($p['notificaciones']) ? 1 : 0,
            (int) !empty($p['sms']),
            (int) !empty($p['correo']),
            (int) !empty($p['ubicacion']),
        ]);
    }


    /** Solo el interruptor maestro: lo usa la configuración del centro de mensajes. */
    public function guardarNotificaciones(int $usuarioId, bool $activo): void
    {
        $this->pdo->prepare(
            'INSERT INTO preferencias_usuario (usuario_id, notificaciones) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE notificaciones = VALUES(notificaciones)'
        )->execute([$usuarioId, $activo ? 1 : 0]);
    }

    /**
     * Baja lógica: la cuenta deja de existir para el sistema pero el histórico
     * de pedidos y las obligaciones fiscales se conservan.
     */
    public function eliminarCuenta(int $usuarioId, string $motivo): void
    {
        $this->pdo->beginTransaction();

        try {
            $this->pdo->prepare(
                "UPDATE usuarios SET deleted_at = NOW(), estado = 'eliminado', eliminado_motivo = ? WHERE id = ?"
            )->execute([mb_substr($motivo, 0, 120), $usuarioId]);

            $this->pdo->prepare('UPDATE direcciones SET deleted_at = NOW() WHERE usuario_id = ? AND deleted_at IS NULL')
                ->execute([$usuarioId]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------------- calificaciones

    /** @return array{promedio: ?float, total: int} */
    public function calificacion(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT AVG(puntaje) AS promedio, COUNT(*) AS total FROM calificaciones WHERE evaluado_id = ?'
        );
        $stmt->execute([$usuarioId]);
        $fila = $stmt->fetch() ?: [];

        $total = (int) ($fila['total'] ?? 0);

        return [
            'promedio' => $total > 0 ? round((float) $fila['promedio'], 1) : null,
            'total'    => $total,
        ];
    }

    /** Roles que participan del mercado y por tanto pueden puntuar. */
    public function rolDe(int $usuarioId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.nombre FROM usuario_roles ur JOIN roles r ON r.id = ur.rol_id
              WHERE ur.usuario_id = ? ORDER BY r.id LIMIT 1'
        );
        $stmt->execute([$usuarioId]);
        $rol = $stmt->fetchColumn();

        return $rol === false ? null : (string) $rol;
    }

    public function miCalificacion(int $evaluadorId, int $evaluadoId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT puntaje FROM calificaciones WHERE evaluador_id = ? AND evaluado_id = ? LIMIT 1');
        $stmt->execute([$evaluadorId, $evaluadoId]);
        $p = $stmt->fetchColumn();

        return $p === false ? null : (int) $p;
    }

    public function calificar(int $evaluadorId, int $evaluadoId, int $puntaje, ?int $pedidoId, string $comentario): void
    {
        $this->pdo->prepare(
            'INSERT INTO calificaciones (evaluador_id, evaluado_id, pedido_id, puntaje, comentario)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE puntaje = VALUES(puntaje), comentario = VALUES(comentario), pedido_id = VALUES(pedido_id)'
        )->execute([$evaluadorId, $evaluadoId, $pedidoId, $puntaje, mb_substr($comentario, 0, 280)]);
    }

    // ---------------------------------------------------------------- tema

    public function tema(int $usuarioId): string
    {
        $stmt = $this->pdo->prepare('SELECT tema FROM preferencias_usuario WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        $tema = $stmt->fetchColumn();

        return $tema === 'oscuro' ? 'oscuro' : 'claro';
    }

    public function guardarTema(int $usuarioId, string $tema): void
    {
        $tema = $tema === 'oscuro' ? 'oscuro' : 'claro';

        $this->pdo->prepare(
            'INSERT INTO preferencias_usuario (usuario_id, tema) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE tema = VALUES(tema)'
        )->execute([$usuarioId, $tema]);
    }
}
