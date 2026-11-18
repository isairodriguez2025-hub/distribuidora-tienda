<?php
/** @var callable $e */
/** @var string $csrf */
/** @var array $pedido */
/** @var array $lineas */
/** @var array $eventos */
/** @var bool $esMayorista */
/** @var bool $esComercio */
/** @var bool $esRepartidor */
/** @var array $mensajes */
/** @var bool $chatAbierto */
/** @var string|null $mensaje */
/** @var string|null $error */
/** @var array{evaluado:int, nombre:string, mio:?int, puede:bool} $calificacion */

$ETIQUETA = [
    'borrador' => 'Borrador', 'pendiente_pago' => 'Esperando pago', 'pago_autorizado' => 'Pago autorizado',
    'pendiente_aceptacion' => 'Por aceptar', 'aceptado' => 'Aceptado', 'preparando' => 'Preparando',
    'listo_para_recoger' => 'Listo para recoger', 'buscando_repartidor' => 'Buscando repartidor',
    'repartidor_asignado' => 'Repartidor asignado', 'en_recoleccion' => 'En recolección',
    'en_camino' => 'En camino', 'en_puerta' => 'En puerta', 'entregado' => 'Entregado',
    'completado' => 'Completado', 'cancelado' => 'Cancelado', 'rechazado' => 'Rechazado',
    'expirado' => 'Expirado', 'en_disputa' => 'En disputa', 'reembolsado' => 'Reembolsado',
];

$filas = '';
foreach ($lineas as $l) {
    $filas .= '<tr><td>' . $e($l['nombre_producto'])
        . ($l['escala_aplicada'] ? '<div style="font-size:12px;color:#9ca3af">' . $e($l['escala_aplicada']) . '</div>' : '')
        . '</td><td>' . (int) $l['cantidad'] . '</td>'
        . '<td style="font-variant-numeric:tabular-nums">$' . number_format((float) $l['precio_unitario'], 2) . '</td>'
        . '<td style="font-variant-numeric:tabular-nums">$' . number_format((float) $l['subtotal'], 2) . '</td></tr>';
}

$linea = '';
foreach ($eventos as $ev) {
    $linea .= '<li style="margin-bottom:8px"><strong>' . $e($ETIQUETA[$ev['estado_nuevo']] ?? $ev['estado_nuevo']) . '</strong>'
        . ' <span style="color:#9ca3af;font-size:12px">' . $e(substr((string) $ev['created_at'], 0, 16)) . '</span>'
        . ($ev['nota'] ? '<div style="font-size:13px;color:#6b7280">' . $e($ev['nota']) . '</div>' : '')
        . '</li>';
}

$acciones = '';
$estado = (string) $pedido['estado'];

if ($esMayorista) {
    $boton = static function (string $accion, string $texto, string $clase = '') use ($e, $csrf, $pedido): string {
        return '<form method="post" action="/pedidos/' . $e($pedido['uuid']) . '/' . $accion . '" style="display:inline;margin:0 8px 0 0">'
            . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<button type="submit" class="' . $clase . '" style="padding:9px 16px;font-size:14px">' . $e($texto) . '</button></form>';
    };

    $acciones = match ($estado) {
        'pendiente_aceptacion' => $boton('aceptar', 'Aceptar pedido') . $boton('rechazar', 'Rechazar', 'boton--claro'),
        'aceptado'             => $boton('preparando', 'Marcar preparando'),
        'preparando'           => $boton('listo', 'Listo para recoger'),
        'listo_para_recoger'   => $boton('buscar-reparto', 'Buscar repartidor'),
        default                => '',
    };

    $acciones .= '<a class="boton boton--claro" href="/pedidos/' . $e($pedido['uuid']) . '/surtido" style="padding:9px 16px;font-size:14px">Hoja de surtido</a>';
}

if ($esComercio && $estado === 'borrador') {
    $acciones = '<form method="post" action="/pedidos/' . $e($pedido['uuid']) . '/pagar" style="display:inline;margin:0">'
        . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
        . '<button type="submit" style="padding:9px 16px;font-size:14px">Confirmar pago</button></form>';
}

