<?php

declare(strict_types=1);

/**
 * API JSON de Ares React.
 *
 * Reutiliza el dominio ya probado (precios, escalas, IVA, reservas de stock) y
 * expone solo lo que la SPA necesita. Corre en un puerto aparte y nunca escribe
 * en la base de producción salvo el carrito del usuario que inicia sesión.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Core\AuditLogger;
use App\Core\Session;
use App\Modules\Auth\AuthService;
use App\Modules\Auth\UsuarioRepository;
use App\Modules\Carrito\CarritoRepository;
use App\Modules\Carrito\CarritoService;
use App\Modules\Busqueda\BusquedaRepository;
use App\Modules\Busqueda\BusquedaService;
use App\Modules\Catalogo\PrecioService;
use App\Modules\Catalogo\ProductoRepository;
use App\Modules\Notificaciones\NotificacionService;
use App\Modules\Pedidos\CheckoutService;
use App\Modules\Pedidos\PedidoService;
use App\Modules\Perfil\PerfilRepository;
use App\Core\Cache;

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$ruta = preg_replace('#^/api#', '', $ruta) ?: '/';
$cuerpo = [];

if (in_array($metodo, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
    $crudo = file_get_contents('php://input') ?: '';

    // DELETE y acciones sin cuerpo: se aceptan vacíos.
    if ($crudo === '') {
        $cuerpo = [];
    }

    // Solo JSON: un formulario de otro sitio no puede enviar este tipo sin preflight.
    $tipo = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if ($crudo !== '' && !str_starts_with($tipo, 'application/json')) {
        fallo('Envía el cuerpo como application/json.', 415, 'TIPO_INVALIDO');
    }

    if ($crudo !== '') {
        $cuerpo = json_decode($crudo, true);
        if (!is_array($cuerpo)) {
            fallo('El cuerpo no es JSON válido.', 400, 'JSON_INVALIDO');
        }
    }

    // Defensa CSRF: si el navegador manda Origin, debe ser el mismo host.
    $origen = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origen !== '') {
        $hostOrigen = (string) parse_url($origen, PHP_URL_HOST);
        $hostPropio = (string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($hostOrigen === '' || strcasecmp($hostOrigen, $hostPropio) !== 0) {
            fallo('Origen no permitido.', 403, 'ORIGEN');
        }
    }
}

function responder(array $datos, int $estado = 200): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fallo(string $mensaje, int $estado = 400, string $codigo = 'ERROR'): void
{
    responder(['ok' => false, 'error' => ['code' => $codigo, 'message' => $mensaje]], $estado);
}

try {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

    Session::iniciar();

    $pdo = Database::conexion();
    $usuarioId = (int) (Session::obtener('user_id') ?? 0);
    $roles = (array) (Session::obtener('roles') ?? []);
    $rol = $roles[0] ?? null;

    $perfilRepo = new PerfilRepository($pdo);

    $comercioId = static function () use ($pdo): int {
        $stmt = $pdo->prepare('SELECT id FROM comercios_perfil WHERE usuario_id = ? LIMIT 1');
        $stmt->execute([(int) (Session::obtener('user_id') ?? 0)]);

        return (int) ($stmt->fetchColumn() ?: 0);
    };

    $mayoristaId = static function () use ($pdo): int {
        $stmt = $pdo->prepare('SELECT id FROM mayoristas_perfil WHERE usuario_id = ? LIMIT 1');
        $stmt->execute([(int) (Session::obtener('user_id') ?? 0)]);

        return (int) ($stmt->fetchColumn() ?: 0);
    };

    $datosUsuario = static function () use ($pdo): array {
        $usuarioId = (int) (Session::obtener('user_id') ?? 0);
        $roles = (array) (Session::obtener('roles') ?? []);
        $stmt = $pdo->prepare('SELECT id, nombre, email, telefono, avatar_ruta FROM usuarios WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$usuarioId]);
        $u = $stmt->fetch() ?: [];

        return [
            'id'       => $usuarioId,
            'nombre'   => (string) ($u['nombre'] ?? ''),
            'email'    => (string) ($u['email'] ?? ''),
            'telefono' => (string) ($u['telefono'] ?? ''),
            'avatar'   => !empty($u["avatar_ruta"]) ? '/avatar/' . $usuarioId : null,
            'roles'    => array_values($roles),
        ];
    };

    // ------------------------------------------------------------ sesión
    if ($ruta === '/session' && $metodo === 'GET') {
        responder([
            'ok'      => true,
            'usuario' => $usuarioId > 0 ? $datosUsuario() : null,
            'rol'     => $rol,
        ]);
    }

    if ($ruta === '/login' && $metodo === 'POST') {
        $email = trim((string) ($cuerpo['email'] ?? ''));
        $clave = (string) ($cuerpo['password'] ?? '');

        if ($email === '' || $clave === '') {
            fallo('Escribe tu correo y contraseña.', 422, 'DATOS_FALTANTES');
        }

        $notificaciones = new NotificacionService($pdo, __DIR__ . '/../storage/correos');
        $auth = new AuthService(new UsuarioRepository($pdo), new AuditLogger($pdo), $notificaciones);
        $resultado = $auth->login($email, $clave, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if (!$resultado['ok']) {
            fallo((string) ($resultado['mensaje'] ?? 'No se pudo iniciar sesión.'), 401, 'CREDENCIALES');
        }

        Session::regenerar();
        Session::poner('user_id', (int) $resultado['usuario']['id']);
        Session::poner('uuid', (string) $resultado['usuario']['uuid']);
        Session::poner('email', (string) $resultado['usuario']['email']);
        Session::poner('roles', $resultado['roles']);

        $roles = (array) (Session::obtener('roles') ?? []);
        responder(['ok' => true, 'usuario' => $datosUsuario(), 'rol' => $roles[0] ?? null]);
    }

    if ($ruta === '/logout' && $metodo === 'POST') {
        Session::destruir();
        responder(['ok' => true]);
    }

    if ($usuarioId <= 0) {
        fallo('Inicia sesión para continuar.', 401, 'SIN_SESION');
    }

    // ------------------------------------------------------------ inicio
    if ($ruta === '/inicio' && $metodo === 'GET') {
        if (!in_array($rol, ['comercio', 'mayorista'], true) && !str_starts_with((string) $rol, 'admin')) {
            fallo('Tu rol no tiene panel.', 403, 'SIN_PERMISO');
        }

        $metricas = [];
        $accesos = [];
        $herramientas = [];

        if ($rol === 'comercio') {
            $cid = $comercioId();

            $pedidos = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE comercio_id = $cid AND estado NOT IN ('entregado','cancelado')")->fetchColumn();
            $hoy = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE comercio_id = $cid AND DATE(created_at) = CURDATE() AND estado <> 'cancelado'")->fetchColumn();
            $metricas = [
                ['valor' => (string) $pedidos, 'etiqueta' => 'Pedidos en curso', 'nota' => 'Ver pedidos', 'href' => '/pedidos'],
                ['valor' => '$' . number_format($hoy, 0), 'etiqueta' => 'Comprado hoy', 'nota' => 'Hoy', 'href' => '/pedidos'],
            ];

            $accesos = [
                ['href' => '/buscar', 'titulo' => 'Buscar productos', 'texto' => 'Compara precios y arma tu carrito', 'icono' => 'buscar'],
                ['href' => '/pedidos', 'titulo' => 'Mis pedidos', 'texto' => 'Sigue tus compras y entregas', 'icono' => 'pedidos'],
                ['href' => '/perfil', 'titulo' => 'Mi perfil', 'texto' => 'Datos, direcciones y sesión', 'icono' => 'perfil'],
            ];

            $herramientas = [];
        } elseif ($rol === 'mayorista') {
            $mid = $mayoristaId();

            $productos = (int) $pdo->query("SELECT COUNT(*) FROM productos WHERE mayorista_id = $mid AND deleted_at IS NULL")->fetchColumn();
            $porSurtir = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE mayorista_id = $mid AND estado IN ('pendiente_aceptacion','aceptado','preparando')")->fetchColumn();
            $ventas = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE mayorista_id = $mid AND DATE(created_at) = CURDATE() AND estado <> 'cancelado'")->fetchColumn();

            $metricas = [
                ['valor' => (string) $productos, 'etiqueta' => 'Productos activos', 'nota' => 'Ver catálogo', 'href' => '/productos'],
                ['valor' => (string) $porSurtir, 'etiqueta' => 'Pedidos por surtir', 'nota' => 'Ver pedidos', 'href' => '/pedidos'],
                ['valor' => '$' . number_format($ventas, 0), 'etiqueta' => 'Vendido hoy', 'nota' => 'Ver pedidos', 'href' => '/pedidos'],
            ];

            $accesos = [
                ['href' => '/productos', 'titulo' => 'Mi catálogo', 'texto' => 'Productos, precios y stock', 'icono' => 'productos'],
                ['href' => '/pedidos', 'titulo' => 'Pedidos recibidos', 'texto' => 'Acepta y surte', 'icono' => 'pedidos'],
                ['href' => '/perfil', 'titulo' => 'Mi perfil', 'texto' => 'Datos y sesión', 'icono' => 'perfil'],
            ];
        } else {
            $usuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE deleted_at IS NULL')->fetchColumn();
            $kyc = (int) $pdo->query("SELECT COUNT(*) FROM mayoristas_perfil WHERE estado_kyc = 'pendiente'")->fetchColumn();
            $reclamos = (int) $pdo->query("SELECT COUNT(*) FROM reclamos WHERE estado = 'abierto'")->fetchColumn();
            $pedidosHoy = (int) $pdo->query('SELECT COUNT(*) FROM pedidos WHERE DATE(created_at) = CURDATE()')->fetchColumn();

            $metricas = [
                ['valor' => (string) $usuarios, 'etiqueta' => 'Usuarios', 'nota' => 'Gestionar', 'href' => '/admin'],
                ['valor' => (string) $kyc, 'etiqueta' => 'KYC pendientes', 'nota' => $kyc > 0 ? 'Revisar' : 'Al día', 'href' => '/admin'],
                ['valor' => (string) $reclamos, 'etiqueta' => 'Reclamos abiertos', 'nota' => $reclamos > 0 ? 'Atender' : 'Al día', 'href' => '/admin'],
                ['valor' => (string) $pedidosHoy, 'etiqueta' => 'Pedidos hoy', 'nota' => 'Operación', 'href' => '/admin'],
            ];

            $accesos = [
                ['href' => '/admin', 'titulo' => 'Panel', 'texto' => 'Métricas y pendientes', 'icono' => 'admin'],
                ['href' => '/admin', 'titulo' => 'Verificaciones', 'texto' => 'Cola de KYC', 'icono' => 'kyc'],
            ];
        }

        responder([
            'ok'       => true,
            'nombre'   => $datosUsuario()['nombre'],
            'rol'      => $rol,
            'metricas' => $metricas,
            'accesos'  => $accesos,
            'herramientas' => $herramientas,
        ]);
    }

    // ------------------------------------------------------------ buscar
    if ($ruta === '/buscar' && $metodo === 'GET') {
        $lat = (float) ($_GET['lat'] ?? 19.4326);
        $lng = (float) ($_GET['lng'] ?? -99.1332);

        $servicio = new BusquedaService(
            new BusquedaRepository($pdo),
            new Cache(__DIR__ . '/../storage/cache'),
            $pdo
        );

        $resultado = $servicio->buscar([
            'q'        => (string) ($_GET['q'] ?? ''),
            'lat'      => $lat,
            'lng'      => $lng,
            'radio'    => (string) ($_GET['radio'] ?? BusquedaService::RADIO_DEFAULT),
            'orden'    => (string) ($_GET['orden'] ?? 'distancia'),
            'page'     => (int) ($_GET['page'] ?? 1),
            'per_page' => (int) ($_GET['per_page'] ?? 24),
        ]);

        if (!$resultado['ok']) {
            fallo((string) ($resultado['error']['message'] ?? 'No se pudo buscar.'), 422, (string) ($resultado['error']['code'] ?? 'ERROR'));
        }

        responder(['ok' => true] + $resultado['data']);
    }

    // ------------------------------------------------------------ carrito
    $carrito = new CarritoService(new CarritoRepository($pdo), new PrecioService());

    if ($ruta === '/carrito' && $metodo === 'GET') {
        responder(['ok' => true] + $carrito->recalcular($comercioId()));
    }

    if ($ruta === '/carrito/agregar' && $metodo === 'POST') {
        $cantidad = $cuerpo['cantidad'] ?? 1;
        if (!is_int($cantidad)) {
            fallo('La cantidad debe ser un número entero.', 422, 'CANTIDAD');
        }

        $r = $carrito->agregarItem($comercioId(), (string) ($cuerpo['producto'] ?? ''), $cantidad);

        if (!$r['ok']) {
            fallo((string) $r['mensaje'], 422, 'NO_AGREGADO');
        }

        responder(['ok' => true, 'mensaje' => $r['mensaje'], 'carrito' => $carrito->recalcular($comercioId())]);
    }

    if ($ruta === '/carrito/item' && in_array($metodo, ['PATCH', 'DELETE'], true)) {
        $uuid = (string) ($cuerpo['producto'] ?? '');
        $cantidad = $metodo === 'DELETE' ? 0 : (int) ($cuerpo['cantidad'] ?? 0);

        if ($metodo === 'PATCH' && $cantidad < 1) {
            fallo('La cantidad debe ser al menos 1.', 422, 'CANTIDAD');
        }

        if ($cantidad < 1) {
            $carrito->quitar($comercioId(), $uuid);
        } else {
            $carrito->actualizar($comercioId(), $uuid, $cantidad);
        }

        responder(['ok' => true, 'carrito' => $carrito->recalcular($comercioId())]);
    }

    if ($ruta === '/carrito' && $metodo === 'DELETE') {
        $carrito->vaciar($comercioId());
        responder(['ok' => true, 'carrito' => $carrito->recalcular($comercioId())]);
    }

    // ------------------------------------------------------------ checkout
    if (($ruta === '/checkout') && $rol !== 'comercio') {
        fallo('Solo los comercios pueden comprar.', 403, 'SIN_PERMISO');
    }

    if ($ruta === '/checkout' && $metodo === 'GET') {
        $resumen = $carrito->recalcular($comercioId());

        responder([
            'ok'          => true,
            'resumen'     => $resumen,
            'direcciones' => $perfilRepo->listarDirecciones($usuarioId),
        ]);
    }

    if ($ruta === '/checkout' && $metodo === 'POST') {
        $checkout = new CheckoutService(
            $pdo,
            $carrito,
            new PedidoService($pdo, new NotificacionService($pdo, __DIR__ . '/../storage/correos')),
            new AuditLogger($pdo)
        );

        $direccionId = (int) ($cuerpo['direccion_id'] ?? 0);
        $duena = $pdo->prepare('SELECT 1 FROM direcciones WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1');
        $duena->execute([$direccionId, $usuarioId]);
        if (!$duena->fetchColumn()) {
            fallo('Elige una dirección tuya.', 422, 'DIRECCION_INVALIDA');
        }

        $r = $checkout->crearCheckout(
            $comercioId(),
            (string) ($cuerpo['idempotency_key'] ?? bin2hex(random_bytes(16))),
            $direccionId,
            $usuarioId
        );

        if (!$r['ok']) {
            fallo((string) $r['mensaje'], 422, (string) ($r['codigo'] ?? 'CHECKOUT'));
        }

        responder(['ok' => true, 'data' => $r['data'], 'carrito' => $carrito->recalcular($comercioId())]);
    }

    // ------------------------------------------------------------ pedidos
    if ($ruta === '/pedidos' && $metodo === 'GET') {
        if ($rol === 'comercio') {
            $sql = "SELECT p.uuid, p.estado, p.total, p.created_at, m.nombre_comercial AS contraparte,
                           (SELECT COUNT(*) FROM detalle_pedido d WHERE d.pedido_id = p.id) AS lineas
                    FROM pedidos p JOIN mayoristas_perfil m ON m.id = p.mayorista_id
                    WHERE p.comercio_id = ? ORDER BY p.created_at DESC LIMIT 50";
            $params = [$comercioId()];
        } elseif (str_starts_with((string) $rol, 'admin')) {
            $sql = "SELECT p.uuid, p.estado, p.total, p.created_at, m.nombre_comercial AS contraparte,
                           (SELECT COUNT(*) FROM detalle_pedido d WHERE d.pedido_id = p.id) AS lineas
                    FROM pedidos p JOIN mayoristas_perfil m ON m.id = p.mayorista_id
                    ORDER BY p.created_at DESC LIMIT 50";
            $params = [];
        } else {
            $sql = "SELECT p.uuid, p.estado, p.total, p.created_at, c.nombre_negocio AS contraparte,
                           (SELECT COUNT(*) FROM detalle_pedido d WHERE d.pedido_id = p.id) AS lineas
                    FROM pedidos p JOIN comercios_perfil c ON c.id = p.comercio_id
                    WHERE p.mayorista_id = ? ORDER BY p.created_at DESC LIMIT 50";
            $params = [$mayoristaId()];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        responder(['ok' => true, 'pedidos' => $stmt->fetchAll() ?: []]);
    }

    // ------------------------------------------------------------ perfil
    if ($ruta === '/perfil' && $metodo === 'GET') {
        $u = $datosUsuario();
        $perfil = $perfilRepo->perfil($usuarioId, (string) $rol) ?: [];
        $direcciones = $perfilRepo->listarDirecciones($usuarioId);

        responder([
            'ok'          => true,
            'usuario'     => $u,
            'perfil'      => $perfil,
            'direcciones' => $direcciones,
            'miembro'     => (string) ($pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM usuarios WHERE id = $usuarioId")->fetchColumn() ?: ''),
        ]);
    }

    // ------------------------------------------------------- catálogo
    if ($ruta === '/productos' && $metodo === 'GET') {
        $repo = new ProductoRepository($pdo);
        $filas = $repo->listarPorMayorista($mayoristaId(), (string) ($_GET['q'] ?? ''));

        responder(['ok' => true, 'productos' => $filas]);
    }

    // ------------------------------------------------------------ admin
    if ($ruta === '/admin' && $metodo === 'GET') {
        if (!str_starts_with((string) $rol, 'admin')) {
            fallo('Solo el equipo de administración.', 403, 'SIN_PERMISO');
        }

        responder([
            'ok' => true,
            'resumen' => [
                'usuarios'   => (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE deleted_at IS NULL')->fetchColumn(),
                'comercios'  => (int) $pdo->query('SELECT COUNT(*) FROM comercios_perfil')->fetchColumn(),
                'mayoristas' => (int) $pdo->query('SELECT COUNT(*) FROM mayoristas_perfil')->fetchColumn(),
                'pedidos'    => (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
                'kyc'        => (int) $pdo->query("SELECT COUNT(*) FROM mayoristas_perfil WHERE estado_kyc = 'pendiente'")->fetchColumn(),
                'reclamos'   => (int) $pdo->query("SELECT COUNT(*) FROM reclamos WHERE estado = 'abierto'")->fetchColumn(),
            ],
        ]);
    }

    fallo('Ruta no encontrada: ' . $ruta, 404, 'NO_ENCONTRADA');
} catch (\Throwable $e) {
    error_log('[ares-api] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    responder(['ok' => false, 'error' => ['code' => 'INTERNO', 'message' => 'Error del servidor.']], 500);
}
