<?php
/** @var callable $e */ /** @var string $csrf */ /** @var array $ventas */ /** @var string|null $mensaje */

$filas = '';

foreach ($ventas as $v) {
    $cancelada = $v['estado'] === 'cancelada';
    $filas .= '<tr' . ($cancelada ? ' style="opacity:.55"' : '') . '>'
        . '<td data-label="Folio"><a href="/pos/ticket/' . $e((string) $v['uuid']) . '">' . $e((string) $v['folio']) . '</a></td>'
        . '<td data-label="Fecha">' . $e(date('d/m/Y H:i', strtotime((string) $v['created_at']))) . '</td>'
        . '<td data-label="Cliente">' . $e((string) ($v['cliente'] ?? 'Público general')) . '</td>'
        . '<td data-label="Total"><strong>$' . number_format((float) $v['total'], 2) . '</strong></td>'
        . '<td data-label="Estado"><span class="chip ' . ($cancelada ? 'chip--alerta' : 'chip--ok') . '">'
        . ($cancelada ? 'cancelada' : 'pagada') . '</span></td>'
        . '<td data-label=""></td></tr>';
}

if ($filas === '') {
    $filas = '<tr><td colspan="6" class="silencio" style="text-align:center;padding:24px">Todavía no hay ventas.</td></tr>';
}

$contenido = '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px">'
    . '<div><h1 style="margin:0">Ventas</h1><p class="silencio" style="margin:2px 0 0">Últimas 100 ventas de la caja.</p></div>'
    . '<a href="/pos" class="boton--claro">Ir a caja</a></div>'
    . ($mensaje !== null ? '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>' : '')
    . '<div class="tarjeta" style="padding:0;overflow:hidden"><table class="tabla-pos">'
    . '<thead><tr><th>Folio</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Estado</th><th></th></tr></thead>'
    . '<tbody>' . $filas . '</tbody></table></div>';

require __DIR__ . '/../layout.php';
