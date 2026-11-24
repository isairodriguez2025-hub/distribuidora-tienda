<?php

declare(strict_types=1);

namespace App\Modules\Carrito;

use App\Modules\Catalogo\Excepciones\EscalaNoAplicaException;
use App\Modules\Catalogo\PrecioService;

/**
 * Carrito persistente por comercio (RF-C04, RF-C07).
 *
 * El carrito no reserva stock: solo lo comprueba. La reserva ocurre al hacer
 * checkout, para no bloquear inventario de gente que nunca termina la compra.
 */
final class CarritoService
{
    public function __construct(
        private readonly CarritoRepository $repo,
        private readonly PrecioService $precios,
    ) {
    }

    /**
     * @return array{ok:bool, mensaje:string}
     */
    public function agregarItem(int $comercioId, string $productoUuid, int $cantidad): array
    {
        $producto = $this->repo->productoPorUuid($productoUuid);

        if ($producto === null) {
            return ['ok' => false, 'mensaje' => 'El producto no existe.'];
        }

        if ((int) $producto['activo'] !== 1 || (int) $producto['mayorista_activo'] !== 1) {
            return ['ok' => false, 'mensaje' => 'El producto no está disponible.'];
        }

        if ($producto['estado_kyc'] !== 'aprobado') {
            return ['ok' => false, 'mensaje' => 'El mayorista no está verificado.'];
        }

        try {
            $this->precios->validarCantidad($producto, $cantidad);
        } catch (EscalaNoAplicaException $e) {
            return ['ok' => false, 'mensaje' => $e->getMessage()];
        }

        $carrito = $this->repo->carritoDe($comercioId);
        $disponible = (int) $producto['stock_disponible'];
        $yaEnCarrito = $this->repo->cantidadDe((int) $carrito['id'], (int) $producto['id']);

        // El stock se compara contra el total que quedaría en el carrito, no
        // contra la cantidad de este clic.
        if ($yaEnCarrito + $cantidad > $disponible) {
            $mensaje = $yaEnCarrito > 0
                ? 'Ya tienes ' . $yaEnCarrito . ' en el carrito y solo hay ' . $disponible . '. Ajusta la cantidad.'
                : 'Solo hay ' . $disponible . ' disponibles.';

            return ['ok' => false, 'mensaje' => $mensaje];
        }

        $this->repo->agregar((int) $carrito['id'], (int) $producto['id'], $cantidad);

        return ['ok' => true, 'mensaje' => 'Producto agregado al carrito.'];
    }

    public function actualizar(int $comercioId, string $productoUuid, int $cantidad): void
    {
        $producto = $this->repo->productoPorUuid($productoUuid);

        if ($producto === null) {
            return;
        }

        $carrito = $this->repo->carritoDe($comercioId);
        $this->repo->actualizar((int) $carrito['id'], (int) $producto['id'], $cantidad);
    }

    public function quitar(int $comercioId, string $productoUuid): void
    {
        $producto = $this->repo->productoPorUuid($productoUuid);

        if ($producto === null) {
            return;
        }

        $carrito = $this->repo->carritoDe($comercioId);
        $this->repo->quitar((int) $carrito['id'], (int) $producto['id']);
    }

    /** Saca del carrito solo los productos que ya se convirtieron en pedido. */
    public function quitarProductos(int $comercioId, array $productoIds): void
    {
        if ($productoIds === []) {
            return;
        }

        $carrito = $this->repo->carritoDe($comercioId);
        $this->repo->quitarProductos((int) $carrito['id'], $productoIds);
    }

    public function vaciar(int $comercioId): void
    {
        $carrito = $this->repo->carritoDe($comercioId);
        $this->repo->vaciar((int) $carrito['id']);
    }

