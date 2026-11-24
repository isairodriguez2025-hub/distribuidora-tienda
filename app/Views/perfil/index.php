<?php
/**
 * Mi perfil: cabecera con la sesión, ficha ancha con la identidad y los datos de
 * contacto, y los tres accesos de la cuenta. La contraseña y la baja viven en
 * Ajustes › Seguridad.
 *
 * @var callable $e
 * @var string   $csrf
 * @var string   $rol
 * @var array|null $perfil
 * @var array    $direcciones
 * @var array    $kyc
 * @var string|null $avatarUrl
 * @var string|null $mensaje
 * @var array{promedio: ?float, total: int} $calificacion
 * @var array{porEstrella: array<int,int>, comentarios: array<int, array>} $desglose
 */
$nombresRol = [
    'admin_total'    => 'Administrador',
    'admin_kyc'      => 'Administrador · Verificación',
    'admin_soporte'  => 'Administrador · Soporte',
    'admin_finanzas' => 'Administrador · Finanzas',
    'comercio'       => 'Comercio',
    'mayorista'      => 'Mayorista',
    'repartidor'     => 'Repartidor',
];
$etiquetaRol = $nombresRol[$rol] ?? ucfirst(str_replace('_', ' ', $rol));

$nombre = (string) ($perfil['nombre_negocio'] ?? $perfil['nombre_comercial'] ?? $perfil['nombre'] ?? 'Mi cuenta');
$correo = (string) ($perfil['email'] ?? '');
$telefono = (string) ($perfil['telefono'] ?? '');

$meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$creada = (string) ($perfil['cuenta_creada'] ?? '');
$miembro = '';
if ($creada !== '') {
    $ts = strtotime($creada);
    if ($ts !== false) {
        $miembro = $meses[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
    }
}

$direccion = '';
if (!empty($direcciones)) {
    $d = $direcciones[0];
    $partes = array_filter([
        (string) ($d['calle'] ?? ''),
        (string) ($d['colonia'] ?? ''),
        (string) ($d['ciudad'] ?? ''),
    ], static fn (string $v): bool => trim($v) !== '');
    $direccion = implode(', ', $partes);
}

$foto = $avatarUrl !== null
    ? '<img class="ficha__foto" src="' . $e($avatarUrl) . '" alt="Foto de ' . $e($nombre) . '" width="168" height="168">'
    : '<span class="ficha__iniciales" aria-hidden="true">' . $e(\App\Modules\Perfil\AvatarService::iniciales($nombre)) . '</span>';

$promedio = $calificacion['promedio'] ?? null;
$total = (int) ($calificacion['total'] ?? 0);

$botonNota = $total > 0
    ? '<button type="button" class="ficha__nota--boton" data-calificar-abrir aria-haspopup="dialog">'
      . '<span class="ficha__estrella" aria-hidden="true">★</span>'
      . '<span>' . $e(number_format((float) $promedio, 1, '.', '')) . '</span>'
      . '<span class="visualmente-oculto">de 5, </span>'
      . '<span class="pista">' . $total . ' calificaciones</span>'
      . '</button>'
    : '<span class="pista">Todavía sin calificaciones</span>';

$verificado = !empty($kyc['completo']);
$insignia = $verificado
    ? '<span class="ficha__verif"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7"></path></svg>Cuenta verificada</span>'
    : '<span class="chip chip--alerta">Verificación pendiente</span>';

$aviso = '';
if (isset($_GET['ok']) && $_GET['ok'] === 'datos') {
    $aviso = '<div class="aviso aviso--ok" role="status">Tus datos se guardaron.</div>';
} elseif (isset($_GET['cal']) && $_GET['cal'] === 'ok') {
    $aviso = '<div class="aviso aviso--ok" role="status">Gracias, tu calificación quedó registrada.</div>';
} elseif ($mensaje !== null) {
    $aviso = '<div class="aviso aviso--ok" role="status">' . $e($mensaje) . '</div>';
}

/* ------------------------------------------------ desglose de calificaciones */
$mayor = max(1, max($desglose['porEstrella'] ?? [1]));
$barras = '';
foreach ([5, 4, 3, 2, 1] as $estrella) {
    $n = (int) ($desglose['porEstrella'][$estrella] ?? 0);
    $ancho = (int) round(($n / $mayor) * 100);
    $barras .= '<div class="calif__barra"><span>' . $estrella . '</span>'
        . '<span class="calif__pista"><span style="width:' . $ancho . '%"></span></span>'
        . '<span>' . $n . '</span></div>';
}

$comentarios = '';
foreach (($desglose['comentarios'] ?? []) as $c) {
    $fecha = strtotime((string) $c['created_at']);
    $comentarios .= '<article class="comentario">'
        . '<header class="comentario__cab">'
        . '<span class="comentario__autor">' . $e((string) $c['autor']) . '</span>'
        . '<span class="comentario__fecha">' . ($fecha !== false ? $e(date('d/m/Y', $fecha)) : '') . '</span>'
        . '</header>'
        . '<div class="calif__estrellas" aria-label="' . (int) $c['puntaje'] . ' de 5">'
        . str_repeat('★', (int) $c['puntaje']) . str_repeat('☆', 5 - (int) $c['puntaje'])
        . '</div>'
        . '<p class="comentario__texto">' . $e((string) $c['comentario']) . '</p>'
        . '</article>';
}
if ($comentarios === '') {
    $comentarios = '<p class="pista" style="margin-top:14px">Aún no hay comentarios escritos.</p>';
}

$contenido = '<div class="cuenta cuenta--ancha">'

    // ------------------------------------------------------------ cabecera
    . '<header class="perfil-cab">'
    . '<div><h1>Mi perfil</h1>'
    . '<p>Tus datos, la verificación de la cuenta y las opciones de la app.</p></div>'
    . '<form method="post" action="/logout" class="perfil-salir">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<button type="submit" class="boton boton--claro">'
    . '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path></svg>'
    . 'Cerrar sesión</button></form>'
    . '</header>'
    . $aviso

    // --------------------------------------------------------------- ficha
    . '<section class="ficha ficha--perfil" aria-labelledby="ficha-nombre">'
    . '<div class="ficha__foto-caja">' . $foto . '</div>'
    . '<div class="ficha__grid">'
    . '<div class="ficha__fila">'
    . '<h1 class="ficha__nombre" id="ficha-nombre">' . $e($nombre) . '</h1>'
    . $botonNota
    . '</div>'
    . '<div class="ficha__fila"><span class="chip chip--rol">' . $e($etiquetaRol) . '</span>' . $insignia . '</div>'
    . '<dl class="ficha__campo"><dt>Correo</dt><dd>' . $e($correo) . '</dd></dl>'
    . '<dl class="ficha__campo"><dt>Antigüedad</dt><dd>' . ($miembro !== '' ? 'Miembro desde ' . $e($miembro) : '—') . '</dd></dl>'
    . '<dl class="ficha__campo"><dt>Teléfono</dt><dd>' . ($telefono !== '' ? $e($telefono) : 'Sin registrar') . '</dd></dl>'
    . '<dl class="ficha__campo"><dt>Dirección principal</dt><dd>' . ($direccion !== '' ? $e($direccion) : 'Sin registrar') . '</dd></dl>'
    . '</div>'
    . '<a class="boton ficha__editar ficha__editar--esquina" href="/perfil/editar">'
    . '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>'
    . 'Editar</a>'
    . '</section>'

    // ------------------------------------------------------------- accesos
    . '<nav class="perfil-acciones" aria-label="Accesos de la cuenta">'
    . '<a class="perfil-accion" href="/kyc"><strong>Verificación</strong>'
    . '<small>Identidad, documentos y estado de la cuenta</small></a>'
    . '<a class="perfil-accion" href="/ayuda"><strong>Ayuda</strong>'
    . '<small>Preguntas frecuentes, soporte y contacto</small></a>'
    . '<a class="perfil-accion" href="/perfil/ajustes"><strong>Ajustes</strong>'
    . '<small>Privacidad, permisos, tema y seguridad</small></a>'
    . '</nav>'
    . '</div>'

    // ------------------------------------- panel lateral de calificaciones
    . '<div class="cajon" id="panel-calificacion" aria-hidden="true">'
    . '<div class="cajon__velo" data-calificar-cerrar></div>'
    . '<aside class="cajon__caja" role="dialog" aria-modal="true" aria-labelledby="calif-titulo">'
    . '<div class="cajon__cab"><h2 class="cajon__titulo" id="calif-titulo">Calificaciones</h2>'
    . '<button type="button" class="cajon__vaciar" data-calificar-cerrar aria-label="Cerrar">'
    . '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg>'
    . '</button></div>'
    . '<div class="cajon__scroll">'
    . '<div class="calif__resumen">'
    . '<div><div class="calif__nota">' . ($promedio !== null ? $e(number_format((float) $promedio, 1, '.', '')) : '—') . '</div>'
    . '<div class="pista">' . $total . ' calificaciones</div></div>'
    . '<div class="calif__estrellas" aria-hidden="true">★★★★★</div>'
    . '</div>'
    . '<div class="calif__barras">' . $barras . '</div>'
    . '<h3 style="margin:10px 0 0;font-size:14px">Comentarios recibidos</h3>'
    . $comentarios
    . '</div></aside></div>';

$scripts = <<<'JS'
(function () {
  var panel = document.getElementById('panel-calificacion');
  if (!panel) return;
  var caja = panel.querySelector('.cajon__caja');
  function abrir() {
    panel.classList.add('cajon--abierto');
    panel.setAttribute('aria-hidden', 'false');
    document.body.classList.add('sin-scroll');
    caja.focus();
  }
  function cerrar() {
    panel.classList.remove('cajon--abierto');
    panel.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('sin-scroll');
  }
  document.querySelectorAll('[data-calificar-abrir]').forEach(function (b) {
    b.addEventListener('click', abrir);
  });
  document.querySelectorAll('[data-calificar-cerrar]').forEach(function (b) {
    b.addEventListener('click', cerrar);
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrar(); });
})();
JS;

require __DIR__ . '/../layout.php';
