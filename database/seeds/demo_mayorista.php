<?php
/**
 * Datos de demostración para la cuenta de mayorista "Insumos del Valle"
 * (mayoristas_perfil 2, usuario 5): catálogo surtido y pedidos en cada estado
 * para que el portal tenga algo que mostrar desde el primer inicio de sesión.
 *
 * Reejecutable: si ya hay pedidos demo de este mayorista no duplica nada.
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();

use App\Config\Database;

$pdo = Database::conexion();

const MAYORISTA = 2;
const COMERCIO = 1;
const DIRECCION = 1;

// ---------------------------------------------------------------- catálogo
$productos = [
    ['Detergente industrial 1 kg', 'DET-IND-1K', 52.00, 120],
    ['Cloro 1 L', 'CLO-1L', 19.90, 200],
    ['Jabón en polvo 1 kg', 'JAB-1K', 34.50, 90],
    ['Fibra para trastes 3 pzas', 'FIB-3', 18.00, 150],
    ['Bolsa de basura 60x90 (50)', 'BOL-6090', 46.00, 80],
    ['Papel higiénico industrial (12)', 'PAP-IND-12', 89.00, 60],
    ['Gel antibacterial 1 L', 'GEL-1L', 41.00, 70],
    ['Escoba de cerda suave', 'ESC-SU', 37.00, 40],
];

$existe = $pdo->prepare('SELECT id FROM productos WHERE mayorista_id = ? AND nombre = ? AND deleted_at IS NULL LIMIT 1');
$alta = $pdo->prepare(
    'INSERT INTO productos (uuid, mayorista_id, categoria_id, nombre, descripcion, precio_base, iva_tasa,
                            unidad_venta, contenido, peso_kg, volumen_m3, cantidad_minima, multiplo_venta, stock_disponible)
     VALUES (UUID(), ?, ?, ?, ?, ?, 0.16, "pieza", 1, 1.0, 0.001, 1, 1, ?)'
);

$ids = [];
foreach ($productos as [$nombre, $sku, $precio, $stock]) {
    $existe->execute([MAYORISTA, $nombre]);
    $id = $existe->fetchColumn();

    if (!$id) {
        $alta->execute([MAYORISTA, 1, $nombre, 'Producto de línea institucional. ' . $sku, $precio, $stock]);
        $id = (int) $pdo->lastInsertId();
    }

    $ids[$nombre] = (int) $id;
}

// ------------------------------------------------------------------ pedidos
$hay = $pdo->prepare('SELECT COUNT(*) FROM pedidos WHERE mayorista_id = ? AND nota_cliente = ?');
$hay->execute([MAYORISTA, 'Pedido demo del portal']);
$yaHay = (int) $hay->fetchColumn();

$pedidos = [
    ['pendiente_aceptacion', [['Detergente industrial 1 kg', 12], ['Cloro 1 L', 24]], 1],
    ['pendiente_aceptacion', [['Papel higiénico industrial (12)', 6]], 3],
    ['preparando', [['Jabón en polvo 1 kg', 10], ['Gel antibacterial 1 L', 8], ['Fibra para trastes 3 pzas', 20]], 5],
    ['listo_para_recoger', [['Bolsa de basura 60x90 (50)', 10], ['Escoba de cerda suave', 4]], 9],
];

$altaCheckout = $pdo->prepare(
    'INSERT INTO checkouts (uuid, comercio_id, idempotency_key, total, estado, created_at)
     VALUES (UUID(), ?, ?, ?, "pagado", NOW() - INTERVAL ? HOUR)'
);

$altaPedido = $pdo->prepare(
    'INSERT INTO pedidos (uuid, checkout_id, comercio_id, mayorista_id, direccion_entrega_id, subtotal, iva, envio,
                          comision_plataforma, total, nota_cliente, estado, created_at, updated_at)
     VALUES (UUID(), ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, NOW() - INTERVAL ? HOUR, NOW() - INTERVAL ? HOUR)'
);
$altaLinea = $pdo->prepare(
    'INSERT INTO detalle_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, iva_tasa, subtotal)
     VALUES (?, ?, ?, ?, ?, 0.16, ?)'
);

$creados = 0;

if ($yaHay === 0) {
    foreach ($pedidos as $indice => [$estado, $lineas, $horas]) {
        $subtotal = 0.0;
        $detalle = [];

        foreach ($lineas as [$nombre, $cantidad]) {
            $productoId = $ids[$nombre] ?? null;
            if ($productoId === null) {
                continue;
            }

            $precio = (float) $pdo->query('SELECT precio_base FROM productos WHERE id = ' . (int) $productoId)->fetchColumn();
            $importe = round($precio * $cantidad, 2);
            $subtotal += $importe;
            $detalle[] = [$productoId, $nombre, $cantidad, $precio, $importe];
        }

        $iva = round($subtotal * 0.16, 2);
        $total = round($subtotal + $iva, 2);

        $altaCheckout->execute([COMERCIO, 'demo-portal-' . $indice . '-' . $horas, $total, $horas]);
        $checkoutId = (int) $pdo->lastInsertId();

        $altaPedido->execute([$checkoutId, COMERCIO, MAYORISTA, DIRECCION, $subtotal, $iva, $total, 'Pedido demo del portal', $estado, $horas, $horas]);
        $pedidoId = (int) $pdo->lastInsertId();

        foreach ($detalle as [$productoId, $nombre, $cantidad, $precio, $importe]) {
            $altaLinea->execute([$pedidoId, $productoId, $nombre, $cantidad, $precio, $importe]);
        }

        $creados++;
    }
}

echo 'Productos: ' . count($ids) . " · Pedidos demo: {$creados}\n";
