DROP DATABASE IF EXISTS tecnostock;
CREATE DATABASE tecnostock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tecnostock;

SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE TABLE categoria (
    id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60) NOT NULL,
    descripcion VARCHAR(180) NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_categoria),
    UNIQUE KEY uq_categoria_nombre (nombre),
    CONSTRAINT chk_categoria_estado CHECK (estado IN (0, 1))
) ENGINE = InnoDB;

CREATE TABLE usuario (
    id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    correo VARCHAR(120) NOT NULL,
    clave_hash VARCHAR(255) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acceso DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuario_correo (correo),
    CONSTRAINT chk_usuario_estado CHECK (estado IN (0, 1))
) ENGINE = InnoDB;

CREATE TABLE producto (
    id_producto INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NULL,
    precio DECIMAL(10, 2) NOT NULL,
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    id_categoria INT UNSIGNED NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_producto),
    UNIQUE KEY uq_producto_codigo (codigo),
    KEY idx_producto_categoria (id_categoria),
    KEY idx_producto_estado (estado),
    CONSTRAINT fk_producto_categoria FOREIGN KEY (id_categoria)
        REFERENCES categoria (id_categoria) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_producto_precio CHECK (precio >= 0),
    CONSTRAINT chk_producto_stock CHECK (stock_actual >= 0),
    CONSTRAINT chk_producto_stock_minimo CHECK (stock_minimo >= 0),
    CONSTRAINT chk_producto_estado CHECK (estado IN (0, 1))
) ENGINE = InnoDB;

CREATE TABLE movimiento (
    id_movimiento INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('entrada', 'salida') NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    observacion VARCHAR(200) NULL,
    stock_resultante INT NOT NULL,
    id_producto INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_movimiento),
    KEY idx_movimiento_producto (id_producto),
    KEY idx_movimiento_fecha (fecha_hora),
    KEY idx_movimiento_tipo (tipo),
    CONSTRAINT fk_movimiento_producto FOREIGN KEY (id_producto)
        REFERENCES producto (id_producto) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimiento_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_movimiento_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_movimiento_stock CHECK (stock_resultante >= 0)
) ENGINE = InnoDB;

INSERT INTO categoria (id_categoria, nombre, descripcion, estado, creado_en) VALUES
(1, 'Audífonos', 'Audio personal y accesorios de escucha', 1, '2026-09-01 09:00:00'),
(2, 'Cargadores', 'Carga y energía para dispositivos móviles', 1, '2026-09-01 09:00:00'),
(3, 'Cables', 'Cables de datos, carga y adaptadores', 1, '2026-09-01 09:00:00'),
(4, 'Fundas', 'Protección y cubiertas para equipos', 1, '2026-09-01 09:00:00'),
(5, 'Almacenamiento', 'Discos, pendrives y tarjetas de memoria', 1, '2026-09-01 09:00:00'),
(6, 'Periféricos', 'Teclados, mouse y accesorios de escritorio', 1, '2026-09-01 09:00:00'),
(7, 'Accesorios varios', 'Línea descontinuada por la empresa', 0, '2026-09-01 09:00:00');

INSERT INTO usuario (id_usuario, nombre, correo, clave_hash, estado, ultimo_acceso, creado_en) VALUES
(1, 'Camila Rojas', 'admin@tecnostock.cl', '$2y$10$4NF/WhgmujJb9GpotM/Jpu1MxFgMRi934gn0tROExKowGiQ4GTOSG', 1, NULL, '2026-09-01 09:00:00'),
(2, 'Diego Fuentes', 'encargado@tecnostock.cl', '$2y$10$TjrNJctKWXyaYruFVVrXLOxvtnEXd6NbM4Picibva25VMnBflKi7S', 1, NULL, '2026-09-01 09:00:00'),
(3, 'Paula Méndez', 'inactivo@tecnostock.cl', '$2y$10$zAur4cggBknBlrCqxJ4uf.MpOzRHX0sor1fmAGZJTeg0HXDT6qaNm', 0, NULL, '2026-09-01 09:00:00');

