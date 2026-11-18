<?php
/**
 * Términos y condiciones del servicio Ares.
 *
 * @var callable $e
 */
$titulo = 'Términos y condiciones';

$contenido = '<article class="legal">'
    . '<a class="volver" href="/perfil">‹ Mi cuenta</a>'
    . '<h1>Términos y condiciones</h1>'
    . '<p class="silencio">Última actualización: noviembre de 2026.</p>'

    . '<h2>1. Qué es Ares</h2>'
    . '<p>Ares es una plataforma que conecta comercios con mayoristas y repartidores
        para comprar, vender y entregar materia prima e insumos. Ares no es dueña de
        los productos: facilita el encuentro, el pago y la logística.</p>'

    . '<h2>2. Tu cuenta</h2>'
    . '<p>Necesitas datos veraces y una cuenta activa para operar. Eres responsable de
        la actividad de tu cuenta y de mantener tu contraseña segura. Podemos suspender
        cuentas que incumplan estos términos o la ley.</p>'

    . '<h2>3. Pedidos y pagos</h2>'
    . '<p>El pedido se forma cuando lo envías al mayorista. El precio, el surtido y los
        tiempos se muestran antes de confirmar. Los pagos se procesan con los métodos
        habilitados; el comprobante y la factura quedan disponibles en tu cuenta.</p>'

    . '<h2>4. Entregas</h2>'
    . '<p>El mayorista prepara el pedido y Ares asigna un repartidor. La entrega se
        confirma con un PIN de cuatro dígitos que se muestra al comprador. Si el pedido
        llega incompleto o dañado, se reporta desde el detalle del pedido dentro de las
        48 horas siguientes.</p>'

    . '<h2>5. Devoluciones y cancelaciones</h2>'
    . '<p>Puedes cancelar sin costo mientras el pedido no haya sido aceptado. Después de
        aceptado, la cancelación depende del mayorista. Los productos perecederos no se
        devuelven salvo error de surtido.</p>'

    . '<h2>6. Uso aceptable</h2>'
    . '<p>No se permite publicar productos falsos, manipular precios, suplantar identidades
        ni usar la plataforma para actividades ilícitas.</p>'

    . '<h2>7. Responsabilidad</h2>'
    . '<p>Ares responde por el funcionamiento de la plataforma, no por la calidad de los
        productos, que es responsabilidad del mayorista que los vende.</p>'

    . '<h2>8. Cambios</h2>'
    . '<p>Si modificamos estos términos te avisaremos en la aplicación con al menos
        15 días de anticipación.</p>'

    . '<h2>9. Contacto</h2>'
    . '<p>Dudas o aclaraciones: <a href="/mensajes/soporte">soporte desde tu cuenta</a>.</p>'
    . '</article>';

require __DIR__ . '/layout.php';
