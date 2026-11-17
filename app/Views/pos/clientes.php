<?php
/** @var callable $e */ /** @var string $csrf */ /** @var array $clientes */ /** @var string|null $mensaje */

$filas = '';

foreach ($clientes as $c) {
    $filas .= '<tr><td data-label="Nombre">' . $e((string) $c['nombre']) . '</td>'
        . '<td data-label="Teléfono">' . $e((string) ($c['telefono'] ?? '')) . '</td>'
        . '<td data-label="Correo">' . $e((string) ($c['email'] ?? '')) . '</td>'
        . '<td data-label="RFC">' . $e((string) ($c['rfc'] ?? '')) . '</td></tr>';
}

if ($filas === '') {
    $filas = '<tr><td colspan="4" class="silencio" style="text-align:center;padding:24px">Sin clientes registrados.</td></tr>';
}

$contenido = '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px">'
    . '<div><h1 style="margin:0">Clientes de la caja</h1><p class="silencio" style="margin:2px 0 0">Para tickets con nombre o factura.</p></div>'
    . '<a href="/pos" class="boton--claro">Ir a caja</a></div>'
    . ($mensaje !== null ? '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>' : '')
    . '<div class="pos-inv">'
    . '<div class="tarjeta" style="padding:0;overflow:hidden"><table class="tabla-pos">'
    . '<thead><tr><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>RFC</th></tr></thead><tbody>' . $filas . '</tbody></table></div>'
    . '<div class="tarjeta" style="padding:18px"><h2 style="margin:0 0 12px">Nuevo cliente</h2>'
    . '<form method="post" action="/pos/clientes"><input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label class="campo"><span>Nombre</span><input name="nombre" required maxlength="120"></label>'
    . '<div class="fila"><label class="campo"><span>Teléfono</span><input name="telefono" maxlength="30"></label>'
    . '<label class="campo"><span>RFC</span><input name="rfc" maxlength="15"></label></div>'
    . '<label class="campo"><span>Correo</span><input type="email" name="email" maxlength="120"></label>'
    . '<button type="submit" style="width:100%;margin-top:8px">Guardar cliente</button></form></div>'
    . '</div>';

require __DIR__ . '/../layout.php';
