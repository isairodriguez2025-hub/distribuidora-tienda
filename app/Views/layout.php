<?php
/**
 * Plantilla base. Todo lo que se imprime pasa por $e() para escapar.
 *
 * @var callable $e
 * @var string   $nonce
 * @var string   $titulo
 * @var string   $contenido
 */
$usuario = \App\Core\Session::obtener('user_id', null);
$roles = \App\Core\Session::obtener('roles', []);
$rol = is_array($roles) && $roles !== [] ? (string) $roles[0] : null;

// Nombre y foto para el menú de cuenta (una sola consulta por página).
$nombreCuenta = '';
$avatarUrl = null;
$tema = 'claro';
if ($usuario !== null) {
    try {
        $stmtCuenta = \App\Config\Database::conexion()->prepare(
            'SELECT u.email, c.nombre_negocio, m.nombre_comercial
               FROM usuarios u
               LEFT JOIN comercios_perfil c ON c.usuario_id = u.id
               LEFT JOIN mayoristas_perfil m ON m.usuario_id = u.id
              WHERE u.id = ? LIMIT 1'
        );
        $stmtCuenta->execute([$usuario]);
        $filaCuenta = $stmtCuenta->fetch() ?: [];
        $nombreCuenta = (string) ($filaCuenta['nombre_negocio'] ?? $filaCuenta['nombre_comercial'] ?? $filaCuenta['email'] ?? '');
        $avatarUrl = \App\Modules\Perfil\AvatarService::url((int) $usuario);

        $stmtTema = \App\Config\Database::conexion()->prepare(
            'SELECT tema FROM preferencias_usuario WHERE usuario_id = ? LIMIT 1'
        );
        $stmtTema->execute([$usuario]);
        $tema = $stmtTema->fetchColumn() === 'oscuro' ? 'oscuro' : 'claro';
    } catch (\Throwable) {
        $nombreCuenta = '';
    }
}

$enlaces = [];
if ($usuario !== null) {
    $enlaces[] = ['/buscar', 'Buscar'];

    if ($rol === 'comercio') {
        $enlaces[] = ['/pedidos', 'Pedidos'];
        $enlaces[] = ['/favoritos', 'Favoritos'];
        $enlaces[] = ['/programados', 'Programados'];
    }

    if ($rol === 'mayorista') {
        $enlaces[] = ['/mayorista/productos', 'Mis productos'];
        $enlaces[] = ['/mayorista/pedidos', 'Pedidos'];
    }

    $enlaces[] = ['/finanzas', 'Finanzas'];
    $enlaces[] = ['/facturacion', 'Facturación'];
    if (in_array($rol, ['admin_total', 'admin_kyc', 'admin_soporte', 'admin_finanzas'], true)) {
        $enlaces[] = ['/admin', 'Admin'];
    }
    if ($rol === 'comercio') {
        $enlaces[] = ['/pos/inventario', 'Inventario'];
        $enlaces[] = ['/pos', 'Caja'];
        $enlaces[] = ['/pos/ventas', 'Ventas'];
    }
    if (in_array($rol, ['admin_total', 'admin_kyc'], true)) {
        $enlaces[] = ['/admin/kyc', 'Verificaciones'];
    }
}

// Mensajes sin leer para el globo del menú.
$sinLeerMsg = 0;
if ($usuario !== null) {
    try {
        $stmtMsg = \App\Config\Database::conexion()->prepare(
            'SELECT COUNT(*) FROM mensajes_directos m
               JOIN conversacion_participantes p
                 ON p.conversacion_id = m.conversacion_id AND p.usuario_id = ?
              WHERE m.id > p.ultimo_leido_id AND m.autor_id <> ?'
        );
        $stmtMsg->execute([$usuario, $usuario]);
        $sinLeerMsg = (int) $stmtMsg->fetchColumn();
    } catch (\Throwable) {
        $sinLeerMsg = 0;
    }
}

/**
 * Iconos de la barra lateral. Se dibujan con trazo y heredan el color, así
 * funcionan igual en claro y en oscuro sin duplicar archivos.
 */
