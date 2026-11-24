<?php

declare(strict_types=1);

namespace App\Modules\Catalogo;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

final class CatalogoController
{
    public function __construct(
        private readonly ProductoService $servicio,
        private readonly ProductoRepository $repo,
        private readonly string $directorioVistas,
        private readonly PrecioService $precios = new PrecioService(),
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
        $mayoristaId = $this->servicio->mayoristaId($this->usuarioId());

        return $this->vista('catalogo/lista', [
            'titulo'     => 'Mis productos',
            'productos'  => $mayoristaId === null ? [] : $this->repo->listarPorMayorista($mayoristaId, (string) $peticion->query('q', '')),
            'busqueda'   => (string) $peticion->query('q', ''),
            'puede'      => $this->servicio->puedePublicar($this->usuarioId()),
            'mensaje'    => $peticion->query('ok') !== null ? 'Cambios guardados.' : null,
        ]);
    }

    public function nuevoForm(Request $peticion): Response
    {
        return $this->vista('catalogo/form', [
            'titulo'     => 'Nuevo producto',
            'categorias' => $this->repo->categorias(),
            'producto'   => null,
            'errores'    => [],
            'valores'    => [],
            'escalas'    => [],
            'puede'      => $this->servicio->puedePublicar($this->usuarioId()),
        ]);
    }

    public function crear(Request $peticion): Response
    {
        $datos = $this->datosDelFormulario($peticion);
        $resultado = $this->servicio->crear($this->usuarioId(), $datos, $peticion->ip());

        if (!$resultado['ok']) {
            return $this->vista('catalogo/form', [
                'titulo'     => 'Nuevo producto',
                'categorias' => $this->repo->categorias(),
                'producto'   => null,
                'errores'    => $resultado['errores'],
                'valores'    => $datos,
                'puede'      => $this->servicio->puedePublicar($this->usuarioId()),
            ], 422);
        }

        return Response::redirect('/mayorista/productos?ok=1');
    }

    public function editarForm(Request $peticion, array $args): Response
    {
        $producto = $this->repo->buscarPorUuid((string) $args['uuid']);

        if ($producto === null) {
            return Response::json(['ok' => false, 'error' => ['code' => 'NO_ENCONTRADO', 'message' => 'Producto no encontrado.']], 404);
        }

        return $this->vista('catalogo/form', [
            'titulo'     => 'Editar producto',
            'categorias' => $this->repo->categorias(),
            'producto'   => $producto,
            'errores'    => [],
            'valores'    => $producto,
            // El repositorio las da de mayor a menor (para calcular precios);
            // en el formulario se capturan de menor a mayor.
            'escalas'    => array_reverse($this->repo->escalas((int) $producto['id'])),
            'puede'      => true,
        ]);
    }

    public function actualizar(Request $peticion, array $args): Response
    {
        $producto = $this->repo->buscarPorUuid((string) $args['uuid']);

        if ($producto === null) {
            return Response::json(['ok' => false, 'error' => ['code' => 'NO_ENCONTRADO', 'message' => 'Producto no encontrado.']], 404);
        }

        $datos = $this->datosDelFormulario($peticion);
        $resultado = $this->servicio->actualizar($this->usuarioId(), (int) $producto['id'], $datos, $peticion->ip());

        if (!$resultado['ok']) {
            return $this->vista('catalogo/form', [
                'titulo'     => 'Editar producto',
                'categorias' => $this->repo->categorias(),
                'producto'   => $producto,
                'errores'    => $resultado['errores'],
                'valores'    => $datos,
                'puede'      => true,
            ], 422);
        }

        return Response::redirect('/mayorista/productos?ok=1');
    }

    public function ajustarStock(Request $peticion, array $args): Response
    {
        $producto = $this->repo->buscarPorUuid((string) $args['uuid']);

        if ($producto === null) {
            return Response::json(['ok' => false, 'error' => ['code' => 'NO_ENCONTRADO', 'message' => 'Producto no encontrado.']], 404);
        }

        $delta = (int) $peticion->input('cantidad', 0);
        $motivo = (string) $peticion->input('motivo', 'Ajuste manual');

        if ($delta === 0) {
            return Response::redirect('/mayorista/productos');
        }

        $ok = $this->repo->ajustarStock((int) $producto['id'], $delta, 'ajuste', $this->usuarioId(), $motivo);

        return Response::redirect('/mayorista/productos' . ($ok ? '?ok=1' : '?error=stock'));
    }

