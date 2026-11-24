<?php
/** @var callable $e */
/** @var string $csrf */
/** @var array $resumen */
/** @var array $direcciones */
/** @var string $idempotency */
/** @var string|null $error */
$opciones = '';
foreach ($direcciones as $d) {
    $opciones .= '<option value="' . (int) $d['id'] . '">' . $e($d['calle']) . ', ' . $e((string) $d['colonia']) . ' · ' . $e($d['ciudad']) . '</option>';
}

// Solo se confirman los proveedores que alcanzaron su mínimo; el resto sigue
// en el carrito.
$listos = array_values(array_filter(
    $resumen['grupos'],
    static fn (array $g): bool => !empty($g['listo'])
));
$quedan = count($resumen['grupos']) - count($listos);
$subtotalPago = (float) ($resumen['subtotal_listo'] ?? $resumen['subtotal']);
$ivaPago = (float) ($resumen['iva_listo'] ?? $resumen['iva']);
$totalPago = (float) ($resumen['total_listo'] ?? $resumen['total']);

$resumenHtml = '';
foreach ($listos as $g) {
    $resumenHtml .= '<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f4f2ec">'
        . '<span>' . $e($g['mayorista']) . ' <span style="color:#9ca3af;font-size:13px">(' . count($g['items']) . ' productos)</span></span>'
        . '<span style="font-variant-numeric:tabular-nums">$' . number_format((float) $g['total'], 2) . '</span></div>';
}

$contenido = '<h1>Confirmar pedido</h1>'
    . ($error !== null ? '<div class="aviso aviso--error">' . $e($error) . '</div>' : '')
    . '<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;align-items:start;margin-top:20px">'
    . '<form method="post" action="/checkout" style="background:#fff;border:1px solid #e8e6df;border-radius:12px;padding:20px">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<input type="hidden" name="idempotency_key" value="' . $e($idempotency) . '">'
    . '<h2 style="margin-top:0">Dirección de entrega</h2>'
    . ($opciones === ''
        ? '<p>No tienes direcciones guardadas. <a href="/perfil">Agrega una</a> para continuar.</p>'
        : '<label><span>Entregar en</span><select name="direccion_id" required>' . $opciones . '</select></label>')
    . '<h2>Resumen</h2>' . $resumenHtml
    . ($quedan > 0
        ? '<p class="checkout__nota">' . $quedan . ' proveedor' . ($quedan === 1 ? '' : 'es')
          . ' aún no alcanza' . ($quedan === 1 ? '' : 'n') . ' su mínimo y se queda'
          . ($quedan === 1 ? '' : 'n') . ' en tu carrito.</p>'
        : '')
    . '<div style="display:flex;justify-content:space-between;margin-top:14px;font-size:13px;color:#6b7280">'
    . '<span>Subtotal</span><span>$' . number_format($subtotalPago, 2) . '</span></div>'
    . '<div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280">'
    . '<span>IVA</span><span>$' . number_format($ivaPago, 2) . '</span></div>'
    . '<div style="display:flex;justify-content:space-between;margin-top:10px;font-size:20px;font-weight:650">'
    . '<span>Total</span><span>$' . number_format($totalPago, 2) . '</span></div>'
    . '<button type="submit" style="width:100%;margin-top:18px;height:46px"' . ($opciones === '' || $listos === [] ? ' disabled' : '') . '>Confirmar pedido</button>'
    . '<p style="font-size:12px;color:#9ca3af;margin:10px 0 0">Al confirmar se reserva el stock por 15 minutos.</p>'
    . '</form>'
    . '<div style="background:#fff;border:1px solid #e8e6df;border-radius:12px;padding:20px">'
    . '<h2 style="margin-top:0;font-size:15px">Qué pasa después</h2>'
    . '<ol style="padding-left:18px;font-size:14px;color:#6b7280;line-height:1.9;margin:0">'
    . '<li>Se reserva el stock de cada mayorista</li>'
    . '<li>Confirmas el pago</li>'
    . '<li>Cada mayorista acepta su parte</li>'
    . '<li>Se prepara y se asigna repartidor</li>'
    . '</ol></div></div>';

require __DIR__ . '/../layout.php';
