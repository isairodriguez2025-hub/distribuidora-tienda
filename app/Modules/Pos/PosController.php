<?php

declare(strict_types=1);

namespace App\Modules\Pos;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * Punto de venta del comercio: caja, ventas, inventario y corte.
 */
final class PosController
{
    public function __construct(
        private readonly PosService $servicio,
        private readonly PosRepository $repo,
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

    /** Pantalla de caja: catálogo a la izquierda, ticket a la derecha. */
    public function caja(Request $peticion): Response
    {
        $usuarioId = $this->usuarioId();
        $this->servicio->sincronizarInventario($usuarioId);

        $turno = $this->repo->turnoAbierto($usuarioId);

        if ($turno === null) {
            return $this->vista('pos/apertura', [
                'titulo'     => 'Abrir caja',
                'ajustes'    => $this->servicio->ajustes($usuarioId),
                'productos'  => count($this->repo->productos($usuarioId)),
                'mensaje'    => $peticion->query('mensaje'),
            ]);
        }

        return $this->vista('pos/caja', [
            'titulo'    => 'Caja',
            'turno'     => $turno,
            'productos' => $this->repo->productos($usuarioId),
            'clientes'  => $this->repo->clientes($usuarioId),
            'ajustes'   => $this->servicio->ajustes($usuarioId),
            'vendido'   => $this->repo->totalVendido((int) $turno['id']),
        ]);
    }

    public function abrirTurno(Request $peticion): Response
    {
        $fondo = max(0.0, (float) $peticion->input('fondo_inicial', 0));

        if ($this->repo->turnoAbierto($this->usuarioId()) !== null) {
            return Response::redirect('/pos');
        }

        $this->repo->abrirTurno($this->usuarioId(), $fondo);

        return Response::redirect('/pos?mensaje=' . urlencode('Caja abierta.'));
    }

    public function cerrarTurno(Request $peticion): Response
    {
        $turno = $this->repo->turnoAbierto($this->usuarioId());

        if ($turno === null) {
            return Response::redirect('/pos');
        }

        $efectivo = 0.0;

        foreach ($this->repo->resumenTurno((int) $turno['id']) as $r) {
            if ($r['metodo'] === 'efectivo') {
                $efectivo = (float) $r['total'];
            }
        }

        $esperado = round((float) $turno['fondo_inicial'] + $efectivo, 2);
        $contado = (float) $peticion->input('efectivo_contado', 0);

        $this->repo->cerrarTurno((int) $turno['id'], $esperado, $contado, (string) $peticion->input('notas', ''));

        return Response::redirect('/pos/corte?turno=' . (int) $turno['id']);
    }

    public function corte(Request $peticion): Response
    {
        $usuarioId = $this->usuarioId();
        $id = (int) $peticion->query('turno', 0);

        if ($id === 0) {
            $actual = $this->repo->turnoAbierto($usuarioId);
            $id = $actual !== null ? (int) $actual['id'] : (int) ($this->repo->ultimoTurno($usuarioId)['id'] ?? 0);
        }

        $turno = $this->repo->turno($id);

        if ($turno === null || (int) $turno['usuario_id'] !== $usuarioId) {
            return Response::redirect('/pos');
        }

        return $this->vista('pos/corte', [
            'titulo'      => 'Corte de caja',
            'turno'       => $turno,
            'resumen'     => $this->repo->resumenTurno($id),
            'movimientos' => $this->repo->movimientos($id),
            'ventas'      => $this->repo->ventas($usuarioId, 200),
            'total'       => $this->repo->totalVendido($id),
        ]);
    }

    /** POST /pos/vender (JSON desde el navegador). */
    public function vender(Request $peticion): Response
    {
        $datos = $peticion->json();

        $items = is_array($datos['items'] ?? null) ? $datos['items'] : [];
        $metodo = (string) ($datos['metodo'] ?? 'efectivo');
        $recibido = (float) ($datos['recibido'] ?? 0);
        $clienteId = isset($datos['cliente_id']) && (int) $datos['cliente_id'] > 0 ? (int) $datos['cliente_id'] : null;

        $resultado = $this->servicio->vender(
            $this->usuarioId(),
            $items,
            $metodo,
            $recibido,
            $clienteId,
            (string) ($datos['notas'] ?? ''),
        );

        return Response::json($resultado, $resultado['ok'] ? 200 : 422);
    }

    public function productos(Request $peticion): Response
    {
        $usuarioId = $this->usuarioId();
        $this->servicio->sincronizarInventario($usuarioId);

        return $this->vista('pos/productos', [
            'titulo'      => 'Inventario',
            'productos'   => $this->repo->productos($usuarioId),
            'ajustes'     => $this->servicio->ajustes($usuarioId),
            'sugerencias' => $this->servicio->sugerenciasReposicion($usuarioId),
            'mensaje'     => $peticion->query('mensaje'),
            'error'       => $peticion->query('error'),
            'editar'      => $peticion->query('editar') !== null
                ? $this->repo->producto($usuarioId, (int) $peticion->query('editar'))
                : null,
        ]);
    }

    public function guardarProducto(Request $peticion): Response
    {
        $usuarioId = $this->usuarioId();
        $id = (int) $peticion->input('id', 0);
        $nombre = trim((string) $peticion->input('nombre', ''));

        if ($nombre === '') {
            return Response::redirect('/pos/productos?error=' . urlencode('El nombre es obligatorio.'));
        }

        $datos = [
            'nombre'        => mb_substr($nombre, 0, 180),
            'sku'           => mb_substr(trim((string) $peticion->input('sku', '')), 0, 60),
            'codigo_barras' => mb_substr(trim((string) $peticion->input('codigo_barras', '')), 0, 60),
            'precio_compra' => max(0, (float) $peticion->input('precio_compra', 0)),
            'precio_venta'  => max(0, (float) $peticion->input('precio_venta', 0)),
            'iva_tasa'      => max(0, (float) $peticion->input('iva_tasa', 0.16)),
            'stock'         => (float) $peticion->input('stock', 0),
            'stock_minimo'  => max(0, (float) $peticion->input('stock_minimo', 1)),
            'unidad'        => mb_substr(trim((string) $peticion->input('unidad', 'pieza')), 0, 30),
            'activo'        => (int) $peticion->input('activo', 1) === 1,
        ];

        $id = $this->repo->guardarProducto($usuarioId, $datos, $id > 0 ? $id : null);

        return Response::redirect('/pos/productos?mensaje=' . urlencode('Producto guardado (# ' . $id . ').'));
    }

    public function eliminarProducto(Request $peticion, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $producto = $this->repo->producto($this->usuarioId(), $id);

        if ($producto !== null) {
            $this->repo->guardarProducto($this->usuarioId(), array_merge($producto, ['activo' => false]), $id);
        }

        return Response::redirect('/pos/productos?mensaje=' . urlencode('Producto desactivado.'));
    }

    public function guardarAjustes(Request $peticion): Response
    {
        $this->repo->guardarAjustes(
            $this->usuarioId(),
            max(0, (float) $peticion->input('margen_venta', 30)),
            (int) $peticion->input('reorden_auto', 1) === 1,
            (int) $peticion->input('iva_incluido', 1) === 1,
        );

        return Response::redirect('/pos/productos?mensaje=' . urlencode('Ajustes guardados.'));
    }

    public function reponer(Request $peticion): Response
    {
        $ids = array_map('intval', (array) ($peticion->input('productos', []) ?: []));
        $resultado = $this->servicio->pedidoReposicion($this->usuarioId(), $ids);

        if (!$resultado['ok']) {
            return Response::redirect('/pos/productos?error=' . urlencode((string) $resultado['mensaje']));
        }

        return Response::redirect('/pedidos/' . urlencode((string) $resultado['uuid']));
    }

    public function ventas(Request $peticion): Response
    {
        return $this->vista('pos/ventas', [
            'titulo'  => 'Ventas',
            'ventas'  => $this->repo->ventas($this->usuarioId(), 100),
            'mensaje' => $peticion->query('mensaje'),
        ]);
    }

    public function ticket(Request $peticion, array $params): Response
    {
        $venta = $this->repo->ventaPorUuid($this->usuarioId(), (string) ($params['uuid'] ?? ''));

        if ($venta === null) {
            return Response::redirect('/pos/ventas');
        }

        return $this->vista('pos/ticket', [
            'titulo' => 'Ticket ' . $venta['folio'],
            'venta'  => $venta,
            'lineas' => $this->repo->lineas((int) $venta['id']),
            'pagos'  => $this->repo->pagos((int) $venta['id']),
        ]);
    }

    public function cancelar(Request $peticion, array $params): Response
    {
        $resultado = $this->servicio->cancelarVenta($this->usuarioId(), (string) ($params['uuid'] ?? ''));
        $clave = $resultado['ok'] ? 'mensaje' : 'mensaje';
        $valor = $resultado['ok'] ? 'Venta cancelada.' : (string) $resultado['mensaje'];

        return Response::redirect('/pos/ventas?' . $clave . '=' . urlencode($valor));
    }

    public function clientes(Request $peticion): Response
    {
        if ($peticion->method() === 'POST') {
            $nombre = trim((string) $peticion->input('nombre', ''));

            if ($nombre !== '') {
                $this->repo->crearCliente(
                    $this->usuarioId(),
                    mb_substr($nombre, 0, 120),
                    mb_substr((string) $peticion->input('telefono', ''), 0, 30),
                    mb_substr((string) $peticion->input('email', ''), 0, 120),
                    mb_substr((string) $peticion->input('rfc', ''), 0, 15),
                );
            }

            return Response::redirect('/pos/clientes?mensaje=' . urlencode('Cliente guardado.'));
        }

        return $this->vista('pos/clientes', [
            'titulo'    => 'Clientes',
            'clientes'  => $this->repo->clientes($this->usuarioId()),
            'mensaje'   => $peticion->query('mensaje'),
        ]);
    }
}
