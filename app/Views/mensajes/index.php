<?php
/**
 * Centro de mensajes: conversaciones y notificaciones del sitio.
 *
 * @var callable $e
 * @var string   $csrf
 * @var string   $tab
 * @var array    $hilos
 * @var array|null $hilo
 * @var string|null $error
 * @var bool     $esAdmin
 * @var int      $yo
 * @var array    $avisos
 * @var int      $eliminados
 */
$error = $error ?? null;
$tab = $tab ?? 'conversaciones';
$avisos = $avisos ?? [];
$eliminados = (int) ($eliminados ?? 0);

$sinLeerAvisos = 0;
foreach ($avisos as $a) {
    if (($a['estado'] ?? '') !== 'leido') {
        $sinLeerAvisos++;
    }
}

// ------------------------------------------------------------- lista de hilos
$lista = '';

foreach ($hilos as $h) {
    $activo = $hilo !== null && (int) $hilo['hilo']['id'] === (int) $h['id'] ? '1' : '0';
    $iniciales = \App\Modules\Perfil\AvatarService::iniciales((string) $h['titulo']);
    $avatar = $h['avatar'] !== null
        ? '<img class="cuenta__foto" src="' . $e($h['avatar']) . '" alt="" width="38" height="38" style="width:38px;height:38px">'
        : '<span class="cuenta__iniciales" style="width:38px;height:38px">' . $e($iniciales) . '</span>';
    $hora = $h['ultimo_at'] !== null ? date('d/m H:i', strtotime((string) $h['ultimo_at'])) : date('d/m', strtotime((string) $h['created_at']));
    $resumen = $h['ultimo'] !== null ? mb_substr((string) $h['ultimo'], 0, 60) : 'Sin mensajes todavía';
    $uuid = (string) $h['uuid'];

    $lista .= '<div class="msg-fila">'
        . '<input class="msg-sel" type="checkbox" name="hilos[]" value="' . $e($uuid) . '" id="sel-' . $e($uuid) . '" aria-label="Seleccionar ' . $e((string) $h['titulo']) . '">'
        . '<a class="msg-hilo" data-activo="' . $activo . '" href="/mensajes?hilo=' . urlencode($uuid) . '">'
        . $avatar
        . '<div style="min-width:0">'
        . '<div class="msg-hilo__nombre">' . $e((string) $h['titulo']) . '</div>'
        . '<div class="msg-hilo__resumen">' . $e($resumen) . '</div>'
        . '</div>'
        . '<div style="display:grid;gap:4px;justify-items:end">'
        . '<span class="msg-hilo__hora">' . $e($hora) . '</span>'
        . ($h['no_leidos'] > 0 ? '<span class="msg-hilo__no-leido">' . (int) $h['no_leidos'] . '</span>' : '')
        . '</div></a>'
        . '<label class="msg-sel__zona" for="sel-' . $e($uuid) . '" aria-hidden="true"></label>'
        . '</div>';
}

if ($lista === '') {
    $lista = '<div class="msg-vacio"><p class="silencio">Todavía no tienes conversaciones.</p>'
        . '<button type="button" class="boton boton--claro" data-abrir="msg-config">Empezar una</button></div>';
}

// ------------------------------------------------------------------- chat
$chat = '<div class="msg-chat msg-chat--vacio">'
    . '<div><h2 style="margin:0 0 6px">Tus mensajes</h2>'
    . '<p class="silencio" style="max-width:340px">Elige una conversación de la izquierda. '
    . 'Aquí solo ves los hilos en los que participas.</p></div></div>';

if ($hilo !== null) {
    $uuid = (string) $hilo['hilo']['uuid'];
    $burbujas = '';
    $ultimoId = 0;

    foreach ($hilo['mensajes'] as $m) {
        $ultimoId = max($ultimoId, (int) $m['id']);
        $mio = (int) $m['autor_id'] === $yo;
        $burbujas .= '<div class="msg-burbuja msg-burbuja--' . ($mio ? 'yo' : 'otro') . '">'
            . nl2br($e((string) $m['cuerpo']))
            . '<span class="msg-burbuja__hora">' . $e(date('H:i', strtotime((string) $m['created_at']))) . '</span></div>';
    }

    if ($burbujas === '') {
        $burbujas = '<p class="silencio" style="text-align:center;margin:auto">Aún no hay mensajes. Escribe el primero.</p>';
    }

    $chat = '<div class="msg-chat" id="chat" data-uuid="' . $e($uuid) . '" data-ultimo="' . $ultimoId . '">'
        . '<div class="msg-chat__head"><strong>' . $e((string) $hilo['titulo']) . '</strong>'
        . ($hilo['hilo']['tipo'] === 'soporte' ? '<span class="etiqueta">soporte</span>' : '')
        . '</div>'
        . '<div class="msg-chat__cuerpo" id="chat-cuerpo">' . $burbujas . '<span id="fin"></span></div>'
        . '<form class="msg-form" method="post" action="/mensajes/enviar">'
        . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
        . '<input type="hidden" name="hilo" value="' . $e($uuid) . '">'
        . '<textarea name="cuerpo" id="cuerpo" placeholder="Escribe un mensaje…" maxlength="2000" required></textarea>'
        . '<button type="submit" style="align-self:flex-end;height:44px">Enviar</button>'
        . '</form></div>';
}

