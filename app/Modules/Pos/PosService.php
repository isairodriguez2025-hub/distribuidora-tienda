<?php

declare(strict_types=1);

namespace App\Modules\Pos;

use App\Core\AuditLogger;
use App\Config\Database;
use App\Modules\Notificaciones\NotificacionService;
use PDO;
use Throwable;

/**
 * Reglas del punto de venta.
 *
 * Una venta es una transacción: o se descuenta stock, se registra el pago y se
 * guarda el movimiento de caja, o no se guarda nada. Nunca queda una venta sin
 * líneas ni stock descontado a medias.
 */
final class PosService
{
    public function __construct(
        private readonly PosRepository $repo,
        private readonly AuditLogger $auditoria,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    public function ajustes(int $usuarioId): array
    {
        return $this->repo->ajustes($usuarioId);
    }

    /** @return array{ok:bool, mensaje:string, folio?:string, total?:float, cambio?:float, uuid?:string} */
    public function vender(int $usuarioId, array $items, string $metodo, float $recibido, ?int $clienteId, string $notas): array
    {
        $turno = $this->repo->turnoAbierto($usuarioId);

        if ($turno === null) {
            return ['ok' => false, 'mensaje' => 'Abre la caja antes de cobrar.'];
        }

        if ($items === []) {
            return ['ok' => false, 'mensaje' => 'El ticket está vacío.'];
        }

        if (!in_array($metodo, ['efectivo', 'tarjeta', 'transferencia', 'credito'], true)) {
            return ['ok' => false, 'mensaje' => 'Método de pago no válido.'];
        }

        $ajustes = $this->repo->ajustes($usuarioId);
        $ivaIncluido = (int) $ajustes['iva_incluido'] === 1;
        $pdo = Database::conexion();

        $pdo->beginTransaction();

        try {
            $lineas = [];
            $subtotal = 0.0;
            $iva = 0.0;
            $total = 0.0;

            foreach ($items as $item) {
                $cantidad = (float) ($item['cantidad'] ?? 0);
                $descuento = round((float) ($item['descuento'] ?? 0), 2);

                if ($cantidad <= 0) {
                    throw new \RuntimeException('Cantidad inválida en el ticket.');
                }

                $stmt = $pdo->prepare(
                    'SELECT * FROM pos_productos WHERE id = ? AND usuario_id = ? AND activo = 1 FOR UPDATE'
                );
                $stmt->execute([(int) ($item['id'] ?? 0), $usuarioId]);
                $producto = $stmt->fetch();

                if (!is_array($producto)) {
                    throw new \RuntimeException('Un producto del ticket ya no existe.');
                }

                if ((float) $producto['stock'] < $cantidad) {
                    throw new \RuntimeException(
                        'Stock insuficiente de ' . $producto['nombre'] . ' (quedan ' . (float) $producto['stock'] . ').'
                    );
                }

                $precio = round((float) $producto['precio_venta'], 2);
                $tasa = (float) $producto['iva_tasa'];
                $bruto = round($precio * $cantidad - $descuento, 2);

                if ($ivaIncluido) {
                    $lineaTotal = $bruto;
                    $lineaSubtotal = round($bruto / (1 + $tasa), 2);
                } else {
                    $lineaSubtotal = $bruto;
                    $lineaTotal = round($bruto * (1 + $tasa), 2);
                }

                $linea = [
                    'id'            => (int) $producto['id'],
                    'descripcion'   => (string) $producto['nombre'],
                    'cantidad'      => $cantidad,
                    'precio'        => $precio,
                    'descuento'     => $descuento,
                    'tasa'          => $tasa,
                    'subtotal'      => $lineaSubtotal,
                    'total'         => $lineaTotal,
                    'iva'           => round($lineaTotal - $lineaSubtotal, 2),
                ];

                $subtotal += $linea['subtotal'];
                $iva += $linea['iva'];
                $total += $linea['total'];

                $pdo->prepare('UPDATE pos_productos SET stock = stock - ? WHERE id = ?')
                    ->execute([$cantidad, (int) $producto['id']]);

                $lineas[] = $linea;
            }

            $subtotal = round($subtotal, 2);
            $iva = round($iva, 2);
            $total = round($total, 2);

            if ($metodo === 'efectivo' && $recibido + 0.001 < $total) {
                throw new \RuntimeException('El efectivo recibido no cubre el total.');
            }

            $cambio = $metodo === 'efectivo' ? round($recibido - $total, 2) : 0.0;

            $folio = 'POS-' . date('ymd') . '-' . str_pad((string) $this->siguienteFolio($usuarioId), 4, '0', STR_PAD_LEFT);
            $uuid = $this->uuid4();

            $pdo->prepare(
                'INSERT INTO pos_ventas (uuid, usuario_id, turno_id, folio, cliente_id, subtotal, iva, total, estado, notas)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $uuid, $usuarioId, (int) $turno['id'], $folio, $clienteId,
                $subtotal, $iva, $total, 'pagada', mb_substr($notas, 0, 255),
            ]);

            $ventaId = (int) $pdo->lastInsertId();

            $insertar = $pdo->prepare(
                'INSERT INTO pos_venta_lineas (venta_id, pos_producto_id, descripcion, cantidad, precio_unitario,
                                               descuento, iva_tasa, subtotal, total)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );

            foreach ($lineas as $l) {
                $insertar->execute([
                    $ventaId, $l['id'], $l['descripcion'], $l['cantidad'], $l['precio'],
                    $l['descuento'], $l['tasa'], $l['subtotal'], $l['total'],
                ]);
            }

            $pdo->prepare(
                'INSERT INTO pos_pagos (venta_id, metodo, monto, recibido, cambio) VALUES (?,?,?,?,?)'
            )->execute([$ventaId, $metodo, $total, $recibido, $cambio]);

            if ($metodo === 'efectivo') {
                $this->repo->registrarMovimiento((int) $turno['id'], 'venta', $total, 'Venta ' . $folio, $folio);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ['ok' => false, 'mensaje' => $e->getMessage()];
        }

        $this->auditoria->log('pos_venta', $usuarioId, 'pos_venta', $uuid, ['total' => $total, 'metodo' => $metodo], '');

        $this->revisarExistencias($usuarioId);

        return ['ok' => true, 'mensaje' => 'Venta registrada.', 'folio' => $folio, 'total' => $total, 'cambio' => $cambio, 'uuid' => $uuid];
    }