$iconoNav = static function (string $ruta): string {
    $dibujos = [
        '/buscar'             => '<circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.6-3.6"></path>',
        '/pedidos'            => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"></path><path d="M14 3v5h5"></path><path d="M9 13h6M9 17h4"></path>',
        '/mayorista/pedidos'  => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"></path><path d="M14 3v5h5"></path><path d="M9 13h6M9 17h4"></path>',
        '/favoritos'          => '<path d="M12 20s-7-4.4-7-9.2A4 4 0 0 1 12 7a4 4 0 0 1 7 3.8C19 15.6 12 20 12 20Z"></path>',
        '/programados'        => '<rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3 11h18"></path>',
        '/mayorista/productos' => '<path d="m21 8-9-5-9 5 9 5 9-5Z"></path><path d="M3 8v8l9 5 9-5V8"></path><path d="M12 13v8"></path>',
        '/finanzas'           => '<path d="M4 19V10M10 19V5M16 19v-6M22 19H2"></path>',
        '/facturacion'        => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2Z"></path><path d="M9 8h6M9 12h6"></path>',
        '/admin'              => '<path d="M12 2.5 4.5 5.5v5.9c0 5 3.2 9.1 7.5 10.6 4.3-1.5 7.5-5.6 7.5-10.6V5.5Z"></path>',
        '/pos/inventario'     => '<path d="M3 7h18v13H3z"></path><path d="M3 7l2-4h14l2 4"></path><path d="M9 12h6"></path>',
        '/pos'                => '<rect x="4" y="3" width="16" height="18" rx="2"></rect><path d="M8 7h8M8 11h8M8 15h4"></path>',
        '/pos/ventas'         => '<path d="M4 4h16v16H4z"></path><path d="M8 9h8M8 13h5"></path>',
        '/admin/kyc'          => '<circle cx="12" cy="12" r="9"></circle><path d="m8.5 12 2.4 2.4 4.6-4.6"></path>',
        '/mensajes'           => '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9 9 0 0 1-3.9-.9L3 21l1.9-4.6A8.4 8.4 0 0 1 12 3a8.4 8.4 0 0 1 9 8.5Z"></path>',
        '/perfil'             => '<circle cx="12" cy="8" r="3.6"></circle><path d="M4.5 20a7.5 7.5 0 0 1 15 0"></path>',
    ];

    $d = $dibujos[$ruta] ?? '<circle cx="12" cy="12" r="8"></circle>';

    return '<svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor"'
        . ' stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $d . '</svg>';
};

$uriActual = (string) ($_SERVER['REQUEST_URI'] ?? '');

$nav = '';
foreach ($enlaces as [$href, $texto]) {
    $activo = str_starts_with($uriActual, $href);
    $globo = $href === '/mensajes' && $sinLeerMsg > 0
        ? '<span class="nav-globo">' . ($sinLeerMsg > 9 ? '9+' : $sinLeerMsg) . '</span>'
        : '';
    $nav .= '<a class="nav-enlace' . ($activo ? ' activo' : '') . '" href="' . $e($href) . '"'
        . ' title="' . $e($texto) . '"' . ($activo ? ' aria-current="page"' : '') . '>'
        . '<span class="nav-enlace__icono">' . $iconoNav($href) . '</span>'
        . '<span class="nav-enlace__texto">' . $e($texto) . $globo . '</span></a>';
}

// Barra inferior del celular: cuatro accesos, siempre los mismos, para que no
// cambien de sitio según el rol.
$enlacePrincipal = match ($rol) {
    'comercio'  => ['/pedidos', 'Pedidos'],
    'mayorista' => ['/mayorista/pedidos', 'Pedidos'],
    'admin_total', 'admin_kyc', 'admin_soporte', 'admin_finanzas' => ['/admin', 'Admin'],
    default     => ['/buscar', 'Buscar'],
};

$inferior = [
    ['/buscar', 'Buscar'],
    $enlacePrincipal,
    ['/mensajes', 'Mensajes'],
    ['/perfil', 'Cuenta'],
];

