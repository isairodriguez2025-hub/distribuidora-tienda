-- 026 · Ocultar conversaciones por usuario.
-- Borrar un hilo no debe borrárselo a la otra persona: la fila de
-- participantes gana una marca y la lista filtra por ella.

ALTER TABLE conversacion_participantes
    ADD COLUMN IF NOT EXISTS oculta TINYINT(1) NOT NULL DEFAULT 0 AFTER silenciada;

CREATE INDEX IF NOT EXISTS idx_participantes_oculta ON conversacion_participantes (usuario_id, oculta);