// --- Vista del repartidor -------------------------------------------------
if ($esRepartidor) {
    $pinCampo = static function (string $accion, string $texto, string $etiqueta, int $largo) use ($e, $csrf, $pedido): string {
        return '<form method="post" action="/repartidor/pedidos/' . $e($pedido['uuid']) . '/' . $accion . '" style="display:flex;gap:8px;align-items:center;margin:0">'
            . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<input name="pin" inputmode="numeric" maxlength="' . $largo . '" placeholder="PIN de ' . $largo . ' dígitos" required'
            . ' style="height:42px;width:210px;letter-spacing:.3em;text-align:center">'
            . '<button type="submit" style="padding:9px 16px;font-size:14px">' . $e($texto) . '</button>'
            . '<span style="font-size:13px;color:#6b7280">' . $e($etiqueta) . '</span></form>';
    };

    $paso = static function (string $accion, string $texto) use ($e, $csrf, $pedido): string {
        return '<form method="post" action="/pedidos/' . $e($pedido['uuid']) . '/' . $accion . '" style="display:inline;margin:0 8px 0 0">'
            . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<button type="submit" style="padding:9px 16px;font-size:14px">' . $e($texto) . '</button></form>';
    };

    $acciones = match ($estado) {
        'repartidor_asignado' => $pinCampo('recolectar', 'Confirmar recolección', 'Pídelo al mayorista', 6),
        'en_recoleccion'      => $paso('en-camino', 'Salir a entregar'),
        'en_camino'           => $paso('en-puerta', 'Llegué al comercio'),
        'en_puerta'           => $pinCampo('entregar', 'Confirmar entrega', 'Pídelo al comercio', 4),
        default               => '',
    };
}

// El comercio puede seguir el viaje mientras va en camino.
if ($esComercio && in_array($estado, ['en_camino', 'en_puerta'], true)) {
    $acciones = '<a class="boton" href="/pedidos/' . $e($pedido['uuid']) . '/seguimiento" style="padding:9px 16px;font-size:14px">Ver en el mapa</a>';
}

// El comercio ve su PIN cuando el repartidor está en la puerta.
$avisoPin = '';
if ($esComercio && $estado === 'en_puerta' && !empty($pedido['pin_entrega_visible'])) {
    $avisoPin = '<div class="aviso aviso--alerta" style="text-align:center">'
        . '<div style="font-size:13px">Da este código al repartidor</div>'
        . '<div style="font-size:34px;font-weight:700;letter-spacing:.3em;margin-top:4px">'
        . $e((string) $pedido['pin_entrega_visible']) . '</div></div>';
}

// --- Chat del pedido ------------------------------------------------------
$bloqueChat = '';

if ($chatAbierto) {
    $hilos = '';

    foreach ($mensajes as $m) {
        $mio = (int) $m['autor_id'] === (int) \App\Core\Session::obtener('user_id', 0);
        $alerta = (int) $m['tiene_alerta_desintermediacion'] === 1;

        $hilos .= '<div style="display:flex;justify-content:' . ($mio ? 'flex-end' : 'flex-start') . ';margin-bottom:8px">'
            . '<div style="max-width:78%;background:' . ($mio ? '#eaf1ec' : '#f3f4f6') . ';'
            . 'border:1px solid ' . ($alerta ? '#f6d5d5' : 'transparent') . ';'
            . 'border-radius:12px;padding:9px 13px">'
            . '<div style="font-size:11px;color:#9ca3af;margin-bottom:2px">'
            . ($mio ? 'Tú' : $e(substr((string) $m['email'], 0, 3) . '***')) . ' · '
            . $e(substr((string) $m['created_at'], 11, 5)) . '</div>'
            . '<div style="font-size:14px;color:#111827;white-space:pre-wrap">' . $e((string) $m['cuerpo']) . '</div>'
            . ($alerta ? '<div style="font-size:11px;color:#b91c1c;margin-top:4px">Marcado por compartir datos de contacto</div>' : '')
            . '</div></div>';
    }

    if ($hilos === '') {
        $hilos = '<p style="font-size:13px;color:#9ca3af;margin:0 0 10px">Todavía no hay mensajes. '
            . 'Usa el chat para coordinar la entrega; no compartas teléfonos ni correos.</p>';
    }

    $bloqueChat = '<h2 id="chat">Mensajes con ' . $e($esMayorista ? $pedido['comercio'] : $pedido['mayorista']) . '</h2>'
        . '<div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:14px;max-width:640px">'
        . '<div style="max-height:320px;overflow-y:auto;margin-bottom:12px">' . $hilos . '</div>'
        . '<form method="post" action="/pedidos/' . $e($pedido['uuid']) . '/mensaje" style="display:flex;gap:8px;margin:0">'
        . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
        . '<input name="cuerpo" placeholder="Escribe un mensaje…" required maxlength="2000" style="flex:1">'
        . '<button type="submit" style="padding:0 18px">Enviar</button>'
        . '</form></div>';
}


