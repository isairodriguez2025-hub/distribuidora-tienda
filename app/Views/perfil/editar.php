<?php
/**
 * Editar cuenta: foto, nombre, correo y dirección principal.
 *
 * @var callable $e
 * @var string   $csrf
 * @var string   $rol
 * @var array<string,string> $errores
 * @var array<string,string> $valores
 * @var string|null $avatarUrl
 * @var bool     $tieneFoto
 * @var mixed    $latitud
 * @var mixed    $longitud
 */
$v = static fn (string $k): string => (string) ($valores[$k] ?? '');
$err = static function (string $k) use ($errores, $e): string {
    return isset($errores[$k]) ? '<span class="campo__error">' . $e($errores[$k]) . '</span>' : '';
};

$foto = $avatarUrl !== null
    ? '<img class="editor__foto" src="' . $e($avatarUrl) . '" alt="" width="96" height="96">'
    : '<span class="editor__iniciales" aria-hidden="true">' . $e(\App\Modules\Perfil\AvatarService::iniciales($v('nombre'))) . '</span>';

$lat = (string) $latitud !== '' ? (string) $latitud : '19.432600';
$lng = (string) $longitud !== '' ? (string) $longitud : '-99.133200';

$ojo = static fn (string $id): string => '<button type="button" class="ojo" data-para="' . $id . '" aria-pressed="false" aria-label="Mostrar contraseña" title="Mostrar contraseña">'
    . '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>';

$contenido = '<div class="editor">'
    . '<a class="volver" href="/perfil">‹ Mi cuenta</a>'
    . '<h1>Editar cuenta</h1>'

    // ------------------------------------------------------------- foto
    . '<section class="carta editor__bloque">'
    . '<h2>Foto de perfil</h2>'
    . '<div class="editor__avatar">' . $foto
    . '<div class="editor__avatar-acciones">'
    . '<form method="post" action="/perfil/avatar" enctype="multipart/form-data" class="editor__subida">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label class="boton boton--claro boton--archivo">Elegir imagen'
    . '<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required></label>'
    . '<button type="submit" class="boton">Subir</button>'
    . '</form>'
    . ($tieneFoto
        ? '<form method="post" action="/perfil/avatar/quitar">'
          . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
          . '<button type="submit" class="boton boton--texto">Quitar foto</button></form>'
        : '')
    . '<p class="pista">JPG, PNG o WebP · máximo 2 MB. Se recorta en cuadrado.</p>'
    . '</div></div></section>'

    // ------------------------------------------------------------- datos
    . '<form method="post" action="/perfil/editar" class="editor__form">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'

    . '<section class="carta editor__bloque">'
    . '<h2>Datos de la cuenta</h2>'
    . '<label class="campo"><span class="campo__etiqueta">Nombre</span>'
    . '<input class="campo__control" name="nombre" value="' . $e($v('nombre')) . '" required autocomplete="organization">'
    . $err('nombre') . '</label>'
    . '<label class="campo"><span class="campo__etiqueta">Correo</span>'
    . '<input class="campo__control" type="email" name="email" value="' . $e($v('email')) . '" required autocomplete="email">'
    . $err('email') . '</label>'
    . '<label class="campo"><span class="campo__etiqueta">Teléfono</span>'
    . '<input class="campo__control" type="tel" name="telefono" value="' . $e($v('telefono')) . '" autocomplete="tel" inputmode="tel">'
    . '</label>'
    . '</section>'

    . '<p class="pista" style="margin:0 0 16px">La contraseña y la baja de la cuenta se manejan en <a href="/perfil/seguridad">Ajustes › Seguridad</a>.</p>'

    // --------------------------------------------------------- dirección
    . '<section class="carta editor__bloque">'
    . '<h2>Dirección de entrega</h2>'
    . '<label class="campo"><span class="campo__etiqueta">Calle y número</span>'
    . '<input class="campo__control" name="calle" value="' . $e($v('calle')) . '" autocomplete="address-line1" placeholder="Av. Insurgentes Sur 1234"></label>'
    . '<div class="editor__doble">'
    . '<label class="campo"><span class="campo__etiqueta">Colonia</span>'
    . '<input class="campo__control" name="colonia" value="' . $e($v('colonia')) . '" autocomplete="address-line2"></label>'
    . '<label class="campo"><span class="campo__etiqueta">Ciudad</span>'
    . '<input class="campo__control" name="ciudad" value="' . $e($v('ciudad')) . '" autocomplete="address-level2"></label>'
    . '</div>'
    . '<div class="editor__tres">'
    . '<label class="campo"><span class="campo__etiqueta">Estado</span>'
    . '<input class="campo__control" name="estado_mx" value="' . $e($v('estado_mx')) . '" autocomplete="address-level1"></label>'
    . '<label class="campo"><span class="campo__etiqueta">C.P.</span>'
    . '<input class="campo__control" name="cp" value="' . $e($v('cp')) . '" inputmode="numeric" maxlength="5" autocomplete="postal-code"></label>'
    . '<label class="campo"><span class="campo__etiqueta">Referencias</span>'
    . '<input class="campo__control" name="referencias" value="' . $e($v('referencias')) . '" placeholder="Portón negro"></label>'
    . '</div>'
    . '<input type="hidden" name="latitud" id="latitud" value="' . $e($lat) . '">'
    . '<input type="hidden" name="longitud" id="longitud" value="' . $e($lng) . '">'
    . '<div class="campo-mapa">'
    . '<button type="button" class="boton boton--claro" id="ubicar-mapa">'
    . '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Z"></path><circle cx="12" cy="10" r="2.6"></circle></svg>'
    . 'Ubicar dirección en el mapa</button>'
    . '<div id="mapa-direccion" class="mapa mapa--form" hidden></div>'
    . '<p class="campo__pista">Toca el mapa o arrastra el marcador para ajustar el punto exacto de entrega.</p>'
    . '</div>'
    . '</section>'

    . '<div class="editor__acciones">'
    . '<a class="boton boton--claro" href="/perfil">Cancelar</a>'
    . '<button type="submit" class="boton">Guardar cambios</button>'
    . '</div>'
    . '</form></div>';

$scripts = <<<'JS'
(function () {
  var boton = document.getElementById('ubicar-mapa');
  var caja = document.getElementById('mapa-direccion');
  var lat = document.getElementById('latitud');
  var lng = document.getElementById('longitud');
  if (!boton || !caja || !lat || !lng || typeof L === 'undefined') return;

  var mapa = null, marcador = null;

  function fijar(ll) {
    lat.value = ll.lat.toFixed(6);
    lng.value = ll.lng.toFixed(6);
    if (marcador) marcador.setLatLng(ll);
  }

  boton.addEventListener('click', function () {
    caja.hidden = false;
    var inicio = [parseFloat(lat.value) || 19.4326, parseFloat(lng.value) || -99.1332];
    if (!mapa) {
      mapa = L.map('mapa-direccion').setView(inicio, 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap'
      }).addTo(mapa);
      marcador = L.marker(inicio, { draggable: true }).addTo(mapa);
      marcador.on('dragend', function () { fijar(marcador.getLatLng()); });
      mapa.on('click', function (e) { fijar(e.latlng); });
    } else {
      mapa.setView(inicio, 15);
      marcador.setLatLng(inicio);
    }
    setTimeout(function () { mapa.invalidateSize(); }, 60);
  });
})();
JS;

require __DIR__ . '/../layout.php';