// ------------------------------------------------------------ notificaciones
$listaAvisos = '';

foreach ($avisos as $a) {
    $nueva = ($a['estado'] ?? '') !== 'leido';
    $cuando = date('d/m H:i', strtotime((string) $a['created_at']));
    $cuerpo = trim((string) ($a['cuerpo'] ?? ''));
    $enlace = trim((string) ($a['enlace'] ?? ''));

    $listaAvisos .= '<article class="aviso-item' . ($nueva ? ' aviso-item--nueva' : '') . '">'
        . '<div class="aviso-item__punto" aria-hidden="true"></div>'
        . '<div class="aviso-item__cuerpo">'
        . '<div class="aviso-item__cabecera"><strong>' . $e((string) $a['asunto']) . '</strong>'
        . '<span class="aviso-item__hora">' . $e($cuando) . '</span></div>'
        . ($cuerpo !== '' ? '<p>' . nl2br($e($cuerpo)) . '</p>' : '')
        . ($enlace !== '' ? '<a class="aviso-item__enlace" href="' . $e($enlace) . '">Ver detalle ›</a>' : '')
        . '</div></article>';
}

if ($listaAvisos === '') {
    $listaAvisos = '<div class="msg-vacio"><p class="silencio">No tienes notificaciones del sitio.</p>'
        . '<p class="pista">Aquí llegan los avisos de pedidos, pagos y cuenta.</p></div>';
}

// ---------------------------------------------------------------- opciones
$opciones = '';

foreach (($contactos ?? []) as $c) {
    $opciones .= '<option value="' . (int) $c['id'] . '">' . $e((string) $c['nombre']) . ' · ' . $e((string) $c['rol']) . '</option>';
}

$formNueva = $opciones === ''
    ? '<p class="pista">No hay contactos disponibles todavía.</p>'
    : '<form method="post" action="/mensajes/directo">'
      . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
      . '<label class="campo"><span class="campo__etiqueta">Contacto</span>'
      . '<select class="campo__control" name="usuario_id" required>' . $opciones . '</select></label>'
      . '<button type="submit" class="boton" style="width:100%">Abrir chat</button></form>';

// ------------------------------------------------------------------ página
$contenido = '<div class="msg-cabecera">'
    . '<h1>Centro de mensajes</h1>'
    . '<p class="silencio">Conversaciones y avisos del sitio, en un solo lugar.</p>'
    . '</div>'

    . '<nav class="msg-tabs" aria-label="Secciones del centro de mensajes">'
    . '<a class="msg-tab" data-activo="' . ($tab === 'conversaciones' ? '1' : '0') . '" href="/mensajes?tab=conversaciones">'
    . 'Conversaciones<span class="msg-tab__cuenta">' . count($hilos) . '</span></a>'
    . '<a class="msg-tab" data-activo="' . ($tab === 'notificaciones' ? '1' : '0') . '" href="/mensajes?tab=notificaciones">'
    . 'Notificaciones' . ($sinLeerAvisos > 0 ? '<span class="msg-tab__cuenta msg-tab__cuenta--nueva">' . $sinLeerAvisos . '</span>' : '') . '</a>'
    . '</nav>'

    . ($error !== null ? '<div class="aviso aviso--error" style="margin-bottom:14px">' . $e($error) . '</div>' : '')
    . ($eliminados > 0 ? '<div class="aviso aviso--ok" style="margin-bottom:14px">'
        . ($eliminados === 1 ? 'Se eliminó 1 conversación.' : 'Se eliminaron ' . $eliminados . ' conversaciones.')
        . '</div>' : '');

if ($tab === 'notificaciones') {
    $contenido .= '<section class="msg-avisos" aria-label="Notificaciones">' . $listaAvisos . '</section>';
} else {
    $contenido .= '<div class="msg-layout">'
        . '<div class="msg-lista">'
        . '<div class="msg-lista__head">'
        . '<strong>Conversaciones</strong>'
        . '<div class="msg-lista__acciones">'
        . '<button type="button" class="boton-icono" data-abrir="msg-config" title="Configuración" aria-label="Configuración">'
        . '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<circle cx="12" cy="12" r="3.2"></circle>'
        . '<path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"></path></svg>'
        . '</button>'
        . '<button type="button" class="boton-icono boton-icono--peligro" id="btn-borrar" title="Eliminar conversaciones" aria-label="Eliminar conversaciones">'
        . '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path></svg>'
        . '</button>'
        . '</div></div>'

        . '<form method="post" action="/mensajes/eliminar" id="form-borrado">'
        . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
        . $lista
        . '</form>'
        . '</div>'
        . $chat
        . '</div>';
}