INSERT INTO producto (id_producto, codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria, creado_en) VALUES
(1, 'TEC-001', 'Audífonos Bluetooth ProSound X2', 'Audífonos inalámbricos con estuche de carga y cancelación pasiva de ruido.', 24990.00, 18, 5, 1, 1, '2026-09-01 09:00:00'),
(2, 'TEC-002', 'Audífonos Gamer HyperSound G7', 'Audífonos circumaurales con micrófono desmontable y luces LED.', 39990.00, 4, 6, 1, 1, '2026-09-01 09:00:00'),
(3, 'TEC-003', 'Cargador rápido 30W USB-C', 'Cargador de pared con carga rápida PD de 30 watts.', 14990.00, 42, 10, 1, 2, '2026-09-01 09:00:00'),
(4, 'TEC-004', 'Cargador inalámbrico 15W', 'Base de carga inalámbrica compatible con Qi.', 19990.00, 9, 10, 1, 2, '2026-09-01 09:00:00'),
(5, 'TEC-005', 'Cable USB-C 2m trenzado', 'Cable de carga y datos USB-C a USB-C de 2 metros.', 6990.00, 75, 20, 1, 3, '2026-09-01 09:00:00'),
(6, 'TEC-006', 'Cable Lightning 1m', 'Cable certificado para dispositivos Apple de 1 metro.', 8990.00, 0, 8, 1, 3, '2026-09-01 09:00:00'),
(7, 'TEC-007', 'Funda antigolpe iPhone 15', 'Funda reforzada con bordes elevados y acabado mate.', 9990.00, 26, 8, 1, 4, '2026-09-01 09:00:00'),
(8, 'TEC-008', 'Funda transparente Galaxy S24', 'Funda delgada de silicona transparente.', 7990.00, 3, 6, 1, 4, '2026-09-01 09:00:00'),
(9, 'TEC-009', 'Pendrive 64GB USB 3.0', 'Unidad flash de 64 GB con lectura de hasta 100 MB/s.', 11990.00, 33, 10, 1, 5, '2026-09-01 09:00:00'),
(10, 'TEC-010', 'SSD portátil 480GB', 'Disco sólido externo USB-C de 480 GB.', 64990.00, 7, 3, 1, 5, '2026-09-01 09:00:00'),
(11, 'TEC-011', 'Teclado mecánico TKL RGB', 'Teclado compacto con switches táctiles e iluminación RGB.', 54990.00, 12, 4, 1, 6, '2026-09-01 09:00:00'),
(12, 'TEC-012', 'Mouse inalámbrico ergonómico', 'Mouse inalámbrico con receptor USB y batería recargable.', 18990.00, 21, 8, 1, 6, '2026-09-01 09:00:00'),
(13, 'TEC-013', 'Hub USB-C 6 en 1', 'Adaptador con HDMI, USB 3.0, lector SD y carga PD.', 27990.00, 5, 5, 1, 6, '2026-09-01 09:00:00'),
(14, 'TEC-014', 'Soporte para notebook aluminio', 'Base regulable de aluminio para notebooks de 11 a 17 pulgadas.', 22990.00, 0, 4, 0, 6, '2026-09-01 09:00:00'),
(15, 'TEC-015', 'Audífonos con cable básicos', 'Audífonos con conector jack 3.5 mm.', 5990.00, 40, 15, 0, 1, '2026-09-01 09:00:00');

INSERT INTO movimiento (fecha_hora, tipo, cantidad, observacion, stock_resultante, id_producto, id_usuario) VALUES
('2026-09-10 09:00:00', 'entrada', 50, 'Carga inicial de temporada', 50, 15, 1),
('2026-09-15 10:00:00', 'entrada', 6, 'Compra a proveedor Tecnología Andes', 6, 14, 2),
('2026-10-01 11:00:00', 'salida', 10, 'Venta mostrador', 40, 15, 1),
('2026-10-05 12:30:00', 'salida', 6, 'Venta online', 0, 14, 2),
('2026-10-20 08:50:00', 'entrada', 100, 'Reposición de bodega', 100, 5, 1),
('2026-10-22 09:05:00', 'entrada', 40, 'Compra a proveedor Tecnología Andes', 40, 9, 2),
('2026-10-24 13:30:00', 'entrada', 30, 'Reposición de bodega', 30, 12, 1),
('2026-10-25 10:30:00', 'entrada', 50, 'Compra a proveedor Energía Sur', 50, 3, 2),
('2026-10-27 14:00:00', 'entrada', 15, 'Compra a proveedor MovilParts', 15, 6, 1),
('2026-10-28 09:40:00', 'entrada', 25, 'Compra a proveedor AudioLab', 25, 1, 2),
('2026-10-29 12:10:00', 'entrada', 30, 'Compra a proveedor MovilParts', 30, 7, 1),
('2026-10-30 11:05:00', 'entrada', 12, 'Compra a proveedor AudioLab', 12, 2, 2),
('2026-11-02 09:15:00', 'entrada', 20, 'Reposición de bodega', 20, 4, 1),
('2026-11-03 16:25:00', 'entrada', 10, 'Compra a proveedor MovilParts', 10, 8, 2),
('2026-11-05 10:45:00', 'entrada', 10, 'Compra a proveedor DataStore', 10, 10, 1),
('2026-11-06 11:20:00', 'entrada', 15, 'Compra a proveedor TecnoInput', 15, 11, 2),
('2026-11-07 09:25:00', 'entrada', 8, 'Compra a proveedor TecnoInput', 8, 13, 1),
('2026-11-10 18:05:00', 'salida', 15, 'Venta online', 0, 6, 2),
('2026-11-11 11:40:00', 'salida', 4, 'Venta mostrador', 26, 7, 1),
('2026-11-12 16:20:00', 'salida', 7, 'Venta online', 18, 1, 2),
('2026-11-13 10:20:00', 'salida', 25, 'Venta mayorista', 75, 5, 1),
('2026-11-14 15:10:00', 'salida', 8, 'Venta mostrador', 42, 3, 2),
('2026-11-15 12:45:00', 'salida', 8, 'Venta online', 4, 2, 1),
('2026-11-16 17:30:00', 'salida', 11, 'Venta mayorista', 9, 4, 2),
('2026-11-17 09:55:00', 'salida', 7, 'Venta mostrador', 3, 8, 1),
('2026-11-18 15:35:00', 'salida', 7, 'Venta online', 33, 9, 2),
('2026-11-18 17:05:00', 'salida', 3, 'Venta mostrador', 12, 11, 1),
('2026-11-19 12:15:00', 'salida', 3, 'Venta online', 7, 10, 2),
('2026-11-19 16:40:00', 'salida', 9, 'Venta mayorista', 21, 12, 1),
('2026-11-20 10:10:00', 'salida', 3, 'Venta mostrador', 5, 13, 2);
