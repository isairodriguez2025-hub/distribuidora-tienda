<?php
/**
 * Corte de caja del turno.
 *
 * @var callable $e
 * @var array    $turno
 * @var array    $resumen
 * @var array    $movimientos
 * @var array    $ventas
 * @var float    $total
 */
$filas = '';
$etiquetas = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'credito' => 'Crédito'];

foreach ($resumen as $r) {
    $filas .= '<tr><td data-label="Método">' . $e($etiquetas[$r['metodo']] ?? (string) $r['metodo']) . '</td>'
        . '<td data-label="Ventas">' . (int) $r['ventas'] . '</td>'
        . '<td data-label="Total"><strong>$' . number_format((float) $r['total'], 2) . '</strong></td></tr>';
}

if ($filas === '') {
    $filas = '<tr><td colspan="3" class="silencio" style="text-align:center;padding:20px">Sin ventas en este turno.</td></tr>';
}

$efectivo = 0.0;

foreach ($resumen as $r) {
    if ($r['metodo'] === 'efectivo') {
        $efectivo = (float) $r['total'];
    }
}

$esperado = (float) ($turno['efectivo_esperado'] ?? 0);
$contado = (float) ($turno['efectivo_contado'] ?? 0);
$diferencia = (float) ($turno['diferencia'] ?? 0);
$abierto = $turno['estado'] === 'abierto';

$contenido = '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px">'
    . '<div><h1 style="margin:0">Corte de caja</h1>'
    . '<p class="silencio" style="margin:2px 0 0">Turno #' . (int) $turno['id'] . ' · '
    . $e(date('d/m/Y H:i', strtotime((string) $turno['abierto_at'])))
    . ($turno['cerrado_at'] !== null ? ' → ' . $e(date('d/m/Y H:i', strtotime((string) $turno['cerrado_at']))) : ' (abierto)')
    . '</p></div>'
    . '<div style="display:flex;gap:8px"><button type="button" class="boton boton--claro" onclick="window.print()">Imprimir</button>'
    . '<a href="/pos" class="boton boton--claro">Ir a caja</a></div></div>'

    . '<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr));margin-bottom:18px">'
    . '<div class="tarjeta" style="padding:16px"><div class="pista">Total vendido</div><div class="cifra">$' . number_format($total, 2) . '</div></div>'
    . '<div class="tarjeta" style="padding:16px"><div class="pista">Fondo inicial</div><div class="cifra">$' . number_format((float) $turno['fondo_inicial'], 2) . '</div></div>'
    . '<div class="tarjeta" style="padding:16px"><div class="pista">Efectivo en caja</div><div class="cifra">$' . number_format((float) $turno['fondo_inicial'] + $efectivo, 2) . '</div></div>'
    . ($abierto ? '' :
        '<div class="tarjeta" style="padding:16px"><div class="pista">Contado</div><div class="cifra">$' . number_format($contado, 2) . '</div></div>'
        . '<div class="tarjeta" style="padding:16px"><div class="pista">Diferencia</div>'
        . '<div class="cifra" style="color:' . (abs($diferencia) < 0.01 ? 'var(--d-verde)' : '#b3261e') . '">$' . number_format($diferencia, 2) . '</div></div>')
    . '</div>'

    . '<h2>Por método de pago</h2>'
    . '<div class="tarjeta" style="padding:0;overflow:hidden"><table class="tabla-pos">'
    . '<thead><tr><th>Método</th><th>Ventas</th><th>Total</th></tr></thead><tbody>' . $filas . '</tbody></table></div>';

if ($abierto) {
    $contenido .= '<div class="tarjeta" style="padding:18px;margin-top:18px;max-width:420px">'
        . '<h2 style="margin:0 0 10px">Cerrar turno</h2>'
        . '<form method="post" action="/pos/turno/cerrar"><input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
        . '<p class="silencio" style="margin:0 0 10px">Esperado en efectivo: <strong>$' . number_format((float) $turno['fondo_inicial'] + $efectivo, 2) . '</strong></p>'
        . '<label class="campo"><span>Efectivo contado</span>'
        . '<input type="number" name="efectivo_contado" step="0.01" min="0" required inputmode="decimal" value="' . number_format((float) $turno['fondo_inicial'] + $efectivo, 2, '.', '') . '"></label>'
        . '<label class="campo"><span>Notas</span><input name="notas" maxlength="255"></label>'
        . '<button type="submit" style="width:100%;margin-top:10px">Cerrar caja</button></form></div>';
}

require __DIR__ . '/../layout.php';
