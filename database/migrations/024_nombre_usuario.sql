-- 024 · Nombre de la cuenta a nivel usuario.
-- Los perfiles de comercio y mayorista guardan el nombre del negocio, pero el
-- repartidor no tiene perfil con nombre: su identidad es la de la cuenta.

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS nombre VARCHAR(120) NOT NULL DEFAULT '' AFTER telefono;

-- Rellena el nombre con lo que ya exista en cada perfil, para que la pantalla
-- de cuenta no arranque vacía.
UPDATE usuarios u
  JOIN comercios_perfil c ON c.usuario_id = u.id
   SET u.nombre = c.nombre_negocio
 WHERE u.nombre = '';

UPDATE usuarios u
  JOIN mayoristas_perfil m ON m.usuario_id = u.id
   SET u.nombre = m.nombre_comercial
 WHERE u.nombre = '';
