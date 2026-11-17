-- 021 · Enlace entre las líneas de pedido y el inventario del POS.
-- Evita duplicar el mismo producto cada vez que se sincroniza.
ALTER TABLE pos_productos
    ADD COLUMN IF NOT EXISTS pedido_linea_id BIGINT UNSIGNED NULL AFTER producto_id,
    ADD UNIQUE KEY IF NOT EXISTS uq_pos_linea (pedido_linea_id);
