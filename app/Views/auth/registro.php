<?php
/**
 * Registro. Misma tarjeta que el login para que el paso entre ambas
 * pantallas no se sienta como un salto de diseño.
 *
 * @var callable $e
 * @var string   $csrf
 * @var array<string,string> $errores
 * @var array<string,string> $valores
 */
$auth = true;

$v = static fn (string $k): string => (string) ($valores[$k] ?? '');
$err = static function (string $k) use ($errores, $e): string {
    return isset($errores[$k]) ? '<span class="campo__error">' . $e((string) $errores[$k]) . '</span>' : '';
};

$ojo = static fn (string $id): string => '<button type="button" class="ojo" data-para="' . $id . '" aria-pressed="false" aria-label="Mostrar contraseña" title="Mostrar contraseña">'
    . '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>';

$general = isset($errores['general'])
    ? '<div class="aviso aviso--error" role="alert">' . $e((string) $errores['general']) . '</div>'
    : '';

$contenido = '<div class="auth-caja">'
    . '<img class="auth-logo" src="/assets/img/ares-logo.svg" alt="Ares" width="68" height="68">'
    . '<h1 class="auth-titulo">Crea tu cuenta</h1>'
    . '<p class="auth-sub">Compra al mayoreo o vende tus productos en Ares.</p>'

    . '<div class="auth-tabs" role="tablist">'
    . '<a class="auth-tab" href="/login" role="tab" aria-selected="false">Entrar</a>'
    . '<a class="auth-tab activo" href="/registro" role="tab" aria-selected="true">Crear cuenta</a>'
    . '</div>'

    . $general

    . '<form method="post" action="/registro" class="auth-form" novalidate>'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'

    . '<label class="campo"><span class="campo__etiqueta">Nombre del negocio</span>'
    . '<input class="campo__control" type="text" name="nombre_negocio" value="' . $e($v('nombre_negocio')) . '" required autocomplete="organization" placeholder="Abarrotes La Esquina">'
    . $err('nombre_negocio') . '</label>'

    . '<div class="auth-doble">'
    . '<label class="campo"><span class="campo__etiqueta">Correo</span>'
    . '<input class="campo__control" type="email" name="email" value="' . $e($v('email')) . '" required autocomplete="email" placeholder="tu@correo.com">'
    . $err('email') . '</label>'

    . '<label class="campo"><span class="campo__etiqueta">Teléfono</span>'
    . '<input class="campo__control" type="tel" name="telefono" value="' . $e($v('telefono')) . '" required autocomplete="tel" inputmode="tel" placeholder="5512345678">'
    . $err('telefono') . '</label>'
    . '</div>'

    . '<label class="campo"><span class="campo__etiqueta">Quiero</span>'
    . '<select class="campo__control" name="rol" required>'
    . '<option value="comercio"' . ($v('rol') === 'comercio' ? ' selected' : '') . '>Comprar al mayoreo</option>'
    . '<option value="mayorista"' . ($v('rol') === 'mayorista' ? ' selected' : '') . '>Vender al mayoreo</option>'
    . '<option value="repartidor"' . ($v('rol') === 'repartidor' ? ' selected' : '') . '>Repartir pedidos</option>'
    . '</select></label>'

    . '<label class="campo"><span class="campo__etiqueta">Contraseña</span>'
    . '<span class="campo__con-boton">'
    . '<input class="campo__control" id="clave1" type="password" name="password" required minlength="12" autocomplete="new-password" placeholder="Mínimo 12 caracteres" data-fuerza="medidor">'
    . $ojo('clave1')
    . '</span>'
    . '<span class="fuerza" id="medidor" aria-hidden="true"><i></i><i></i><i></i><i></i></span>'
    . '<span class="campo__pista" id="fuerza-texto">Usa mayúsculas, números y un símbolo.</span>'
    . $err('password') . '</label>'

    . '<label class="campo"><span class="campo__etiqueta">Repite la contraseña</span>'
    . '<span class="campo__con-boton">'
    . '<input class="campo__control" id="clave2" type="password" name="password_confirmacion" required autocomplete="new-password" placeholder="Otra vez">'
    . $ojo('clave2')
    . '</span>'
    . $err('password_confirmacion') . '</label>'

    . '<button type="submit" class="boton boton--ancho">Crear cuenta</button>'
    . '</form>'

    . '<p class="auth-pie">¿Ya tienes cuenta? <a href="/login">Entrar</a></p>'
    . '<p class="auth-legal">Al crear una cuenta aceptas el <a href="/privacidad">aviso de privacidad</a>.</p>'
    . '</div>';

require __DIR__ . '/../layout.php';
