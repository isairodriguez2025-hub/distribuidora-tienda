<?php

declare(strict_types=1);

namespace App\Modules\Pedidos;

use App\Core\AuditLogger;
use App\Modules\Auth\UsuarioRepository;
use App\Modules\Carrito\CarritoService;
use PDO;

/**
 * Checkout: convierte el carrito en pedidos, uno por mayorista (RN-02, RN-04).
 *
 * Dos garantías que este servicio da y que no se pueden romper desde fuera:
 *  1. El total se recalcula aquí. Nunca se usa el que manda el navegador.
 *  2. La reserva de stock es atómica: si dos personas compran la última pieza
 *     a la vez, solo una actualiza la fila y la otra recibe STOCK_INSUFICIENTE.
 */
final class CheckoutService
{
    public const TTL_RESERVA_MIN = 15;
    public const TTL_IDEMPOTENCIA_HORAS = 24;

    public function __construct(
        private readonly PDO $pdo,
        private readonly CarritoService $carrito,
        private readonly PedidoService $pedidos,
        private readonly AuditLogger $auditoria,
    ) {
    }

    /**
     * @return array{ok:bool, mensaje:string, codigo?:string, data?:array<string,mixed>}
     */
    public function crearCheckout(int $comercioId, string $idempotencyKey, int $direccionId, int $usuarioId): array
    {
        $idempotencyKey = substr(trim($idempotencyKey), 0, 64);

        if ($idempotencyKey === '') {
            return ['ok' => false, 'mensaje' => 'Falta la clave de idempotencia.', 'codigo' => 'SIN_IDEMPOTENCIA'];
        }

        // Doble clic en "confirmar": se devuelve la misma respuesta, sin reprocesar.
        $previa = $this->respuestaGuardada($idempotencyKey);

        if ($previa !== null) {
            $previa['repetida'] = true;

            return ['ok' => true, 'mensaje' => 'Checkout ya procesado.', 'data' => $previa];
        }

        $resumen = $this->carrito->recalcular($comercioId);

        if ($resumen['items'] === 0) {
            return ['ok' => false, 'mensaje' => 'El carrito está vacío.', 'codigo' => 'CARRITO_VACIO'];
        }

        // Solo se cobran los proveedores que ya alcanzaron su mínimo; el resto
        // se queda en el carrito para completarlo después.
        $gruposListos = array_values(array_filter(
            $resumen['grupos'],
            static fn (array $grupo): bool => !empty($grupo['listo'])
        ));

        if ($gruposListos === []) {
            return [
                'ok'      => false,
                'mensaje' => implode(' ', $resumen['problemas']),
                'codigo'  => 'CARRITO_INCOMPLETO',
            ];
        }

        $totalCobrar = (float) $resumen['total_listo'];
        $subtotalCobrar = (float) $resumen['subtotal_listo'];
        $ivaCobrar = (float) $resumen['iva_listo'];
        $productosCobrados = [];

        foreach ($gruposListos as $grupo) {
            foreach ($grupo['items'] as $item) {
                $productosCobrados[] = (int) $item['producto_id'];
            }
        }

        $this->pdo->beginTransaction();

        try {
            $checkoutUuid = UsuarioRepository::uuid4();

            $this->pdo->prepare(
                "INSERT INTO checkouts (uuid, comercio_id, idempotency_key, total, estado)
                 VALUES (?,?,?,?,'pendiente_pago')"
            )->execute([$checkoutUuid, $comercioId, $idempotencyKey, $totalCobrar]);

            $checkoutId = (int) $this->pdo->lastInsertId();
            $pedidosCreados = [];

            foreach ($gruposListos as $grupo) {
                $pedidoUuid = UsuarioRepository::uuid4();

                $this->pdo->prepare(
                    "INSERT INTO pedidos
                     (uuid, checkout_id, comercio_id, mayorista_id, direccion_entrega_id,
                      subtotal, iva, envio, comision_plataforma, total, estado, acepta_antes_de)
                     VALUES (?,?,?,?,?,?,?,0,0,?,'borrador', DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
                )->execute([
                    $pedidoUuid, $checkoutId, $comercioId, (int) $grupo['mayorista_id'], $direccionId,
                    $grupo['subtotal'], $grupo['iva'], $grupo['total'],
                ]);

                $pedidoId = (int) $this->pdo->lastInsertId();

                foreach ($grupo['items'] as $item) {
                    // Reserva atómica: la condición va en el WHERE, así que si
                    // otra transacción ya se llevó el stock, esta no actualiza
                    // ninguna fila y rowCount() es 0.
                    $stmt = $this->pdo->prepare(
                        'UPDATE productos
                         SET stock_disponible = stock_disponible - ?,
                             stock_reservado  = stock_reservado  + ?
                         WHERE id = (SELECT id FROM (SELECT id FROM productos WHERE uuid = ?) x)
                           AND stock_disponible >= ?'
                    );
                    $stmt->execute([$item['cantidad'], $item['cantidad'], $item['producto_uuid'], $item['cantidad']]);

                    if ($stmt->rowCount() === 0) {
                        $this->pdo->rollBack();

                        return [
                            'ok'      => false,
                            'mensaje' => 'Se agotó "' . $item['nombre'] . '" mientras confirmabas. Ajusta tu carrito.',
                            'codigo'  => 'STOCK_INSUFICIENTE',
                        ];
                    }

                    $this->pdo->prepare(
                        'INSERT INTO inventario_reservas (producto_id, pedido_id, cantidad, estado, expira_en)
                         SELECT id, ?, ?, \'activa\', DATE_ADD(NOW(), INTERVAL ? MINUTE) FROM productos WHERE uuid = ?'
                    )->execute([$pedidoId, $item['cantidad'], self::TTL_RESERVA_MIN, $item['producto_uuid']]);

                    // Snapshot inmutable: si el mayorista cambia el precio mañana,
                    // este pedido conserva el de hoy.
                    $this->pdo->prepare(
                        'INSERT INTO detalle_pedido
                         (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario,
                          escala_aplicada, iva_tasa, subtotal)
                         SELECT ?, id, ?, ?, ?, ?, ?, ? FROM productos WHERE uuid = ?'
                    )->execute([
                        $pedidoId, $item['nombre'], $item['cantidad'], $item['precio_unitario'],
                        $item['escala_aplicada'], $item['iva_tasa'], $item['subtotal'], $item['producto_uuid'],
                    ]);
                }

                $this->pdo->prepare(
                    "INSERT INTO pedido_eventos (pedido_id, estado_anterior, estado_nuevo, actor_usuario_id, nota)
                     VALUES (?, NULL, 'borrador', ?, 'Pedido creado desde el carrito')"
                )->execute([$pedidoId, $usuarioId]);

                $pedidosCreados[] = ['uuid' => $pedidoUuid, 'id' => $pedidoId, 'total' => $grupo['total']];
            }

            $respuesta = [
                'checkout_uuid' => $checkoutUuid,
                'total'         => $totalCobrar,
                'subtotal'      => $subtotalCobrar,
                'iva'           => $ivaCobrar,
                'pedidos'       => $pedidosCreados,
                'quedan'        => max(0, (int) $resumen['total_grupos'] - count($gruposListos)),
                'expira_en'     => self::TTL_RESERVA_MIN . ' minutos',
            ];

            $this->guardarRespuesta($idempotencyKey, $usuarioId, $respuesta);

            $this->pdo->commit();

            // Solo salen del carrito los productos que ya son pedido.
            $this->carrito->quitarProductos($comercioId, $productosCobrados);

            $this->auditoria->log('checkout_creado', $usuarioId, 'checkout', $checkoutUuid, [
                'total'   => $totalCobrar,
                'pedidos' => count($pedidosCreados),
            ], '0.0.0.0');

            return ['ok' => true, 'mensaje' => 'Pedido confirmado.', 'data' => $respuesta];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /** @return array<string,mixed>|null */
    private function respuestaGuardada(string $llave): ?array
    {
        $stmt = $this->pdo->prepare('SELECT respuesta FROM idempotency_keys WHERE llave = ? AND expira_en > NOW() LIMIT 1');
        $stmt->execute([$llave]);
        $json = $stmt->fetchColumn();

        if ($json === false) {
            return null;
        }

        $datos = json_decode((string) $json, true);

        return is_array($datos) ? $datos : null;
    }

    private function guardarRespuesta(string $llave, int $usuarioId, array $respuesta): void
    {
        $this->pdo->prepare(
            'INSERT INTO idempotency_keys (llave, usuario_id, ruta, respuesta, expira_en)
             VALUES (?,?,?,?, DATE_ADD(NOW(), INTERVAL ? HOUR))
             ON DUPLICATE KEY UPDATE respuesta = VALUES(respuesta)'
        )->execute([
            $llave, $usuarioId, '/checkout', json_encode($respuesta, JSON_UNESCAPED_UNICODE), self::TTL_IDEMPOTENCIA_HORAS,
        ]);
    }
}
