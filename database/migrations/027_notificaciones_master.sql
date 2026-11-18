-- 027 · Interruptor maestro de notificaciones.
-- Vive en preferencias_usuario porque se edita desde dos sitios distintos
-- (Privacidad y Configuración del centro de mensajes) y debe ser el mismo dato.

ALTER TABLE preferencias_usuario
    ADD COLUMN IF NOT EXISTS notificaciones TINYINT(1) NOT NULL DEFAULT 1 AFTER usuario_id;
