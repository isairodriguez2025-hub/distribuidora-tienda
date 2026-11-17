<?php
/** @var callable $e */
/** @var string $csrf */

$titulo = 'Aviso de privacidad';

$contenido = '<h1>Aviso de privacidad</h1>'
    . '<div style="max-width:720px;background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:24px;margin:18px 0">'
    . '<p><strong>Última actualización:</strong> ' . date('d/m/Y') . '</p>'
    . '<h2>1. Datos que recopilamos</h2>'
    . '<p>Cuando te registras en Didi2 B2B recopilamos tu correo electrónico, número de teléfono, nombre del negocio y datos fiscales (RFC, razón social, régimen fiscal) según tu rol como comercio o mayorista.</p>'
    . '<h2>2. Cómo usamos tus datos</h2>'
    . '<p>Tus datos se utilizan exclusivamente para:</p>'
    . '<ul>'
    . '<li>Crear y gestionar tu cuenta en la plataforma</li>'
    . '<li>Procesar pedidos y facturas</li>'
    . '<li>Enviar notificaciones relacionadas con tus operaciones</li>'
    . '<li>Cumplir con obligaciones fiscales mexicanas</li>'
    . '</ul>'
    . '<h2>3. Cookies</h2>'
    . '<p>Didi2 utiliza cookies esenciales para mantener tu sesión y recordar tu carrito de compras. No utilizamos cookies de terceros para publicidad.</p>'
    . '<h2>4. Tus derechos</h2>'
    . '<p>Puedes solicitar en cualquier momento el acceso, rectificación o eliminación de tus datos personales contactando a <a href="mailto:soporte@did2.mx">soporte@did2.mx</a>.</p>'
    . '<h2>5. Seguridad</h2>'
    . '<p>Implementamos medidas técnicas y organizativas para proteger tus datos, incluyendo cifrado en tránsito (HTTPS) y en reposo, autenticación con contraseñas Argon2id, y auditoría de accesos.</p>'
    . '<h2>6. Contacto</h2>'
    . '<p>Si tienes preguntas sobre este aviso, escríbenos a <a href="mailto:soporte@did2.mx">soporte@did2.mx</a>.</p>'
    . '</div>'
    . '<a class="boton boton--claro" href="/">← Volver al inicio</a>';

require __DIR__ . '/layout.php';
