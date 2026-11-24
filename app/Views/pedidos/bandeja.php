<?php
/** @var callable $e */
/** @var string $csrf */
/** @var array $pedidos */
/** @var array $contadores */
/** @var string $filtro */

$ETIQUETA = [
    'pendiente_pago' => 'Esperando pago', 'pago_autorizado' => 'Pago autorizado',
    'pendiente_aceptacion' => 'Por aceptar', 'aceptado' => 'Aceptado',
    'preparando' => 'Preparando', 'listo_para_recoger' => 'Listo',
    'buscando_repartidor' => 'Buscando repartidor', 'repartidor_asignado' => 'Repartidor asignado',
    'en_recoleccion' => 'En recolección', 'en_camino' => 'En camino',
    'en_puerta' => 'En puerta', 'entregado' => 'Entregado', 'completado' => 'Completado',
    'cancelado' => 'Cancelado', 'rechazado' => 'Rechazado', 'expirado' => 'Expirado',
    'en_disputa' => 'En disputa', 'reembolsado' => 'Reembolsado', 'entrega_fallida' => 'Entrega fallida',
];

$filtros = '<a class="boton boton--claro" href="/mayorista/pedidos" style="' . ($filtro === '' ? 'border-color:#1f4d3a;color:#1f4d3a' : '') . '">Todos</a>';
foreach (['pendiente_aceptacion' => 'Por aceptar', 'aceptado' => 'Aceptados', 'preparando' => 'Preparando', 'listo_para_recoger' => 'Listos'] as $k => $texto) {
    $n = $contadores[$k] ?? 0;
    $filtros .= '<a class="boton boton--claro" href="/mayorista/pedidos?estado=' . $k . '" style="' . ($filtro === $k ? 'border-color:#1f4d3a;color:#1f4d3a' : '') . '">'
        . $e($texto) . ($n > 0 ? ' <strong>(' . $n . ')</strong>' : '') . '</a>';
}

$filas = '';
foreach ($pedidos as $p) {
    $estado = (string) $p['estado'];
    $etiqueta = $ETIQUETA[$estado] ?? $estado;
    $color = match (true) {
        in_array($estado, ['pendiente_aceptacion', 'pendiente_pago'], true) => '#a56a12',
        in_array($estado, ['cancelado', 'rechazado', 'expirado'], true) => '#b91c1c',
        in_array($estado, ['completado', 'entregado'], true) => '#1f4d3a',
        default => '#4b5563',
    };

    $acciones = '<a href="/pedidos/' . $e($p['uuid']) . '" style="font-size:13px">Ver</a>';
    if ($estado === 'pendiente_aceptacion') {
        $acciones = '<form method="post" action="/pedidos/' . $e($p['uuid']) . '/aceptar" style="display:inline;margin:0">'
            . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<button type="submit" style="padding:6px 12px;font-size:13px">Aceptar</button></form> '
            . '<a href="/pedidos/' . $e($p['uuid']) . '" style="font-size:13px;margin-left:8px">Ver</a>';
    } elseif ($estado === 'aceptado') {
        $acciones = '<form method="post" action="/pedidos/' . $e($p['uuid']) . '/preparando" style="display:inline;margin:0">'
            . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<button type="submit" class="boton boton--claro" style="padding:6px 12px;font-size:13px">Marcar preparando</button></form>';
    } elseif ($estado === 'preparando') {
        $acciones = '<form method="post" action="/pedidos/' . $e($p['uuid']) . '/listo" style="display:inline;margin:0">'
            . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<button type="submit" style="padding:6px 12px;font-size:13px">Listo para recoger</button></form>';
    }

    $limite = '';
    if ($estado === 'pendiente_aceptacion' && $p['acepta_antes_de']) {
        $limite = '<div class="cuenta" data-limite="' . $e((string) $p['acepta_antes_de']) . '" style="font-size:12px;color:#a56a12"></div>';
    }

    $filas .= '<tr><td><div style="font-weight:500">' . $e($p['comercio']) . '</div>'
        . '<div style="font-size:12px;color:#9ca3af">' . (int) $p['lineas'] . ' productos · ' . $e(substr((string) $p['created_at'], 0, 16)) . '</div></td>'
        . '<td style="font-variant-numeric:tabular-nums">$' . number_format((float) $p['total'], 2) . '</td>'
        . '<td><span style="color:' . $color . ';font-size:13px">' . $e($etiqueta) . '</span>' . $limite . '</td>'
        . '<td style="text-align:right">' . $acciones . '</td></tr>';
}

if ($filas === '') {
    $filas = '<tr><td colspan="4"><div class="vacio">No hay pedidos en este filtro.</div></td></tr>';
}

$contenido = '<h1>Pedidos recibidos</h1>'
    . '<div style="display:flex;gap:8px;flex-wrap:wrap;margin:18px 0">' . $filtros . '</div>'
    . '<table><thead><tr><th>Comercio</th><th>Total</th><th>Estado</th><th></th></tr></thead><tbody>' . $filas . '</tbody></table>';

$scripts = <<<'JS'
document.querySelectorAll('.cuenta[data-limite]').forEach(el => {
    const limite = new Date(el.dataset.limite.replace(' ', 'T')).getTime();
    const pintar = () => {
        const resta = limite - Date.now();
        if (resta <= 0) { el.textContent = 'Plazo vencido'; return; }
        const m = Math.floor(resta / 60000), s = Math.floor((resta % 60000) / 1000);
        el.textContent = 'Acepta en ' + m + ':' + String(s).padStart(2, '0');
    };
    pintar();
    setInterval(pintar, 1000);
});
JS;

require __DIR__ . '/../layout.php';
