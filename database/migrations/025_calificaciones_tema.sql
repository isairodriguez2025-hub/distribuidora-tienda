-- 025 · Calificaciones entre usuarios y tema de la aplicación.
--
-- Reglas del negocio:
--   · Solo puntúan comercios, mayoristas y repartidores. Las cuentas de
--     administración quedan fuera: no participan del mercado.
--   · Nadie se puntúa a sí mismo.
--   · Una calificación por persona y por evaluado: si vuelve a puntuar, se
--     actualiza en lugar de acumularse (evita inflar el promedio).

CREATE TABLE IF NOT EXISTS calificaciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    evaluador_id BIGINT UNSIGNED NOT NULL,
    evaluado_id BIGINT UNSIGNED NOT NULL,
    pedido_id BIGINT UNSIGNED NULL,
    puntaje TINYINT UNSIGNED NOT NULL,
    comentario VARCHAR(280) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_calificacion (evaluador_id, evaluado_id),
    KEY idx_calificaciones_evaluado (evaluado_id),
    CONSTRAINT fk_calif_evaluador FOREIGN KEY (evaluador_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_calif_evaluado  FOREIGN KEY (evaluado_id)  REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT chk_calif_rango CHECK (puntaje BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE preferencias_usuario
    ADD COLUMN IF NOT EXISTS tema ENUM('claro', 'oscuro') NOT NULL DEFAULT 'claro' AFTER ubicacion;
