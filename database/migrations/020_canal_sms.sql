-- 020 · Canal SMS en las preferencias de notificación.
ALTER TABLE preferencias_notificacion
    ADD COLUMN IF NOT EXISTS sms TINYINT(1) NOT NULL DEFAULT 1 AFTER push;
