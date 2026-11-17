-- 019 · Mejoras: identidad (avatar), mensajería directa y POS del comercio.
-- Todas las sentencias son idempotentes para poder reejecutar el archivo.

-- ---------------------------------------------------------------- identidad
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS avatar_ruta VARCHAR(190) NULL AFTER telefono,
    ADD COLUMN IF NOT EXISTS avatar_actualizado_at DATETIME NULL AFTER avatar_ruta;

-- -------------------------------------------------------------- mensajería
CREATE TABLE IF NOT EXISTS conversaciones (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid        CHAR(36) NOT NULL,
    tipo        ENUM('directa','soporte') NOT NULL DEFAULT 'directa',
    asunto      VARCHAR(160) NULL,
    creada_por  BIGINT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_conversaciones_uuid (uuid),
    KEY idx_conversaciones_tipo (tipo, updated_at),
    CONSTRAINT fk_conversaciones_autor FOREIGN KEY (creada_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conversacion_participantes (
    conversacion_id BIGINT UNSIGNED NOT NULL,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    ultimo_leido_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    silenciada      TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (conversacion_id, usuario_id),
    KEY idx_participantes_usuario (usuario_id),
    CONSTRAINT fk_part_conversacion FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_part_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mensajes_directos (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversacion_id BIGINT UNSIGNED NOT NULL,
    autor_id        BIGINT UNSIGNED NOT NULL,
    cuerpo          TEXT NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_directos_conversacion (conversacion_id, id),
    KEY idx_directos_autor (autor_id),
    CONSTRAINT fk_directos_conversacion FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_directos_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------ notificaciones
ALTER TABLE notificaciones
    MODIFY COLUMN canal ENUM('correo','push','bandeja','sms') NOT NULL;

-- --------------------------------------------------------------------- POS
CREATE TABLE IF NOT EXISTS pos_ajustes (
    usuario_id      BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    margen_venta    DECIMAL(5,2) NOT NULL DEFAULT 30.00,
    reorden_auto    TINYINT(1) NOT NULL DEFAULT 1,
    iva_incluido    TINYINT(1) NOT NULL DEFAULT 1,
    actualizado_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pos_ajustes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pos_productos (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    producto_id     BIGINT UNSIGNED NULL,
    nombre          VARCHAR(180) NOT NULL,
    sku             VARCHAR(60) NULL,
    codigo_barras   VARCHAR(60) NULL,
    precio_compra   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    precio_venta    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    iva_tasa        DECIMAL(5,2) NOT NULL DEFAULT 16.00,
    stock           DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    stock_minimo    DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    unidad          VARCHAR(30) NOT NULL DEFAULT 'pieza',
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pos_producto (usuario_id, producto_id),
    KEY idx_pos_productos_usuario (usuario_id, activo),
    KEY idx_pos_productos_barras (usuario_id, codigo_barras),
    CONSTRAINT fk_pos_productos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_productos_catalogo FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pos_clientes (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  BIGINT UNSIGNED NOT NULL,
    nombre      VARCHAR(150) NOT NULL,
    telefono    VARCHAR(20) NULL,
    email       VARCHAR(190) NULL,
    rfc         VARCHAR(13) NULL,
    notas       VARCHAR(255) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pos_clientes_usuario (usuario_id, nombre),
    CONSTRAINT fk_pos_clientes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pos_turnos (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id        BIGINT UNSIGNED NOT NULL,
    abierto_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cerrado_at        DATETIME NULL,
    fondo_inicial     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    efectivo_esperado DECIMAL(12,2) NULL,
    efectivo_contado  DECIMAL(12,2) NULL,
    diferencia        DECIMAL(12,2) NULL,
    estado            ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto',
    notas             VARCHAR(255) NULL,
    KEY idx_pos_turnos_usuario (usuario_id, estado),
    CONSTRAINT fk_pos_turnos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pos_ventas (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid         CHAR(36) NOT NULL,
    usuario_id   BIGINT UNSIGNED NOT NULL,
    turno_id     BIGINT UNSIGNED NOT NULL,
    folio        VARCHAR(30) NOT NULL,
    cliente_id   BIGINT UNSIGNED NULL,
    subtotal     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    iva          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    estado       ENUM('pagada','cancelada') NOT NULL DEFAULT 'pagada',
    notas        VARCHAR(255) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pos_ventas_uuid (uuid),
    UNIQUE KEY uq_pos_ventas_folio (usuario_id, folio),
    KEY idx_pos_ventas_turno (turno_id, created_at),
    CONSTRAINT fk_pos_ventas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_ventas_turno FOREIGN KEY (turno_id) REFERENCES pos_turnos(id),
    CONSTRAINT fk_pos_ventas_cliente FOREIGN KEY (cliente_id) REFERENCES pos_clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pos_venta_lineas (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venta_id         BIGINT UNSIGNED NOT NULL,
    pos_producto_id  BIGINT UNSIGNED NULL,
    descripcion      VARCHAR(180) NOT NULL,
    cantidad         DECIMAL(12,3) NOT NULL DEFAULT 1.000,
    precio_unitario  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    descuento        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    iva_tasa         DECIMAL(5,2) NOT NULL DEFAULT 16.00,
    subtotal         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    KEY idx_pos_lineas_venta (venta_id),
    CONSTRAINT fk_pos_lineas_venta FOREIGN KEY (venta_id) REFERENCES pos_ventas(id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_lineas_producto FOREIGN KEY (pos_producto_id) REFERENCES pos_productos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pos_pagos (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venta_id    BIGINT UNSIGNED NOT NULL,
    metodo      ENUM('efectivo','tarjeta','transferencia','credito') NOT NULL,
    monto       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    recibido    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    cambio      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    referencia  VARCHAR(80) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pos_pagos_venta (venta_id),
    KEY idx_pos_pagos_metodo (metodo, created_at),
    CONSTRAINT fk_pos_pagos_venta FOREIGN KEY (venta_id) REFERENCES pos_ventas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pos_movimientos (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    turno_id    BIGINT UNSIGNED NOT NULL,
    tipo        ENUM('venta','ingreso','retiro','devolucion') NOT NULL,
    monto       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    concepto    VARCHAR(180) NOT NULL,
    referencia  VARCHAR(60) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pos_mov_turno (turno_id, created_at),
    CONSTRAINT fk_pos_mov_turno FOREIGN KEY (turno_id) REFERENCES pos_turnos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