$navInferior = '';
foreach ($inferior as [$href, $texto]) {
    $activo = str_starts_with($uriActual, $href);
    $globo = $href === '/mensajes' && $sinLeerMsg > 0
        ? '<span class="inferior__globo">' . ($sinLeerMsg > 9 ? '9+' : $sinLeerMsg) . '</span>'
        : '';
    $navInferior .= '<a class="inferior__item' . ($activo ? ' activo' : '') . '" href="' . $e($href) . '"'
        . ($activo ? ' aria-current="page"' : '') . '>'
        . '<span class="inferior__icono">' . $iconoNav($href) . $globo . '</span>'
        . '<span class="inferior__texto">' . $e($texto) . '</span></a>';
}
?>
<!doctype html>
<html lang="es" data-tema="<?= $tema ?>">
<head>
    <meta charset="utf-8">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="/assets/img/ares-logo.svg">
    <link rel="apple-touch-icon" href="/assets/img/ares-192.png">
    <meta name="theme-color" content="#0B0C0E">
    <link rel="icon" href="/icon-192.svg" type="image/svg+xml">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($titulo) ?> · Ares</title>
    <?php if (!empty($mapa)): ?>
        <link rel="stylesheet" href="/assets/leaflet/leaflet.css">
    <?php endif; ?>
    <style nonce="<?= $e($nonce) ?>">
        :root {
            --tinta: #1a1a1a;
            --gris: #6b7280;
            --gris-claro: #9ca3af;
            --linea: #e8e6df;
            --fondo: #fafaf7;
            --marca: #1f4d3a;
            --marca-suave: #eaf1ec;
            --alerta: #a56a12;
            --error: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--fondo);
            color: var(--tinta);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        header.barra {
            background: #fff;
            border-bottom: 1px solid var(--linea);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .barra__interior {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            gap: 28px;
        }
        .marca {
            font-weight: 650;
            font-size: 17px;
            letter-spacing: -.02em;
            color: var(--marca);
            text-decoration: none;
        }
        .marca span { color: var(--gris-claro); font-weight: 400; }
        nav { display: flex; gap: 20px; flex: 1; }
        nav a {
            color: var(--gris);
            text-decoration: none;
            font-size: 14px;
            padding: 4px 0;
            border-bottom: 2px solid transparent;
        }
        nav a:hover { color: var(--tinta); }
        nav a.activo { color: var(--marca); border-bottom-color: var(--marca); }
        .salir {
            font-size: 14px;
            color: var(--gris);
            text-decoration: none;
        }
        .salir:hover { color: var(--error); }
        main {
            max-width: 960px;
            margin: 0 auto;
            padding: 40px 24px 72px;
        }
        /* Pantallas que necesitan todo el ancho (buscador con mapa). */
        .ancho-completo .barra__interior { max-width: 100%; padding: 0 24px; }
        .ancho-completo main { max-width: 100%; padding: 24px 24px 48px; }
        h1 {
            font-size: 26px;
            letter-spacing: -.02em;
            margin: 0 0 6px;
            font-weight: 650;
        }
        h2 {
            font-size: 17px;
            letter-spacing: -.01em;
            margin: 32px 0 10px;
            font-weight: 600;
        }
        p { color: var(--gris); margin: 0 0 12px; }
        a { color: var(--marca); }
        code {
            background: #f2f1ec;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
        /* Formularios */
        input, select, textarea {
            font: inherit;
            color: var(--tinta);
            background: #fff;
            border: 1px solid var(--linea);
            border-radius: 8px;
            padding: 9px 12px;
            width: 100%;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--marca);
            box-shadow: 0 0 0 3px rgba(31, 77, 58, .1);
        }
        input[type=checkbox] { width: auto; accent-color: var(--marca); }
        label { display: block; margin-bottom: 12px; }
        label > span {
            display: block;
            font-size: 13px;
            color: var(--gris);
            margin-bottom: 4px;
        }
        button, .boton {
            font: inherit;
            font-size: 14px;
            font-weight: 500;
            background: var(--marca);
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: 10px 18px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .boton:hover { background: #17392b; }
        .boton--claro {
            background: #fff;
            color: var(--tinta);
            border: 1px solid var(--linea);
        }
        .boton--claro:hover { background: #f7f6f2; }
        /* Tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background: #fff;
            border: 1px solid var(--linea);
            border-radius: 10px;
            overflow: hidden;
        }
        thead th {
            text-align: left;
            font-weight: 500;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--gris-claro);
            padding: 10px 14px;
            border-bottom: 1px solid var(--linea);
            background: #fcfcfa;
        }
        tbody td { padding: 12px 14px; border-bottom: 1px solid #f4f2ec; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: #fcfcfa; }
        /* Avisos */
        .aviso {
            padding: 11px 14px;
            border-radius: 8px;
            font-size: 14px;
            margin: 0 0 16px;
        }
        .aviso--ok { background: var(--marca-suave); border: 1px solid #cfdcd3; color: #1f4d3a; }
        .aviso--alerta { background: #fdf6e6; border: 1px solid #efe0bd; color: #7a5a12; }
        .aviso--error { background: #fef2f2; border: 1px solid #f6d5d5; color: var(--error); }
        .error-campo { font-size: 12px; color: var(--error); display: block; margin-top: 3px; }
        .vacio { color: var(--gris-claro); padding: 20px 0; }
        <?= $estilos ?? '' ?>
        /* --- Selector de cantidad (compartido) --------------------------- */
        .stepper { display:inline-flex; align-items:center; border:1px solid #E5E7EB; border-radius:8px; overflow:hidden; background:#fff; }
        .stepper button {
            width:32px; height:36px; padding:0; background:#fff; border:0; color:#4b5563;
            font-size:16px; line-height:1; cursor:pointer;
            transition: background .12s ease, color .12s ease;
        }
        .stepper button:hover { background:#f3f4f6; color:#111827; }
        .stepper button:active { background:#e5e7eb; }
        .stepper input {
            width:52px; height:36px; padding:0; border:0; border-left:1px solid #E5E7EB; border-right:1px solid #E5E7EB;
            text-align:center; font-size:14px; border-radius:0; appearance:textfield; -moz-appearance:textfield;
        }
        .stepper input:focus { outline:none; box-shadow:none; }
        .stepper input::-webkit-outer-spin-button,
        .stepper input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }

        /* --- Botón flotante del carrito ---------------------------------- */
        .fab-carrito {
            position: fixed; right: 24px; bottom: 24px; z-index: 90;
            width: 58px; height: 58px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: #1f4d3a; color: #fff; text-decoration: none;
            box-shadow: 0 8px 24px rgba(17,24,39,.22), 0 2px 6px rgba(17,24,39,.12);
            transition: transform .15s ease, background .15s ease, box-shadow .15s ease;
        }
        .fab-carrito:hover { background:#17392b; transform: translateY(-2px); box-shadow: 0 12px 28px rgba(17,24,39,.26); }
        .fab-carrito:active { transform: translateY(0); }
        .fab-carrito__badge {
            position: absolute; top: -4px; right: -4px; min-width: 22px; height: 22px; padding: 0 6px;
            border-radius: 999px; background: #b91c1c; color: #fff;
            font-size: 12px; font-weight: 600; line-height: 22px; text-align: center;
            border: 2px solid #fafaf7;
        }
        @media (max-width: 640px) {
            .fab-carrito { right: 16px; bottom: 16px; width: 54px; height: 54px; }
        }

        @media (max-width: 640px) {
            .barra__interior { gap: 14px; padding: 0 16px; height: 56px; }
            /* En móvil la marca se queda en "Didi2" y el menú se desliza. */
            .marca span { display: none; }
            nav {
                gap: 16px; overflow-x: auto; white-space: nowrap;
                scrollbar-width: none; -ms-overflow-style: none;
            }
            nav::-webkit-scrollbar { display: none; }
            nav a { flex: 0 0 auto; }
            main { padding: 24px 16px 48px; }
            h1 { font-size: 22px; }
        }
    </style>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="<?= !empty($anchoCompleto) ? 'ancho-completo' : '' ?><?= !empty($auth) ? ' auth' : '' ?>">
<?php if (empty($auth)): ?>
<?php if ($usuario !== null): ?>
<aside class="lateral" id="nav-principal" aria-label="Menú principal">
    <a class="lateral__marca" href="/buscar" aria-label="Ares, ir a la búsqueda">
        <img class="lateral__logo" src="/assets/img/ares-logo.svg" alt="" width="38" height="38">
        <span class="lateral__nombre">Ares <em>· B2B</em></span>
    </a>

    <nav class="lateral__nav" aria-label="Principal"><?= $nav ?></nav>

    <div class="lateral__pie">
<div class="cuenta">
                <button type="button" class="cuenta__boton" id="cuenta-boton" aria-haspopup="true" aria-expanded="false" aria-controls="cuenta-menu">
                    <?php if ($avatarUrl !== null): ?>
                        <img class="cuenta__foto" src="<?= $e($avatarUrl) ?>" alt="" width="32" height="32" decoding="async">
                    <?php else: ?>
                        <span class="cuenta__iniciales" aria-hidden="true"><?= $e(\App\Modules\Perfil\AvatarService::iniciales($nombreCuenta)) ?></span>
                    <?php endif; ?>
                    <span class="cuenta__chevron" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>
                    </span>
                    <span class="visually-hidden">Mi cuenta</span>
                </button>

                <div class="cuenta__menu" id="cuenta-menu" role="menu" data-abierto="0">
                    <div class="cuenta__cabecera">
                        <div class="cuenta__nombre"><?= $e($nombreCuenta) ?></div>
                        <div class="cuenta__rol"><?= $e(str_replace('_', ' ', (string) $rol)) ?></div>
                    </div>
                    <a role="menuitem" href="/perfil">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.6"></circle><path d="M4.5 20a7.5 7.5 0 0 1 15 0"></path></svg>
                        <span>Mi perfil</span></a>
                    <?php if ($rol === 'comercio'): ?>
                        <a role="menuitem" href="/pedidos">
                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"></path><path d="M14 3v5h5"></path><path d="M9 13h6M9 17h4"></path></svg>
                            <span>Mis pedidos</span></a>
                    <?php elseif ($rol === 'mayorista'): ?>
                        <a role="menuitem" href="/mayorista/pedidos">
                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"></path><path d="M14 3v5h5"></path><path d="M9 13h6M9 17h4"></path></svg>
                            <span>Pedidos recibidos</span></a>
                    <?php endif; ?>
                    <a role="menuitem" href="/mensajes">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9 9 0 0 1-3.9-.9L3 21l1.9-4.6A8.4 8.4 0 0 1 12 3a8.4 8.4 0 0 1 9 8.5Z"></path></svg>
                        <span>Mensajes</span></a>
                    <div class="cuenta__sep"></div>
                    <form method="post" action="/logout" style="margin:0">
                        <input type="hidden" name="_csrf" value="<?= $e(\App\Core\Csrf::token()) ?>">
                        <button type="submit" role="menuitem" class="cuenta__salir">Cerrar sesión</button>
                    </form>
                </div>
    </div>
</aside>
<?php endif; ?>
<header class="barra barra--movil">
    <div class="barra__interior">
        <a class="marca" href="<?= $usuario !== null ? '/buscar' : '/login' ?>"><img class="marca__logo" src="/assets/img/ares-logo.svg" alt="" width="28" height="28">Ares <span>· B2B</span></a>
    </div>
</header>
<?php endif; ?>
<main class="<?= !empty($auth) ? 'auth' : '' ?>">
    <?= $contenido ?>
</main>
<?php if ($usuario !== null && empty($auth)): ?>
<nav class="inferior" aria-label="Navegación principal"><?= $navInferior ?></nav>
<?php endif; ?>
<?php if (!empty($mapa)): ?>
    <script src="/assets/leaflet/leaflet.js"></script>
<?php endif; ?>
<?php
$rolActual = is_array($roles) && $roles !== [] ? (string) $roles[0] : null;
$enCarrito = str_starts_with((string) ($_SERVER['REQUEST_URI'] ?? ''), '/carrito');
?>
<?php if ($usuario !== null && $rolActual === 'comercio' && !$enCarrito): ?>
    <?php $articulos = \App\Modules\Carrito\CarritoContador::paraUsuario((int) $usuario); ?>
    <a class="fab-carrito" href="/carrito" aria-label="Ir al carrito<?= $articulos > 0 ? ' (' . $articulos . ' artículos)' : '' ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <?php if ($articulos > 0): ?>
            <span class="fab-carrito__badge"><?= $articulos > 99 ? '99+' : $articulos ?></span>
        <?php endif; ?>
    </a>
<?php endif; ?>
<?php if (!empty($scripts)): ?>
    <script nonce="<?= $e($nonce) ?>"><?= $scripts ?></script>
<?php endif; ?>
<script nonce="<?= $nonce ?? '' ?>">
if ("serviceWorker" in navigator) { window.addEventListener("load", function() { navigator.serviceWorker.register("/sw.js"); }); }

// Mostrar u ocultar contraseña. El botón conserva el foco y anuncia el
// cambio de estado a los lectores de pantalla.
document.querySelectorAll('.ojo').forEach(function (boton) {
    boton.addEventListener('click', function () {
        var campo = document.getElementById(boton.dataset.para);
        if (!campo) return;
        var visible = campo.type === 'password';
        campo.type = visible ? 'text' : 'password';
        boton.classList.toggle('activo', visible);
        boton.setAttribute('aria-pressed', visible ? 'true' : 'false');
        boton.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        campo.focus();
    });
});

// Medidor de fuerza: longitud, mayúscula, número y símbolo.
var medidor = document.getElementById('medidor');
if (medidor) {
    var entrada = document.querySelector('[data-fuerza="medidor"]');
    var texto = document.getElementById('fuerza-texto');
    var etiquetas = ['Muy débil', 'Débil', 'Aceptable', 'Buena', 'Excelente'];
    entrada.addEventListener('input', function () {
        var v = entrada.value, nivel = 0;
        if (v.length >= 12) nivel++;
        if (/[A-ZÁÉÍÓÚÑ]/.test(v)) nivel++;
        if (/[0-9]/.test(v)) nivel++;
        if (/[^A-Za-z0-9]/.test(v)) nivel++;
        if (v.length === 0) nivel = 0;
        medidor.dataset.nivel = String(nivel);
        if (texto) texto.textContent = v.length === 0 ? 'Usa mayúsculas, números y un símbolo.' : etiquetas[nivel];
    });
}

// Cambio de tema: se aplica al instante y se guarda en la cuenta. Si el
// navegador no ejecuta JS, el botón Guardar del <noscript> hace lo mismo.
var formTema = document.getElementById('form-tema');
if (formTema) {
    var casillaTema = formTema.querySelector('input[name="tema"]');
    casillaTema.addEventListener('change', function () {
        document.documentElement.dataset.tema = casillaTema.checked ? 'oscuro' : 'claro';
        formTema.submit();
    });
}

// Diálogos: se abren desde un botón y se cierran con Cancelar o Escape.
document.querySelectorAll('[data-abrir]').forEach(function (boton) {
    boton.addEventListener('click', function () {
        var dialogo = document.getElementById(boton.dataset.abrir);
        if (!dialogo) return;
        if (typeof dialogo.showModal === 'function') { dialogo.showModal(); }
        else { dialogo.setAttribute('open', ''); }
    });
});
document.querySelectorAll('dialog [data-cerrar]').forEach(function (boton) {
    boton.addEventListener('click', function () {
        var dialogo = boton.closest('dialog');
        if (dialogo) { dialogo.close(); }
    });
});

// Menú de cuenta y navegación móvil.
(function () {
    var boton = document.getElementById('cuenta-boton');
    var menu = document.getElementById('cuenta-menu');
    var navToggle = document.getElementById('nav-toggle');
    var nav = document.getElementById('nav-principal');

    function cerrarMenu() {
        if (!menu) return;
        menu.setAttribute('data-abierto', '0');
        boton.setAttribute('aria-expanded', 'false');
    }

    if (boton && menu) {
        boton.addEventListener('click', function (e) {
            e.stopPropagation();
            var abierto = menu.getAttribute('data-abierto') === '1';
            menu.setAttribute('data-abierto', abierto ? '0' : '1');
            boton.setAttribute('aria-expanded', abierto ? 'false' : 'true');
        });

        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target) && e.target !== boton) cerrarMenu();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { cerrarMenu(); boton.focus(); }
        });
    }

    if (navToggle && nav) {
        navToggle.addEventListener('click', function () {
            var abierto = nav.getAttribute('data-abierto') === '1';
            nav.setAttribute('data-abierto', abierto ? '0' : '1');
            navToggle.setAttribute('aria-expanded', abierto ? 'false' : 'true');
        });
    }
})();
</script>
</body>
</html>
