-- 029 · Foto del producto en la caja.
-- La ventana de ventas muestra la imagen en cada tarjeta; si no hay foto se
-- usa una ilustración genérica.

ALTER TABLE pos_productos
    ADD COLUMN IF NOT EXISTS imagen VARCHAR(255) NULL AFTER nombre;
