<?php
/**
 * Productos sintéticos para la caja, con su ilustración.
 *
 * Reejecutable: si el producto ya existe (mismo nombre y usuario) solo se
 * actualizan precio, existencias y foto.
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();

use App\Config\Database;

$pdo = Database::conexion();
$usuarioId = 2; // comercio@didi2.mx

$productos = [
    ['Café molido 1 kg', 'CAF-1K', '7501001000011', 140.00, 185.00, 12, 3, 'cafe-molido'],
    ['Azúcar estándar 1 kg', 'AZU-1K', '7501001000028', 28.00, 38.50, 20, 5, 'azucar'],
    ['Aceite vegetal 900 ml', 'ACE-900', '7501001000035', 34.00, 49.90, 6, 2, 'aceite'],
    ['Detergente líquido 1 L', 'DET-1L', '7501001000042', 44.00, 62.00, 9, 3, 'detergente'],
    ['Agua purificada 1.5 L', 'AGU-15', '7501001000059', 9.50, 14.50, 36, 12, 'agua'],
    ['Mayonesa 800 g', 'MAY-800', '7501001000066', 33.00, 48.00, 7, 2, 'mayonesa'],
    ['Atún en agua 140 g', 'ATU-140', '7501001000073', 15.00, 21.50, 24, 8, 'atun'],
    ['Frijol negro 1 kg', 'FRI-1K', '7501001000080', 25.00, 36.00, 15, 4, 'frijol'],
    ['Arroz grano largo 1 kg', 'ARR-1K', '7501001000097', 22.00, 32.00, 18, 5, 'arroz'],
    ['Harina de trigo 1 kg', 'HAR-1K', '7501001000103', 18.00, 26.50, 11, 3, 'harina'],
    ['Té verde 20 sobres', 'TE-20', '7501001000110', 27.00, 39.00, 8, 2, 'te-verde'],
    ['Papel de baño 4 rollos', 'PAP-4', '7501001000127', 31.00, 45.00, 13, 4, 'papel-bano'],
];

$buscar = $pdo->prepare('SELECT id FROM pos_productos WHERE usuario_id = ? AND nombre = ? LIMIT 1');
$insertar = $pdo->prepare(
    'INSERT INTO pos_productos (usuario_id, nombre, imagen, sku, codigo_barras, precio_compra, precio_venta,
                                iva_tasa, stock, stock_minimo, unidad, activo)
     VALUES (?, ?, ?, ?, ?, ?, ?, 0.16, ?, ?, "pieza", 1)'
);
$actualizar = $pdo->prepare(
    'UPDATE pos_productos SET imagen = ?, sku = ?, codigo_barras = ?, precio_compra = ?, precio_venta = ?,
            stock = ?, stock_minimo = ?, activo = 1
     WHERE id = ?'
);

$nuevos = 0;
$actualizados = 0;

foreach ($productos as [$nombre, $sku, $codigo, $compra, $venta, $stock, $minimo, $slug]) {
    $imagen = '/assets/img/productos/' . $slug . '.svg';

    $buscar->execute([$usuarioId, $nombre]);
    $id = $buscar->fetchColumn();

    if ($id) {
        $actualizar->execute([$imagen, $sku, $codigo, $compra, $venta, $stock, $minimo, (int) $id]);
        $actualizados++;
    } else {
        $insertar->execute([$usuarioId, $nombre, $imagen, $sku, $codigo, $compra, $venta, $stock, $minimo]);
        $nuevos++;
    }
}

echo "Productos: {$nuevos} nuevos, {$actualizados} actualizados\n";
