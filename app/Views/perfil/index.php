<?php
/**
 * Perfil: identidad (foto), verificación, direcciones y sesión.
 *
 * @var callable $e
 * @var string   $csrf
 * @var string   $rol
 * @var array|null $perfil
 * @var array    $direcciones
 * @var array    $kyc
 * @var array    $errores
 * @var string|null $avatarUrl
 * @var string|null $mensaje
 */
$nombre = $perfil['nombre_negocio'] ?? $perfil['nombre_comercial'] ?? 'Mi negocio';
$avatarUrl = $avatarUrl ?? null;
$mensaje = $mensaje ?? null;
$iniciales = \App\Modules\Perfil\AvatarService::iniciales((string) $nombre);

$lista = '';
foreach ($direcciones as $d) {
    $lista .= '<li class="carta" style="margin-bottom:10px;padding:14px 16px">'
        . '<strong>' . $e($d['calle']) . '</strong>'
        . ($d['es_principal'] ? ' <span class="etiqueta">principal</span>' : '')
        . '<div class="silencio">' . $e((string) $d['colonia']) . ' · ' . $e($d['ciudad'])
        . ' (' . $e((string) $d['latitud']) . ', ' . $e((string) $d['longitud']) . ')</div></li>';
}
if ($lista === '') {
    $lista = '<li class="silencio">Todavía no tienes direcciones guardadas.</li>';
}

$estadoKyc = $kyc['completo']
    ? '<p><span class="etiqueta etiqueta--ok">Verificación completa</span></p>'
    : '<p><span class="etiqueta etiqueta--alerta">Faltan ' . count($kyc['faltantes']) . ' documento(s)</span> '
      . '<a href="/kyc">Completar verificación</a></p>';

$foto = $avatarUrl !== null
    ? '<img class="avatar-editor__foto" src="' . $e($avatarUrl) . '" alt="Tu foto de perfil" width="96" height="96">'
    : '<span class="avatar-editor__iniciales" aria-hidden="true">' . $e($iniciales) . '</span>';

$contenido = ($mensaje !== null ? '<div class="aviso aviso--ok" style="margin-bottom:16px">' . $e($mensaje) . '</div>' : '')
    . (isset($errores['avatar']) ? '<div class="aviso aviso--error" style="margin-bottom:16px">' . $e($errores['avatar']) . '</div>' : '')
    . '<h1>Mi cuenta</h1>'
    . '<p class="silencio">' . $e($nombre) . ' · ' . $e(str_replace('_', ' ', $rol)) . '</p>'

    . '<section class="carta" style="margin-top:20px">'
    . '<h2 style="margin-top:0">Foto de perfil</h2>'
    . '<div class="avatar-editor">'
    . $foto
    . '<div style="flex:1;min-width:220px">'
    . '<form method="post" action="/perfil/avatar" enctype="multipart/form-data">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label class="campo"><span>JPG, PNG o WebP · máximo 2 MB</span>'
    . '<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required></label>'
    . '<button type="submit">Guardar foto</button>'
    . '</form>'
    . ($avatarUrl !== null
        ? '<form method="post" action="/perfil/avatar/quitar" style="margin-top:8px">'
          . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
          . '<button type="submit" class="boton--claro" style="font-size:13px">Quitar foto</button></form>'
        : '')
    . '<p class="pista" style="margin-top:8px">Se recorta en cuadrado y se muestra junto a tu nombre en el menú.</p>'
    . '</div></div></section>'

    . '<div class="grid grid--2" style="margin-top:18px">'
    . '<section class="carta"><h2 style="margin-top:0">Verificación</h2>' . $estadoKyc . '</section>'
    . '<section class="carta"><h2 style="margin-top:0">Sesión</h2>'
    . '<p class="silencio">Sal de este dispositivo cuando termines.</p>'
    . '<form method="post" action="/logout">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<button type="submit" class="boton--claro">Cerrar sesión</button></form></section>'
    . '</div>'

    . '<section style="margin-top:24px"><h2>Direcciones</h2>'
    . '<ul style="list-style:none;padding:0;margin:0">' . $lista . '</ul></section>'

    . '<section class="carta" style="margin-top:20px"><h2 style="margin-top:0">Agregar dirección</h2>'
    . (isset($errores['direccion']) ? '<p style="color:#b91c1c">' . $e($errores['direccion']) . '</p>' : '')
    . '<form method="post" action="/perfil/direccion">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label class="campo"><span>Calle y número</span><input name="calle" required></label>'
    . '<div class="fila">'
    . '<label class="campo"><span>Colonia</span><input name="colonia"></label>'
    . '<label class="campo"><span>Ciudad</span><input name="ciudad" required></label>'
    . '</div>'
    . '<div class="fila">'
    . '<label class="campo"><span>Latitud</span><input name="latitud" type="number" step="0.000001" required></label>'
    . '<label class="campo"><span>Longitud</span><input name="longitud" type="number" step="0.000001" required></label>'
    . '</div>'
    . '<label style="display:flex;gap:8px;align-items:center;margin-bottom:14px"><input type="checkbox" name="es_principal" value="1"> Marcar como principal</label>'
    . '<button type="submit">Guardar dirección</button>'
    . '</form></section>';

require __DIR__ . '/../layout.php';
