<?php

declare(strict_types=1);

namespace App\Modules\Confianza;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Modules\Finanzas\FinanzasService;
use PDO;

/**
 * Reseñas, chat, reclamos y finanzas en un solo punto de entrada.
 */
final class ConfianzaController
{
    public function __construct(
        private readonly ResenaService $resenas,
        private readonly ChatService $chat,
        private readonly ReclamoService $reclamos,
        private readonly FinanzasService $finanzas,
        private readonly PDO $pdo,
        private readonly string $directorioVistas,
    ) {
    }

    private function vista(string $plantilla, array $datos, int $estado = 200): Response
    {
        $nonce = base64_encode(random_bytes(16));
        $vista = new View($this->directorioVistas, $nonce);
        $datos['nonce'] ??= $nonce;
        $datos['csrf'] ??= Csrf::token();

        return Response::html($vista->render($plantilla, $datos), $estado)->withNonce($nonce);
    }

    private function usuarioId(): int
    {
        return (int) Session::obtener('user_id', 0);
    }

    /** @return string[] */
    private function roles(): array
    {
        $roles = Session::obtener('roles', []);

        return is_array($roles) ? array_values($roles) : [];
    }

    private function pedidoPorUuid(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, c.usuario_id AS comercio_usuario, m.usuario_id AS mayorista_usuario
             FROM pedidos p
             JOIN comercios_perfil c ON c.id = p.comercio_id
             JOIN mayoristas_perfil m ON m.id = p.mayorista_id
             WHERE p.uuid = ? LIMIT 1'
        );
        $stmt->execute([$uuid]);

        return $stmt->fetch() ?: null;
    }

    /** POST /pedidos/{uuid}/resena */
    public function resenar(Request $peticion, array $args): Response
    {
        $pedido = $this->pedidoPorUuid((string) $args['uuid']);

        if ($pedido === null) {
            return Response::redirect('/pedidos');
        }

        $roles = $this->roles();
        $rol = null;

        if (in_array('comercio', $roles, true) && (int) $pedido['comercio_usuario'] === $this->usuarioId()) {
            $rol = 'comercio';
        } elseif (in_array('mayorista', $roles, true) && (int) $pedido['mayorista_usuario'] === $this->usuarioId()) {
            $rol = 'mayorista';
        }

        if ($rol === null) {
            return Response::redirect('/pedidos/' . $pedido['uuid'] . '?error=' . urlencode('No puedes calificar este pedido.'));
        }

        $r = $this->resenas->crear(
            (int) $pedido['id'],
            $this->usuarioId(),
            $rol,
            (int) $peticion->input('calificacion', 0),
            $peticion->input('comentario') !== null ? (string) $peticion->input('comentario') : null,
        );

        return Response::redirect('/pedidos/' . $pedido['uuid'] . ($r['ok'] ? '?ok=1' : '?error=' . urlencode($r['mensaje'])));
    }

    /** POST /pedidos/{uuid}/mensaje */
    public function mensaje(Request $peticion, array $args): Response
    {
        $pedido = $this->pedidoPorUuid((string) $args['uuid']);

        if ($pedido === null) {
            return Response::redirect('/pedidos');
        }

        $r = $this->chat->enviar((int) $pedido['id'], $this->usuarioId(), (string) $peticion->input('cuerpo', ''));

        $aviso = $r['ok'] && ($r['alerta'] ?? false)
            ? 'Mensaje enviado. Por seguridad no compartas teléfonos ni correos dentro de la plataforma.'
            : null;

        return Response::redirect('/pedidos/' . $pedido['uuid'] . ($r['ok'] ? '?ok=1' : '?error=' . urlencode($r['mensaje'])) . ($aviso !== null ? '#chat' : ''));
    }

    /** POST /pedidos/{uuid}/reclamo */
    public function reclamar(Request $peticion, array $args): Response
    {
        $pedido = $this->pedidoPorUuid((string) $args['uuid']);

        if ($pedido === null) {
            return Response::redirect('/pedidos');
        }

        if ((int) $pedido['comercio_usuario'] !== $this->usuarioId()) {
            return Response::redirect('/pedidos/' . $pedido['uuid'] . '?error=' . urlencode('Solo el comercio puede reclamar.'));
        }

        $r = $this->reclamos->abrir(
            (int) $pedido['id'],
            $this->usuarioId(),
            (string) $peticion->input('tipo', 'calidad'),
            (string) $peticion->input('descripcion', ''),
        );

        return Response::redirect('/pedidos/' . $pedido['uuid'] . ($r['ok'] ? '?ok=1' : '?error=' . urlencode($r['mensaje'])));
    }

    /** GET /finanzas */
    public function finanzas(Request $peticion): Response
    {
        $desde = (string) $peticion->query('desde', date('Y-m-01'));
        $hasta = (string) $peticion->query('hasta', date('Y-m-d'));
        $roles = $this->roles();

        $datos = ['desde' => $desde, 'hasta' => $hasta, 'tipo' => '', 'resumen' => [], 'detalle' => []];

        if (in_array('comercio', $roles, true)) {
            $stmt = $this->pdo->prepare('SELECT id FROM comercios_perfil WHERE usuario_id = ? LIMIT 1');
            $stmt->execute([$this->usuarioId()]);
            $id = (int) $stmt->fetchColumn();

            $r = $this->finanzas->comercio($id, $desde, $hasta);
            $datos = array_merge($datos, ['tipo' => 'comercio', 'resumen' => $r['resumen'], 'detalle' => $r['por_categoria']]);
        } elseif (in_array('mayorista', $roles, true)) {
            $stmt = $this->pdo->prepare('SELECT id FROM mayoristas_perfil WHERE usuario_id = ? LIMIT 1');
            $stmt->execute([$this->usuarioId()]);
            $id = (int) $stmt->fetchColumn();

            $r = $this->finanzas->mayorista($id, $desde, $hasta);
            $datos = array_merge($datos, ['tipo' => 'mayorista', 'resumen' => $r['resumen']]);
        } elseif (in_array('repartidor', $roles, true)) {
            $stmt = $this->pdo->prepare('SELECT id FROM repartidores_perfil WHERE usuario_id = ? LIMIT 1');
            $stmt->execute([$this->usuarioId()]);
            $id = (int) $stmt->fetchColumn();

            $r = $this->finanzas->repartidor($id, $desde, $hasta);
            $datos = array_merge($datos, ['tipo' => 'repartidor', 'resumen' => $r['resumen'], 'detalle' => $r['viajes']]);
        }

        return $this->vista('finanzas/resumen', ['titulo' => 'Finanzas'] + $datos);
    }

    /** GET /finanzas/export */
    public function exportar(Request $peticion): Response
    {
        $stmt = $this->pdo->prepare('SELECT id FROM comercios_perfil WHERE usuario_id = ? LIMIT 1');
        $stmt->execute([$this->usuarioId()]);
        $comercioId = $stmt->fetchColumn();

        if ($comercioId === false) {
            return Response::json(['ok' => false, 'error' => ['code' => 'PROHIBIDO', 'message' => 'Solo comercios exportan.']], 403);
        }

        $csv = $this->finanzas->csvComercio(
            (int) $comercioId,
            (string) $peticion->query('desde', date('Y-m-01')),
            (string) $peticion->query('hasta', date('Y-m-d')),
        );

        return Response::html($csv, 200)
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="pedidos.csv"');
    }
}
