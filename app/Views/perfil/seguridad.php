<?php
/**
 * Seguridad: cambio de contraseña y baja de la cuenta. Vive dentro de
 * Ajustes para que "Mi perfil" quede solo con los datos de identidad.
 *
 * @var callable $e
 * @var string   $csrf
 */
$error = (string) ($_GET['error'] ?? '');
$ok = isset($_GET['ok']);

$mensajes = [
    'actual'   => 'La contraseña actual no coincide.',
    'longitud' => 'La contraseña nueva necesita al menos 12 caracteres.',
    'repetir'  => 'Las contraseñas nuevas no coinciden.',
];

$ojo = static fn (string $id): string => '<button type="button" class="ojo" data-para="' . $id . '" aria-pressed="false" aria-label="Mostrar contraseña" title="Mostrar contraseña">'
    . '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>';

$contenido = '<div class="ajustes">'
    . '<a class="volver" href="/perfil/ajustes">‹ Ajustes</a>'
    . '<h1>Seguridad</h1>'
    . '<p class="pista" style="margin:-4px 0 14px"><a href="/perfil">Mi perfil</a></p>'

    . ($ok ? '<div class="aviso aviso--ok" role="status">Tu contraseña quedó actualizada.</div>' : '')
    . (isset($mensajes[$error]) ? '<div class="aviso aviso--error" role="alert">' . $e($mensajes[$error]) . '</div>' : '')

    . '<section class="carta editor__bloque">'
    . '<h2>Contraseña</h2>'
    . '<p class="pista" style="margin-top:-6px">Al cambiarla se cierran las demás sesiones.</p>'
    . '<form method="post" action="/perfil/password">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label class="campo"><span class="campo__etiqueta">Contraseña actual</span>'
    . '<span class="campo__con-boton">'
    . '<input class="campo__control" id="p_actual" type="password" name="password_actual" autocomplete="current-password" placeholder="La que usas hoy" required>'
    . $ojo('p_actual') . '</span></label>'
    . '<div class="editor__doble">'
    . '<label class="campo"><span class="campo__etiqueta">Nueva</span>'
    . '<span class="campo__con-boton">'
    . '<input class="campo__control" id="p_nueva" type="password" name="password_nueva" minlength="12" autocomplete="new-password" placeholder="Mínimo 12 caracteres" data-fuerza="medidor" required>'
    . $ojo('p_nueva') . '</span></label>'
    . '<label class="campo"><span class="campo__etiqueta">Repite la nueva</span>'
    . '<span class="campo__con-boton">'
    . '<input class="campo__control" id="p_rep" type="password" name="password_repetir" autocomplete="new-password" placeholder="Otra vez" required>'
    . $ojo('p_rep') . '</span></label>'
    . '</div>'
    . '<span class="fuerza" id="medidor" aria-hidden="true"><i></i><i></i><i></i><i></i></span>'
    . '<span class="campo__pista" id="fuerza-texto">Usa mayúsculas, números y un símbolo.</span>'
    . '<div class="ajustes__acciones"><button type="submit" class="boton">Guardar contraseña</button></div>'
    . '</form>'
    . '</section>'

    // ------------------------------------------- baja, fuera de la tarjeta
    . '<div class="peligro-zona">'
    . '<div><strong>Eliminar mi cuenta</strong>'
    . '<p class="pista" style="margin:2px 0 0">Se borran tu foto, tus direcciones y el acceso. Los pedidos realizados se conservan por obligaciones fiscales.</p></div>'
    . '<button type="button" class="boton boton--peligro" data-abrir="baja-cuenta">Eliminar cuenta</button>'
    . '</div>'
    . '</div>'

    . '<dialog class="dialogo" id="baja-cuenta" aria-labelledby="baja-titulo">'
    . '<form method="post" action="/perfil/eliminar">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<h2 id="baja-titulo">Eliminar mi cuenta</h2>'
    . '<p>Esta acción no se puede deshacer. Escribe <strong>ELIMINAR</strong> para confirmar.</p>'
    . '<label class="campo"><span class="campo__etiqueta">Confirmación</span>'
    . '<input class="campo__control" name="confirmacion" required autocomplete="off" placeholder="ELIMINAR"></label>'
    . '<label class="campo"><span class="campo__etiqueta">Motivo (opcional)</span>'
    . '<input class="campo__control" name="motivo" maxlength="120" placeholder="Ya no lo necesito"></label>'
    . '<div class="dialogo__acciones">'
    . '<button type="button" class="boton boton--claro" data-cerrar>Cancelar</button>'
    . '<button type="submit" class="boton boton--peligro">Eliminar definitivamente</button>'
    . '</div></form></dialog>';

require __DIR__ . '/../layout.php';
