<?php

declare(strict_types=1);

namespace App\Modules\Programados;

use App\Modules\Auth\UsuarioRepository;
use App\Modules\Catalogo\PrecioService;
use App\Modules\Notificaciones\NotificacionService;
use PDO;

/**
 * Pedidos programados (RN-12).
 *
 * Regla que no se negocia: el sistema NUNCA cobra sin confirmación explícita.
 * El worker solo prepara un borrador con precios vigentes y avisa al comercio;
 * si el comercio no confirma, el pedido no existe.
 */
final class ProgramadoService
{
    public const FRECUENCIAS = ['unica', 'semanal', 'quincenal', 'mensual'];

    public function __construct(
        private readonly PDO $pdo,
        private readonly PrecioService $precios,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * @param array{frecuencia:string, dia_semana?:int, hora:string, items:array<int,array{producto_uuid:string,cantidad:int}>} $datos
     */
    public function crear(int $comercioId, int $mayoristaId, array $datos): array
    {
        $frecuencia = in_array($datos['frecuencia'] ?? '', self::FRECUENCIAS, true) ? $datos['frecuencia'] : 'semanal';
        $hora = preg_match('/^\d{2}:\d{2}$/', (string) ($datos['hora'] ?? '')) ? $datos['hora'] : '09:00';
        $items = $datos['items'] ?? [];

        if ($items === []) {
            return ['ok' => false, 'mensaje' => 'La plantilla necesita al menos un producto.'];
        }

        $this->pdo->beginTransaction();

        try {
            $this->pdo->prepare(
                "INSERT INTO pedidos_programados (uuid, comercio_id, mayorista_id, frecuencia, dia_semana, hora, estado, proximo_envio)
                 VALUES (?,?,?,?,?,?, 'activa', ?)"
            )->execute([
                UsuarioRepository::uuid4(), $comercioId, $mayoristaId, $frecuencia,
                isset($datos['dia_semana']) ? (int) $datos['dia_semana'] : null,
                $hora,
                $this->proximoEnvio($frecuencia, isset($datos['dia_semana']) ? (int) $datos['dia_semana'] : null, $hora),
            ]);

            $programadoId = (int) $this->pdo->lastInsertId();

            $insertar = $this->pdo->prepare(
                'INSERT INTO pedidos_programados_items (programado_id, producto_id, cantidad)
                 SELECT ?, id, ? FROM productos WHERE uuid = ? LIMIT 1'
            );

            foreach ($items as $item) {
                $insertar->execute([$programadoId, (int) ($item['cantidad'] ?? 1), (string) ($item['producto_uuid'] ?? '')]);
            }

            $this->pdo->commit();

            return ['ok' => true, 'mensaje' => 'Plantilla creada.', 'id' => $programadoId];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function cambiarEstado(int $programadoId, int $comercioId, string $estado): bool
    {
        if (!in_array($estado, ['activa', 'pausada', 'cancelada'], true)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE pedidos_programados SET estado = ? WHERE id = ? AND comercio_id = ?');
        $stmt->execute([$estado, $programadoId, $comercioId]);

        return $stmt->rowCount() > 0;
    }

    public function listar(int $comercioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pp.*, m.nombre_comercial AS mayorista,
                    (SELECT COUNT(*) FROM pedidos_programados_items i WHERE i.programado_id = pp.id) AS lineas
             FROM pedidos_programados pp
             JOIN mayoristas_perfil m ON m.id = pp.mayorista_id
             WHERE pp.comercio_id = ?
             ORDER BY pp.estado, pp.proximo_envio"
        );
        $stmt->execute([$comercioId]);

        return $stmt->fetchAll() ?: [];
    }


    /**
     * Lista de pedido con fecha concreta (pantalla Agendar): el comercio elige
     * el día y la hora en que quiere recibirla.
     */
    public function crearLista(int $comercioId, string $fecha, string $hora, array $items): array
    {
        $fecha = trim($fecha);
        $hora = trim($hora);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
            return ['ok' => false, 'mensaje' => 'Elige un día válido para la lista.'];
        }

        if (preg_match('/^\d{2}:\d{2}$/', $hora) !== 1) {
            $hora = '09:00';
        }

        if (strtotime($fecha) < strtotime(date('Y-m-d'))) {
            return ['ok' => false, 'mensaje' => 'La fecha ya pasó. Elige un día de hoy en adelante.'];
        }

        if ($items === []) {
            return ['ok' => false, 'mensaje' => 'La lista necesita al menos un producto.'];
        }

        // El mayorista sale del primer producto: una lista es de un proveedor.
        $buscar = $this->pdo->prepare(
            'SELECT mayorista_id FROM productos WHERE uuid = ? LIMIT 1'
        );
        $buscar->execute([(string) $items[0]['producto_uuid']]);
        $mayoristaId = (int) $buscar->fetchColumn();

        if ($mayoristaId === 0) {
            return ['ok' => false, 'mensaje' => 'No encontramos el producto elegido.'];
        }

        $this->pdo->beginTransaction();

        try {
            $this->pdo->prepare(
                "INSERT INTO pedidos_programados
                     (uuid, comercio_id, mayorista_id, frecuencia, dia_semana, hora, fecha_deseada, estado, proximo_envio)
                 VALUES (?,?,?, 'unica', NULL, ?, ?, 'activa', ?)"
            )->execute([
                UsuarioRepository::uuid4(), $comercioId, $mayoristaId, $hora, $fecha, $fecha . ' ' . $hora . ':00',
            ]);

            $listaId = (int) $this->pdo->lastInsertId();

            $insertar = $this->pdo->prepare(
                'INSERT INTO pedidos_programados_items (programado_id, producto_id, cantidad)
                 SELECT ?, id, ? FROM productos WHERE uuid = ? LIMIT 1'
            );

            foreach ($items as $item) {
                $insertar->execute([$listaId, max(1, (int) ($item['cantidad'] ?? 1)), (string) ($item['producto_uuid'] ?? '')]);
            }

            $this->pdo->commit();

            return ['ok' => true, 'id' => $listaId];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();

            return ['ok' => false, 'mensaje' => 'No se pudo guardar la lista.'];
        }
    }

    /**
     * Worker: prepara borradores 24 h antes.
     *
     * @return array{borradores:int, omitidos:int}
     */
    public function generarBorradores(): array
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM pedidos_programados
             WHERE estado = 'activa' AND proximo_envio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
             LIMIT 50"
        );

        $plantillas = $stmt->fetchAll() ?: [];
        $creados = 0;
        $omitidos = 0;

        foreach ($plantillas as $plantilla) {
            $resultado = $this->crearBorrador($plantilla);

            if ($resultado['ok']) {
                $creados++;
            } else {
                $omitidos++;
            }

            // Se avanza el calendario aunque no se haya podido preparar, para
            // no reintentar la misma plantilla en cada pasada del worker.
            $this->pdo->prepare('UPDATE pedidos_programados SET proximo_envio = ? WHERE id = ?')
                ->execute([
                    $this->proximoEnvio((string) $plantilla['frecuencia'], $plantilla['dia_semana'] === null ? null : (int) $plantilla['dia_semana'], (string) $plantilla['hora']),
                    (int) $plantilla['id'],
                ]);
        }

        return ['borradores' => $creados, 'omitidos' => $omitidos];
    }

