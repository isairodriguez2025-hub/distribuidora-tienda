<?php
/**
 * Ayuda: qué hacer cuando algo no sale, y cómo llegar a una persona.
 *
 * @var callable $e
 */
$contenido = '<div class="ajustes">'
    . '<a class="volver" href="/perfil">‹ Mi cuenta</a>'
    . '<h1>Ayuda</h1>'
    . '<p class="pista" style="margin:-4px 0 14px"><a href="/perfil">Mi perfil</a></p>'
    . '<p class="silencio">Estamos para resolverlo. La mayoría de los casos se
        contestan en menos de una hora en horario laboral.</p>'

    . '<section class="carta" style="margin-top:16px">'
    . '<h2 style="margin-top:0">Hablar con una persona</h2>'
    . '<p class="pista">Escríbenos desde tu cuenta: el mensaje llega con tu
        nombre, tu rol y el pedido que estabas viendo, así no tienes que
        explicarlo todo otra vez.</p>'
    . '<a class="boton" href="/mensajes/soporte">Abrir el chat de soporte</a>'
    . '</section>'

    . '<section class="carta" style="margin-top:14px">'
    . '<h2 style="margin-top:0">Problemas frecuentes</h2>'

    . '<details class="faq"><summary>Mi pedido no avanza de estado</summary>'
    . '<p>Si sigue en <strong>por aceptar</strong>, el mayorista todavía no lo ha
        tomado. Si está en <strong>buscando repartidor</strong>, ya fue aceptado y
        falta asignar quién lo lleva. Puedes cancelar sin costo mientras no haya
        sido aceptado.</p></details>'

    . '<details class="faq"><summary>No me llega el código por SMS</summary>'
    . '<p>Revisa que tu teléfono esté en <a href="/perfil/editar">tus datos</a> y
        que los mensajes de texto estén activos en
        <a href="/perfil/privacidad">Privacidad</a>. Si sigue sin llegar, pide el
        enlace por correo desde la pantalla de recuperación.</p></details>'

    . '<details class="faq"><summary>El repartidor no encuentra mi dirección</summary>'
    . '<p>Agrega una referencia en <a href="/perfil/editar">Editar cuenta</a>:
        color del portón, entre qué calles está, o el nombre del negocio en la
        fachada. Con eso basta casi siempre.</p></details>'

    . '<details class="faq"><summary>Quiero cambiar mi contraseña</summary>'
    . '<p>Se hace desde <a href="/perfil/editar">Editar cuenta</a>. Al guardarla
        se cierran las demás sesiones abiertas.</p></details>'

    . '<details class="faq"><summary>¿Cómo funciona la calificación?</summary>'
    . '<p>Comercios, mayoristas y repartidores se puntúan entre sí de 1 a 5
        estrellas después de una entrega. Solo tú ves tu promedio, y se actualiza
        cuando alguien vuelve a puntuarte.</p></details>'
    . '</section>'

    . '<section class="carta" style="margin-top:14px">'
    . '<h2 style="margin-top:0">Legal</h2>'
    . '<p class="pista">Consulta el <a href="/terminos">los términos y condiciones</a>
        o el <a href="/aviso-privacidad">aviso de privacidad</a>.</p>'
    . '</section>'
    . '</div>';

require __DIR__ . '/../layout.php';
