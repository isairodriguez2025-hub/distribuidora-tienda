<?php

declare(strict_types=1);

namespace App\Modules\Favoritos;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use PDO;

final class FavoritosController
{
    public function __construct(
        private readonly FavoritoService $favoritos,
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

    private function comercioId(): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM comercios_perfil WHERE usuario_id = ? LIMIT 1');
        $stmt->execute([(int) Session::obtener('user_id', 0)]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function index(Request $peticion): Response
    {
        $comercioId = $this->comercioId();

        if ($comercioId === null) {
            return Response::redirect('/perfil');
        }

        return $this->vista('favoritos/index', [
            'titulo'      => 'Favoritos',
            'favoritos'   => $this->favoritos->listar($comercioId),
            'sugerencias' => $this->favoritos->sugerencias($comercioId),
            'mensaje'     => $peticion->query('ok') !== null ? 'Listo.' : null,
            'error'       => $peticion->query('error'),
        ]);
    }


    /** GET /portafolio — misma data que favoritos, vista de tarjetas. */
    public function portafolio(Request $peticion): Response
    {
        $comercioId = $this->comercioId();

        if ($comercioId === null) {
            return Response::redirect('/perfil');
        }

        return $this->vista('portafolio/index', [
            'titulo'      => 'Portafolio',
            'favoritos'   => $this->favoritos->listar($comercioId),
            'sugerencias' => $this->favoritos->sugerencias($comercioId),
            'mensaje'     => $peticion->query('ok') !== null ? 'Listo.' : null,
            'error'       => $peticion->query('error'),
        ]);
    }

    public function agregar(Request $peticion): Response
    {
        $comercioId = $this->comercioId();

        if ($comercioId === null) {
            return Response::redirect('/perfil');
        }

        $r = $this->favoritos->agregar($comercioId, (string) $peticion->input('producto', ''), (string) $peticion->input('carpeta', 'General'));
        $volver = (string) $peticion->input('volver', '/favoritos');

        return Response::redirect($volver . ($r['ok'] ? '?ok=1' : '?error=' . urlencode($r['mensaje'])));
    }

    public function quitar(Request $peticion): Response
    {
        $comercioId = $this->comercioId();

        if ($comercioId !== null) {
            $this->favoritos->quitar($comercioId, (string) $peticion->input('producto', ''));
        }

        return Response::redirect('/favoritos?ok=1');
    }

    /** POST /favoritos/recompra/{uuid} */
    public function recompra(Request $peticion, array $args): Response
    {
        $comercioId = $this->comercioId();

        if ($comercioId === null) {
            return Response::redirect('/perfil');
        }

        $stmt = $this->pdo->prepare('SELECT id FROM pedidos WHERE uuid = ? AND comercio_id = ? LIMIT 1');
        $stmt->execute([(string) $args['uuid'], $comercioId]);
        $pedidoId = $stmt->fetchColumn();

        if ($pedidoId === false) {
            return Response::redirect('/pedidos?error=' . urlencode('Ese pedido no es tuyo.'));
        }

        $r = $this->favoritos->recomprar($comercioId, (int) $pedidoId);

        if ($r['omitidos'] !== []) {
            $texto = implode(' · ', array_map(
                static fn (array $o): string => $o['nombre'] . ' (' . $o['motivo'] . ')'
                    . ($o['alternativa'] ? ' → alternativa: ' . $o['alternativa'] : ''),
                $r['omitidos'],
            ));

            return Response::redirect('/carrito?error=' . urlencode('No se agregaron: ' . $texto));
        }

        return Response::redirect('/carrito?ok=1');
    }
}
