-- Avisos del sitio de demostración: pedidos, pagos y cuenta.
-- El tono y los tiempos imitan lo que genera la aplicación en producción.

INSERT INTO notificaciones (usuario_id, canal, tipo, asunto, cuerpo, enlace, estado, enviado_at)
SELECT 2, 'bandeja', 'pedido_nuevo', 'Tu pedido salió del almacén',
       'Centro Abarrotero preparó el pedido de 2 costales de harina. Llega hoy entre 14:00 y 17:00.',
       '/pedidos', 'enviado', NOW() - INTERVAL 3 HOUR
WHERE NOT EXISTS (SELECT 1 FROM notificaciones WHERE usuario_id = 2 AND asunto = 'Tu pedido salió del almacén');

INSERT INTO notificaciones (usuario_id, canal, tipo, asunto, cuerpo, enlace, estado, enviado_at)
SELECT 2, 'bandeja', 'pago_confirmado', 'Pago confirmado',
       'Recibimos $348.00 por el pedido de harina. La factura ya está disponible.',
       '/facturacion', 'enviado', NOW() - INTERVAL 1 DAY
WHERE NOT EXISTS (SELECT 1 FROM notificaciones WHERE usuario_id = 2 AND asunto = 'Pago confirmado');

INSERT INTO notificaciones (usuario_id, canal, tipo, asunto, cuerpo, enlace, estado, enviado_at)
SELECT 2, 'bandeja', 'cuenta', 'Verifica tu cuenta',
       'Sube tu constancia de situación fiscal para emitir facturas sin interrupciones.',
       '/kyc', 'enviado', NOW() - INTERVAL 3 DAY
WHERE NOT EXISTS (SELECT 1 FROM notificaciones WHERE usuario_id = 2 AND asunto = 'Verifica tu cuenta');

INSERT INTO notificaciones (usuario_id, canal, tipo, asunto, cuerpo, enlace, estado, enviado_at)
SELECT 4, 'bandeja', 'pedido_nuevo', 'Nuevo pedido por aceptar',
       'Abarrotes La Esquina pidió 2 costales de harina de trigo. Tienes 30 minutos para aceptarlo.',
       '/mayorista/pedidos', 'enviado', NOW() - INTERVAL 40 MINUTE
WHERE NOT EXISTS (SELECT 1 FROM notificaciones WHERE usuario_id = 4 AND asunto = 'Nuevo pedido por aceptar');
