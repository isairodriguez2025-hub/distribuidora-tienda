<?php
/** @var callable $e */
/** @var string $csrf */
/** @var array $resumen */
/** @var string|null $mensaje */
/** @var string|null $error */
$grupos = '';
foreach ($resumen['grupos'] as $g) {
    $filas = '';
    foreach ($g['items'] as $i) {
        $alerta = $i['agotado'] ? ' <span style="color:#b91c1c;font-size:12px">agotado</span>' : '';
        $filas .= '<tr><td><div style="font-weight:500">' . $e($i['nombre']) . $alerta . '</div>'
            . '<div style="font-size:12px;color:#9ca3af">' . $e($i['unidad_venta']) . ' de ' . $e($i['contenido'])
            . ($i['escala_aplicada'] ? ' · ' . $e($i['escala_aplicada']) : '') . '</div></td>'
            . '<td>'
            . '<form method="post" action="/carrito/actualizar" class="stepper stepper--carrito">'
            . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<input type="hidden" name="producto" value="' . $e($i['producto_uuid']) . '">'
            . '<button type="button" class="stepper__menos" aria-label="Disminuir">−</button>'
            . '<input type="number" name="cantidad" value="' . (int) $i['cantidad'] . '" min="1" max="' . (int) $i['stock_disponible'] . '" step="1" aria-label="Cantidad">'
            . '<button type="button" class="stepper__mas" aria-label="Aumentar">+</button>'
            . '</form></td>'
            . '<td style="font-variant-numeric:tabular-nums">$' . number_format((float) $i['precio_unitario'], 2) . '</td>'
            . '<td style="font-variant-numeric:tabular-nums">$' . number_format((float) $i['total'], 2) . '</td>'
            . '<td style="text-align:right"><form method="post" action="/carrito/quitar" style="margin:0">'
            . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<input type="hidden" name="producto" value="' . $e($i['producto_uuid']) . '">'
            . '<button type="submit" style="background:none;border:0;color:#b91c1c;font-size:13px;cursor:pointer;padding:0">Quitar</button>'
            . '</form></td></tr>';
    }

    $minimo = $g['cumple_minimo']
        ? '<span style="color:#1f4d3a;font-size:13px">Mínimo cumplido</span>'
        : '<span style="color:#a56a12;font-size:13px">Faltan $' . number_format((float) $g['faltante'], 2) . ' para el mínimo</span>';

    $grupos .= '<div style="background:#fff;border:1px solid #e8e6df;border-radius:12px;padding:18px;margin-bottom:16px">'
        . '<div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:12px">'
        . '<div><div style="font-weight:600">' . $e($g['mayorista']) . '</div>' . $minimo . '</div>'
        . '<div style="font-size:18px;font-weight:600">$' . number_format((float) $g['total'], 2) . '</div></div>'
        . '<table style="border:0"><thead><tr><th>Producto</th><th>Cant.</th><th>Unitario</th><th>Total</th><th></th></tr></thead>'
        . '<tbody>' . $filas . '</tbody></table></div>';
}

if ($grupos === '') {
    $grupos = '<div style="background:#fff;border:1px solid #e8e6df;border-radius:12px;padding:32px;text-align:center">'
        . '<p style="margin:0 0 14px">Tu carrito está vacío.</p>'
        . '<a class="boton" href="/buscar">Buscar productos</a></div>';
}

$problemas = '';
foreach ($resumen['problemas'] as $p) {
    $problemas .= '<li>' . $e($p) . '</li>';
}

$contenido = '<h1>Mi carrito</h1>'
    . ($mensaje !== null ? '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>' : '')
    . ($error !== null ? '<div class="aviso aviso--error">' . $e($error) . '</div>' : '')
    . ($problemas !== '' ? '<div class="aviso aviso--alerta"><strong>Revisa esto antes de continuar:</strong><ul style="margin:8px 0 0;padding-left:18px">' . $problemas . '</ul></div>' : '')
    . $grupos
    . ($resumen['items'] > 0
        ? '<div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;padding:18px;background:#fff;border:1px solid #e8e6df;border-radius:12px">'
          . '<div><div style="font-size:13px;color:#6b7280">Subtotal $' . number_format((float) $resumen['subtotal'], 2)
          . ' · IVA $' . number_format((float) $resumen['iva'], 2) . '</div>'
          . '<div style="font-size:24px;font-weight:650">$' . number_format((float) $resumen['total'], 2) . '</div></div>'
          . ((int) ($resumen['listos'] ?? 0) > 0 && (int) $resumen['listos'] < (int) ($resumen['total_grupos'] ?? 0)
              ? '<p class="checkout__nota">Solo se cobrarán los ' . (int) $resumen['listos'] . ' proveedores que alcanzan su mínimo; el resto sigue en el carrito.</p>'
              : '')
          . ($resumen['puede_checkout']
              ? '<a class="boton" href="/checkout">Continuar al pago</a>'
              : '<span style="font-size:14px;color:#a56a12">Corrige los avisos para continuar</span>')
          . '</div>'
        : '');

$scripts = <<<'JS'
(function () {
  'use strict';
  document.querySelectorAll('.stepper--carrito').forEach(function (form) {
    var input = form.querySelector('input[type="number"]');
    var menos = form.querySelector('.stepper__menos');
    var mas = form.querySelector('.stepper__mas');
    if (!input || !menos || !mas) return;

    var max = parseInt(input.getAttribute('max'), 10) || 999;

    function submit() {
      var v = parseInt(input.value, 10);
      if (isNaN(v) || v < 1) v = 1;
      if (v > max) v = max;
      input.value = v;
      form.submit();
    }

    menos.addEventListener('click', function () {
      var v = parseInt(input.value, 10) || 1;
      input.value = Math.max(1, v - 1);
      form.submit();
    });

    mas.addEventListener('click', function () {
      var v = parseInt(input.value, 10) || 1;
      input.value = Math.min(max, v + 1);
      form.submit();
    });

    input.addEventListener('change', submit);
  });
})();
JS;

require __DIR__ . '/../layout.php';