    public function cancelarVenta(int $usuarioId, string $uuid): array
    {
        $venta = $this->repo->ventaPorUuid($usuarioId, $uuid);

        if ($venta === null || $venta['estado'] !== 'pagada') {
            return ['ok' => false, 'mensaje' => 'Esa venta no se puede cancelar.'];
        }

        $pdo = Database::conexion();
        $pdo->beginTransaction();

        try {
            foreach ($this->repo->lineas((int) $venta['id']) as $linea) {
                if ($linea['pos_producto_id'] !== null) {
                    $pdo->prepare('UPDATE pos_productos SET stock = stock + ? WHERE id = ? AND usuario_id = ?')
                        ->execute([(float) $linea['cantidad'], (int) $linea['pos_producto_id'], $usuarioId]);
                }
            }

            $pdo->prepare("UPDATE pos_ventas SET estado = 'cancelada' WHERE id = ?")->execute([(int) $venta['id']]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();

            return ['ok' => false, 'mensaje' => 'No se pudo cancelar: ' . $e->getMessage()];
        }

        $this->auditoria->log('pos_cancelacion', $usuarioId, 'pos_venta', $uuid, [], '');

        return ['ok' => true, 'mensaje' => 'Venta cancelada y stock devuelto.'];
    }

    /**
     * Importa al inventario del POS las líneas de pedidos ya entregados.
     * Se ejecuta al abrir la caja: si el comercio compró 20 costales, los tiene
     * en su inventario sin capturarlos a mano.
     */
    public function sincronizarInventario(int $usuarioId): int
    {
        $ajustes = $this->repo->ajustes($usuarioId);
        $margen = (float) $ajustes['margen_venta'] / 100;
        $pdo = Database::conexion();

        $stmt = $pdo->prepare(
            "SELECT dp.id AS linea_id, dp.producto_id, dp.nombre_producto, dp.cantidad, dp.precio_unitario, dp.iva_tasa
               FROM detalle_pedido dp
               JOIN pedidos p ON p.id = dp.pedido_id
               JOIN comercios_perfil c ON c.id = p.comercio_id
              WHERE c.usuario_id = ?
                AND p.estado IN ('entregado','completado')
                AND NOT EXISTS (SELECT 1 FROM pos_productos pp WHERE pp.pedido_linea_id = dp.id)"
        );
        $stmt->execute([$usuarioId]);
        $faltantes = $stmt->fetchAll() ?: [];

        if ($faltantes === []) {
            return 0;
        }

        $insertar = $pdo->prepare(
            'INSERT IGNORE INTO pos_productos
                (usuario_id, producto_id, pedido_linea_id, nombre, precio_compra, precio_venta, iva_tasa,
                 stock, stock_minimo, unidad, activo)
             VALUES (?,?,?,?,?,?,?,?,?,?,1)'
        );

        foreach ($faltantes as $f) {
            $compra = round((float) $f['precio_unitario'], 2);
            $venta = round($compra * (1 + $margen), 2);

            $insertar->execute([
                $usuarioId, (int) $f['producto_id'], (int) $f['linea_id'], $f['nombre_producto'],
                $compra, $venta, (float) $f['iva_tasa'], (float) $f['cantidad'],
                max(1, (int) ceil((float) $f['cantidad'] * 0.2)), 'pieza',
            ]);
        }

        return count($faltantes);
    }

    /** Productos por debajo del mínimo, con la cantidad sugerida a pedir. */
    public function sugerenciasReposicion(int $usuarioId): array
    {
        $bajos = $this->repo->productos($usuarioId, '', true);
        $salida = [];

        foreach ($bajos as $p) {
            $objetivo = max((float) $p['stock_minimo'] * 3, (float) $p['stock_minimo'] + 1);
            $salida[] = [
                'id'        => (int) $p['id'],
                'nombre'    => (string) $p['nombre'],
                'stock'     => (float) $p['stock'],
                'minimo'    => (float) $p['stock_minimo'],
                'sugerido'  => max(1, (int) ceil($objetivo - (float) $p['stock'])),
                'proveedor' => (int) $p['producto_id'],
            ];
        }

        return $salida;
    }

    /**
     * Crea un pedido en borrador al mayorista habitual con los productos
     * sugeridos. El comercio lo revisa y paga desde la pantalla de pedidos.
     */
    public function pedidoReposicion(int $usuarioId, array $ids): array
    {
        $mayoristaId = $this->repo->mayoristaHabitual($usuarioId);
        $direccionId = $this->repo->direccionHabitual($usuarioId);

        if ($mayoristaId === null || $direccionId === null) {
            return ['ok' => false, 'mensaje' => 'Necesitas al menos un pedido entregado y una dirección para reponer.'];
        }

        $pdo = Database::conexion();
        $detalle = [];
        $subtotal = 0.0;
        $iva = 0.0;

        foreach ($this->sugerenciasReposicion($usuarioId) as $s) {
            if ($ids !== [] && !in_array($s['id'], $ids, true)) {
                continue;
            }

            $producto = $this->repo->producto($usuarioId, $s['id']);

            if ($producto === null || $producto['producto_id'] === null) {
                continue;
            }

            $precio = (float) $producto['precio_compra'];
            $tasa = (float) $producto['iva_tasa'];
            $lineaSub = round($precio * $s['sugerido'], 2);

            $detalle[] = [
                'producto_id' => (int) $producto['producto_id'],
                'nombre'      => (string) $producto['nombre'],
                'cantidad'    => $s['sugerido'],
                'precio'      => $precio,
                'tasa'        => $tasa,
                'subtotal'    => $lineaSub,
            ];

            $subtotal += $lineaSub;
            $iva += round($lineaSub * $tasa, 2);
        }

        if ($detalle === []) {
            return ['ok' => false, 'mensaje' => 'No hay productos con proveedor para reponer.'];
        }

        $total = round($subtotal + $iva, 2);
        $comercioId = $this->comercioId($usuarioId);

        $pdo->beginTransaction();

        try {
            $pdo->prepare(
                "INSERT INTO checkouts (uuid, comercio_id, idempotency_key, total, estado)
                 VALUES (?,?,?,?, 'pendiente_pago')"
            )->execute([$this->uuid4(), $comercioId, 'pos-' . bin2hex(random_bytes(8)), $total]);
            $checkoutId = (int) $pdo->lastInsertId();

            $pdo->prepare(
                "INSERT INTO pedidos (uuid, checkout_id, comercio_id, mayorista_id, direccion_entrega_id,
                                      subtotal, iva, total, estado)
                 VALUES (?,?,?,?,?,?,?,?, 'borrador')"
            )->execute([$this->uuid4(), $checkoutId, $comercioId, $mayoristaId, $direccionId, round($subtotal, 2), round($iva, 2), $total]);

            $pedidoId = (int) $pdo->lastInsertId();

            $insertar = $pdo->prepare(
                'INSERT INTO detalle_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario,
                                             escala_aplicada, iva_tasa, subtotal)
                 VALUES (?,?,?,?,?,?,?,?)'
            );

            foreach ($detalle as $d) {
                $insertar->execute([$pedidoId, $d['producto_id'], $d['nombre'], $d['cantidad'], $d['precio'], 1, $d['tasa'], $d['subtotal']]);
            }

            $pdo->prepare(
                "INSERT INTO pedido_eventos (pedido_id, estado_anterior, estado_nuevo, nota)
                 VALUES (?, NULL, 'borrador', 'Reposición sugerida por el punto de venta')"
            )->execute([$pedidoId]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();

            return ['ok' => false, 'mensaje' => 'No se pudo crear el pedido: ' . $e->getMessage()];
        }

        $uuid = (string) $pdo->query('SELECT uuid FROM pedidos WHERE id = ' . $pedidoId)->fetchColumn();

        $this->notificaciones->encolar(
            $usuarioId,
            'reposicion',
            'Pedido de reposición listo',
            'Armamos un borrador con los productos que están por agotarse. Revísalo y confírmalo.',
            '/pedidos/' . $uuid,
        );

        return ['ok' => true, 'mensaje' => 'Pedido de reposición creado.', 'uuid' => $uuid];
    }

