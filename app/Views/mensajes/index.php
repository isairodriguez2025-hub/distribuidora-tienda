<?php
/**
 * Bandeja de mensajes: lista de hilos + conversación.
 *
 * @var callable $e
 * @var string   $csrf
 * @var array    $hilos
 * @var array|null $hilo
 * @var string|null $error
 * @var bool     $esAdmin
 * @var int      $yo
 */
$error = $error ?? null;

$lista = '';

foreach ($hilos as $h) {
    $activo = $hilo !== null && (int) $hilo['hilo']['id'] === (int) $h['id'] ? '1' : '0';
    $iniciales = \App\Modules\Perfil\AvatarService::iniciales((string) $h['titulo']);
    $avatar = $h['avatar'] !== null
        ? '<img class="cuenta__foto" src="' . $e($h['avatar']) . '" alt="" width="38" height="38" style="width:38px;height:38px">'
        : '<span class="cuenta__iniciales" style="width:38px;height:38px">' . $e($iniciales) . '</span>';
    $hora = $h['ultimo_at'] !== null ? date('d/m H:i', strtotime((string) $h['ultimo_at'])) : date('d/m', strtotime((string) $h['created_at']));
    $resumen = $h['ultimo'] !== null ? mb_substr((string) $h['ultimo'], 0, 60) : 'Sin mensajes todavía';

    $lista .= '<a class="msg-hilo" data-activo="' . $activo . '" href="/mensajes?hilo=' . urlencode((string) $h['uuid']) . '">'
        . $avatar
        . '<div style="min-width:0">'
        . '<div class="msg-hilo__nombre">' . $e((string) $h['titulo']) . '</div>'
        . '<div class="msg-hilo__resumen">' . $e($resumen) . '</div>'
        . '</div>'
        . '<div style="display:grid;gap:4px;justify-items:end">'
        . '<span class="msg-hilo__hora">' . $e($hora) . '</span>'
        . ($h['no_leidos'] > 0 ? '<span class="msg-hilo__no-leido">' . (int) $h['no_leidos'] . '</span>' : '')
        . '</div></a>';
}

if ($lista === '') {
    $lista = '<div style="padding:26px 18px;text-align:center">'
        . '<p class="silencio" style="margin-bottom:14px">Todavía no tienes conversaciones.</p>'
        . '<form method="post" action="/mensajes/soporte">'
        . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
        . '<button type="submit">Escribir a soporte</button></form></div>';
}

$chat = '<div class="msg-chat" style="align-items:center;justify-content:center;padding:30px;text-align:center">'
    . '<div><h2 style="margin:0 0 6px">Tus mensajes</h2>'
    . '<p class="silencio" style="max-width:340px">Elige una conversación de la izquierda o escríbele al equipo de soporte. '
    . 'Los administradores responden desde el mismo canal.</p></div></div>';

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

$opciones = '';

foreach (($contactos ?? []) as $c) {
    $opciones .= '<option value="' . (int) $c['id'] . '">' . $e((string) $c['nombre']) . ' · ' . $e((string) $c['rol']) . '</option>';
}

$nueva = $opciones === '' ? '' : '<details class="msg-nueva"><summary>Nueva conversación</summary>'
    . '<form method="post" action="/mensajes/directo">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<label class="campo"><span>Contacto</span><select name="usuario_id" required>' . $opciones . '</select></label>'
    . '<button type="submit" style="width:100%">Abrir chat</button></form></details>';

$contenido = '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px">'
    . '<div><h1 style="margin:0">Mensajes</h1><p class="silencio" style="margin:2px 0 0">Conversaciones seguras dentro de Didi2.</p></div>'
    . '<form method="post" action="/mensajes/soporte" style="margin:0">'
    . '<input type="hidden" name="_csrf" value="' . $e($csrf) . '">'
    . '<button type="submit" class="boton--claro">Escribir a soporte</button></form></div>'
    . ($error !== null ? '<div class="aviso aviso--error" style="margin-bottom:14px">' . $e($error) . '</div>' : '')
    . '<div class="msg-layout">'
    . '<div class="msg-lista"><div class="msg-lista__head"><strong>Conversaciones</strong>'
    . '<span class="pista">' . count($hilos) . '</span></div>' . $nueva . $lista . '</div>'
    . $chat
    . '</div>';

$scripts = '';

if ($hilo !== null) {
    $scripts = <<<'JS'
(function () {
    var YO = __YO__;
    var chat = document.getElementById('chat');
    if (!chat) return;
    var cuerpo = document.getElementById('chat-cuerpo');
    var uuid = chat.dataset.uuid;
    var ultimo = parseInt(chat.dataset.ultimo, 10) || 0;
    var area = document.getElementById('cuerpo');

    // Enter envía, Shift+Enter hace salto de línea.
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

require __DIR__ . '/../layout.php';
