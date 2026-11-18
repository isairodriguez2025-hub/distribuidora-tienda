<?php
/**
 * Solicitud de recuperación. Responde siempre lo mismo para no revelar
 * si el correo o el teléfono pertenecen a una cuenta.
 *
 * @var callable $e
 * @var string   $csrf
 * @var string|null $mensaje
 * @var string|null $enlace
 */
$auth = true;

$contenido = '<div class="auth-caja">'
    . '<img class="auth-logo" src="/assets/img/ares-logo.svg" alt="Ares" width="64" height="64">'
    . '<h1 class="auth-titulo">Recupera tu cuenta</h1>'
    . '<p class="auth-sub">Te enviamos un enlace por correo y un código por SMS.</p>'

    . ($mensaje !== null ? '<div class="aviso aviso--ok" role="status">' . $e($mensaje) . '</div>' : '')
    . ($enlace !== null ? '<p class="auth-legal"><a href="' . $e($enlace) . '">Abrir el enlace de restablecimiento</a></p>' : '')

    . '<form method="post" action="/recuperar" class="auth-form">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label class="campo"><span class="campo__etiqueta">Correo o teléfono</span>'
    . '<input class="campo__control" type="text" name="identificador" required autocomplete="username" autofocus placeholder="tu@correo.com o 5512345678">'
    . '<span class="campo__pista">Si la cuenta existe, recibirás las instrucciones en menos de un minuto.</span>'
    . '</label>'
    . '<button type="submit" class="boton boton--ancho">Enviar instrucciones</button>'
    . '</form>'

    . '<p class="auth-pie"><a href="/login">Volver a entrar</a></p>'
    . '</div>';

require __DIR__ . '/../layout.php';
