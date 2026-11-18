<?php
/**
 * Portafolio: los productos guardados del comercio.
 *
 * @var callable    $e
 * @var string      $csrf
 * @var array       $favoritos
 * @var array       $sugerencias
 * @var string|null $mensaje
 * @var string|null $error
 */

$estrellas = '<svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true">'
    . '<path d="m12 3.6 2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L3.5 9.8l5.9-.9Z"></path></svg>';

$fichas = '';
foreach ($favoritos as $f) {
    $agotado = (int) $f['stock_disponible'] === 0;

    $accion = $agotado
        ? '<span class="portafolio__agotado">Agotado por ahora</span>'
        : '<form method="post" action="/carrito/agregar" class="portafolio__form">'
          . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
          . '<input type="hidden" name="producto" value="' . $e($f['uuid']) . '">'
          . '<input type="hidden" name="cantidad" value="' . (int) $f['cantidad_minima'] . '">'
          . '<input type="hidden" name="volver" value="/portafolio">'
          . '<button type="submit" class="boton portafolio__pedir">Volver a pedir</button>'
          . '</form>';

    $fichas .= '<article class="portafolio">'
        . '<span class="portafolio__estrella" title="Está en tu portafolio">' . $estrellas . '</span>'
        . '<h3>' . $e((string) $f['nombre']) . '</h3>'
        . '<p class="portafolio__mayorista">' . $e((string) $f['mayorista']) . '</p>'
        . '<dl class="portafolio__datos">'
        . '<div><dt>Precio</dt><dd>$' . number_format((float) $f['precio_base'], 2) . '</dd></div>'
        . '<div><dt>Unidades</dt><dd>' . ($agotado ? '—' : (int) $f['stock_disponible']) . '</dd></div>'
        . '</dl>'
        . $accion
        . '<form method="post" action="/portafolio/quitar" class="portafolio__quitar">'
        . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
        . '<input type="hidden" name="producto" value="' . $e($f['uuid']) . '">'
        . '<button type="submit">Quitar del portafolio</button></form>'
        . '</article>';
}

if ($fichas === '') {
    $fichas = '<div class="vacio">Todavía no has guardado productos. '
        . 'Cuando encuentres algo que te sirva, guárdalo y aparecerá aquí.</div>';
}

$sugerencia = '';
foreach ($sugerencias as $s) {
    $sugerencia .= '<li>' . $e((string) $s['producto'])
        . ' <span>hace ' . (int) $s['dias'] . ' días de tu última compra</span></li>';
}

$bloqueSugerencia = $sugerencia !== ''
    ? '<section class="portafolio-sugerencia"><h2>Es momento de reabastecer</h2>'
      . '<ul>' . $sugerencia . '</ul>'
      . '<a class="boton boton--claro" href="/buscar">Buscar en el catálogo</a></section>'
    : '';

$contenido = '<header class="pagina-cabecera">'
    . '<h1>Portafolio</h1>'
    . '<p>Los productos que guardaste, con su mayorista, precio y existencias.</p>'
    . '</header>'
    . ($mensaje !== null ? '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>' : '')
    . ($error !== null ? '<div class="aviso aviso--error">' . $e($error) . '</div>' : '')
    . '<div class="portafolio-grid">' . $fichas . '</div>'
    . $bloqueSugerencia;

require __DIR__ . '/../layout.php';
