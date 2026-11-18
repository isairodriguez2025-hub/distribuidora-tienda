<?php
/**
 * Mi cuenta. Una ficha de identidad arriba, acciones de sesión en medio y el
 * bloque "Más…" al final con privacidad, términos y baja de la cuenta.
 *
 * @var callable $e
 * @var string   $csrf
 * @var string   $rol
 * @var array|null $perfil
 * @var array    $errores
 * @var string|null $avatarUrl
 * @var string|null $mensaje
 * @var array{promedio: ?float, total: int} $calificacion
 */
$nombresRol = [
    'admin_total'   => 'Administrador',
    'admin_kyc'     => 'Administrador · KYC',
    'admin_soporte' => 'Administrador · Soporte',
    'admin_finanzas' => 'Administrador · Finanzas',
    'comercio'      => 'Comercio',
    'mayorista'     => 'Mayorista',
    'repartidor'    => 'Repartidor',
];
$etiquetaRol = $nombresRol[$rol] ?? ucfirst(str_replace('_', ' ', $rol));

$nombre = (string) ($perfil['nombre_negocio'] ?? $perfil['nombre_comercial'] ?? $perfil['nombre'] ?? 'Mi cuenta');
$correo = (string) ($perfil['email'] ?? '');

$foto = $avatarUrl !== null
    ? '<img class="ficha__foto" src="' . $e($avatarUrl) . '" alt="" width="88" height="88">'
    : '<span class="ficha__iniciales" aria-hidden="true">' . $e(\App\Modules\Perfil\AvatarService::iniciales($nombre)) . '</span>';

$promedio = $calificacion['promedio'] ?? null;
$total = (int) ($calificacion['total'] ?? 0);

$nota = $promedio !== null
    ? '<span class="ficha__nota" title="' . $e((string) $total) . ' calificaciones">'
      . '<span class="ficha__estrella" aria-hidden="true">★</span>'
      . '<span class="ficha__puntos">' . $e(number_format((float) $promedio, 1, '.', '')) . '</span>'
      . '<span class="visualmente-oculto">de 5</span></span>'
    : '';

$aviso = '';
if (isset($_GET['ok']) && $_GET['ok'] === 'datos') {
    $aviso = '<div class="aviso aviso--ok" role="status">Tus datos se guardaron.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'confirmacion') {
    $aviso = '<div class="aviso aviso--error" role="alert">Para eliminar la cuenta escribe ELIMINAR.</div>';
} elseif (isset($_GET['cal']) && $_GET['cal'] === 'ok') {
    $aviso = '<div class="aviso aviso--ok" role="status">Gracias, tu calificación quedó registrada.</div>';
} elseif ($mensaje !== null) {
    $aviso = '<div class="aviso aviso--ok" role="status">' . $e($mensaje) . '</div>';
}

$contenido = '<div class="cuenta">'
    . '<header class="pagina-cabecera">'
    . '<h1>Mi perfil</h1>'
    . '<p>Tus datos, la seguridad de la cuenta y las opciones de privacidad.</p>'
    . '</header>'
    . $aviso

    // ---------------------------------------------------------- ficha
    . '<section class="ficha" aria-labelledby="ficha-nombre">'
    . '<div class="ficha__foto-caja">' . $foto . '</div>'
    . '<div class="ficha__datos">'
    . '<div class="ficha__linea">'
    . '<h1 class="ficha__nombre" id="ficha-nombre">' . $e($nombre) . '</h1>'
    . $nota
    . '</div>'
    . '<span class="chip chip--rol">' . $e($etiquetaRol) . '</span>'
    . '<p class="ficha__correo">' . $e($correo) . '</p>'
    . '</div>'
    . '<a class="boton ficha__editar" href="/perfil/editar">'
    . '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>'
    . 'Editar</a>'
    . '</section>'

    // -------------------------------------------------------------- atajos
    . '<nav class="atajos" aria-label="Accesos de la cuenta">'
    . '<a class="atajo" href="/kyc">'
    . '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M12 2.5 4.5 5.5v5.9c0 5 3.2 9.1 7.5 10.6 4.3-1.5 7.5-5.6 7.5-10.6V5.5Z"></path><path d="m8.8 11.6 2.2 2.2 4.4-4.4"></path></svg>'
    . '<span>Seguridad</span></a>'

    . '<a class="atajo" href="/ayuda">'
    . '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M4 14v-2a8 8 0 0 1 16 0v2"></path>'
    . '<rect x="2.5" y="13" width="4.5" height="7" rx="2"></rect>'
    . '<rect x="17" y="13" width="4.5" height="7" rx="2"></rect>'
    . '<path d="M20 20v.5a2.5 2.5 0 0 1-2.5 2.5H13"></path></svg>'
    . '<span>Ayuda</span></a>'

    . '<a class="atajo" href="/perfil/ajustes">'
    . '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<circle cx="12" cy="12" r="3.2"></circle>'
    . '<path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"></path></svg>'
    . '<span>Ajustes</span></a>'
    . '</nav>'

    // ------------------------------------------------------------- sesión
    . '<div class="cuenta__sesion">'
    . '<form method="post" action="/logout" class="cuenta__form">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<button type="submit" class="boton boton--claro boton--fila">'
    . '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path></svg>'
    . 'Cerrar sesión</button></form>'

    . '<button type="button" class="boton boton--peligro boton--fila" data-abrir="baja-cuenta">'
    . '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path></svg>'
    . 'Eliminar cuenta</button>'
    . '</div>'
    . '</div>'

    // ------------------------------------------------- diálogo de baja
    . '<dialog class="dialogo" id="baja-cuenta" aria-labelledby="baja-titulo">'
    . '<form method="post" action="/perfil/eliminar">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<h2 id="baja-titulo">Eliminar mi cuenta</h2>'
    . '<p>Se borran tu foto, tus direcciones y el acceso. Los pedidos ya
        realizados se conservan por obligaciones fiscales y de garantía.</p>'
    . '<label class="campo"><span class="campo__etiqueta">Escribe <strong>ELIMINAR</strong> para confirmar</span>'
    . '<input class="campo__control" name="confirmacion" required autocomplete="off" placeholder="ELIMINAR"></label>'
    . '<label class="campo"><span class="campo__etiqueta">Motivo (opcional)</span>'
    . '<input class="campo__control" name="motivo" maxlength="120" placeholder="Ya no lo necesito"></label>'
    . '<div class="dialogo__acciones">'
    . '<button type="button" class="boton boton--claro" data-cerrar>Cancelar</button>'
    . '<button type="submit" class="boton boton--peligro">Eliminar definitivamente</button>'
    . '</div></form></dialog>';

require __DIR__ . '/../layout.php';
