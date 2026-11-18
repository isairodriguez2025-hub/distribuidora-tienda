<?php

declare(strict_types=1);

namespace App\Modules\Programados;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use PDO;

final class ProgramadosController
{
    public function __construct(
        private readonly ProgramadoService $servicio,
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

        $mayoristas = $this->pdo->query(
            "SELECT id, nombre_comercial FROM mayoristas_perfil WHERE estado_kyc = 'aprobado' AND activo = 1 ORDER BY nombre_comercial"
        )->fetchAll() ?: [];

        $productos = $this->pdo->query(
            "SELECT p.uuid, p.nombre, m.nombre_comercial AS mayorista
             FROM productos p JOIN mayoristas_perfil m ON m.id = p.mayorista_id
             WHERE p.activo = 1 AND p.stock_disponible > 0 AND p.deleted_at IS NULL
             ORDER BY m.nombre_comercial, p.nombre LIMIT 200"
        )->fetchAll() ?: [];

        return $this->vista('programados/index', [
            'titulo'     => 'Pedidos programados',
            'plantillas' => $this->servicio->listar($comercioId),
            'mayoristas' => $mayoristas,
            'productos'  => $productos,
            'mensaje'    => $peticion->query('ok') !== null ? 'Listo.' : null,
            'error'      => $peticion->query('error'),
        ]);
    }

    public function crear(Request $peticion): Response
    {
        $comercioId = $this->comercioId();

        if ($comercioId === null) {
            return Response::redirect('/perfil');
        }

        $items = [];
        $uuids = (array) $peticion->input('producto', []);
        $cantidades = (array) $peticion->input('cantidad', []);

        foreach ($uuids as $i => $uuid) {
            if ((string) $uuid !== '') {
                $items[] = ['producto_uuid' => (string) $uuid, 'cantidad' => (int) ($cantidades[$i] ?? 1)];
            }
        }

        $r = $this->servicio->crear($comercioId, (int) $peticion->input('mayorista_id', 0), [
            'frecuencia' => (string) $peticion->input('frecuencia', 'semanal'),
            'dia_semana' => $peticion->input('dia_semana'),
            'hora'       => (string) $peticion->input('hora', '09:00'),
            'items'      => $items,
        ]);

        return Response::redirect('/programados' . ($r['ok'] ? '?ok=1' : '?error=' . urlencode($r['mensaje'])));
    }


    /** POST /agendar — lista con día y hora concretos. */
    public function crearLista(Request $peticion): Response
    {
        $comercioId = $this->comercioId();

        if ($comercioId === null) {
            return Response::redirect('/perfil');
        }

        $items = [];
        $uuids = (array) $peticion->input('producto', []);
        $cantidades = (array) $peticion->input('cantidad', []);

        foreach ($uuids as $i => $uuid) {
            if ((string) $uuid !== '') {
                $items[] = ['producto_uuid' => (string) $uuid, 'cantidad' => (int) ($cantidades[$i] ?? 1)];
            }
        }

        $r = $this->servicio->crearLista(
            $comercioId,
            (string) $peticion->input('fecha', ''),
            (string) $peticion->input('hora', '09:00'),
            $items,
        );

        return Response::redirect('/agendar' . ($r['ok'] ? '?ok=1' : '?error=' . urlencode((string) $r['mensaje'])));
    }

    /** Vista nueva de Agendar, con las listas y el diálogo de alta. */
    public function agendar(Request $peticion): Response
    {
        $comercioId = $this->comercioId();

        if ($comercioId === null) {
            return Response::redirect('/perfil');
        }

        return $this->vista('agendar/index', [
            'titulo'     => 'Agendar',
            'listas'     => $this->servicio->listar($comercioId),
            'productos'  => $this->pdo->query(
                "SELECT p.uuid, p.nombre, m.nombre_comercial AS mayorista, p.precio_base, p.cantidad_minima
                   FROM productos p JOIN mayoristas_perfil m ON m.id = p.mayorista_id
                  WHERE p.activo = 1 AND p.stock_disponible > 0 AND p.deleted_at IS NULL
                  ORDER BY m.nombre_comercial, p.nombre LIMIT 200"
            )->fetchAll() ?: [],
            'mensaje'    => $peticion->query('ok') !== null ? 'Lista agendada.' : null,
            'error'      => $peticion->query('error'),
        ]);
    }

    public function estado(Request $peticion, array $args): Response
    {
        $comercioId = $this->comercioId();

        if ($comercioId === null) {
            return Response::redirect('/perfil');
        }

        $this->servicio->cambiarEstado((int) $args['id'], $comercioId, (string) $args['estado']);

        return Response::redirect('/programados?ok=1');
    }
}
