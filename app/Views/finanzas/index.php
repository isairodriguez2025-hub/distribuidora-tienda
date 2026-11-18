<?php
/**
 * Finanzas del comercio: estado de gastos y facturación.
 *
 * @var callable $e
 * @var string   $tab
 * @var string   $periodo
 * @var array    $gastos
 * @var array    $facturas
 */

$dinero = static fn (float $v): string => '$' . number_format($v, 2);

$estados = $gastos['estados'];
$esMes = (bool) $gastos['esMes'];

// ---------------------------------------------------------------- pestañas
$tabGastos = '<a class="fin-tab' . ($tab === 'gastos' ? ' activo' : '') . '" href="/finanzas?tab=gastos&periodo=' . $e($periodo) . '">Estado de gastos</a>';
$tabFacturas = '<a class="fin-tab' . ($tab === 'facturacion' ? ' activo' : '') . '" href="/finanzas?tab=facturacion">Facturación</a>';

// --------------------------------------------------------- selector de periodo
$anioActual = (int) date('Y');
$mesActual = (int) date('n');
$nombreMes = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$opciones = '';

for ($a = $anioActual; $a >= $anioActual - 2; $a--) {
    $opciones .= '<option value="' . $a . '"' . ($periodo === (string) $a ? ' selected' : '') . '>Año ' . $a . '</option>';

    $hasta = $a === $anioActual ? $mesActual : 12;
    for ($m = $hasta; $m >= 1; $m--) {
        $valor = sprintf('%d-%02d', $a, $m);
        $opciones .= '<option value="' . $valor . '"' . ($periodo === $valor ? ' selected' : '') . '>'
            . $nombreMes[$m] . ' de ' . $a . '</option>';
    }
}

$selector = '<form method="get" action="/finanzas" class="fin-filtro">'
    . '<input type="hidden" name="tab" value="gastos">'
    . '<label><span>Periodo</span><select name="periodo" onchange="this.form.submit()">' . $opciones . '</select></label>'
    . '<noscript><button type="submit" class="boton boton--claro">Aplicar</button></noscript>'
    . '</form>';

// ------------------------------------------------------------------- gráfica
$barras = '';
$maximo = 0.0;
foreach ($gastos['serie'] as $punto) {
    $maximo = max($maximo, (float) $punto['valor']);
}

// Escala redonda para el eje de las cantidades: se elige el salto más pequeño
// de la lista que deje la barra más alta por debajo del techo.
$candidatos = [50, 100, 200, 250, 500, 1000, 1500, 2000, 2500, 3000, 4000, 5000, 7500, 10000, 15000, 20000, 25000, 50000, 100000];
$paso = (float) end($candidatos);
foreach ($candidatos as $c) {
    if ($c >= $maximo / 4) {
        $paso = (float) $c;
        break;
    }
}
$tope = $paso * 4;

$corto = static function (float $v): string {
    if ($v >= 1000) {
        return '$' . rtrim(rtrim(number_format($v / 1000, 1, '.', ''), '0'), '.') . 'k';
    }

    return '$' . number_format($v, 0);
};

$ejeY = '';
for ($i = 4; $i >= 0; $i--) {
    $valorEje = $paso * $i;
    $ejeY .= '<span style="bottom:' . ($i * 25) . '%">' . $corto($valorEje) . '</span>';
}

$lineas = '';
for ($i = 0; $i <= 4; $i++) {
    $lineas .= '<i class="gasto-linea" style="bottom:' . ($i * 25) . '%"></i>';
}

$cuenta = count($gastos['serie']);
foreach ($gastos['serie'] as $i => $punto) {
    $valor = (float) $punto['valor'];
    $alto = $valor > 0 ? max(3, (int) round($valor / max($tope, 0.01) * 100)) : 0;
    $clase = $valor > 0 ? 'gasto-barra' : 'gasto-barra gasto-barra--vacia';

    // En la vista mensual solo se rotulan algunos días para que no se encimen.
    $mostrarEtiqueta = !$esMes || $cuenta <= 16 || ((int) $punto['etiqueta']) % 5 === 0 || $i === 0;

    $barras .= '<div class="' . $clase . '" title="' . $e($punto['etiqueta']) . ' · ' . $dinero($valor) . '">'
        . '<span class="gasto-barra__pista">' . ($valor > 0 ? $dinero($valor) : '') . '</span>'
        . '<i style="height:' . max(0.6, $alto) . '%"></i>'
        . '<b' . ($mostrarEtiqueta ? '' : ' class="oculta"') . '>' . $e($punto['etiqueta']) . '</b>'
        . '</div>';
}

