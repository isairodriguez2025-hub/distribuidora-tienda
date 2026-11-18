<?php
/**
 * Nueva contraseña a partir del token de recuperación.
 *
 * @var callable $e
 * @var string   $csrf
 * @var string   $token
 * @var string|null $error
 */
$auth = true;

$ojo = '<button type="button" class="ojo" data-para="clave" aria-pressed="false" aria-label="Mostrar contraseña" title="Mostrar contraseña">'
    . '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>';

$contenido = '<div class="auth-caja">'
    . '<img class="auth-logo" src="/assets/img/ares-logo.svg" alt="Ares" width="64" height="64">'
    . '<h1 class="auth-titulo">Elige una contraseña nueva</h1>'
    . '<p class="auth-sub">Al guardarla se cerrarán todas las sesiones abiertas.</p>'

    . ($error !== null ? '<div class="aviso aviso--error" role="alert">' . $e($error) . '</div>' : '')

    . '<form method="post" action="/restablecer" class="auth-form">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<input type="hidden" name="token" value="' . $e($token) . '">'

    . '<label class="campo"><span class="campo__etiqueta">Contraseña</span>'
    . '<span class="campo__con-boton">'
    . '<input class="campo__control" id="clave" type="password" name="password" minlength="12" required autocomplete="new-password" autofocus placeholder="Mínimo 12 caracteres" data-fuerza="medidor">'
    . $ojo
    . '</span>'
    . '<span class="fuerza" id="medidor" aria-hidden="true"><i></i><i></i><i></i><i></i></span>'
    . '<span class="campo__pista" id="fuerza-texto">Usa mayúsculas, números y un símbolo.</span>'
    . '</label>'

    . '<button type="submit" class="boton boton--ancho">Guardar contraseña</button>'
    . '</form>'

    . '<p class="auth-pie"><a href="/login">Volver a entrar</a></p>'
    . '</div>';

require __DIR__ . '/../layout.php';
