-- 023 · Preferencias del usuario para la pantalla de Privacidad.
-- Una fila por usuario: los tres interruptores viven juntos porque se leen y
-- se guardan siempre en la misma pantalla.

CREATE TABLE IF NOT EXISTS preferencias_usuario (
    usuario_id BIGINT UNSIGNED NOT NULL,
    sms TINYINT(1) NOT NULL DEFAULT 1,
    correo TINYINT(1) NOT NULL DEFAULT 1,
    ubicacion TINYINT(1) NOT NULL DEFAULT 1,
    actualizado_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id),
    CONSTRAINT fk_pref_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS eliminado_motivo VARCHAR(120) NOT NULL DEFAULT '' AFTER estado;
