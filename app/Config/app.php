<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Csrf;
use App\Core\ErrorHandler;
use App\Core\HealthController;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AuthRequired;
use App\Middleware\CsrfProtect;
use App\Middleware\RateLimit;
use App\Middleware\RoleRequired;
use App\Middleware\SecurityHeaders;
use App\Config\Database;
use App\Core\AuditLogger;
use App\Modules\Auth\AuthController;
use App\Modules\Auth\AuthService;
use App\Modules\Auth\UsuarioRepository;
use App\Modules\Kyc\KycRepository;
use App\Modules\Kyc\KycService;
use App\Modules\Perfil\PerfilController;
use App\Modules\Perfil\PerfilRepository;
use App\Modules\Catalogo\CatalogoController;
use App\Modules\Catalogo\ProductoRepository;
use App\Modules\Catalogo\ProductoService;
use App\Core\Cache;
use App\Modules\Busqueda\BusquedaController;
use App\Modules\Busqueda\BusquedaRepository;
use App\Modules\Busqueda\BusquedaService;
use App\Modules\Sistema\StaticController;
use App\Modules\Carrito\CarritoController;
use App\Modules\Carrito\CarritoRepository;
use App\Modules\Carrito\CarritoService;
use App\Modules\Pedidos\CheckoutService;
use App\Modules\Pedidos\PedidoService;
use App\Modules\Pedidos\PedidosController;
use App\Modules\Pagos\PagoController;
use App\Modules\Pagos\PagoService;
use App\Modules\Pagos\SimuladoGateway;
use App\Modules\Pagos\WebhookController;
use App\Modules\Jobs\TickController;
use App\Modules\Jobs\Worker;
use App\Modules\Notificaciones\NotificacionController;
use App\Modules\Notificaciones\NotificacionService;
use App\Modules\Logistica\AsignacionService;
use App\Modules\Logistica\PruebaEntregaService;
use App\Modules\Logistica\RepartidorController;
use App\Modules\Logistica\TarifaService;
use App\Modules\Logistica\TrackingController;
use App\Modules\Logistica\TrackingService;
use App\Modules\Favoritos\FavoritoService;
use App\Modules\Favoritos\FavoritosController;
use App\Modules\Programados\ProgramadoService;
use App\Modules\Programados\ProgramadosController;
use App\Modules\Confianza\ChatService;
use App\Modules\Confianza\ConfianzaController;
use App\Modules\Confianza\ReclamoService;
use App\Modules\Confianza\ResenaService;
use App\Modules\Finanzas\FinanzasService;
use App\Modules\Admin\AdminController;
use App\Modules\PostMvp\PostMvpController;
use App\Modules\Facturacion\FacturacionController;
use App\Modules\Facturacion\FacturacionService;

/**
 * Arranque de la aplicación: registra servicios y rutas.
 * Devuelve el contenedor para que index.php lo use.
 */

$correlationId = bin2hex(random_bytes(8));

$logger = new Logger(__DIR__ . '/../../storage/logs', $correlationId);

$errorHandler = new ErrorHandler($logger);
$errorHandler->registrar();

$router = new Router();

$seguridad = new SecurityHeaders();
$directorioRate = __DIR__ . '/../../storage/rate';

// --- Rutas públicas --------------------------------------------------------
$router->get('/health', new HealthController(), [$seguridad]);

// La raíz no es una pantalla: manda a donde corresponde según la sesión.
$router->get('/', static function (\App\Core\Request $peticion): \App\Core\Response {
    return \App\Core\Response::redirect(
        \App\Core\Session::obtener('user_id', null) !== null ? '/perfil' : '/login'
    );
}, [$seguridad]);

// Página de ejemplo: demuestra el escape y el nonce de la CSP.
$router->get('/demo', static function (Request $peticion, array $args) use ($seguridad): Response {
    $nonce = base64_encode(random_bytes(16));
    $vista = new View(__DIR__ . '/../Views', $nonce);

    return Response::html($vista->render('ejemplo', [
        'titulo'  => 'Núcleo',
        'mensaje' => 'Núcleo funcionando',
        'nonce'   => $nonce,
    ]))->withNonce($nonce);
}, [$seguridad]);