// --------------------------------------------------------------- diálogos
$contenido .= '<dialog class="dialogo" id="msg-config" aria-labelledby="msg-config-titulo">'
    . '<h2 id="msg-config-titulo">Configuración de mensajes</h2>'
    . '<p>Empieza una conversación nueva o ajusta cómo quieres recibir avisos.</p>'
    . $formNueva
    . '<div class="dialogo__acciones" style="justify-content:space-between;margin-top:16px">'
    . '<a class="boton boton--claro" href="/perfil/privacidad">Avisos y privacidad</a>'
    . '<button type="button" class="boton boton--claro" data-cerrar>Cerrar</button>'
    . '</div></dialog>'

    . '<dialog class="dialogo" id="confirmar-borrado" aria-labelledby="borrado-titulo">'
    . '<h2 id="borrado-titulo">Eliminar conversaciones</h2>'
    . '<p id="borrado-texto">Se quitarán de tu bandeja. La otra persona conserva su historial.</p>'
    . '<div class="dialogo__acciones">'
    . '<button type="button" class="boton boton--claro" data-cerrar>Cancelar</button>'
    . '<button type="submit" form="form-borrado" class="boton boton--peligro">Eliminar</button>'
    . '</div></dialog>';

$scripts = '';

if ($hilo !== null && $tab === 'conversaciones') {
    $scripts = <<<'JS'
(function () {
    var YO = __YO__;
    var chat = document.getElementById('chat');
    if (!chat) return;
    var cuerpo = document.getElementById('chat-cuerpo');
    var uuid = chat.dataset.uuid;
    var ultimo = parseInt(chat.dataset.ultimo, 10) || 0;
    var area = document.getElementById('cuerpo');

    if (area) {
        area.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); area.form.submit(); }
        });
        area.focus();
    }

    function bajar() { cuerpo.scrollTop = cuerpo.scrollHeight; }
    bajar();

    function burbuja(m) {
        var div = document.createElement('div');
        var mio = Number(m.autor_id) === YO;
        div.className = 'msg-burbuja msg-burbuja--' + (mio ? 'yo' : 'otro');
        div.textContent = m.cuerpo;
        var hora = document.createElement('span');
        hora.className = 'msg-burbuja__hora';
        hora.textContent = (m.created_at || '').slice(11, 16);
        div.appendChild(hora);
        return div;
    }

    function consultar() {
        if (document.hidden) return;
        fetch('/mensajes/' + encodeURIComponent(uuid) + '/nuevos?desde=' + ultimo, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok || !d.data || !d.data.mensajes.length) return;
            d.data.mensajes.forEach(function (m) {
                cuerpo.insertBefore(burbuja(m), document.getElementById('fin'));
                ultimo = Math.max(ultimo, Number(m.id));
            });
            bajar();
        }).catch(function () { /* sin red: se reintenta al siguiente ciclo */ });
    }

    setInterval(consultar, 8000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) consultar(); });
})();
JS;

    $scripts = str_replace('__YO__', (string) (int) $yo, $scripts);
}

$scripts .= <<<'JS'

// Borrado de conversaciones: el primer toque entra en modo selección, el
// segundo pide confirmación. Sin selección no se abre el diálogo.
(function () {
    var boton = document.getElementById('btn-borrar');
    var form = document.getElementById('form-borrado');
    if (!boton || !form) return;

    var dialogo = document.getElementById('confirmar-borrado');
    var texto = document.getElementById('borrado-texto');
    var modo = false;

    function marcados() {
        return form.querySelectorAll('input[name="hilos[]"]:checked');
    }

    boton.addEventListener('click', function () {
        if (!modo) {
            modo = true;
            document.body.classList.add('msg-modo-borrar');
            boton.classList.add('activo');
            boton.setAttribute('aria-label', 'Eliminar seleccionadas');
            return;
        }

        var elegidos = marcados();
        if (elegidos.length === 0) {
            boton.classList.add('boton-icono--aviso');
            setTimeout(function () { boton.classList.remove('boton-icono--aviso'); }, 600);
            return;
        }

        texto.textContent = elegidos.length === 1
            ? 'Se quitará 1 conversación de tu bandeja. La otra persona conserva su historial.'
            : 'Se quitarán ' + elegidos.length + ' conversaciones de tu bandeja. La otra persona conserva su historial.';

        if (typeof dialogo.showModal === 'function') { dialogo.showModal(); }
        else { dialogo.setAttribute('open', ''); }
    });

    // Escape o clic fuera del diálogo: se cancela sin borrar.
    dialogo.addEventListener('cancel', function () {
        document.body.classList.remove('msg-modo-borrar');
        boton.classList.remove('activo');
        modo = false;
    });
})();
JS;

require __DIR__ . '/../layout.php';
