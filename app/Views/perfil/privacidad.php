<?php
/**
 * Privacidad: tres interruptores que se guardan juntos.
 *
 * @var callable $e
 * @var string   $csrf
 * @var array<string,int> $preferencias
 * @var bool     $guardado
 */
$pref = static fn (string $k): bool => (int) ($preferencias[$k] ?? 1) === 1;

$interruptor = static function (string $nombre, string $titulo, string $detalle, bool $activo) use ($e): string {
    return '<label class="opcion">'
        . '<span class="opcion__texto"><strong>' . $e($titulo) . '</strong><small>' . $e($detalle) . '</small></span>'
        . '<span class="switch">'
        . '<input type="checkbox" name="' . $nombre . '" value="1"' . ($activo ? ' checked' : '') . '>'
        . '<span class="switch__pista" aria-hidden="true"></span>'
        . '</span></label>';
};

$contenido = '<div class="ajustes">'
    . '<a class="volver" href="/perfil">‹ Mi cuenta</a>'
    . '<h1>Privacidad</h1>'
    . '<p class="silencio">Elige cómo quieres que te contactemos y qué compartes.</p>'

    . ($guardado ? '<div class="aviso aviso--ok" role="status">Tus preferencias se guardaron.</div>' : '')

    . '<form method="post" action="/perfil/privacidad" class="carta ajustes__grupo">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'

    . $interruptor('notificaciones', 'Habilitar notificaciones', 'Interruptor general. Si lo apagas, no llega ningún aviso salvo los de seguridad.', $pref('notificaciones'))
    . $interruptor('sms', 'Mensajes de texto', 'Avisos de pedidos y códigos de seguridad por SMS.', $pref('sms'))
    . $interruptor('correo', 'Correo electrónico', 'Resúmenes, facturas y novedades de tu cuenta.', $pref('correo'))
    . $interruptor('ubicacion', 'Compartir mi ubicación', 'Solo mientras tengas un pedido en camino, para que el repartidor te encuentre.', $pref('ubicacion'))

    . '<div class="ajustes__acciones"><button type="submit" class="boton">Guardar preferencias</button></div>'
    . '</form>'

    . '<p class="ajustes__nota">Los avisos de seguridad (recuperar contraseña, verificación)
       se envían siempre, incluso con los interruptores apagados: sin ellos podrías
       quedarte fuera de tu cuenta.</p>'
    . '</div>';

require __DIR__ . '/../layout.php';