// --- Rutas de prueba de los middleware -------------------------------------
$router->get('/api/privado', static fn (): Response => Response::json(['ok' => true, 'data' => 'contenido privado']),
    [$seguridad, new AuthRequired()]);

$router->get('/api/solo-admin', static fn (): Response => Response::json(['ok' => true, 'data' => 'solo admin']),
    [$seguridad, new AuthRequired(), new RoleRequired(['admin_total'])]);

$router->post('/api/accion', static fn (): Response => Response::json(['ok' => true, 'data' => 'acción ejecutada']),
    [$seguridad, new CsrfProtect()]);

$router->get('/api/limitado', static fn (): Response => Response::json(['ok' => true, 'data' => 'dentro del límite']),
    [$seguridad, new RateLimit(3, 60, $directorioRate)]);

// --- Autenticación ---------------------------------------------------------
$pdo = Database::conexion();
$auditoria = new AuditLogger($pdo);
$perfiles = new PerfilRepository($pdo);
$notificaciones = new NotificacionService($pdo, __DIR__ . '/../../storage/correos');
$auth = new AuthController(
    new AuthService(new UsuarioRepository($pdo), $auditoria, $notificaciones),
    __DIR__ . '/../Views',
    $perfiles,
);

$kyc = new KycService(
    new KycRepository($pdo),
    $auditoria,
    $pdo,
    __DIR__ . '/../../storage/kyc',
);

$avatares = new \App\Modules\Perfil\AvatarService($pdo, __DIR__ . '/../../storage/avatars');

$perfil = new PerfilController($perfiles, $kyc, $avatares, __DIR__ . '/../Views', $auditoria);

$productos = new ProductoRepository($pdo);
$catalogo = new CatalogoController(
    new ProductoService($productos, $auditoria, $pdo),
    $productos,
    __DIR__ . '/../Views',
);

$router->get('/login', [$auth, 'loginForm'], [$seguridad]);
$router->post('/login', [$auth, 'login'], [
    $seguridad,
    new CsrfProtect(),
    new RateLimit(5, 900, $directorioRate, 'email'),  // por cuenta
    new RateLimit(40, 900, $directorioRate),          // respaldo por IP
]);
$router->get('/registro', [$auth, 'registroForm'], [$seguridad]);
$router->post('/registro', [$auth, 'registro'], [$seguridad, new CsrfProtect(), new RateLimit(10, 3600, $directorioRate)]);
$router->post('/logout', [$auth, 'logout'], [$seguridad, new CsrfProtect()]);
$router->get('/verificar', [$auth, 'verificar'], [$seguridad]);
$router->get('/recuperar', [$auth, 'recuperarForm'], [$seguridad]);
$router->post('/recuperar', [$auth, 'recuperar'], [$seguridad, new CsrfProtect(), new RateLimit(3, 3600, $directorioRate)]);
$router->get('/restablecer', [$auth, 'restablecerForm'], [$seguridad]);
$router->post('/restablecer', [$auth, 'restablecer'], [$seguridad, new CsrfProtect()]);

