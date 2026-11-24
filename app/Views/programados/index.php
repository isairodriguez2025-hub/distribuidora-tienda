<?php
/** @var callable $e */
/** @var string $csrf */
/** @var array $plantillas */
/** @var array $mayoristas */
/** @var array $productos */
/** @var string|null $mensaje */
/** @var string|null $error */

$ETIQUETA = ['activa' => 'Activa', 'pausada' => 'Pausada', 'cancelada' => 'Cancelada'];

$filas = '';
foreach ($plantillas as $p) {
    $estado = (string) $p['estado'];
    $color = match ($estado) {
        'activa' => '#1f4d3a', 'pausada' => '#a56a12', default => '#b91c1c',
    };

    $accion = $estado === 'activa'
        ? '<form method="post" action="/programados/' . (int) $p['id'] . '/pausada" style="display:inline;margin:0 6px 0 0">'
          . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
          . '<button type="submit" class="boton boton--claro" style="padding:5px 11px;font-size:12px">Pausar</button></form>'
        : ($estado === 'pausada'
            ? '<form method="post" action="/programados/' . (int) $p['id'] . '/activa" style="display:inline;margin:0 6px 0 0">'
              . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
              . '<button type="submit" style="padding:5px 11px;font-size:12px">Reactivar</button></form>'
            : '');

    if ($estado !== 'cancelada') {
        $accion .= '<form method="post" action="/programados/' . (int) $p['id'] . '/cancelada" style="display:inline;margin:0">'
            . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<button type="submit" style="background:none;border:0;color:#b91c1c;font-size:12px;cursor:pointer;padding:0">Cancelar</button></form>';
    }

    $filas .= '<tr><td><div style="font-weight:500">' . $e($p['mayorista']) . '</div>'
        . '<div style="font-size:12px;color:#9ca3af">' . (int) $p['lineas'] . ' productos · ' . $e($p['frecuencia']) . '</div></td>'
        . '<td style="font-size:13px">' . $e(substr((string) $p['proximo_envio'], 0, 16)) . '</td>'
        . '<td><span style="color:' . $color . ';font-size:13px">' . $e($ETIQUETA[$estado] ?? $estado) . '</span></td>'
        . '<td style="text-align:right">' . $accion . '</td></tr>';
}

if ($filas === '') {
    $filas = '<tr><td colspan="4"><div class="vacio">No tienes pedidos programados.</div></td></tr>';
}

$opcionesMayo = '';
foreach ($mayoristas as $m) {
    $opcionesMayo .= '<option value="' . (int) $m['id'] . '">' . $e($m['nombre_comercial']) . '</option>';
}

$opcionesProd = '';
foreach ($productos as $p) {
    $opcionesProd .= '<option value="' . $e($p['uuid']) . '">' . $e($p['mayorista']) . ' — ' . $e($p['nombre']) . '</option>';
}

$contenido = '<h1>Pedidos programados</h1>'
    . '<p>El sistema prepara un borrador 24 horas antes con los precios de ese día. '
    . '<strong>Nunca se cobra sin que tú confirmes.</strong></p>'
    . ($mensaje !== null ? '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>' : '')
    . ($error !== null ? '<div class="aviso aviso--error">' . $e($error) . '</div>' : '')
    . '<table style="margin-top:18px"><thead><tr><th>Mayorista</th><th>Próximo envío</th><th>Estado</th><th></th></tr></thead>'
    . '<tbody>' . $filas . '</tbody></table>'
    . '<h2>Nueva plantilla</h2>'
    . '<form method="post" action="/programados" style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:18px;max-width:640px">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label><span>Mayorista</span><select name="mayorista_id" required>' . $opcionesMayo . '</select></label>'
    . '<div style="display:flex;gap:12px">'
    . '<label style="flex:1"><span>Frecuencia</span><select name="frecuencia">'
    . '<option value="semanal">Semanal</option><option value="quincenal">Quincenal</option>'
    . '<option value="mensual">Mensual</option><option value="unica">Una sola vez</option></select></label>'
    . '<label style="flex:1"><span>Hora</span><input type="time" name="hora" value="09:00" required></label>'
    . '</div>'
    . '<label><span>Producto</span><select name="producto[]">' . $opcionesProd . '</select></label>'
    . '<label><span>Cantidad</span><input type="number" name="cantidad[]" value="1" min="1"></label>'
    . '<button type="submit" style="margin-top:8px">Crear plantilla</button>'
    . '</form>';

require __DIR__ . '/../layout.php';
