<?php
/**
 * Panel de administración: métricas clicables y lo que pide atención hoy.
 *
 * @var callable $e
 * @var int      $totalUsuarios
 * @var int      $totalComercios
 * @var int      $totalMayoristas
 * @var int      $pedidosHoy
 * @var int      $reclamosAbiertos
 * @var int      $kycPendientes
 */

$metrica = static function (string $href, string $valor, string $etiqueta, string $nota = '', string $clase = ''): string {
    return '<a class="metrica' . ($clase !== '' ? ' ' . $clase : '') . '" href="' . $href . '">'
        . '<span class="metrica__valor">' . $valor . '</span>'
        . '<span class="metrica__etiqueta">' . $etiqueta . '</span>'
        . ($nota !== '' ? '<span class="metrica__nota">' . $nota . '</span>' : '')
        . '</a>';
};

$pendientes = '';
if ($kycPendientes > 0) {
    $pendientes .= '<a class="atencion" href="/admin/kyc">'
        . '<span class="atencion__punto" aria-hidden="true"></span>'
        . '<span class="atencion__texto"><strong>' . $kycPendientes . ' verificación'
        . ($kycPendientes === 1 ? '' : 'es') . ' pendiente' . ($kycPendientes === 1 ? '' : 's') . '</strong>'
        . '<span>Revisa los documentos para publicar sus productos.</span></span>'
        . '<span class="atencion__accion">Revisar</span></a>';
}

if ($reclamosAbiertos > 0) {
    $pendientes .= '<a class="atencion atencion--alerta" href="/admin/reclamos">'
        . '<span class="atencion__punto" aria-hidden="true"></span>'
        . '<span class="atencion__texto"><strong>' . $reclamosAbiertos . ' reclamo'
        . ($reclamosAbiertos === 1 ? '' : 's') . ' abierto' . ($reclamosAbiertos === 1 ? '' : 's') . '</strong>'
        . '<span>Un pedido espera respuesta del equipo.</span></span>'
        . '<span class="atencion__accion">Atender</span></a>';
}

$bloqueAtencion = $pendientes !== ''
    ? '<section class="admin-panel"><h2>Requiere tu atención</h2><div class="admin-atencion">' . $pendientes . '</div></section>'
    : '<section class="admin-panel admin-panel--ok"><h2>Requiere tu atención</h2>'
    . '<p class="admin-al-dia"><span aria-hidden="true">✓</span> Nada pendiente: verificaciones y reclamos al día.</p></section>';

$contenido = '<header class="pagina-cabecera"><h1>Panel de administración</h1>'
    . '<p>Resumen de la operación y lo que necesita una decisión hoy.</p></header>'
    . '<div class="admin-grid">'
    . '<section class="admin-panel admin-panel--metricas"><h2>Comunidad</h2>'
    . '<div class="admin-kpis">'
    . $metrica('/admin/usuarios', (string) $totalUsuarios, 'Usuarios', 'Ver todos')
    . $metrica('/admin/usuarios?rol=comercio', (string) $totalComercios, 'Comercios', 'Ver lista')
    . $metrica('/admin/usuarios?rol=mayorista', (string) $totalMayoristas, 'Mayoristas', 'Ver lista')
    . $metrica('/admin/kyc', (string) $kycPendientes, 'KYC pendientes', $kycPendientes > 0 ? 'Requieren revisión' : 'Al día', $kycPendientes > 0 ? 'metrica--alerta' : '')
    . $metrica('/admin/reclamos', (string) $reclamosAbiertos, 'Reclamos abiertos', $reclamosAbiertos > 0 ? 'Sin resolver' : 'Al día', $reclamosAbiertos > 0 ? 'metrica--alerta' : '')
    . $metrica('/admin/parametros', (string) $pedidosHoy, 'Pedidos hoy', 'Ver operación')
    . '</div></section>'
    . $bloqueAtencion
    . '</div>'
    . '<div class="admin-acciones">'
    . '<a class="boton boton--claro" href="/admin/usuarios">Gestionar usuarios</a>'
    . '<a class="boton boton--claro" href="/admin/parametros">Parámetros de la plataforma</a>'
    . '<a class="boton boton--claro" href="/admin/kyc">Cola de verificación</a>'
    . '</div>';

require __DIR__ . '/../layout.php';