    /**
     * Recalcula el carrito completo: precios vigentes, escalas, IVA, stock y
     * mínimos por mayorista. Es la única fuente de verdad para el total.
     *
     * @return array{grupos:array<int,array<string,mixed>>, subtotal:float, iva:float,
     *               total:float, puede_checkout:bool, problemas:array<int,string>, items:int}
     */
    public function recalcular(int $comercioId): array
    {
        $carrito = $this->repo->carritoDe($comercioId);
        $items = $this->repo->items((int) $carrito['id']);

        $grupos = [];
        $problemas = [];

        foreach ($items as $item) {
            $mayoristaId = (int) $item['mayorista_id'];

            if (!isset($grupos[$mayoristaId])) {
                $grupos[$mayoristaId] = [
                    'mayorista_id'  => $mayoristaId,
                    'mayorista'     => $item['nombre_comercial'],
                    'mayorista_uuid' => $item['mayorista_uuid'],
                    'monto_minimo'  => (float) $item['monto_minimo'],
                    'items'         => [],
                    'subtotal'      => 0.0,
                    'iva'           => 0.0,
                    'total'         => 0.0,
                    'bloqueado'     => false,
                ];
            }

            $cantidad = (int) $item['cantidad'];
            $disponible = (int) $item['stock_disponible'];
            $agotado = $disponible < $cantidad;
            $inactivo = (int) $item['activo'] !== 1 || $item['deleted_at'] !== null;

            if ($agotado) {
                $problemas[] = $item['nombre'] . ': solo quedan ' . $disponible . '.';
            }

            if ($inactivo) {
                $problemas[] = $item['nombre'] . ': ya no está disponible.';
            }

            if ($agotado || $inactivo) {
                $grupos[$mayoristaId]['bloqueado'] = true;
            }

            try {
                $linea = $this->precios->calcularLinea($item, $cantidad, $this->repo->escalasDe((int) $item['producto_id']));
            } catch (EscalaNoAplicaException $e) {
                $problemas[] = $item['nombre'] . ': ' . $e->getMessage();
                $linea = [
                    'precio_unitario' => (float) $item['precio_base'],
                    'iva'             => 0.0,
                    'escala_aplicada' => null,
                    'iva_tasa'        => (float) $item['iva_tasa'],
                    'subtotal'        => 0.0,
                    'iva_total'       => 0.0,
                    'total'           => 0.0,
                ];
            }

            $grupos[$mayoristaId]['items'][] = [
                'producto_id'     => (int) $item['producto_id'],
                'producto_uuid'   => $item['uuid'],
                'nombre'          => $item['nombre'],
                'cantidad'        => $cantidad,
                'unidad_venta'    => $item['unidad_venta'],
                'contenido'       => $item['contenido'],
                'precio_unitario' => $linea['precio_unitario'],
                'iva'             => $linea['iva'],
                'iva_tasa'        => $linea['iva_tasa'],
                'escala_aplicada' => $linea['escala_aplicada'],
                'subtotal'        => $linea['subtotal'],
                'iva_total'       => $linea['iva_total'],
                'total'           => $linea['total'],
                'stock_disponible' => $disponible,
                'agotado'         => $agotado,
                'inactivo'        => $inactivo,
            ];

            $grupos[$mayoristaId]['subtotal'] += $linea['subtotal'];
            $grupos[$mayoristaId]['iva'] += $linea['iva_total'];
            $grupos[$mayoristaId]['total'] += $linea['total'];
        }

        $subtotal = 0.0;
        $iva = 0.0;
        $total = 0.0;
        $subtotalListo = 0.0;
        $ivaListo = 0.0;
        $totalListo = 0.0;
        $listos = 0;

        foreach ($grupos as $id => $grupo) {
            $grupos[$id]['subtotal'] = $this->precios->redondear($grupo['subtotal']);
            $grupos[$id]['iva'] = $this->precios->redondear($grupo['iva']);
            $grupos[$id]['total'] = $this->precios->redondear($grupo['total']);

            // RN-06: cada mayorista tiene su pedido mínimo.
            $faltante = $this->precios->redondear($grupo['monto_minimo'] - $grupo['subtotal']);
            $grupos[$id]['cumple_minimo'] = $faltante <= 0;
            $grupos[$id]['faltante'] = max(0.0, $faltante);

            if ($faltante > 0) {
                $problemas[] = $grupo['mayorista'] . ': faltan $' . number_format($faltante, 2) . ' para el pedido mínimo.';
            }

            // Un grupo se puede pedir cuando alcanza su mínimo y no tiene
            // artículos agotados o retirados.
            $grupos[$id]['listo'] = $grupos[$id]['cumple_minimo'] && empty($grupos[$id]['bloqueado']);

            if ($grupos[$id]['listo']) {
                $listos++;
                $subtotalListo += $grupo['subtotal'];
                $ivaListo += $grupo['iva'];
                $totalListo += $grupo['total'];
            }

            $subtotal += $grupo['subtotal'];
            $iva += $grupo['iva'];
            $total += $grupo['total'];
        }

        return [
            'grupos'         => array_values($grupos),
            'subtotal'       => $this->precios->redondear($subtotal),
            'iva'            => $this->precios->redondear($iva),
            'total'          => $this->precios->redondear($total),
            'puede_checkout' => $listos > 0,
            'listos'         => $listos,
            'total_grupos'   => count($grupos),
            'subtotal_listo' => $this->precios->redondear($subtotalListo),
            'iva_listo'      => $this->precios->redondear($ivaListo),
            'total_listo'    => $this->precios->redondear($totalListo),
            'problemas'      => $problemas,
            'items'          => count($items),
        ];
    }
}
