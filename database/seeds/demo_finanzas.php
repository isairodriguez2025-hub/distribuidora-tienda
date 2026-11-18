<?php
/**
 * Datos de demostración para la pestaña de gastos y facturación del comercio
 * de prueba (usuario 2, "Casa de Toño"). Reejecutable: si ya hay pedidos
 * marcados como demo no duplica nada.
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();

use App\Config\Database;

$pdo = Database::conexion();

const COMERCIO = 2;
const MAYORISTA = 4;
const DIRECCION = 1;

$yaHay = (int) $pdo->query('SELECT COUNT(*) FROM checkouts WHERE idempotency_key LIKE "demo-gasto-%"')->fetchColumn();
if ($yaHay > 0) {
    echo "Ya existen los pedidos de demostración ({$yaHay}), no se duplican.\n";
    exit(0);
}

$uuid = static fn (): string => sprintf(
    '%08x-%04x-4%03x-%04x-%012x',
    random_int(0, 0xffffffff), random_int(0, 0xffff), random_int(0, 0xfff),
    random_int(0x8000, 0xbfff), random_int(0, 0xffffffffffff)
);

$proveedores = ['Abarrotes Maru', 'Distribuidora del Valle', 'La Merced Mayorista'];
$estados = ['recibido', 'recibido', 'recibido', 'en_camino', 'buscando_repartidor'];

// Totales por mes: suben hacia el cierre de año, con un bache en abril.
$porMes = [
    '2025-12' => [3, 1800, 2400, 1250],
    '2026-01' => [2, 2100, 3400],
    '2026-02' => [3, 1600, 2900, 4100],
    '2026-03' => [2, 3800, 2250],
    '2026-04' => [1, 1450],
    '2026-05' => [3, 3300, 2600, 1950],
    '2026-06' => [2, 4200, 3100],
    '2026-07' => [3, 2700, 5200, 2400],
    '2026-08' => [2, 6100, 3400],
    '2026-09' => [3, 4500, 3800, 2900],
    '2026-10' => [3, 5600, 4300, 3200],
    '2026-11' => [2, 4800, 6900],
];

$insertarCheckout = $pdo->prepare(
    'INSERT INTO checkouts (uuid, comercio_id, idempotency_key, total, estado, created_at)
     VALUES (?, ?, ?, ?, "pagado", ?)'
);

$insertarPedido = $pdo->prepare(
    'INSERT INTO pedidos (uuid, grupo_pedido, checkout_id, comercio_id, mayorista_id, direccion_entrega_id,
                          subtotal, iva, envio, total, estado, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)'
);

$folio = 1;
$insertarFactura = $pdo->prepare(
    'INSERT INTO facturas (uuid, pedido_id, emisor_usuario_id, receptor_usuario_id, serie, folio,
                           lugar_expedicion, metodo_pago, forma_pago, rfc_emisor, razon_social_emisor,
                           regimen_fiscal_emisor, rfc_receptor, razon_social_receptor, uso_cfdi_receptor,
                           cp_receptor, subtotal, iva, total, estado, fecha_timbrado, created_at)
     VALUES (?, ?, ?, ?, "A", ?, "15000", "PUE", "Transferencia", "MARU850101ABC",
             "Abarrotes Maru SA de CV", "601", "CTO920315XYZ", "Casa de Toño SA de CV", "G03",
             "15000", ?, ?, ?, "timbrada", ?, ?)'
);

$totalPedidos = 0;

foreach ($porMes as $mes => $montos) {
    array_shift($montos);
    $dia = 3;

    foreach ($montos as $monto) {
        $fecha = sprintf('%s-%02d 10:%02d:00', $mes, $dia, random_int(0, 59));
        $dia = min(28, $dia + 8);

        $subtotal = round($monto / 1.16, 2);
        $iva = round($monto - $subtotal, 2);
        $grupo = $uuid();

        $insertarCheckout->execute([$uuid(), COMERCIO, 'demo-gasto-' . $mes . '-' . $dia . '-' . $monto, $monto, $fecha]);
        $checkoutId = (int) $pdo->lastInsertId();

        $insertarPedido->execute([
            $uuid(), $grupo, $checkoutId, COMERCIO, MAYORISTA, DIRECCION,
            $subtotal, $iva, $monto, $estados[$totalPedidos % count($estados)], $fecha, $fecha,
        ]);
        $pedidoId = (int) $pdo->lastInsertId();
        $totalPedidos++;

        // Factura del mayorista en dos de cada tres pedidos.
        if ($totalPedidos % 3 !== 0) {
            $insertarFactura->execute([
                $uuid(), $pedidoId, MAYORISTA, COMERCIO, $folio++,
                $subtotal, $iva, $monto, $fecha, $fecha,
            ]);
        }
    }
}

echo "Pedidos demo: {$totalPedidos} · facturas: " . ($folio - 1) . "\n";