    /** Avisa al comercio cuando un producto cruza el mínimo. */
    private function revisarExistencias(int $usuarioId): void
    {
        $ajustes = $this->repo->ajustes($usuarioId);

        if ((int) $ajustes['reorden_auto'] !== 1) {
            return;
        }

        foreach ($this->sugerenciasReposicion($usuarioId) as $s) {
            $this->notificaciones->encolar(
                $usuarioId,
                'stock_bajo',
                'Se está agotando: ' . $s['nombre'],
                'Quedan ' . $s['stock'] . ' y tu mínimo es ' . $s['minimo'] . '. Te sugerimos pedir ' . $s['sugerido'] . '.',
                '/pos?reponer=1',
            );
        }
    }

    private function siguienteFolio(int $usuarioId): int
    {
        // Se toma el folio más alto del día, no el número de ventas: si alguna
        // se borra, contar volvía a generar un folio ya usado y la venta fallaba.
        $prefijo = 'POS-' . date('ymd') . '-';

        $stmt = Database::conexion()->prepare(
            'SELECT COALESCE(MAX(CAST(SUBSTRING(folio, ?) AS UNSIGNED)), 0)
               FROM pos_ventas
              WHERE usuario_id = ? AND folio LIKE ?'
        );
        $stmt->execute([strlen($prefijo) + 1, $usuarioId, $prefijo . '%']);

        return (int) $stmt->fetchColumn() + 1;
    }

    private function comercioId(int $usuarioId): int
    {
        $stmt = Database::conexion()->prepare('SELECT id FROM comercios_perfil WHERE usuario_id = ? LIMIT 1');
        $stmt->execute([$usuarioId]);

        return (int) $stmt->fetchColumn();
    }

    private function uuid4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
