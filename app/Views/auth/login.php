<?php
/**
 * Inicio de sesión. Tarjeta centrada, sin cabecera de navegación.
 *
 * @var callable $e
 * @var string   $csrf
 * @var string|null $error
 * @var string   $email
 */
$auth = true;

$ojo = '<button type="button" class="ojo" data-para="clave" aria-pressed="false" aria-label="Mostrar contraseña" title="Mostrar contraseña">'
    . '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>';

$contenido = '<div class="auth-caja">'
    . '<img class="auth-logo" src="/assets/img/ares-logo.svg" alt="Ares" width="76" height="76">'
    . '<h1 class="auth-titulo">Entra en Ares</h1>'
    . '<p class="auth-sub">Materia prima, insumos y logística en un solo lugar.</p>'

    . '<div class="auth-tabs" role="tablist">'
    . '<a class="auth-tab activo" href="/login" role="tab" aria-selected="true">Entrar</a>'
    . '<a class="auth-tab" href="/registro" role="tab" aria-selected="false">Crear cuenta</a>'
    . '</div>'

    . ($error !== null ? '<div class="aviso aviso--error" role="alert">' . $e($error) . '</div>' : '')

    . '<form method="post" action="/login" class="auth-form" novalidate>'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'

    . '<label class="campo"><span class="campo__etiqueta">Correo</span>'
    . '<input class="campo__control" type="email" name="email" value="' . $e($email) . '" required autocomplete="email" autofocus placeholder="tu@correo.com">'
    . '</label>'

    . '<label class="campo"><span class="campo__etiqueta">Contraseña</span>'
    . '<span class="campo__con-boton">'
    . '<input class="campo__control" id="clave" type="password" name="password" required autocomplete="current-password" placeholder="Tu contraseña">'
    . $ojo
    . '</span></label>'

    . '<div class="auth-fila">'
    . '<label class="auth-check"><input type="checkbox" name="recordar" value="1"> Mantener sesión</label>'
    . '<a class="auth-enlace" href="/recuperar">¿Olvidaste tu contraseña?</a>'
    . '</div>'

    . '<button type="submit" class="boton boton--ancho">Entrar</button>'
    . '</form>'

    . '<p class="auth-pie">¿Aún no tienes cuenta? <a href="/registro">Crear una cuenta</a></p>'
    . '<p class="auth-legal">Al continuar aceptas el <a href="/privacidad">aviso de privacidad</a>.</p>'
    . '</div>';

require __DIR__ . '/../layout.php';