    /**
     * GET /mayorista/productos/{uuid}/escalas-preview?cantidad=N
     * Devuelve lo que pagaría un comercio por esa cantidad.
     */
    public function escalasPreview(Request $peticion, array $args): Response
    {
        $producto = $this->repo->buscarPorUuid((string) $args['uuid']);

        if ($producto === null) {
            return Response::json(['ok' => false, 'error' => ['code' => 'NO_ENCONTRADO', 'message' => 'Producto no encontrado.']], 404);
        }

        $cantidad = (int) $peticion->query('cantidad', 0);

        try {
            $linea = $this->precios->calcularLinea($producto, $cantidad, $this->repo->escalas((int) $producto['id']));
        } catch (\App\Modules\Catalogo\Excepciones\EscalaNoAplicaException $e) {
            return Response::json([
                'ok'    => false,
                'error' => ['code' => 'CANTIDAD_INVALIDA', 'message' => $e->getMessage()],
            ], 422);
        }

        return Response::json(['ok' => true, 'data' => $linea]);
    }

    /** GET /mayorista/productos/plantilla — CSV con los encabezados exactos. */
    public function plantillaCsv(Request $peticion): Response
    {
        $filas = [
            ['sku', 'nombre', 'precio_base', 'stock_disponible', 'peso_kg', 'volumen_m3', 'unidad_venta', 'contenido', 'cantidad_minima', 'multiplo_venta'],
            ['AZU-50', 'Azúcar estándar 50 kg', '1250.00', '20', '50', '0.05', 'Caja', '12 pzas', '1', '1'],
            ['CAF-1KG', 'Café molido 1 kg', '185.50', '40', '1', '0.004', 'Pieza', '1 pza', '1', '1'],
        ];

        $csv = '';
        foreach ($filas as $fila) {
            $csv .= implode(',', array_map(
                static fn ($v): string => str_contains((string) $v, ',') ? '"' . $v . '"' : (string) $v,
                $fila
            )) . "\r\n";
        }

        // El BOM hace que Excel respete los acentos al abrirlo.
        return Response::descarga('plantilla-inventario.csv', "\xEF\xBB\xBF" . $csv);
    }

    public function importarCsv(Request $peticion): Response
    {
        $archivo = $peticion->file('csv') ?? [];

        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return Response::redirect('/mayorista/productos?error=csv');
        }

        $resultado = $this->servicio->importarCsv($this->usuarioId(), (string) $archivo['tmp_name'], $peticion->ip());

        return $this->vista('catalogo/importacion', [
            'titulo'  => 'Importación de catálogo',
            'ok'      => $resultado['ok'],
            'errores' => $resultado['errores'],
        ]);
    }

    /** Filas de escala capturadas; las vacías se descartan. */
    private function escalasDelFormulario(Request $peticion): array
    {
        $minimos = (array) $peticion->input('escala_min', []);
        $tipos = (array) $peticion->input('escala_tipo', []);
        $valores = (array) $peticion->input('escala_valor', []);

        $escalas = [];

        foreach ($minimos as $i => $minimo) {
            $minimo = (int) $minimo;
            $valor = (float) ($valores[$i] ?? 0);

            if ($minimo <= 0 || $valor <= 0) {
                continue;
            }

            $escalas[] = [
                'cantidad_minima' => $minimo,
                'tipo'            => (string) ($tipos[$i] ?? 'porcentaje'),
                'valor'           => $valor,
            ];
        }

        return $escalas;
    }

    private function datosDelFormulario(Request $peticion): array
    {
        return [
            'nombre'           => (string) $peticion->input('nombre', ''),
            'categoria_id'     => (int) $peticion->input('categoria_id', 0),
            'descripcion'      => (string) $peticion->input('descripcion', ''),
            'precio_base'      => (float) $peticion->input('precio_base', 0),
            'iva_tasa'         => (float) $peticion->input('iva_tasa', 16),
            'unidad_venta'     => (string) $peticion->input('unidad_venta', ''),
            'contenido'        => (string) $peticion->input('contenido', ''),
            'peso_kg'          => (float) $peticion->input('peso_kg', 0),
            'volumen_m3'       => (float) $peticion->input('volumen_m3', 0),
            'cantidad_minima'  => (int) $peticion->input('cantidad_minima', 1),
            'multiplo_venta'   => (int) $peticion->input('multiplo_venta', 1),
            'perecedero'       => (bool) $peticion->input('perecedero', false),
            'requiere_frio'    => (bool) $peticion->input('requiere_frio', false),
            'inflamable'       => (bool) $peticion->input('inflamable', false),
            'restringido'      => (bool) $peticion->input('restringido', false),
            'activo'           => (bool) $peticion->input('activo', false),
            'stock_inicial'    => (int) $peticion->input('stock_inicial', 0),
            'escalas'          => $this->escalasDelFormulario($peticion),
        ];
    }
}