$grafica = '<div class="gasto-panel">'
    . '<div class="gasto-panel__cabeza">'
    . '<div><div class="gasto-panel__etiqueta">Gastado en ' . $e(strtolower($gastos['etiqueta'])) . '</div>'
    . '<div class="gasto-panel__total">' . $dinero((float) $gastos['total']) . '</div></div>'
    . '<div class="gasto-panel__cuenta">' . count($gastos['pedidos']) . ' pedido' . (count($gastos['pedidos']) === 1 ? '' : 's') . '</div>'
    . '</div>'
    . '<div class="gasto-grafica' . ($esMes ? ' gasto-grafica--mes' : '') . '">'
    . '<div class="gasto-eje-y">' . $ejeY . '</div>'
    . '<div class="gasto-plot">' . $lineas . '<div class="gasto-barras">' . $barras . '</div></div>'
    . '</div>'
    . '</div>';

// -------------------------------------------------------------- fichas de gasto
$fichas = '';
$mesesCortos = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

foreach ($gastos['pedidos'] as $p) {
    $fecha = strtotime((string) $p['created_at']);
    $estado = $estados[(string) $p['estado']] ?? (string) $p['estado'];

    $fichas .= '<a class="gasto-ficha" href="/pedidos/' . $e((string) $p['uuid']) . '">'
        . '<div class="gasto-ficha__fecha"><b>' . date('d', $fecha) . '</b><span>' . $mesesCortos[(int) date('n', $fecha)] . '</span></div>'
        . '<div class="gasto-ficha__datos">'
        . '<strong>' . $e((string) ($p['mayorista'] ?? 'Mayorista')) . '</strong>'
        . '<span>Pedido del ' . date('d/m/Y', $fecha) . ' · ' . $e($estado) . '</span>'
        . '</div>'
        . '<div class="gasto-ficha__monto">' . $dinero((float) $p['total']) . '</div>'
        . '</a>';
}

if ($fichas === '') {
    $fichas = '<div class="vacio">No hay gastos en este periodo.</div>';
}

// ---------------------------------------------------------------- facturación
$listaFacturas = '';
foreach ($facturas as $f) {
    $fecha = strtotime((string) ($f['fecha_timbrado'] ?? $f['created_at']));
    $folio = $f['serie'] . '-' . str_pad((string) $f['folio'], 6, '0', STR_PAD_LEFT);

    $listaFacturas .= '<a class="factura-ficha" href="/finanzas/factura/' . $e((string) $f['uuid']) . '">'
        . '<div class="factura-ficha__icono" aria-hidden="true">'
        . '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'
        . '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2Z"></path><path d="M9 8h6M9 12h6"></path></svg></div>'
        . '<div class="factura-ficha__datos"><strong>' . $e($folio) . '</strong>'
        . '<span>' . $e((string) $f['razon_social_emisor']) . ' · ' . date('d/m/Y', $fecha) . '</span></div>'
        . '<div class="factura-ficha__monto">' . $dinero((float) $f['total']) . '</div>'
        . '</a>';
}

if ($listaFacturas === '') {
    $listaFacturas = '<div class="vacio">Todavía no tienes facturas. El mayorista las emite al cerrar cada pedido.</div>';
}

$panelGastos = '<div class="fin-panel">' . $selector . $grafica
    . '<h2 class="fin-subtitulo">Cada gasto</h2><div class="gasto-lista">' . $fichas . '</div></div>';

$panelFacturas = '<div class="fin-panel">'
    . '<p class="fin-nota">Las emite el mayorista. Aquí ves el documento tal como quedó registrado.</p>'
    . '<div class="factura-lista">' . $listaFacturas . '</div></div>';

$contenido = '<header class="pagina-cabecera">'
    . '<h1>Finanzas</h1>'
    . '<p>Lo que has gastado y las facturas de tus pedidos, en un solo lugar.</p>'
    . '</header>'
    . '<nav class="fin-tabs" aria-label="Secciones de finanzas">' . $tabGastos . $tabFacturas . '</nav>'
    . ($tab === 'gastos' ? $panelGastos : $panelFacturas);

require __DIR__ . '/../layout.php';
