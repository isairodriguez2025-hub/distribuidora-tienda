<?php
/**
 * Inventario del POS: alta/edición, ajustes y reposición sugerida.
 *
 * @var callable $e
 * @var string   $csrf
 * @var array    $productos
 * @var array    $ajustes
 * @var array    $sugerencias
 * @var string|null $mensaje
 * @var string|null $error
 * @var array|null  $editar
 */
$filas = '';

foreach ($productos as $p) {
    $bajo = (float) $p['stock'] <= (float) $p['stock_minimo'];
    $filas .= '<tr>'
        . '<td data-label="Producto"><strong>' . $e((string) $p['nombre']) . '</strong>'
        . ($p['sku'] !== '' ? '<div class="pista">' . $e((string) $p['sku']) . '</div>' : '') . '</td>'
        . '<td data-label="Compra">$' . number_format((float) $p['precio_compra'], 2) . '</td>'
        . '<td data-label="Venta">$' . number_format((float) $p['precio_venta'], 2) . '</td>'
        . '<td data-label="Stock"><span class="' . ($bajo ? 'chip chip--alerta' : 'chip chip--ok') . '">'
        . number_format((float) $p['stock'], 2) . ' ' . $e((string) $p['unidad']) . '</span></td>'
        . '<td data-label="Mínimo">' . number_format((float) $p['stock_minimo'], 2) . '</td>'
        . '<td data-label="Acciones" style="white-space:nowrap">'
        . '<a class="pista" href="/pos/productos?editar=' . (int) $p['id'] . '">Editar</a><span class="sep"> · </span>'
        . '<form method="post" action="/pos/productos/' . (int) $p['id'] . '/eliminar" style="display:inline" '
        . 'onsubmit="return confirm(\'¿Desactivar este producto?\')">'
        . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
        . '<button type="submit" class="enlace-peligro">Quitar</button></form>'
        . '</td></tr>';
}

if ($filas === '') {
    $filas = '<tr><td colspan="6" class="silencio" style="text-align:center;padding:24px">Sin productos. '
        . 'Se importan automáticamente de tus pedidos entregados o puedes capturarlos aquí.</td></tr>';
}

$sug = '';

foreach ($sugerencias as $s) {
    $sug .= '<label class="sugerencia"><input type="checkbox" name="productos[]" value="' . (int) $s['id'] . '" checked>'
        . '<span><strong>' . $e((string) $s['nombre']) . '</strong>'
        . '<div class="pista">Quedan ' . number_format((float) $s['stock'], 2) . ' · mínimo ' . number_format((float) $s['minimo'], 2) . '</div></span>'
        . '<span class="chip chip--alerta">pedir ' . (int) $s['sugerido'] . '</span></label>';
}

$bloqueSug = $sug === '' ? '' : '<div class="tarjeta" style="padding:18px;margin-bottom:18px">'
    . '<h2 style="margin:0 0 4px">Reposición sugerida</h2>'
    . '<p class="silencio" style="margin:0 0 12px">Estos productos están en el mínimo. Podemos armar el pedido al mayorista con un clic.</p>'
    . '<form method="post" action="/pos/reponer"><input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . $sug . '<button type="submit" style="width:100%;margin-top:10px">Crear pedido de reposición</button></form></div>';

$v = $editar ?? [];
$esEdicion = $editar !== null;