// --- Perfil, direcciones y KYC ---------------------------------------------
$router->get('/perfil', [$perfil, 'perfil'], [$seguridad, new AuthRequired()]);
$router->post('/perfil/avatar', [$perfil, 'subirAvatar'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->post('/perfil/avatar/quitar', [$perfil, 'quitarAvatar'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->get('/avatar/{id:\d+}', [$perfil, 'avatar'], [$seguridad]);
$router->post('/perfil/direccion', [$perfil, 'guardarDireccion'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->get('/perfil/editar', [$perfil, 'editarForm'], [$seguridad, new AuthRequired()]);
$router->post('/perfil/editar', [$perfil, 'editar'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->get('/perfil/privacidad', [$perfil, 'privacidad'], [$seguridad, new AuthRequired()]);
$router->post('/perfil/privacidad', [$perfil, 'guardarPrivacidad'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->post('/perfil/eliminar', [$perfil, 'eliminar'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->get('/perfil/ajustes', [$perfil, 'ajustes'], [$seguridad, new AuthRequired()]);
$router->post('/perfil/tema', [$perfil, 'guardarTema'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->post('/perfil/notificaciones', [$perfil, 'notificaciones'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->get('/ayuda', [$perfil, 'ayuda'], [$seguridad, new AuthRequired()]);
$router->post('/calificar', [$perfil, 'calificar'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->get('/kyc', [$perfil, 'kycForm'], [$seguridad, new AuthRequired()]);
$router->post('/kyc', [$perfil, 'subirKyc'], [$seguridad, new AuthRequired(), new CsrfProtect()]);

$router->get('/admin/kyc', [$perfil, 'colaKyc'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total', 'admin_kyc'])]);
$router->post('/admin/kyc/{id:\d+}/aprobar', [$perfil, 'aprobarKyc'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total', 'admin_kyc']), new CsrfProtect()]);
$router->post('/admin/kyc/{id:\d+}/rechazar', [$perfil, 'rechazarKyc'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total', 'admin_kyc']), new CsrfProtect()]);

// --- Catálogo del mayorista ------------------------------------------------
$soloMayorista = [$seguridad, new AuthRequired(), new RoleRequired(['mayorista'])];

$router->get('/mayorista/productos', [$catalogo, 'index'], $soloMayorista);
$router->get('/mayorista/productos/nuevo', [$catalogo, 'nuevoForm'], $soloMayorista);
$router->post('/mayorista/productos', [$catalogo, 'crear'], [...$soloMayorista, new CsrfProtect()]);
$router->get('/mayorista/productos/{uuid:[0-9a-fA-F-]{36}}/editar', [$catalogo, 'editarForm'], $soloMayorista);
$router->post('/mayorista/productos/{uuid:[0-9a-fA-F-]{36}}', [$catalogo, 'actualizar'], [...$soloMayorista, new CsrfProtect()]);
$router->post('/mayorista/productos/{uuid:[0-9a-fA-F-]{36}}/stock', [$catalogo, 'ajustarStock'], [...$soloMayorista, new CsrfProtect()]);
$router->post('/mayorista/productos/csv', [$catalogo, 'importarCsv'], [...$soloMayorista, new CsrfProtect()]);
$router->get('/mayorista/productos/{uuid:[0-9a-fA-F-]{36}}/escalas-preview', [$catalogo, 'escalasPreview'], $soloMayorista);

// --- Carrito y pedidos -----------------------------------------------------
$pedidoService = new PedidoService($pdo, $notificaciones);
$carritoService = new CarritoService(new CarritoRepository($pdo), new \App\Modules\Catalogo\PrecioService());

$carrito = new CarritoController(
    $carritoService,
    new CheckoutService($pdo, $carritoService, $pedidoService, $auditoria),
    $perfiles,
    $pdo,
    __DIR__ . '/../Views',
);

$favoritoService = new FavoritoService($pdo, $carritoService);
$favoritosCtl = new FavoritosController($favoritoService, $pdo, __DIR__ . '/../Views');

$programadoService = new ProgramadoService($pdo, new \App\Modules\Catalogo\PrecioService(), $notificaciones);
$programadosCtl = new ProgramadosController($programadoService, $pdo, __DIR__ . '/../Views');

$admin = new AdminController($pdo, __DIR__ . '/../Views');
$postmvp = new PostMvpController($pdo, __DIR__ . '/../Views');
$facturacion = new FacturacionController(
    new FacturacionService($pdo),
    $pdo,
    __DIR__ . '/../Views',
);

$confianza = new ConfianzaController(
    new ResenaService($pdo),
    new ChatService($pdo),
    new ReclamoService($pdo, $pedidoService),
    new FinanzasService($pdo),
    $pdo,
    __DIR__ . '/../Views',
);

$configPagos = require __DIR__ . '/pagos.php';
$pasarela = new SimuladoGateway($configPagos['webhook_secret'], 300);
$pagoService = new PagoService($pdo, $pedidoService, $pasarela, $auditoria, $configPagos);

$tarifas = new TarifaService($pdo);
$asignacion = new AsignacionService($pdo, $tarifas, $pedidoService);

// El worker necesita la asignación para vencer ofertas: se construye después.
$worker = new Worker($pdo, $pedidoService, $notificaciones, $programadoService, $asignacion);
$tick = new TickController($worker, (string) ($_ENV['WORKER_TOKEN'] ?? ''));
$pruebas = new PruebaEntregaService($pdo);

$chatService = new ChatService($pdo);
$pedidos = new PedidosController($pedidoService, $pdo, __DIR__ . '/../Views', $pagoService, $asignacion, $pruebas, $chatService);

$repartidorCtl = new RepartidorController($asignacion, $pruebas, $pedidoService, $pdo, __DIR__ . '/../Views');

$tracking = new TrackingService($pdo, new \App\Core\Cache(__DIR__ . '/../../storage/cache', 120));
$trackingCtl = new TrackingController($tracking, $pdo, __DIR__ . '/../Views');

$webhooks = new WebhookController($pagoService);
$pagosCtl = new PagoController($pagoService, $pasarela, $pdo, __DIR__ . '/../Views');

$soloComercio = [$seguridad, new AuthRequired(), new RoleRequired(['comercio'])];

$router->get('/carrito', [$carrito, 'ver'], $soloComercio);
$router->post('/carrito/agregar', [$carrito, 'agregar'], [...$soloComercio, new CsrfProtect()]);
$router->post('/carrito/actualizar', [$carrito, 'actualizar'], [...$soloComercio, new CsrfProtect()]);
$router->post('/carrito/quitar', [$carrito, 'quitar'], [...$soloComercio, new CsrfProtect()]);
$router->post('/carrito/vaciar', [$carrito, 'vaciar'], [...$soloComercio, new CsrfProtect()]);
$router->get('/checkout', [$carrito, 'checkoutForm'], $soloComercio);
$router->post('/checkout', [$carrito, 'checkout'], [...$soloComercio, new CsrfProtect()]);

$router->get('/pedidos', [$pedidos, 'misPedidos'], $soloComercio);
$router->get('/pedidos/{uuid:[0-9a-fA-F-]{36}}', [$pedidos, 'detalle'], [$seguridad, new AuthRequired()]);
$router->get('/pedidos/{uuid:[0-9a-fA-F-]{36}}/surtido', [$pedidos, 'surtido'], [$seguridad, new AuthRequired()]);
$router->post('/pedidos/{uuid:[0-9a-fA-F-]{36}}/pagar', [$pedidos, 'pagar'], [...$soloComercio, new CsrfProtect()]);
$router->post('/pedidos/{uuid:[0-9a-fA-F-]{36}}/{accion:aceptar|rechazar|preparando|listo|buscar-reparto|cancelar|en-camino|en-puerta}',
    [$pedidos, 'accion'], [$seguridad, new AuthRequired(), new CsrfProtect()]);

$router->get('/mayorista/pedidos', [$pedidos, 'bandeja'], [$seguridad, new AuthRequired(), new RoleRequired(['mayorista'])]);

// --- Pagos y webhooks ------------------------------------------------------
// El webhook no lleva sesión ni CSRF: se autentica con la firma HMAC.
$router->post('/pagos/webhook', [$webhooks, 'recibir'], [$seguridad]);
$router->get('/internal/tick', [$tick, 'tick'], [$seguridad]);

$avisosCtl = new NotificacionController($notificaciones, __DIR__ . '/../Views');
// Los avisos ahora viven en el centro de mensajes: las rutas viejas
// redirigen para no romper marcadores.
$router->get('/notificaciones', function (): Response {
    return Response::redirect('/mensajes?tab=notificaciones');
}, [$seguridad, new AuthRequired()]);

// --- Mensajería interna ----------------------------------------------------
$mensajesRepo = new \App\Modules\Mensajes\MensajeRepository($pdo);
$mensajesSrv = new \App\Modules\Mensajes\MensajeService($mensajesRepo, $notificaciones, $auditoria);
$mensajesCtl = new \App\Modules\Mensajes\MensajeController($mensajesSrv, $notificaciones, __DIR__ . '/../Views');
$explorarCtl = new \App\Modules\Explorar\ExplorarController($pdo, __DIR__ . '/../Views');
$finanzasCtl = new \App\Modules\Finanzas\FinanzasController($pdo, __DIR__ . '/../Views');

$router->get('/mensajes', [$mensajesCtl, 'index'], [$seguridad, new AuthRequired()]);
$router->post('/mensajes/enviar', [$mensajesCtl, 'enviar'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->post('/mensajes/eliminar', [$mensajesCtl, 'eliminar'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->post('/mensajes/soporte', [$mensajesCtl, 'soporte'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->post('/mensajes/directo', [$mensajesCtl, 'directo'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->get('/mensajes/{uuid:[0-9a-fA-F-]{36}}/nuevos', [$mensajesCtl, 'nuevos'], [$seguridad, new AuthRequired()]);

// --- Punto de venta del comercio -------------------------------------------
$posRepo = new \App\Modules\Pos\PosRepository($pdo);
$posSrv = new \App\Modules\Pos\PosService($posRepo, $auditoria, $notificaciones);
$posCtl = new \App\Modules\Pos\PosController($posSrv, $posRepo, __DIR__ . '/../Views');

$router->get('/pos', [$posCtl, 'caja'], $soloComercio);
$router->get('/pos/inventario', [$posCtl, 'productos'], $soloComercio);
$router->post('/pos/turno/abrir', [$posCtl, 'abrirTurno'], [...$soloComercio, new CsrfProtect()]);
$router->post('/pos/turno/cerrar', [$posCtl, 'cerrarTurno'], [...$soloComercio, new CsrfProtect()]);
$router->get('/pos/corte', [$posCtl, 'corte'], $soloComercio);
$router->post('/pos/vender', [$posCtl, 'vender'], [...$soloComercio, new CsrfProtect()]);
$router->get('/pos/productos', [$posCtl, 'productos'], $soloComercio);
$router->post('/pos/productos', [$posCtl, 'guardarProducto'], [...$soloComercio, new CsrfProtect()]);
$router->post('/pos/productos/{id:\d+}/eliminar', [$posCtl, 'eliminarProducto'], [...$soloComercio, new CsrfProtect()]);
$router->post('/pos/ajustes', [$posCtl, 'guardarAjustes'], [...$soloComercio, new CsrfProtect()]);
$router->post('/pos/reponer', [$posCtl, 'reponer'], [...$soloComercio, new CsrfProtect()]);
$router->get('/pos/ventas', [$posCtl, 'ventas'], $soloComercio);
$router->get('/pos/ticket/{uuid:[0-9a-fA-F-]{36}}', [$posCtl, 'ticket'], $soloComercio);
$router->post('/pos/ventas/{uuid:[0-9a-fA-F-]{36}}/cancelar', [$posCtl, 'cancelar'], [...$soloComercio, new CsrfProtect()]);
$router->get('/pos/clientes', [$posCtl, 'clientes'], $soloComercio);
$router->post('/pos/clientes', [$posCtl, 'clientes'], [...$soloComercio, new CsrfProtect()]);

// --- Repartidor ------------------------------------------------------------
$soloRepartidor = [$seguridad, new AuthRequired(), new RoleRequired(['repartidor'])];

$router->get('/repartidor', [$repartidorCtl, 'panel'], $soloRepartidor);
$router->post('/repartidor/disponibilidad', [$repartidorCtl, 'disponibilidad'], [...$soloRepartidor, new CsrfProtect()]);
$router->post('/repartidor/ofertas/{id:\d+}/aceptar', [$repartidorCtl, 'aceptar'], [...$soloRepartidor, new CsrfProtect()]);
$router->post('/repartidor/pedidos/{uuid:[0-9a-fA-F-]{36}}/recolectar', [$repartidorCtl, 'recolectar'], [...$soloRepartidor, new CsrfProtect()]);
$router->post('/repartidor/pedidos/{uuid:[0-9a-fA-F-]{36}}/entregar', [$repartidorCtl, 'entregar'], [...$soloRepartidor, new CsrfProtect()]);
$router->post('/tracking/posicion', [$trackingCtl, 'posicion'], [...$soloRepartidor, new CsrfProtect()]);

// --- Seguimiento -----------------------------------------------------------
$router->get('/pedidos/{uuid:[0-9a-fA-F-]{36}}/tracking', [$trackingCtl, 'json'], [$seguridad, new AuthRequired()]);
$router->get('/pedidos/{uuid:[0-9a-fA-F-]{36}}/seguimiento', [$trackingCtl, 'mapa'], [$seguridad, new AuthRequired()]);

// --- Confianza: reseñas, chat, reclamos y finanzas ------------------------
$router->post('/pedidos/{uuid:[0-9a-fA-F-]{36}}/resena', [$confianza, 'resenar'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->post('/pedidos/{uuid:[0-9a-fA-F-]{36}}/mensaje', [$confianza, 'mensaje'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->post('/pedidos/{uuid:[0-9a-fA-F-]{36}}/reclamo', [$confianza, 'reclamar'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->get('/finanzas', static function (\App\Core\Request $peticion) use ($finanzasCtl, $confianza, $pdo): \App\Core\Response {
    $stmt = $pdo->prepare('SELECT r.nombre FROM usuario_roles ur JOIN roles r ON r.id = ur.rol_id WHERE ur.usuario_id = ?');
    $stmt->execute([(int) \App\Core\Session::obtener('user_id', 0)]);

    return (string) $stmt->fetchColumn() === 'comercio'
        ? $finanzasCtl->index($peticion)
        : $confianza->finanzas($peticion);
}, [$seguridad, new AuthRequired()]);
$router->get('/finanzas/factura/{uuid:[0-9a-fA-F-]{36}}', [$finanzasCtl, 'factura'], [$seguridad, new AuthRequired()]);
$router->get('/finanzas/export', [$confianza, 'exportar'], [$seguridad, new AuthRequired()]);

// --- Facturación CFDI 4.0 ------------------------------------------------
$router->post('/facturacion/datos', [$facturacion, 'guardarDatos'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->post('/pedidos/{uuid:[0-9a-fA-F-]{36}}/factura', [$facturacion, 'facturar'], [$seguridad, new AuthRequired(), new CsrfProtect()]);
$router->get('/facturas/{uuid:[0-9a-fA-F-]{36}}/xml', [$facturacion, 'xml'], [$seguridad, new AuthRequired()]);

// --- Panel de administración ------------------------------------------------
$router->get('/admin', [$admin, 'dashboard'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total', 'admin_kyc'])]);
$router->get('/admin/usuarios', [$admin, 'listarUsuarios'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total'])]);
$router->get('/admin/usuarios/{id:\d+}', [$admin, 'detalleUsuario'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total'])]);
$router->post('/admin/usuarios/{id:\d+}/rol', [$admin, 'cambiarRol'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total']), new CsrfProtect()]);
$router->post('/admin/usuarios/{id:\d+}/estado', [$admin, 'cambiarEstado'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total']), new CsrfProtect()]);
$router->get('/admin/reclamos', [$admin, 'listarReclamos'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total', 'admin_kyc'])]);
$router->post('/admin/reclamos/{id:\d+}/resolver', [$admin, 'resolverReclamo'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total']), new CsrfProtect()]);
$router->get('/admin/parametros', [$admin, 'listarParametros'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total'])]);
$router->post('/admin/parametros/{clave}', [$admin, 'actualizarParametro'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total']), new CsrfProtect()]);
$router->get('/admin/parametros/{clave}/historial', [$admin, 'historialParametro'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total'])]);

// --- Post-MVP: Fletes, BNPL, Licitaciones --------------------------------
$router->get('/postmvp', [$postmvp, 'dashboard'], [$seguridad, new AuthRequired()]);
$router->get('/postmvp/consolidaciones', [$postmvp, 'listarConsolidaciones'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total'])]);
$router->post('/postmvp/consolidaciones', [$postmvp, 'crearConsolidacion'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total']), new CsrfProtect()]);
$router->get('/postmvp/bnpl', [$postmvp, 'listarBnpl'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total'])]);
$router->post('/postmvp/bnpl', [$postmvp, 'crearBnpl'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total']), new CsrfProtect()]);
$router->post('/postmvp/bnpl/{id:\d+}/pagar', [$postmvp, 'registrarPago'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total']), new CsrfProtect()]);
$router->get('/postmvp/licitaciones', [$postmvp, 'listarLicitaciones'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total'])]);
$router->post('/postmvp/licitaciones', [$postmvp, 'crearLicitacion'], [$seguridad, new AuthRequired(), new RoleRequired(['admin_total']), new CsrfProtect()]);
$router->post('/postmvp/licitaciones/{id:\d+}/cotizar', [$postmvp, 'cotizarLicitacion'], [...$soloComercio, new CsrfProtect()]);

// --- Aviso de privacidad -------------------------------------------------
$router->get('/terminos', function (): Response {
    $nonce = base64_encode(random_bytes(16));
    $vista = new View(__DIR__ . '/../Views', $nonce);
    return Response::html($vista->render('terminos', ['nonce' => $nonce, 'csrf' => Csrf::token()]))->withNonce($nonce);
});

$router->get('/aviso-privacidad', function (): Response {
    $nonce = base64_encode(random_bytes(16));
    $vista = new View(__DIR__ . '/../Views', $nonce);
    return Response::html($vista->render('privacidad', ['nonce' => $nonce, 'csrf' => Csrf::token()]))->withNonce($nonce);
});

// --- Favoritos y programados ----------------------------------------------
$router->get('/explorar', [$explorarCtl, 'index'], [$seguridad, new AuthRequired()]);
$router->get('/portafolio', [$favoritosCtl, 'portafolio'], $soloComercio);
$router->post('/portafolio/agregar', [$favoritosCtl, 'agregar'], [...$soloComercio, new CsrfProtect()]);
$router->post('/portafolio/quitar', [$favoritosCtl, 'quitar'], [...$soloComercio, new CsrfProtect()]);
$router->get('/agendar', [$programadosCtl, 'agendar'], $soloComercio);
$router->post('/agendar', [$programadosCtl, 'crearLista'], [...$soloComercio, new CsrfProtect()]);

// Nombres anteriores: se conservan para no romper enlaces guardados.
$router->get('/favoritos', static fn (): \App\Core\Response => \App\Core\Response::redirect('/portafolio'), [$seguridad, new AuthRequired()]);
$router->get('/programados', static fn (): \App\Core\Response => \App\Core\Response::redirect('/agendar'), [$seguridad, new AuthRequired()]);
$router->get('/facturacion', static function (\App\Core\Request $peticion) use ($facturacion, $pdo): \App\Core\Response {
    $stmt = $pdo->prepare('SELECT r.nombre FROM usuario_roles ur JOIN roles r ON r.id = ur.rol_id WHERE ur.usuario_id = ?');
    $stmt->execute([(int) \App\Core\Session::obtener('user_id', 0)]);

    return (string) $stmt->fetchColumn() === 'comercio'
        ? \App\Core\Response::redirect('/finanzas?tab=facturacion')
        : $facturacion->index($peticion);
}, [$seguridad, new AuthRequired()]);
$router->post('/favoritos/agregar', [$favoritosCtl, 'agregar'], [...$soloComercio, new CsrfProtect()]);
$router->post('/favoritos/quitar', [$favoritosCtl, 'quitar'], [...$soloComercio, new CsrfProtect()]);
$router->post('/favoritos/recompra/{uuid:[0-9a-fA-F-]{36}}', [$favoritosCtl, 'recompra'], [...$soloComercio, new CsrfProtect()]);

$router->post('/programados', [$programadosCtl, 'crear'], [...$soloComercio, new CsrfProtect()]);
$router->post('/programados/{id:\d+}/{estado:activa|pausada|cancelada}', [$programadosCtl, 'estado'], [...$soloComercio, new CsrfProtect()]);
$router->get('/pago/simulado/{referencia}', [$pagosCtl, 'mostrar'], [$seguridad, new AuthRequired()]);
$router->post('/pago/simulado/{referencia}', [$pagosCtl, 'confirmar'], [$seguridad, new AuthRequired(), new CsrfProtect()]);

// --- Búsqueda geográfica ---------------------------------------------------
$busqueda = new BusquedaController(
    new BusquedaService(
        new BusquedaRepository($pdo),
        new Cache(__DIR__ . '/../../storage/cache', BusquedaService::TTL_BUSQUEDA),
        $pdo,
    ),
    __DIR__ . '/../Views',
);

$estaticos = new StaticController(__DIR__ . '/../../public/assets');
$router->get('/assets/{ruta:.*}', [$estaticos, 'servir'], [$seguridad]);

$router->get('/busqueda/productos', [$busqueda, 'productos'], [$seguridad]);
$router->get('/buscar', [$busqueda, 'pagina'], [$seguridad, new AuthRequired()]);
$router->get('/mayoristas/{uuid:[0-9a-fA-F-]{36}}', [$busqueda, 'mayorista'], [$seguridad]);

// Panel de ejemplo protegido por rol.
// --- Paneles de entrada ----------------------------------------------------
// El login manda a /comercio, /mayorista, /admin o /repartidor según el rol.
// Cada uno redirige a la pantalla real de ese rol.
$router->get('/comercio', static fn (): Response => Response::redirect('/explorar'),
    [$seguridad, new AuthRequired(), new RoleRequired(['comercio'])]);

$router->get('/mayorista', static fn (): Response => Response::redirect('/mayorista/productos'),
    [$seguridad, new AuthRequired(), new RoleRequired(['mayorista'])]);

// --- Compatibilidad con las URLs del despliegue anterior -------------------
// El sitio que estuvo publicado antes vivía en /tienda y /app/*. Quien tenga
// esos enlaces guardados cae en 404; se redirige a la pantalla equivalente.
$router->get('/tienda', static fn (): Response => Response::redirect('/buscar'), [$seguridad]);
$router->get('/tienda/{ruta:.*}', static fn (): Response => Response::redirect('/buscar'), [$seguridad]);
$router->get('/app', static fn (): Response => Response::redirect('/login'), [$seguridad]);
$router->get('/app/comercio', static fn (): Response => Response::redirect('/buscar'), [$seguridad]);
$router->get('/app/mayorista', static fn (): Response => Response::redirect('/mayorista/productos'), [$seguridad]);
$router->get('/app/repartidor', static fn (): Response => Response::redirect('/repartidor'), [$seguridad]);
$router->get('/app/admin', static fn (): Response => Response::redirect('/admin'), [$seguridad]);

$contenedor = new Container();
$contenedor->set(Logger::class, $logger);
$contenedor->set(ErrorHandler::class, $errorHandler);
$contenedor->set(Router::class, $router);
$contenedor->set(AuthService::class, $auth);

return $contenedor;
