<?php
/**
 * Ajustes: lo que antes vivía en "Más…" más la personalización de la app.
 *
 * @var callable $e
 * @var string   $csrf
 * @var string   $tema
 * @var bool     $ok
 */
$oscuro = $tema === 'oscuro';

$contenido = '<div class="ajustes">'
    . '<a class="volver" href="/perfil">‹ Mi cuenta</a>'
    . '<h1>Ajustes</h1>'

    . ($ok ? '<div class="aviso aviso--ok" role="status">Preferencias guardadas.</div>' : '')

    . '<section class="cuenta__mas" aria-label="Legal y privacidad">'
    . '<a class="fila-opcion" href="/perfil/privacidad">'
    . '<span>Privacidad</span><span class="fila-opcion__flecha" aria-hidden="true">›</span></a>'
    . '<a class="fila-opcion" href="/terminos">'
    . '<span>Términos y condiciones</span><span class="fila-opcion__flecha" aria-hidden="true">›</span></a>'
    . '</section>'

    . '<section class="carta ajustes__grupo" style="margin-top:16px">'
    . '<h2 style="margin:0 0 4px">Personalización de la app</h2>'
    . '<p class="pista" style="margin:0 0 6px">Elige cómo se ve Ares en este dispositivo.</p>'
    . '<form method="post" action="/perfil/tema" id="form-tema">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label class="opcion">'
    . '<span class="opcion__texto"><strong>Modo oscuro</strong>'
    . '<small>Fondo negro y texto claro. Se guarda en tu cuenta, no solo en este equipo.</small></span>'
    . '<span class="switch">'
    . '<input type="checkbox" name="tema" value="oscuro" id="tema-oscuro"' . ($oscuro ? ' checked' : '') . '>'
    . '<span class="switch__pista" aria-hidden="true"></span>'
    . '</span></label>'
    . '<noscript><div class="ajustes__acciones"><button type="submit" class="boton">Guardar</button></div></noscript>'
    . '</form>'
    . '</section>'

    . '<p class="ajustes__nota">El modo oscuro se aplica al instante y se recuerda
       en tu cuenta: si entras desde otro equipo, lo verás igual.</p>'
    . '</div>';

require __DIR__ . '/../layout.php';
