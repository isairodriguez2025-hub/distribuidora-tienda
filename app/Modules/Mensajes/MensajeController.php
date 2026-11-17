<?php

declare(strict_types=1);

namespace App\Modules\Mensajes;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * Bandeja e hilo de mensajería. Una sola pantalla con dos paneles.
 */
final class MensajeController
{
    public function __construct(
        private readonly MensajeService $servicio,
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

    public function index(Request $peticion): Response
    {
        $usuarioId = $this->usuarioId();
        $uuid = trim((string) $peticion->query('hilo', ''));
        $error = null;
        $hilo = null;

        if ($uuid !== '') {
            $hilo = $this->servicio->abrir($usuarioId, $uuid);

            if ($hilo === null) {
                $error = 'Esa conversación no existe o no participas en ella.';
            }
        }

        return $this->vista('mensajes/index', [
            'titulo'  => 'Mensajes',
            'hilos'   => $this->servicio->bandeja($usuarioId),
            'contactos' => $this->servicio->contactos($usuarioId),
            'hilo'    => $hilo,
            'error'   => $error,
            'esAdmin' => $this->servicio->esAdmin($usuarioId),
            'yo'      => $usuarioId,
        ]);
    }

    public function enviar(Request $peticion): Response
    {
        $usuarioId = $this->usuarioId();
        $uuid = (string) $peticion->input('hilo', '');
        $cuerpo = (string) $peticion->input('cuerpo', '');

        $resultado = $this->servicio->enviar($usuarioId, $uuid, $cuerpo, $peticion->ip());

        if (!$resultado['ok']) {
            return $this->vista('mensajes/index', [
                'titulo'  => 'Mensajes',
                'hilos'   => $this->servicio->bandeja($usuarioId),
                'contactos' => $this->servicio->contactos($usuarioId),
                'hilo'    => $uuid !== '' ? $this->servicio->abrir($usuarioId, $uuid) : null,
                'error'   => $resultado['mensaje'],
                'esAdmin' => $this->servicio->esAdmin($usuarioId),
                'yo'      => $usuarioId,
            ], 422);
        }

        return Response::redirect('/mensajes?hilo=' . urlencode($uuid) . '#fin');
    }

    public function soporte(Request $peticion): Response
    {
        $resultado = $this->servicio->soporte($this->usuarioId(), $peticion->ip());

        return Response::redirect('/mensajes?hilo=' . urlencode((string) $resultado['uuid']));
    }

    /** POST /mensajes/directo — abre el hilo con otro usuario (p. ej. desde un pedido). */
    public function directo(Request $peticion): Response
    {
        $otro = (int) $peticion->input('usuario_id', 0);
        $resultado = $this->servicio->directo($this->usuarioId(), $otro, $peticion->ip());

        if (!$resultado['ok']) {
            return Response::redirect('/mensajes');
        }

        return Response::redirect('/mensajes?hilo=' . urlencode((string) $resultado['uuid']));
    }

    /** GET /mensajes/{uuid}/nuevos?desde=N — polling del hilo abierto. */
    public function nuevos(Request $peticion, array $parametros): Response
    {
        $uuid = (string) ($parametros['uuid'] ?? '');
        $desde = (int) $peticion->query('desde', 0);
        $mensajes = $this->servicio->nuevos($this->usuarioId(), $uuid, $desde);

        return Response::json(['ok' => true, 'data' => ['mensajes' => $mensajes]]);
    }
}