    /**
     * Crea el pedido en borrador con precios vigentes. No reserva stock.
     */
    private function crearBorrador(array $plantilla): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.cantidad, p.* FROM pedidos_programados_items i
             JOIN productos p ON p.id = i.producto_id
             WHERE i.programado_id = ?'
        );
        $stmt->execute([(int) $plantilla['id']]);
        $lineas = $stmt->fetchAll() ?: [];

        if ($lineas === []) {
            return ['ok' => false, 'mensaje' => 'Plantilla sin productos.'];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM direcciones WHERE usuario_id = (SELECT usuario_id FROM comercios_perfil WHERE id = ?) AND deleted_at IS NULL
             ORDER BY es_principal DESC LIMIT 1'
        );
        $stmt->execute([(int) $plantilla['comercio_id']]);
        $direccionId = $stmt->fetchColumn();

        if ($direccionId === false) {
            return ['ok' => false, 'mensaje' => 'El comercio no tiene dirección.'];
        }

        $subtotal = 0.0;
        $iva = 0.0;
        $detalle = [];
        $omitidos = [];

        foreach ($lineas as $linea) {
            $cantidad = (int) $linea['cantidad'];

            if ((int) $linea['stock_disponible'] < $cantidad || (int) $linea['activo'] !== 1) {
                $omitidos[] = $linea['nombre'];
                continue;
            }

            $calculo = $this->precios->calcularLinea($linea, $cantidad);
            $subtotal += $calculo['subtotal'];
            $iva += $calculo['iva_total'];
            $detalle[] = ['linea' => $linea, 'calculo' => $calculo, 'cantidad' => $cantidad];
        }

        if ($detalle === []) {
            return ['ok' => false, 'mensaje' => 'Ningún producto tiene stock.'];
        }

        $total = round($subtotal + $iva, 2);

        $this->pdo->beginTransaction();

        try {
            $this->pdo->prepare(
                "INSERT INTO checkouts (uuid, comercio_id, idempotency_key, total, estado) VALUES (?,?,?,?, 'pendiente_pago')"
            )->execute([UsuarioRepository::uuid4(), (int) $plantilla['comercio_id'], 'prog-' . bin2hex(random_bytes(10)), $total]);
            $checkoutId = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare(
                "INSERT INTO pedidos (uuid, checkout_id, comercio_id, mayorista_id, direccion_entrega_id,
                                      subtotal, iva, total, estado, es_programado, fecha_entrega_programada)
                 VALUES (?,?,?,?,?,?,?,?, 'borrador', 1, ?)"
            )->execute([
                UsuarioRepository::uuid4(), $checkoutId, (int) $plantilla['comercio_id'], (int) $plantilla['mayorista_id'],
                (int) $direccionId, round($subtotal, 2), round($iva, 2), $total, $plantilla['proximo_envio'],
            ]);

            $pedidoId = (int) $this->pdo->lastInsertId();

            $insertar = $this->pdo->prepare(
                'INSERT INTO detalle_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario,
                                             escala_aplicada, iva_tasa, subtotal)
                 VALUES (?,?,?,?,?,?,?,?)'
            );

            foreach ($detalle as $d) {
                $insertar->execute([
                    $pedidoId, (int) $d['linea']['id'], $d['linea']['nombre'], $d['cantidad'],
                    $d['calculo']['precio_unitario'], $d['calculo']['escala_aplicada'],
                    $d['calculo']['iva_tasa'], $d['calculo']['subtotal'],
                ]);
            }

            $this->pdo->prepare(
                "INSERT INTO pedido_eventos (pedido_id, estado_anterior, estado_nuevo, nota)
                 VALUES (?, NULL, 'borrador', 'Borrador de pedido programado')"
            )->execute([$pedidoId]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        $uuid = $this->pdo->query("SELECT uuid FROM pedidos WHERE id = {$pedidoId}")->fetchColumn();

        $aviso = $omitidos === [] ? '' : ' Sin stock: ' . implode(', ', $omitidos) . '.';

        $this->notificaciones->encolar(
            (int) $this->pdo->query("SELECT usuario_id FROM comercios_perfil WHERE id = " . (int) $plantilla['comercio_id'])->fetchColumn(),
            'pedido_programado',
            'Tu pedido programado está listo para confirmar',
            'Preparamos el borrador con precios de hoy. Revísalo y confírmalo; si no lo haces, no se cobra nada.' . $aviso,
            '/pedidos/' . $uuid,
        );

        return ['ok' => true, 'pedido_id' => $pedidoId];
    }

    /** Calcula la siguiente fecha según la frecuencia. */
    private function proximoEnvio(string $frecuencia, ?int $diaSemana, string $hora): string
    {
        $base = new \DateTimeImmutable('now');

        $siguiente = match ($frecuencia) {
            'unica'     => $base->modify('+1 day'),
            'semanal'   => $base->modify('+7 days'),
            'quincenal' => $base->modify('+15 days'),
            'mensual'   => $base->modify('+1 month'),
            default     => $base->modify('+7 days'),
        };

        [$h, $m] = array_map('intval', explode(':', $hora));

        return $siguiente->setTime($h, $m)->format('Y-m-d H:i:s');
    }
}
