-- Crear base de datos
CREATE DATABASE IF NOT EXISTS reservas_db;
USE reservas_db;

-- Tabla alumnos
CREATE TABLE IF NOT EXISTS alumnos (
    carnet VARCHAR(20) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    grado VARCHAR(50),
    seccion VARCHAR(10),
    contrasena VARCHAR(100) NOT NULL
);

-- Tabla administradores
CREATE TABLE IF NOT EXISTS administradores (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    carnet VARCHAR(20) UNIQUE NOT NULL,
    contrasena VARCHAR(100) NOT NULL
);

-- Tabla platos
CREATE TABLE IF NOT EXISTS platos (
    id_plato INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    imagen LONGBLOB,
    activo TINYINT(1) DEFAULT 1,
    limite_disponible INT DEFAULT 0
);

-- Tabla complementos
CREATE TABLE IF NOT EXISTS complementos (
    id_complemento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL, -- ejemplo: bebida, guarnicion, ensalada, extra
    precio DECIMAL(10,2) NOT NULL
);

-- Tabla pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    carnet_alumno VARCHAR(20),
    id_plato INT,
    descripcion_pedido TEXT,
    monto DECIMAL(10,2) NOT NULL,
    id_admin INT,
    fecha_reserva DATE NOT NULL,
    FOREIGN KEY (carnet_alumno) REFERENCES alumnos(carnet) ON DELETE CASCADE,
    FOREIGN KEY (id_plato) REFERENCES platos(id_plato) ON DELETE CASCADE,
    FOREIGN KEY (id_admin) REFERENCES administradores(id_admin) ON DELETE CASCADE
);

-- Insertar administrador de prueba
INSERT INTO administradores (carnet, contrasena) VALUES ('admin1', 'admin123');

-- Insertar alumno de prueba
INSERT INTO alumnos (carnet, nombre, grado, seccion, contrasena) 
VALUES ('SG242683', 'Cesar Antonio Serrano Gutierrez', '12°', 'A', 'alumno123');

-- Insertar platos de prueba
INSERT INTO platos (nombre, descripcion, precio, activo, limite_disponible) VALUES
('Huevo estrellado con plátano', 'Plato típico con huevo, plátano y frijoles', 1.50, 1, 4),
('Carne asada con arroz y ensalada', 'Deliciosa carne asada con guarnición', 1.50, 1, 4);

-- Insertar complementos de prueba
INSERT INTO complementos (nombre, tipo, precio) VALUES
('Coca-Cola', 'bebida', 0.25),
('Pepsi', 'bebida', 0.25),
('Papas fritas', 'guarnicion', 0.25),
('Ensalada fresca', 'ensalada', 0.25),
('Tortillas', 'extra', 0.10);
