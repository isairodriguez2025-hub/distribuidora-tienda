<?php
/**
 * Permisos del dispositivo. Son preferencias de este equipo, así que se
 * guardan en localStorage y no en la cuenta.
 *
 * @var callable $e
 */
$permisos = [
    ['camara', 'Cámara', 'Escanear códigos de barras y tomar fotos de productos.', 'face'],
    ['ubicacion', 'Compartir ubicación', 'Dirección de entrega y seguimiento del repartidor en vivo.', 'pin'],
    ['microfono', 'Micrófono', 'Notas de voz en el chat y llamadas con el proveedor.', 'mic'],
    ['notificaciones', 'Notificaciones', 'Pedidos, mensajes y avisos de la cuenta.', 'bell'],
    ['galeria', 'Fotos y videos', 'Subir comprobantes, evidencias de entrega y fotos de perfil.', 'image'],
];

$iconos = [
    'face'  => '<path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"></path><circle cx="12" cy="12" r="3.4"></circle>',
    'pin'   => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Z"></path><circle cx="12" cy="10" r="2.6"></circle>',
    'mic'   => '<rect x="9" y="3" width="6" height="11" rx="3"></rect><path d="M5 11a7 7 0 0 0 14 0"></path><path d="M12 18v3"></path>',
    'bell'  => '<path d="M18 9a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7"></path><path d="M10.5 20a2 2 0 0 0 3 0"></path>',
    'image' => '<rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="8.5" cy="9.5" r="1.6"></circle><path d="m21 16-5-5-9 9"></path>',
];

$filas = '';
foreach ($permisos as [$clave, $titulo, $descripcion, $icono]) {
    $filas .= '<label class="opcion opcion--permiso">'
        . '<span class="opcion__icono" aria-hidden="true">'
        . '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'
        . $iconos[$icono] . '</svg></span>'
        . '<span class="opcion__texto"><strong>' . $e($titulo) . '</strong>'
        . '<small>' . $e($descripcion) . '</small></span>'
        . '<span class="switch"><input type="checkbox" data-permiso="' . $e($clave) . '" aria-label="' . $e($titulo) . '">'
        . '<span class="switch__pista" aria-hidden="true"></span></span>'
        . '</label>';
}

$contenido = '<div class="ajustes">'
    . '<a class="volver" href="/perfil/ajustes">‹ Ajustes</a>'
    . '<h1>Permisos del dispositivo</h1>'
    . '<p class="pista" style="margin:-4px 0 14px"><a href="/perfil">Mi perfil</a></p>'
    . '<p class="pista" style="margin:-8px 0 16px">Estos permisos se guardan en este equipo, no en tu cuenta. '
    . 'Si los desactivas, Ares te los vuelve a pedir la próxima vez que los necesite.</p>'
    . '<section class="carta ajustes__grupo" aria-label="Permisos">' . $filas . '</section>'
    . '</div>';

$scripts = <<<'JS'
(function () {
  var CLAVE = 'ares-permisos';
  var guardado = {};
  try { guardado = JSON.parse(localStorage.getItem(CLAVE) || '{}'); } catch (e) { guardado = {}; }

  document.querySelectorAll('[data-permiso]').forEach(function (c) {
    var clave = c.getAttribute('data-permiso');
    c.checked = guardado[clave] !== false;
    c.addEventListener('change', function () {
      guardado[clave] = c.checked;
      try { localStorage.setItem(CLAVE, JSON.stringify(guardado)); } catch (e) {}
      if (typeof window.aresToast === 'function') {
        window.aresToast(c.checked ? 'Permiso activado en este equipo' : 'Permiso desactivado en este equipo');
      }
    });
  });
})();
JS;

require __DIR__ . '/../layout.php';
