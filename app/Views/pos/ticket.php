<?php
/**
 * Ticket imprimible (80 mm). Se abre en pestaña nueva desde la caja.
 *
 * @var callable $e
 * @var array    $venta
 * @var array    $lineas
 * @var array    $pagos
 */
$filas = '';

foreach ($lineas as $l) {
    $filas .= '<tr><td>' . $e((string) $l['descripcion']) . '<div class="t-small">'
        . number_format((float) $l['cantidad'], 2) . ' × $' . number_format((float) $l['precio_unitario'], 2)
        . '</div></td><td class="t-right">$' . number_format((float) $l['total'], 2) . '</td></tr>';
}

$pago = $pagos[0] ?? null;

$contenido = '<style>
.ticket { width: 300px; margin: 0 auto; background: #fff; padding: 18px; font-family: ui-monospace, "DejaVu Sans Mono", monospace; font-size: 12.5px; color: #111; border: 1px solid #e8e6df; border-radius: 8px; }
.ticket h2 { text-align: center; margin: 0 0 2px; font-size: 15px; letter-spacing: .5px; }
.ticket .t-centro { text-align: center; }
.ticket .t-small { font-size: 11px; color: #555; }
.ticket .t-right { text-align: right; white-space: nowrap; }
.ticket table { width: 100%; border-collapse: collapse; margin: 10px 0; }
.ticket td { padding: 4px 0; vertical-align: top; }
.ticket .t-linea { border-top: 1px dashed #999; margin: 8px 0; }
.ticket .t-total { display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; margin-top: 6px; }
.ticket .t-fila { display: flex; justify-content: space-between; padding: 2px 0; }
@media print {
  .barra, footer, .no-print { display: none !important; }
  main { padding: 0 !important; max-width: none !important; }
  body { background: #fff !important; }
  .ticket { border: 0; width: 72mm; padding: 0; }
  @page { margin: 6mm; }
}
</style>'
    . '<div class="no-print" style="display:flex;gap:8px;justify-content:center;margin-bottom:16px">'
    . '<button type="button" onclick="window.print()">Imprimir</button>'
    . '<a href="/pos" class="boton--claro">Volver a caja</a></div>'
    . '<div class="ticket">'
    . '<h2>DIDI2</h2>'
    . '<div class="t-centro t-small">Comprobante de venta</div>'
    . '<div class="t-linea"></div>'
    . '<div class="t-fila"><span>Folio</span><span>' . $e((string) $venta['folio']) . '</span></div>'
    . '<div class="t-fila"><span>Fecha</span><span>' . $e(date('d/m/Y H:i', strtotime((string) $venta['created_at']))) . '</span></div>'
    . '<div class="t-fila"><span>Estado</span><span>' . $e((string) $venta['estado']) . '</span></div>'
    . '<div class="t-linea"></div>'
    . '<table>' . $filas . '</table>'
    . '<div class="t-linea"></div>'
    . '<div class="t-fila"><span>Subtotal</span><span>$' . number_format((float) $venta['subtotal'], 2) . '</span></div>'
    . '<div class="t-fila"><span>IVA</span><span>$' . number_format((float) $venta['iva'], 2) . '</span></div>'
    . '<div class="t-total"><span>TOTAL</span><span>$' . number_format((float) $venta['total'], 2) . '</span></div>'
    . ($pago !== null ? '<div class="t-linea"></div>'
        . '<div class="t-fila"><span>Pago</span><span>' . $e(ucfirst((string) $pago['metodo'])) . '</span></div>'
        . '<div class="t-fila"><span>Recibido</span><span>$' . number_format((float) $pago['recibido'], 2) . '</span></div>'
        . '<div class="t-fila"><span>Cambio</span><span>$' . number_format((float) $pago['cambio'], 2) . '</span></div>'
        : '')
    . '<div class="t-linea"></div>'
    . '<div class="t-centro t-small">¡Gracias por su compra!</div>'
    . '</div>';

require __DIR__ . '/../layout.php';
