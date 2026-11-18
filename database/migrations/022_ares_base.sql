-- 022 · Base de Ares: auditoría completa, ciclo de pedidos, carritos
-- múltiples, recuperación de contraseña, imágenes de producto y programados
-- calculados por ventas.

-- ---------------------------------------------------------------- auditoría
ALTER TABLE audit_log
    ADD COLUMN IF NOT EXISTS nombre VARCHAR(120) NOT NULL DEFAULT '' AFTER usuario_id,
    ADD COLUMN IF NOT EXISTS rol VARCHAR(40) NOT NULL DEFAULT '' AFTER nombre,
    ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255) NOT NULL DEFAULT '' AFTER entidad_id,
    ADD COLUMN IF NOT EXISTS antes LONGTEXT NULL AFTER descripcion,
    ADD COLUMN IF NOT EXISTS despues LONGTEXT NULL AFTER antes,
    ADD COLUMN IF NOT EXISTS user_agent VARCHAR(255) NOT NULL DEFAULT '' AFTER ip,
    ADD INDEX IF NOT EXISTS idx_audit_fecha (created_at),
    ADD INDEX IF NOT EXISTS idx_audit_accion (accion),
    ADD INDEX IF NOT EXISTS idx_audit_entidad (entidad);

-- ------------------------------------------------------------ ciclo de pedido
ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS grupo_pedido CHAR(36) NULL AFTER uuid,
    ADD COLUMN IF NOT EXISTS nota_cliente VARCHAR(255) NOT NULL DEFAULT '' AFTER total,
    ADD COLUMN IF NOT EXISTS motivo_cancelacion VARCHAR(255) NOT NULL DEFAULT '' AFTER nota_cliente,
    ADD INDEX IF NOT EXISTS idx_pedidos_estado (estado),
    ADD INDEX IF NOT EXISTS idx_pedidos_grupo (grupo_pedido);

CREATE TABLE IF NOT EXISTS pedido_eventos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id BIGINT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(30) NULL,
    estado_nuevo VARCHAR(30) NOT NULL,
    usuario_id BIGINT UNSIGNED NULL,
    nota VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_eventos_pedido (pedido_id, id),
    CONSTRAINT fk_eventos_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estados nuevos. Los viejos se conservan como alias para no romper filas
-- históricas ni enlaces ya enviados.
UPDATE pedidos SET estado = 'por_aceptar'          WHERE estado IN ('pendiente', 'pagado');
UPDATE pedidos SET estado = 'buscando_repartidor'  WHERE estado IN ('aceptado', 'preparando');
UPDATE pedidos SET estado = 'recibido'             WHERE estado IN ('entregado', 'completado');

-- ------------------------------------------------------- carritos múltiples
CREATE TABLE IF NOT EXISTS carritos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    mayorista_id BIGINT UNSIGNED NULL,
    nombre VARCHAR(80) NOT NULL DEFAULT 'Carrito',
    estado ENUM('activo', 'guardado', 'convertido') NOT NULL DEFAULT 'activo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_carrito_uuid (uuid),
    KEY idx_carritos_usuario (usuario_id, estado),
    CONSTRAINT fk_carrito_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carrito_lineas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    carrito_id BIGINT UNSIGNED NOT NULL,
    producto_id BIGINT UNSIGNED NOT NULL,
    cantidad DECIMAL(12,3) NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_linea_carrito (carrito_id, producto_id),
    CONSTRAINT fk_linea_carrito FOREIGN KEY (carrito_id) REFERENCES carritos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------ recuperación de contraseña
CREATE TABLE IF NOT EXISTS recuperacion_password (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    codigo_hash CHAR(64) NULL,
    expira_at DATETIME NOT NULL,
    usado_at DATETIME NULL,
    ip VARCHAR(45) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_recuperacion_token (token_hash),
    KEY idx_recuperacion_usuario (usuario_id, created_at),
    CONSTRAINT fk_recuperacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------- inventario con imágenes
ALTER TABLE pos_productos
    ADD COLUMN IF NOT EXISTS imagen VARCHAR(120) NULL AFTER nombre,
    ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255) NOT NULL DEFAULT '' AFTER imagen,
    ADD INDEX IF NOT EXISTS idx_pos_codigo (usuario_id, codigo_barras);

-- ------------------------------------------- programados calculados por ventas
ALTER TABLE pedidos_programados
    ADD COLUMN IF NOT EXISTS modo ENUM('fijo', 'por_ventas') NOT NULL DEFAULT 'fijo' AFTER frecuencia,
    ADD COLUMN IF NOT EXISTS dias_objetivo TINYINT UNSIGNED NOT NULL DEFAULT 7 AFTER modo,
    ADD COLUMN IF NOT EXISTS ultimo_pedido_id BIGINT UNSIGNED NULL AFTER proximo_envio,
    ADD COLUMN IF NOT EXISTS ultima_ejecucion DATETIME NULL AFTER ultimo_pedido_id;