$bloqueCalificar = '';
if (!empty($calificacion['puede']) && (int) $calificacion['evaluado'] > 0) {
    $mio = $calificacion['mio'];
    $estrellas = '';
    for ($n = 5; $n >= 1; $n--) {
        $estrellas .= '<input class="estrella__radio" type="radio" id="cal-' . $n . '" name="puntaje" value="' . $n . '"'
            . ($mio === $n ? ' checked' : '') . ' required>'
            . '<label class="estrella__icono" for="cal-' . $n . '" aria-label="' . $n . ' de 5">★</label>';
    }

    $bloqueCalificar = '<section class="carta calificar">'
        . '<h2 style="margin-top:0">' . ($mio !== null ? 'Tu calificación para ' : 'Califica a ') . $e((string) $calificacion['nombre']) . '</h2>'
        . '<p class="pista">' . ($mio !== null
            ? 'Ya lo puntuaste con ' . $mio . ' de 5. Puedes cambiarlo cuando quieras.'
            : 'Solo lo ves tú y la otra persona ve su promedio. Sirve para que los buenos proveedores destaquen.') . '</p>'
        . '<form method="post" action="/calificar">'
        . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
        . '<input type="hidden" name="evaluado_id" value="' . (int) $calificacion['evaluado'] . '">'
        . '<input type="hidden" name="pedido_id" value="' . (int) $pedido['id'] . '">'
        . '<input type="hidden" name="volver" value="/pedidos/' . $e((string) $pedido['uuid']) . '">'
        . '<div class="estrellas">' . $estrellas . '</div>'
        . '<label class="campo"><span class="campo__etiqueta">Comentario (opcional)</span>'
        . '<input class="campo__control" name="comentario" maxlength="280" placeholder="Llegó a tiempo y completo"></label>'
        . '<button type="submit" class="boton">' . ($mio !== null ? 'Actualizar calificación' : 'Enviar calificación') . '</button>'
        . '</form></section>';
}

$contenido = '<h1>Pedido ' . $e(substr((string) $pedido['uuid'], 0, 8)) . '</h1>'
    . '<p>' . $e($pedido['mayorista']) . ' → ' . $e($pedido['comercio']) . '</p>'
    . ($mensaje !== null ? '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>' : '')
    . ($error !== null ? '<div class="aviso aviso--error">' . $e($error) . '</div>' : '')
    . '<div style="display:flex;gap:24px;flex-wrap:wrap;margin:18px 0;padding:16px;background:#fff;border:1px solid #e8e6df;border-radius:12px">'
    . '<div><div style="font-size:12px;color:#9ca3af">Estado</div><div style="font-weight:600">' . $e($ETIQUETA[$estado] ?? $estado) . '</div></div>'
    . '<div><div style="font-size:12px;color:#9ca3af">Total</div><div style="font-weight:600">$' . number_format((float) $pedido['total'], 2) . '</div></div>'
    . '<div><div style="font-size:12px;color:#9ca3af">Entrega</div><div style="font-weight:500">' . $e($pedido['calle']) . ', ' . $e($pedido['ciudad']) . '</div></div>'
    . '</div>'
    . $avisoPin
    . ($acciones !== '' ? '<div style="margin:18px 0">' . $acciones . '</div>' : '')
    . '<h2>Productos</h2>'
    . '<table><thead><tr><th>Producto</th><th>Cant.</th><th>Unitario</th><th>Subtotal</th></tr></thead><tbody>' . $filas . '</tbody></table>'
    . '<div style="display:flex;justify-content:flex-end;gap:28px;margin-top:14px;font-size:14px;color:#6b7280">'
    . '<span>Subtotal $' . number_format((float) $pedido['subtotal'], 2) . '</span>'
    . '<span>IVA $' . number_format((float) $pedido['iva'], 2) . '</span>'
    . '<strong style="color:#1a1a1a">Total $' . number_format((float) $pedido['total'], 2) . '</strong></div>'
    . '<h2>Historial</h2><ul style="padding-left:18px;margin:0">' . $linea . '</ul>'
    . $bloqueChat
    . $bloqueCalificar;

require __DIR__ . '/../layout.php';
