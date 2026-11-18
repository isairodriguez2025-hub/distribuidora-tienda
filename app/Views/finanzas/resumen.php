<?php
/** @var callable $e */
/** @var string $tipo */
/** @var array $resumen */
/** @var array $detalle */
/** @var string $desde */
/** @var string $hasta */

$dinero = static fn ($v): string => '$' . number_format((float) $v, 2);

$tarjetas = '';
if ($tipo === 'comercio') {
    $tarjetas = '<div><div class="lbl">Pedidos</div><div class="val">' . (int) ($resumen['pedidos'] ?? 0) . '</div></div>'
        . '<div><div class="lbl">Gasto total</div><div class="val">' . $dinero($resumen['total'] ?? 0) . '</div></div>'
        . '<div><div class="lbl">IVA</div><div class="val">' . $dinero($resumen['iva'] ?? 0) . '</div></div>';
} elseif ($tipo === 'mayorista') {
    $tarjetas = '<div><div class="lbl">Pedidos</div><div class="val">' . (int) ($resumen['pedidos'] ?? 0) . '</div></div>'
        . '<div><div class="lbl">Venta bruta</div><div class="val">' . $dinero(((float) ($resumen['subtotal'] ?? 0)) + ((float) ($resumen['iva'] ?? 0))) . '</div></div>'
        . '<div><div class="lbl">Comisión</div><div class="val">−' . $dinero($resumen['comision'] ?? 0) . '</div></div>'
        . '<div><div class="lbl">Neto</div><div class="val" style="color:#1f4d3a">' . $dinero($resumen['neto'] ?? 0) . '</div></div>';
} elseif ($tipo === 'repartidor') {
    $tarjetas = '<div><div class="lbl">Viajes</div><div class="val">' . (int) ($resumen['viajes'] ?? 0) . '</div></div>'
        . '<div><div class="lbl">Ganado</div><div class="val" style="color:#1f4d3a">' . $dinero($resumen['total'] ?? 0) . '</div></div>';
}

$filas = '';
foreach ($detalle as $d) {
    if ($tipo === 'comercio') {
        $filas .= '<tr><td>' . $e($d['categoria']) . '</td><td style="text-align:right">' . $dinero($d['monto']) . '</td></tr>';
    } else {
        $filas .= '<tr><td>' . $e(substr((string) $d['uuid'], 0, 8)) . '</td><td>' . $e((string) $d['fecha']) . '</td>'
            . '<td style="text-align:right">' . $dinero($d['tarifa']) . '</td></tr>';
    }
}

$tabla = '';
if ($filas !== '') {
    $tabla = $tipo === 'comercio'
        ? '<h2>Por categoría</h2><table><thead><tr><th>Categoría</th><th style="text-align:right">Monto</th></tr></thead><tbody>' . $filas . '</tbody></table>'
        : '<h2>Viajes</h2><table><thead><tr><th>Pedido</th><th>Fecha</th><th style="text-align:right">Tarifa</th></tr></thead><tbody>' . $filas . '</tbody></table>';
}

$contenido = '<h1>Finanzas</h1>'
    . '<form method="get" action="/finanzas" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:12px;margin:18px 0">'
    . '<label style="margin:0"><span style="font-size:12px;color:#6b7280">Desde</span><input type="date" name="desde" value="' . $e($desde) . '"></label>'
    . '<label style="margin:0"><span style="font-size:12px;color:#6b7280">Hasta</span><input type="date" name="hasta" value="' . $e($hasta) . '"></label>'
    . '<button type="submit" style="height:42px">Aplicar</button>'
    . ($tipo === 'comercio' ? '<a class="boton boton--claro" style="height:42px;padding:0 16px;line-height:42px" href="/finanzas/export?desde=' . $e($desde) . '&hasta=' . $e($hasta) . '">Exportar CSV</a>' : '')
    . '</form>'
    . '<style>.lbl{font-size:12px;color:#9ca3af}.val{font-size:22px;font-weight:700}</style>'
    . '<div style="display:flex;gap:28px;flex-wrap:wrap;background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:18px">' . $tarjetas . '</div>'
    . $tabla;

require __DIR__ . '/../layout.php';
