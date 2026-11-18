<?php

declare(strict_types=1);

namespace App\Modules\Jobs;

use App\Modules\Logistica\AsignacionService;
use App\Modules\Notificaciones\NotificacionService;
use App\Modules\Pedidos\PedidoService;
use PDO;

/**
 * Worker de tareas periódicas.
 *
 * En producción lo dispara cron cada minuto; en Windows, el Programador de
 * tareas; y en hosting sin cron, `GET /internal/tick` con un token.
 *
 * Cada tarea se ejecuta aislada: si una falla, las demás siguen. El resultado
 * queda en `ejecuciones_worker` para poder ver qué pasó y cuándo.
 */
final class Worker
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly PedidoService $pedidos,
        private readonly NotificacionService $notificaciones,
        private readonly ?\App\Modules\Programados\ProgramadoService $programados = null,
        private readonly ?AsignacionService $asignacion = null,
    ) {
    }

    /**
     * Ejecuta todas las tareas.
     *
     * @return array<int, array{tarea:string, resultado:string, ms:int}>
     */
    public function ejecutar(): array
    {
        $resultados = [];

        foreach ([
            'reservas_vencidas'      => fn () => $this->expirarReservas(),
            // Sin esta tarea las ofertas de reparto no vencen nunca y las
            // rondas 2 y 3 jamás llegan a lanzarse.
            'ofertas_vencidas'       => fn () => $this->asignacion === null
                ? 'sin servicio'
                : json_encode($this->asignacion->procesarVencidas(), JSON_UNESCAPED_UNICODE),
            'pedidos_sin_respuesta'  => fn () => $this->expirarSinRespuesta(),
            'programados'            => fn () => $this->prepararProgramados(),
            'notificaciones'         => fn () => $this->enviarNotificaciones(),
            'jobs'                   => fn () => $this->procesarJobs(),
            'limpieza'               => fn () => $this->limpieza(),
        ] as $nombre => $tarea) {
            $inicio = microtime(true);

            try {
                $resultado = $tarea();
            } catch (\Throwable $e) {
                $resultado = 'error: ' . mb_substr($e->getMessage(), 0, 200);
            }

            $ms = (int) round((microtime(true) - $inicio) * 1000);

            $this->registrar($nombre, $resultado, $ms);
            $resultados[] = ['tarea' => $nombre, 'resultado' => $resultado, 'ms' => $ms];
        }

        return $resultados;
    }

    /** RN-04: libera el stock de las reservas que pasaron de 15 minutos. */
    private function expirarReservas(): string
    {
        $liberados = $this->pedidos->expirarReservasVencidas();

        return $liberados === 0
            ? 'sin reservas vencidas'
            : "{$liberados} reserva(s) liberadas";
    }

    /** RN-05: el mayorista no respondió en 30 minutos. */
    private function expirarSinRespuesta(): string
    {
        $stmt = $this->pdo->query(
            "SELECT id FROM pedidos
             WHERE estado = 'pendiente_aceptacion' AND acepta_antes_de IS NOT NULL AND acepta_antes_de < NOW()
             LIMIT 100"
        );

        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $expirados = 0;

        foreach ($ids as $id) {
            $r = $this->pedidos->transicionar(
                (int) $id,
                'expirado',
                null,
                ['worker', 'sistema'],
                'El mayorista no respondió en el plazo de 30 minutos',
            );

            if ($r['ok']) {
                $expirados++;
            }
        }

        return $expirados === 0 ? 'sin pedidos vencidos' : "{$expirados} pedido(s) expirados";
    }

    /** RN-12: prepara borradores de pedidos programados 24 h antes. */
    private function prepararProgramados(): string
    {
        if ($this->programados === null) {
            return 'sin servicio de programados';
        }

        $r = $this->programados->generarBorradores();

        return $r['borradores'] === 0 && $r['omitidos'] === 0
            ? 'sin plantillas por preparar'
            : "{$r['borradores']} borrador(es), {$r['omitidos']} omitido(s)";
    }

    private function enviarNotificaciones(): string
    {
        $r = $this->notificaciones->enviarPendientes(100);

        return "enviadas {$r['enviadas']}, fallidas {$r['fallidas']}";
    }

    /**
     * Cola genérica de trabajos con reintento exponencial.
     */
    private function procesarJobs(): string
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM jobs
             WHERE estado = 'pendiente' AND disponible_en <= NOW()
             ORDER BY id LIMIT 20"
        );

        $jobs = $stmt->fetchAll() ?: [];
        $completados = 0;
        $fallidos = 0;

        foreach ($jobs as $job) {
            $this->pdo->prepare("UPDATE jobs SET estado = 'en_proceso', intentos = intentos + 1 WHERE id = ?")
                ->execute([(int) $job['id']]);

            try {
                $this->ejecutarJob((string) $job['tipo'], json_decode((string) $job['payload'], true) ?: []);

                $this->pdo->prepare("UPDATE jobs SET estado = 'completado', procesado_at = NOW() WHERE id = ?")
                    ->execute([(int) $job['id']]);
                $completados++;
            } catch (\Throwable $e) {
                $intentos = (int) $job['intentos'] + 1;
                $maximos = (int) $job['max_intentos'];
                $agotado = $intentos >= $maximos;

                // Espera exponencial: 1, 2, 4 minutos entre intentos.
                $espera = 2 ** ($intentos - 1);

                $this->pdo->prepare(
                    'UPDATE jobs SET estado = ?, error = ?, disponible_en = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?'
                )->execute([
                    $agotado ? 'fallido' : 'pendiente',
                    mb_substr($e->getMessage(), 0, 500),
                    $espera,
                    (int) $job['id'],
                ]);

                if ($agotado) {
                    $fallidos++;
                }
            }
        }

        return $jobs === [] ? 'cola vacía' : "{$completados} completados, {$fallidos} fallidos";
    }

    private function ejecutarJob(string $tipo, array $payload): void
    {
        match ($tipo) {
            'notificar' => $this->notificaciones->encolar(
                (int) ($payload['usuario_id'] ?? 0),
                (string) ($payload['tipo'] ?? 'general'),
                (string) ($payload['asunto'] ?? 'Aviso'),
                (string) ($payload['cuerpo'] ?? ''),
                $payload['enlace'] ?? null,
                $payload['canales'] ?? ['bandeja', 'correo'],
            ),
            default => throw new \RuntimeException("Tipo de job desconocido: {$tipo}"),
        };
    }

    /** Limpieza de datos que ya no sirven. */
    private function limpieza(): string
    {
        $claves = $this->pdo->exec('DELETE FROM idempotency_keys WHERE expira_en < NOW()');

        $tracking = 0;
        $existe = $this->pdo->query("SHOW TABLES LIKE 'tracking_puntos'")->fetchColumn();

        if ($existe !== false) {
            $tracking = $this->pdo->exec('DELETE FROM tracking_puntos WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');
        }

        return 'claves vencidas ' . (int) $claves . ', puntos de tracking ' . (int) $tracking;
    }

    private function registrar(string $tarea, string $resultado, int $ms): void
    {
        $this->pdo->prepare(
            'INSERT INTO ejecuciones_worker (tarea, duracion_ms, resultado) VALUES (?,?,?)'
        )->execute([$tarea, $ms, mb_substr($resultado, 0, 255)]);
    }
}
