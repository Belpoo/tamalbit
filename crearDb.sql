-- Onboarding BD:
-- 1) Crear/seleccionar base
-- 2) Reiniciar tablas respetando dependencias
-- 3) Crear esquema (categorias, usuarios, productos, gastos)
-- 4) Cargar datos semilla

CREATE DATABASE IF NOT EXISTS tamalbit_db;

USE tamalbit_db;

-- Limpieza controlada para poder re-ejecutar este script sin errores de FK.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS gastos;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS categorias;
SET FOREIGN_KEY_CHECKS = 1;

-- Catálogo maestro de categorías de productos.
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

-- Usuarios identificados por person_id (id externo de la API).
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Productos disponibles para compra dentro de la app.
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    imagen_producto VARCHAR(255) DEFAULT NULL,
    categoria_id INT NOT NULL,

    UNIQUE KEY uk_producto_categoria (nombre, categoria_id),
    CONSTRAINT fk_productos_categorias
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- Historial local de gastos validados por API.
CREATE TABLE IF NOT EXISTS gastos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    descripcion VARCHAR(255),
    tamalbits INT NOT NULL DEFAULT 0,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_gastos_fecha (fecha),
    INDEX idx_gastos_usuario (usuario_id),
    INDEX idx_gastos_producto (producto_id),

    CONSTRAINT fk_gastos_usuarios
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_gastos_productos
        FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- Datos semilla para iniciar la app con contenido visible.
INSERT INTO categorias(nombre)
VALUES
('Comida'),
('Bebida'),
('Transporte'),
('Servicios publicos'),
('Otros')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO productos(nombre, precio, imagen_producto, categoria_id)
SELECT 'Orejas de pollo', 100, 'orejas-de-pollo.jpg', id FROM categorias WHERE nombre = 'Comida'
ON DUPLICATE KEY UPDATE precio = VALUES(precio), imagen_producto = VALUES(imagen_producto);

INSERT INTO productos(nombre, precio, imagen_producto, categoria_id)
SELECT 'Patas de zancudo', 5000, 'patas-de-zancudo.jpg', id FROM categorias WHERE nombre = 'Comida'
ON DUPLICATE KEY UPDATE precio = VALUES(precio), imagen_producto = VALUES(imagen_producto);

INSERT INTO productos(nombre, precio, imagen_producto, categoria_id)
SELECT 'Hamburguesa', 15, 'hamburguesa.jpg', id FROM categorias WHERE nombre = 'Comida'
ON DUPLICATE KEY UPDATE precio = VALUES(precio), imagen_producto = VALUES(imagen_producto);

INSERT INTO productos(nombre, precio, imagen_producto, categoria_id)
SELECT 'Pizza', 30, 'pizza.jpg', id FROM categorias WHERE nombre = 'Comida'
ON DUPLICATE KEY UPDATE precio = VALUES(precio), imagen_producto = VALUES(imagen_producto);

INSERT INTO productos(nombre, precio, imagen_producto, categoria_id)
SELECT 'Gaseosa', 5, 'gaseosa.jpg', id FROM categorias WHERE nombre = 'Bebida'
ON DUPLICATE KEY UPDATE precio = VALUES(precio), imagen_producto = VALUES(imagen_producto);