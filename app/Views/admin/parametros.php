<?php
/** @var callable $e */
/** @var string $csrf */
/** @var array $parametros */

$filas = '';
foreach ($parametros as $p) {
    $tipoBadge = '<span style="background:#f3f4f6;padding:2px 8px;border-radius:999px;font-size:12px">' . $e($p['tipo']) . '</span>';
    $filas .= '<tr><td><code>' . $e($p['clave']) . '</code></td>'
        . '<td><input type="text" name="valor" value="' . $e($p['valor_actual']) . '" style="width:100%;max-width:300px" data-clave="' . $e($p['clave']) . '" class="param-input"></td>'
        . '<td>' . $tipoBadge . '</td>'
        . '<td>' . $e($p['descripcion'] ?? '') . '</td>'
        . '<td style="text-align:right">v' . $p['version'] . '</td>'
        . '<td><button type="button" class="boton boton--claro param-save" data-clave="' . $e($p['clave']) . '">Guardar</button></td></tr>';
}

$contenido = '<h1>Parámetros del sistema</h1>'
    . '<p style="font-size:13px;color:#6b7280;margin-bottom:16px">Los cambios se guardan con versionado automático y registro de quién los modificó.</p>'
    . '<div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;overflow-x:auto">'
    . '<table><thead><tr><th>Clave</th><th>Valor</th><th>Tipo</th><th>Descripción</th><th style="text-align:right">Versión</th><th></th></tr></thead>'
    . '<tbody>' . $filas . '</tbody></table></div>'
    . '<script nonce="' . $e($nonce ?? '') . '">'
    . 'document.querySelectorAll(".param-save").forEach(function(btn){'
    . 'btn.addEventListener("click",function(){var key=this.dataset.clave;var input=document.querySelector(".param-input[data-clave="+key+"]");'
    . 'fetch("/admin/parametros/"+key,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"_csrf=' . $e($csrf) . '&valor="+encodeURIComponent(input.value)})'
    . '.then(r=>r.json()).then(d=>{alert(d.mensaje||d.error?.message||"OK");location.reload();});'
    . '});});'
    . '</script>';

require __DIR__ . '/../layout.php';
