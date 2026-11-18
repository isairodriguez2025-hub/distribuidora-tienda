<?php

declare(strict_types=1);

namespace App\Modules\Explorar;

use App\Core\Csrf;
use App\Core\Iconos;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use PDO;

/**
 * Página de inicio. Saluda por su nombre y abre con una tarjeta que cambia
 * según el tipo de cuenta.
 */
final class ExplorarController
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $directorioVistas,
    ) {
    }

    public function index(Request $peticion): Response
    {
        $usuarioId = (int) Session::obtener('user_id', 0);

        $stmt = $this->pdo->prepare('SELECT nombre FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$usuarioId]);
        $nombre = (string) ($stmt->fetchColumn() ?: 'de nuevo');

        $roles = $this->pdo->prepare(
            'SELECT r.nombre FROM usuario_roles ur JOIN roles r ON r.id = ur.rol_id WHERE ur.usuario_id = ?'
        );
        $roles->execute([$usuarioId]);
        $rol = (string) ($roles->fetchColumn() ?: 'comercio');

        return $this->vista('explorar/index', [
            'titulo'    => 'Explorar',
            'nombre'    => $nombre,
            'rol'       => $rol,
            'principal' => $this->principal($rol),
            'extra'     => $this->extra($rol),
            'resumen'   => $this->resumen($usuarioId, $rol),
            'metricas'  => $this->metricas($usuarioId, $rol),
        ]);
    }

    // ------------------------------------------------------------- contenido

    /** Las cuatro acciones principales de cada tipo de cuenta. */
    private function principal(string $rol): array
    {
        return match ($rol) {
            'comercio' => [
                ['/buscar', 'Buscar', 'Encuentra productos y mayoristas cerca de ti.', 'Buscar'],
                ['/finanzas', 'Finanzas', 'Tus gastos, la gráfica del año y tus facturas.', 'Finanzas'],
                ['/portafolio', 'Portafolio', 'Los productos que guardaste, listos para repetir.', 'Portafolio'],
                ['/agendar', 'Agendar', 'Arma una lista y elige el día en que la quieres.', 'Agendar'],
            ],
            'mayorista' => [
                ['/buscar', 'Buscar', 'Explora el catálogo completo.', 'Buscar'],
                ['/mayorista/productos', 'Mis productos', 'Tu catálogo, precios y existencias.', 'Mis productos'],
                ['/mayorista/pedidos', 'Pedidos', 'Lo que te acaban de pedir.', 'Pedidos'],
                ['/finanzas', 'Finanzas', 'Ventas, comisiones y lo que te queda.', 'Finanzas'],
            ],
            'repartidor' => [
                ['/buscar', 'Buscar', 'Explora el catálogo completo.', 'Buscar'],
                ['/finanzas', 'Finanzas', 'Tus viajes y lo que has ganado.', 'Finanzas'],
            ],
            'admin_total', 'admin_kyc', 'admin_soporte', 'admin_finanzas' => [
                ['/admin', 'Administración', 'El pulso de la plataforma.', 'Admin'],
                ['/admin/kyc', 'Verificaciones', 'Cuentas esperando revisión.', 'Verificaciones'],
                ['/buscar', 'Buscar', 'Explora el catálogo completo.', 'Buscar'],
            ],
            default => [
                ['/buscar', 'Buscar', 'Explora el catálogo completo.', 'Buscar'],
            ],
        };
    }

    /** Accesos secundarios, en una franja aparte. */
    private function extra(string $rol): array
    {
        if ($rol !== 'comercio') {
            return [];
        }

        return [
            ['/pos', 'Caja', 'Cobrar en mostrador.', 'pos'],
            ['/pos/inventario', 'Inventario', 'Existencias y altas de producto.', 'inventario'],
            ['/pos/ventas', 'Ventas', 'El historial del día.', 'ventas'],
            ['/pedidos', 'Pedidos', 'El seguimiento de tus compras.', 'pedidos'],
            ['/mensajes', 'Mensajes', 'Habla con mayoristas y soporte.', 'mensajes'],
        ];
    }

    /**
     * Cuatro cifras del día para la cabecera. Cada una lleva a la pantalla
     * donde se resuelve: un número que no se puede accionar no sirve.
     */
    private function metricas(int $usuarioId, string $rol): array
    {
        $fichas = [];

        try {
            if ($rol === 'comercio') {
                $perfil = $this->perfilId('comercios_perfil', $usuarioId);

                $q = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM pedidos WHERE comercio_id = ?
                      AND estado IN ('pendiente_aceptacion','buscando_repartidor','en_camino')"
                );
                $q->execute([$perfil]);
                $fichas[] = ['valor' => (string) (int) $q->fetchColumn(), 'etiqueta' => 'Pedidos en curso', 'nota' => 'compras sin cerrar', 'href' => '/pedidos'];

                $q = $this->pdo->prepare(
                    'SELECT COALESCE(SUM(total), 0) FROM pos_ventas WHERE usuario_id = ? AND DATE(created_at) = CURDATE()'
                );
                $q->execute([$usuarioId]);
                $fichas[] = ['valor' => '$' . number_format((float) $q->fetchColumn(), 0), 'etiqueta' => 'Vendido hoy', 'nota' => 'en mostrador', 'href' => '/pos/ventas'];
            }

            if ($rol === 'mayorista') {
                $perfil = $this->perfilId('mayoristas_perfil', $usuarioId);

                $q = $this->pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE mayorista_id = ? AND estado = 'pendiente_aceptacion'");
                $q->execute([$perfil]);
                $fichas[] = ['valor' => (string) (int) $q->fetchColumn(), 'etiqueta' => 'Por aceptar', 'nota' => 'esperan tu respuesta', 'href' => '/mayorista/pedidos'];

                $q = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM pedidos WHERE mayorista_id = ? AND estado IN ('preparando','listo_para_recoger')"
                );
                $q->execute([$perfil]);
                $fichas[] = ['valor' => (string) (int) $q->fetchColumn(), 'etiqueta' => 'Por surtir', 'nota' => 'en preparación', 'href' => '/mayorista/pedidos'];

                $q = $this->pdo->prepare(
                    "SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE mayorista_id = ?
                      AND DATE(created_at) = CURDATE() AND estado NOT IN ('cancelado','rechazado')"
                );
                $q->execute([$perfil]);
                $fichas[] = ['valor' => '$' . number_format((float) $q->fetchColumn(), 0), 'etiqueta' => 'Vendido hoy', 'nota' => 'sin cancelados', 'href' => '/finanzas'];

                $q = $this->pdo->prepare('SELECT COUNT(*) FROM productos WHERE mayorista_id = ? AND activo = 1 AND deleted_at IS NULL AND stock_disponible <= 0');
                $q->execute([$perfil]);
                $fichas[] = ['valor' => (string) (int) $q->fetchColumn(), 'etiqueta' => 'Agotados', 'nota' => 'no se pueden vender', 'href' => '/mayorista/productos'];
            }

            if ($rol === 'repartidor') {
                $perfil = $this->perfilId('repartidores_perfil', $usuarioId);

                $q = $this->pdo->prepare(
                    "SELECT COUNT(*), COALESCE(SUM(e.tarifa), 0)
                       FROM envios e JOIN pedidos p ON p.id = e.pedido_id
                      WHERE e.repartidor_id = ? AND p.estado = 'entregado' AND DATE(p.updated_at) = CURDATE()"
                );
                $q->execute([$perfil]);
                $fila = $q->fetch(PDO::FETCH_NUM) ?: [0, 0];
                $fichas[] = ['valor' => (string) (int) $fila[0], 'etiqueta' => 'Viajes hoy', 'nota' => 'entregados', 'href' => '/repartidor'];
                $fichas[] = ['valor' => '$' . number_format((float) $fila[1], 2), 'etiqueta' => 'Ganado hoy', 'nota' => 'antes de comisión', 'href' => '/repartidor'];

                $q = $this->pdo->prepare("SELECT COUNT(*) FROM ofertas_envio WHERE repartidor_id = ? AND estado = 'pendiente' AND expira_en > NOW()");
                $q->execute([$perfil]);
                $fichas[] = ['valor' => (string) (int) $q->fetchColumn(), 'etiqueta' => 'Ofertas activas', 'nota' => 'esperan respuesta', 'href' => '/repartidor'];

                $q = $this->pdo->prepare('SELECT calificacion_promedio FROM repartidores_perfil WHERE id = ?');
                $q->execute([$perfil]);
                $fichas[] = ['valor' => number_format((float) $q->fetchColumn(), 1) . ' ★', 'etiqueta' => 'Calificación', 'nota' => 'promedio', 'href' => '/repartidor'];
            }

            if (str_starts_with($rol, 'admin')) {
                $q = $this->pdo->query('SELECT COUNT(*) FROM pedidos WHERE DATE(created_at) = CURDATE()');
                $fichas[] = ['valor' => (string) (int) $q->fetchColumn(), 'etiqueta' => 'Pedidos hoy', 'nota' => 'toda la plataforma', 'href' => '/admin'];

                $q = $this->pdo->query("SELECT COUNT(*) FROM kyc_documentos WHERE estado = 'pendiente'");
                $fichas[] = ['valor' => (string) (int) $q->fetchColumn(), 'etiqueta' => 'KYC pendientes', 'nota' => 'cuentas por verificar', 'href' => '/admin/kyc'];

                $q = $this->pdo->query('SELECT COUNT(*) FROM envios WHERE repartidor_id IS NULL');
                $fichas[] = ['valor' => (string) (int) $q->fetchColumn(), 'etiqueta' => 'Envíos sin repartidor', 'nota' => 'necesitan atención', 'href' => '/admin'];

                $q = $this->pdo->query("SELECT COUNT(*) FROM reclamos WHERE estado <> 'resuelto'");
                $fichas[] = ['valor' => (string) (int) $q->fetchColumn(), 'etiqueta' => 'Reclamos abiertos', 'nota' => 'sin resolver', 'href' => '/admin/reclamos'];
            }
        } catch (\Throwable) {
        }

        return $fichas;
    }

    /** Identificador del perfil de una cuenta según su tabla. */
    private function perfilId(string $tabla, int $usuarioId): int
    {
        static $permitidas = ['comercios_perfil', 'mayoristas_perfil', 'repartidores_perfil'];

        if (!in_array($tabla, $permitidas, true)) {
            return 0;
        }

        $q = $this->pdo->prepare('SELECT id FROM ' . $tabla . ' WHERE usuario_id = ? LIMIT 1');
        $q->execute([$usuarioId]);

        return (int) $q->fetchColumn();
    }

    private function resumen(int $usuarioId, string $rol): array
    {
        $datos = ['mensajes' => 0, 'pedidos' => 0, 'portafolio' => 0];

        try {
            $q = $this->pdo->prepare(
                'SELECT COUNT(*) FROM mensajes_directos m
                   JOIN conversacion_participantes p
                     ON p.conversacion_id = m.conversacion_id AND p.usuario_id = ?
                  WHERE m.id > p.ultimo_leido_id AND m.autor_id <> ?'
            );
            $q->execute([$usuarioId, $usuarioId]);
            $datos['mensajes'] = (int) $q->fetchColumn();
        } catch (\Throwable) {
        }

        try {
            if ($rol === 'comercio') {
                $q = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM pedidos WHERE comercio_id = ?
                      AND estado IN ('pendiente_aceptacion','buscando_repartidor','en_camino')"
                );
                $q->execute([$usuarioId]);
                $datos['pedidos'] = (int) $q->fetchColumn();

                $q = $this->pdo->prepare('SELECT COUNT(*) FROM favoritos WHERE comercio_id = ?');
                $q->execute([$usuarioId]);
                $datos['portafolio'] = (int) $q->fetchColumn();
            }
        } catch (\Throwable) {
        }

        return $datos;
    }

    private function vista(string $plantilla, array $datos, int $estado = 200): Response
    {
        $nonce = base64_encode(random_bytes(16));
        $vista = new View($this->directorioVistas, $nonce);

        $datos['nonce'] ??= $nonce;
        $datos['csrf'] ??= Csrf::token();

        return Response::html($vista->render($plantilla, $datos), $estado)->withNonce($nonce);
    }
}
