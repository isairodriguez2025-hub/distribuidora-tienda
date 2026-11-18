<?php
/**
 * Agendar: listas de pedido con día y hora.
 *
 * @var callable    $e
 * @var string      $csrf
 * @var array       $listas
 * @var array       $productos
 * @var string|null $mensaje
 * @var string|null $error
 */

$ETIQUETA = ['activa' => 'Agendada', 'pausada' => 'Pausada', 'cancelada' => 'Cancelada'];

$opciones = '';
foreach ($productos as $p) {
    $opciones .= '<option value="' . $e((string) $p['uuid']) . '" data-minimo="' . (int) ($p['cantidad_minima'] ?? 1) . '">'
        . $e((string) $p['nombre']) . ' · ' . $e((string) $p['mayorista'])
        . ' · $' . number_format((float) $p['precio_base'], 2) . '</option>';
}

$fichas = '';
foreach ($listas as $l) {
    $estado = (string) $l['estado'];
    $color = match ($estado) {
        'activa' => '#1f4d3a', 'pausada' => '#a56a12', default => '#b91c1c',
    };

    $cuando = $l['fecha_deseada'] !== null
        ? date('d/m/Y', strtotime((string) $l['fecha_deseada'])) . ' a las ' . substr((string) $l['hora'], 0, 5)
        : date('d/m/Y H:i', strtotime((string) $l['proximo_envio']));

    $acciones = $estado === 'activa'
        ? '<form method="post" action="/programados/' . (int) $l['id'] . '/pausada"><input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
          . '<button type="submit" class="boton boton--claro boton--mini">Pausar</button></form>'
        : ($estado === 'pausada'
            ? '<form method="post" action="/programados/' . (int) $l['id'] . '/activa"><input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
              . '<button type="submit" class="boton boton--claro boton--mini">Reactivar</button></form>'
            : '');

    if ($estado !== 'cancelada') {
        $acciones .= '<form method="post" action="/programados/' . (int) $l['id'] . '/cancelada"><input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
            . '<button type="submit" class="boton-texto boton-texto--rojo">Cancelar</button></form>';
    }

    $fichas .= '<article class="lista">'
        . '<div class="lista__cabeza">'
        . '<div><h3>' . $e((string) $l['mayorista']) . '</h3>'
        . '<p>' . (int) $l['lineas'] . ' producto' . ((int) $l['lineas'] === 1 ? '' : 's') . '</p></div>'
        . '<span class="lista__estado" style="color:' . $color . '">' . ($ETIQUETA[$estado] ?? $estado) . '</span>'
        . '</div>'
        . '<div class="lista__cuando">'
        . '<span class="lista__etiqueta">Entrega</span>'
        . '<strong>' . $e($cuando) . '</strong>'
        . '</div>'
        . '<div class="lista__acciones">' . $acciones . '</div>'
        . '</article>';
}

if ($fichas === '') {
    $fichas = '<div class="vacio">Todavía no tienes listas. Crea la primera y elige el día en que la quieres.</div>';
}

$contenido = '<header class="pagina-cabecera">'
    . '<h1>Agendar</h1>'
    . '<p>Arma tu lista de productos y elige el día y la hora en que la quieres recibir.</p>'
    . '</header>'
    . ($mensaje !== null ? '<div class="aviso aviso--ok">' . $e($mensaje) . '</div>' : '')
    . ($error !== null ? '<div class="aviso aviso--error">' . $e($error) . '</div>' : '')

    . '<div class="agendar-cabeza">'
    . '<h2 class="fin-subtitulo">Mis listas</h2>'
    . '<button type="button" class="boton" data-abrir="lista-nueva">Crear nueva lista</button>'
    . '</div>'
    . '<div class="lista-grid">' . $fichas . '</div>'

    . '<dialog class="dialogo dialogo--ancho" id="lista-nueva" aria-labelledby="lista-nueva-titulo">'
    . '<h2 id="lista-nueva-titulo">Tarjeta de pedido</h2>'
    . '<form method="post" action="/agendar">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<div class="fila">'
    . '<label class="campo"><span>¿Qué día lo quieres?</span>'
    . '<input type="date" name="fecha" required min="' . date('Y-m-d') . '" value="' . date('Y-m-d', strtotime('+3 days')) . '"></label>'
    . '<label class="campo"><span>¿A qué hora?</span>'
    . '<input type="time" name="hora" required value="09:00"></label>'
    . '</div>'
    . '<div class="lista-lineas" id="lista-lineas">'
    . '<label class="campo"><span>Producto</span><select name="producto[]" required>' . $opciones . '</select></label>'
    . '<label class="campo campo--corto"><span>Cantidad</span><input type="number" name="cantidad[]" value="1" min="1"></label>'
    . '</div>'
    . '<button type="button" class="boton-texto" id="agregar-linea">+ Agregar otro producto</button>'
    . '<div class="dialogo__acciones">'
    . '<button type="button" class="boton boton--claro" data-cerrar>Cancelar</button>'
    . '<button type="submit" class="boton">Agendar</button>'
    . '</div>'
    . '</form></dialog>'
    . '<script nonce="' . $e($nonce) . '">'
    . '(function () {'
    . 'var caja = document.getElementById("lista-lineas");'
    . 'var boton = document.getElementById("agregar-linea");'
    . 'if (!caja || !boton) return;'
    . 'boton.addEventListener("click", function () {'
    . 'var copia = caja.cloneNode(true);'
    . 'copia.removeAttribute("id");'
    . 'var sel = copia.querySelector("select"); if (sel) sel.selectedIndex = 0;'
    . 'var num = copia.querySelector("input[type=number]"); if (num) num.value = 1;'
    . 'caja.parentNode.insertBefore(copia, boton);'
    . '});'
    . '})();'
    . '</script>';

require __DIR__ . '/../layout.php';
