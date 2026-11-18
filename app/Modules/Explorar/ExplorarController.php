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
