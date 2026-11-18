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

$aviso = '';
if (isset($_GET['ok']) && $_GET['ok'] === 'datos') {
    $aviso = '<div class="aviso aviso--ok" role="status">Tus datos se guardaron.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'confirmacion') {
    $aviso = '<div class="aviso aviso--error" role="alert">Para eliminar la cuenta escribe ELIMINAR.</div>';
} elseif ($mensaje !== null) {
    $aviso = '<div class="aviso aviso--ok" role="status">' . $e($mensaje) . '</div>';
}

$contenido = '<div class="cuenta">'
    . $aviso

    // ---------------------------------------------------------- ficha
    . '<section class="ficha" aria-labelledby="ficha-nombre">'
    . '<div class="ficha__foto-caja">' . $foto . '</div>'
    . '<div class="ficha__datos">'
    . '<h1 class="ficha__nombre" id="ficha-nombre">' . $e($nombre) . '</h1>'
    . '<span class="chip chip--rol">' . $e($etiquetaRol) . '</span>'
    . '<p class="ficha__correo">' . $e($correo) . '</p>'
    . '</div>'
    . '<a class="boton ficha__editar" href="/perfil/editar">'
    . '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>'
    . 'Editar</a>'
    . '</section>'

    // --------------------------------------------------- acciones de sesión
    . '<div class="cuenta__acciones">'
    . '<a class="boton boton--claro boton--accion" href="/kyc">'
    . '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M12 2 4 5v6c0 5 3.4 9.2 8 11 4.6-1.8 8-6 8-11V5Z"></path><path d="m9 12 2 2 4-4"></path></svg>'
    . '<span><strong>Verificación de cuenta</strong><small>Documentos e identidad</small></span>'
    . '<span class="cuenta__flecha" aria-hidden="true">›</span></a>'

    . '<form method="post" action="/logout" class="cuenta__form">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<button type="submit" class="boton boton--claro boton--accion">'
    . '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path></svg>'
    . '<span><strong>Cerrar sesión</strong><small>Salir de este dispositivo</small></span>'
    . '<span class="cuenta__flecha" aria-hidden="true">›</span></button></form>'
    . '</div>'

    // ------------------------------------------------------------- más…
    . '<section class="cuenta__mas" aria-labelledby="mas-titulo">'
    . '<h2 class="cuenta__mas-titulo" id="mas-titulo">Más…</h2>'
    . '<a class="fila-opcion" href="/perfil/privacidad">'
    . '<span>Privacidad</span><span class="fila-opcion__flecha" aria-hidden="true">›</span></a>'
    . '<a class="fila-opcion" href="/terminos">'
    . '<span>Términos y condiciones</span><span class="fila-opcion__flecha" aria-hidden="true">›</span></a>'
    . '<button type="button" class="fila-opcion fila-opcion--peligro" data-abrir="baja-cuenta">'
    . '<span>Eliminar cuenta</span><span class="fila-opcion__flecha" aria-hidden="true">›</span></button>'
    . '</section>'
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
