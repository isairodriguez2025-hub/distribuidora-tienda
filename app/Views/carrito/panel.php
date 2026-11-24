<?php
/**
 * Cuerpo del panel lateral del carrito. Se renderiza solo (sin layout) para
 * inyectarlo por fetch, y también desde /carrito/panel al abrir el cajón.
 *
 * @var callable $e
 * @var string   $csrf
 * @var array    $resumen
 */
$gruposHtml = '';

foreach ($resumen['grupos'] as $g) {
    $minimo = (float) ($g['monto_minimo'] ?? 0);
    $subtotal = (float) $g['subtotal'];
    $pct = $minimo > 0 ? min(100, max(0, ($subtotal / $minimo) * 100)) : 100;
    $falta = (float) ($g['faltante'] ?? 0);

    $estado = $g['cumple_minimo']
        ? '<p class="cprog__txt cprog__txt--ok">Mínimo cumplido</p>'
        : '<p class="cprog__txt">Faltan $' . number_format($falta, 2) . '</p>';

    $lineas = '';
    foreach ($g['items'] as $i) {
        $cant = (int) $i['cantidad'];
        $peligro = (int) $i['stock_disponible'] < $cant;
        $lineas .= '<li class="clinea' . ($peligro ? ' clinea--alerta' : '') . '" data-linea="' . $e($i['producto_uuid']) . '">'
            . '<div class="clinea__info">'
            . '<span class="clinea__nom">' . $e($i['nombre'])
            . ($peligro ? ' <span class="clinea__aviso">agotado</span>' : '') . '</span>'
            . '<span class="clinea__sub">' . $e($i['unidad_venta']) . ' de ' . $e($i['contenido']) . '</span>'
            . '</div>'
            . '<div class="clinea__der">'
            . '<span class="clinea__precio">$' . number_format((float) $i['total'], 2) . '</span>'
            . '<div class="stepper stepper--mini" role="group" aria-label="Cantidad de ' . $e($i['nombre']) . '">'
            . '<button type="button" class="stepper__menos' . ($cant === 1 ? ' stepper__menos--basura' : '') . '"'
            . ' data-paso="-1" data-uuid="' . $e($i['producto_uuid']) . '" data-cantidad="' . $cant . '"'
            . ' data-nombre="' . $e($i['nombre']) . '" data-max="' . (int) $i['stock_disponible'] . '"'
            . ' aria-label="' . ($cant === 1 ? 'Quitar ' : 'Disminuir ') . $e($i['nombre']) . '">'
            . ($cant === 1
                ? '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path></svg>'
                : '−')
            . '</button>'
            . '<span class="stepper__valor" data-valor>' . $cant . '</span>'
            . '<button type="button" class="stepper__mas" data-paso="1" data-uuid="' . $e($i['producto_uuid']) . '"'
            . ' data-cantidad="' . $cant . '" data-nombre="' . $e($i['nombre']) . '" data-max="' . (int) $i['stock_disponible'] . '"'
            . ' aria-label="Aumentar ' . $e($i['nombre']) . '">+</button>'
            . '</div></div></li>';
    }

    $gruposHtml .= '<section class="cgrupo" data-grupo="' . $e($g['mayorista_uuid'] ?? '') . '">'
        . '<header class="cgrupo__cab">'
        . '<strong class="cgrupo__nom">' . $e($g['mayorista']) . '</strong>'
        . (!empty($g['listo']) ? '<span class="cgrupo__listo">Listo</span>' : '')
        . '<span class="cgrupo__total">$' . number_format((float) $g['total'], 2) . '</span>'
        . '</header>'
        . '<div class="cprog" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . (int) round($pct) . '">'
        . '<span style="width:' . number_format($pct, 1, '.', '') . '%"></span></div>'
        . $estado
        . '<a class="cgrupo__mas" href="/buscar?mayorista=' . $e($g['mayorista_uuid'] ?? '') . '">+ Agregar más de este proveedor</a>'
        . '<ul class="clista">' . $lineas . '</ul>'
        . '</section>';
}

if ($gruposHtml === '') {
    $gruposHtml = '<div class="cvacio">'
        . '<p>Tu carrito está vacío.</p>'
        . '<a class="boton" href="/buscar" data-cajon-cerrar>Buscar productos</a>'
        . '</div>';
}

// El globo del botón cuenta unidades, así que la cabecera usa el mismo número
// para que no se contradigan en la misma pantalla.
$unidades = 0;
foreach ($resumen['grupos'] as $g) {
    foreach ($g['items'] as $it) {
        $unidades += (int) $it['cantidad'];
    }
}

$listos = (int) ($resumen['listos'] ?? 0);
$totalGrupos = (int) ($resumen['total_grupos'] ?? 0);
$parcial = $listos > 0 && $listos < $totalGrupos;
// Sin proveedores listos se muestran los totales completos (no $0.00).
$cobraTodo = $listos === $totalGrupos || $listos === 0;

$subtotal = (float) ($cobraTodo ? $resumen['subtotal'] : ($resumen['subtotal_listo'] ?? 0));
$iva = (float) ($cobraTodo ? $resumen['iva'] : ($resumen['iva_listo'] ?? 0));
$total = (float) ($cobraTodo ? $resumen['total'] : ($resumen['total_listo'] ?? 0));
$puede = (bool) $resumen['puede_checkout'];
?>
<div class="cajon__cab">
    <h2 class="cajon__titulo">Mi carrito
        <span class="cajon__cuenta" data-carrito-cuenta><?= $unidades ?></span>
    </h2>
    <?php if ((int) $resumen['items'] > 0): ?>
        <button type="submit" form="form-vaciar" class="cajon__vaciar">Vaciar</button>
    <?php endif; ?>
</div>

<div class="cajon__scroll" data-cajon-scroll>
    <?= $gruposHtml ?>
</div>

<?php if ((int) $resumen['items'] > 0): ?>
<div class="cajon__pie">
    <dl class="cifras">
        <div><dt>Subtotal<?= $parcial ? ' a pagar' : '' ?></dt><dd>$<?= number_format($subtotal, 2) ?></dd></div>
        <div><dt>IVA (16%)</dt><dd>$<?= number_format($iva, 2) ?></dd></div>
        <div><dt>Cargos de la app</dt><dd class="cifras__pend">—</dd></div>
        <div class="cifras__total"><dt>Total a pagar</dt><dd>$<?= number_format($total, 2) ?></dd></div>
    </dl>
    <?php if ($puede && $parcial): ?>
        <p class="cajon__nota cajon__nota--arriba">
            <?= $listos ?> de <?= $totalGrupos ?> proveedores alcanzan el mínimo.
            Los demás se quedan en el carrito.
        </p>
        <a class="cajon__cta" href="/checkout" data-cajon-cerrar>Continuar con <?= $listos ?> proveedor<?= $listos === 1 ? '' : 'es' ?></a>
    <?php elseif ($puede): ?>
        <a class="cajon__cta" href="/checkout" data-cajon-cerrar>Continuar el pago</a>
    <?php else: ?>
        <button type="button" class="cajon__cta cajon__cta--off" disabled aria-disabled="true">Continuar el pago</button>
        <p class="cajon__nota">Completa el mínimo de cada proveedor para continuar.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<form id="form-vaciar" method="post" action="/carrito/vaciar" hidden>
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
</form>