$contenido = '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px">'
    . '<div><h1 style="margin:0">Inventario</h1><p class="silencio" style="margin:2px 0 0">Precios, existencias y reposición.</p></div>'
    . '<a href="/pos" class="boton boton--claro">Ir a caja</a></div>'
    . ($mensaje !== null ? '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>' : '')
    . ($error !== null ? '<div class="aviso aviso--error">' . $e($error) . '</div>' : '')
    . $bloqueSug
    . '<div class="pos-inv">'
    . '<div class="tarjeta" style="padding:0;overflow:hidden">'
    . '<table class="tabla-pos"><thead><tr><th>Producto</th><th>Compra</th><th>Venta</th><th>Stock</th><th>Mínimo</th><th></th></tr></thead>'
    . '<tbody>' . $filas . '</tbody></table></div>'

    . '<div style="display:grid;gap:16px;align-content:start">'
    . '<div class="tarjeta" style="padding:18px">'
    . '<h2 style="margin:0 0 12px">' . ($esEdicion ? 'Editar producto' : 'Nuevo producto') . '</h2>'
    . '<form method="post" action="/pos/productos">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . ($esEdicion ? '<input type="hidden" name="id" value="' . (int) $v['id'] . '">' : '')
    . '<label class="campo"><span>Nombre</span><input name="nombre" required maxlength="180" value="' . $e((string) ($v['nombre'] ?? '')) . '"></label>'
    . '<div class="fila"><label class="campo"><span>SKU</span><input name="sku" maxlength="60" value="' . $e((string) ($v['sku'] ?? '')) . '"></label>'
    . '<label class="campo"><span>Código de barras</span><input name="codigo_barras" maxlength="60" value="' . $e((string) ($v['codigo_barras'] ?? '')) . '"></label></div>'
    . '<div class="fila"><label class="campo"><span>Costo</span><input type="number" step="0.01" min="0" name="precio_compra" value="' . number_format((float) ($v['precio_compra'] ?? 0), 2, '.', '') . '"></label>'
    . '<label class="campo"><span>Precio venta</span><input type="number" step="0.01" min="0" name="precio_venta" value="' . number_format((float) ($v['precio_venta'] ?? 0), 2, '.', '') . '"></label></div>'
    . '<div class="fila"><label class="campo"><span>Stock</span><input type="number" step="0.001" name="stock" value="' . number_format((float) ($v['stock'] ?? 0), 3, '.', '') . '"></label>'
    . '<label class="campo"><span>Mínimo</span><input type="number" step="0.001" min="0" name="stock_minimo" value="' . number_format((float) ($v['stock_minimo'] ?? 1), 3, '.', '') . '"></label></div>'
    . '<div class="fila"><label class="campo"><span>IVA</span><select name="iva_tasa">'
    . '<option value="0.16"' . ((float) ($v['iva_tasa'] ?? 0.16) === 0.16 ? ' selected' : '') . '>16%</option>'
    . '<option value="0.08"' . ((float) ($v['iva_tasa'] ?? 0) === 0.08 ? ' selected' : '') . '>8%</option>'
    . '<option value="0"' . ((float) ($v['iva_tasa'] ?? 0) === 0.0 ? ' selected' : '') . '>Exento</option></select></label>'
    . '<label class="campo"><span>Unidad</span><input name="unidad" maxlength="30" value="' . $e((string) ($v['unidad'] ?? 'pieza')) . '"></label></div>'
    . '<button type="submit" style="width:100%;margin-top:8px">' . ($esEdicion ? 'Guardar cambios' : 'Agregar producto') . '</button>'
    . ($esEdicion ? '<a href="/pos/productos" class="pista" style="display:block;text-align:center;margin-top:10px">Cancelar edición</a>' : '')
    . '</form></div>'

    . '<div class="tarjeta" style="padding:18px"><h2 style="margin:0 0 12px">Ajustes</h2>'
    . '<form method="post" action="/pos/ajustes"><input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label class="campo"><span>Margen al importar pedidos (%)</span>'
    . '<input type="number" name="margen_venta" min="0" max="300" step="1" value="' . (int) $ajustes['margen_venta'] . '"></label>'
    . '<label class="campo" style="flex-direction:row;align-items:center;gap:8px;margin-top:10px">'
    . '<input type="checkbox" name="iva_incluido" value="1"' . ((int) $ajustes['iva_incluido'] === 1 ? ' checked' : '') . '>'
    . '<span>Los precios ya incluyen IVA</span></label>'
    . '<label class="campo" style="flex-direction:row;align-items:center;gap:8px;margin-top:6px">'
    . '<input type="checkbox" name="reorden_auto" value="1"' . ((int) $ajustes['reorden_auto'] === 1 ? ' checked' : '') . '>'
    . '<span>Avisarme cuando un producto llegue al mínimo</span></label>'
    . '<button type="submit" class="boton boton--claro" style="width:100%;margin-top:12px">Guardar ajustes</button></form></div>'
    . '</div></div>';

require __DIR__ . '/../layout.php';
