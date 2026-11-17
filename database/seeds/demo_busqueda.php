<?php

declare(strict_types=1);

/**
 * Datos de demostración para la búsqueda geográfica:
 * una zona de cobertura en CDMX y cuatro mayoristas a distintas distancias.
 */

require __DIR__ . '/../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();

$pdo = App\Config\Database::conexion();

// --- Zona de cobertura: polígono aproximado de la CDMX ---------------------
$poligono = [
    [19.5920, -99.3600], [19.5920, -98.9400], [19.2000, -98.9400],
    [19.2000, -99.3600], [19.5920, -99.3600],
];

$pdo->prepare('DELETE FROM zonas_cobertura WHERE nombre = ?')->execute(['Zona piloto CDMX']);
$pdo->prepare('INSERT INTO zonas_cobertura (nombre, activa, poligono) VALUES (?, 1, ?)')
    ->execute(['Zona piloto CDMX', json_encode($poligono)]);

// --- Mayoristas de demostración -------------------------------------------
$mayoristas = [
    ['Centro Abarrotero', 'abarrotes', 19.4326, -99.1332, 4.60, 1500.00],
    ['Insumos del Valle', 'limpieza',   19.3900, -99.1700, 4.20, 2000.00],
    ['Ferretera Norte',   'ferreteria', 19.4800, -99.1500, 3.80, 1000.00],
    ['Distribuidora Sur', 'abarrotes',  19.3500, -99.1600, 4.90, 2500.00],
];

$productos = [
    'Centro Abarrotero' => [
        ['Café molido 1 kg', 185.50, 1, 40],
        ['Azúcar estándar 50 kg', 1250.00, 1, 12],
    ],
    'Insumos del Valle' => [
        ['Detergente 1 kg', 52.00, 2, 60],
        ['Cloro 1 L', 19.90, 2, 80],
    ],
    'Ferretera Norte' => [
        ['Tornillo 1/4 x 2"', 3.50, 3, 500],
        ['Martillo de uña 16 oz', 189.00, 3, 15],
    ],
    'Distribuidora Sur' => [
        ['Café en grano 5 kg', 890.00, 1, 8],
        ['Jabón en polvo 10 kg', 320.00, 2, 25],
    ],
];

foreach ($mayoristas as [$nombre, $giro, $lat, $lng, $reputacion, $minimo]) {
    $email = 'demo-' . strtolower(str_replace(' ', '-', $nombre)) . '@didi2.mx';

    $pdo->prepare('DELETE FROM usuarios WHERE email = ?')->execute([$email]);
    $pdo->prepare(
        "INSERT INTO usuarios (uuid, email, password_hash, telefono, estado, email_verificado_at)
         VALUES (UUID(), ?, ?, '5500000000', 'activo', NOW())"
    )->execute([$email, App\Core\Password::hash('demo1234')]);

    $usuarioId = (int) $pdo->lastInsertId();

    // Sin el rol en usuario_roles, RoleRequired rechaza la cuenta.
    $pdo->prepare("INSERT INTO usuario_roles (usuario_id, rol_id) SELECT ?, id FROM roles WHERE nombre = 'mayorista'")
        ->execute([$usuarioId]);

    $pdo->prepare(
        "INSERT INTO mayoristas_perfil
         (uuid, usuario_id, nombre_comercial, categoria_giro, latitud, longitud, monto_minimo,
          estado_kyc, reputacion, horario_apertura, horario_cierre, activo)
         VALUES (UUID(), ?, ?, ?, ?, ?, ?, 'aprobado', ?, '08:00:00', '18:00:00', 1)"
    )->execute([$usuarioId, $nombre, $giro, $lat, $lng, $minimo, $reputacion]);

    $mayoristaId = (int) $pdo->lastInsertId();

    foreach ($productos[$nombre] as [$prod, $precio, $categoria, $stock]) {
        $pdo->prepare(
            "INSERT INTO productos
             (uuid, mayorista_id, categoria_id, nombre, descripcion, precio_base, iva_tasa,
              unidad_venta, contenido, peso_kg, volumen_m3, cantidad_minima, multiplo_venta,
              stock_disponible, activo)
             VALUES (UUID(), ?, ?, ?, ?, ?, 16.00, 'caja', '12 piezas', 12, 0.02, 1, 1, ?, 1)"
        )->execute([$mayoristaId, $categoria, $prod, "Producto de {$nombre}", $precio, $stock]);
    }
}

echo "Zona de cobertura y " . count($mayoristas) . " mayoristas creados.\n";
