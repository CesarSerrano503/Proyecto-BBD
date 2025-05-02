-- 1. Crear la base de datos y seleccionarla
CREATE DATABASE IF NOT EXISTS `reservas_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;
USE `reservas_db`;

-- 2. Tabla administradores
CREATE TABLE IF NOT EXISTS `administradores` (
  `id_admin`       INT             NOT NULL AUTO_INCREMENT,
  `carnet`         VARCHAR(50)     NOT NULL,
  `contrasena`     VARCHAR(255)    NOT NULL,
  `nombre_completo` VARCHAR(100)   DEFAULT NULL,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `usuario` (`carnet`)
) ENGINE=MyISAM
  AUTO_INCREMENT=3
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_0900_ai_ci;

-- 3. Tabla alumnos
CREATE TABLE IF NOT EXISTS `alumnos` (
  `carnet`     VARCHAR(20)    NOT NULL,
  `nombre`     VARCHAR(100)   NOT NULL,
  `grado`      VARCHAR(50)    NOT NULL,
  `seccion`    VARCHAR(10)    NOT NULL,
  `contrasena` VARCHAR(255)   NOT NULL,
  PRIMARY KEY (`carnet`)
) ENGINE=MyISAM
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_0900_ai_ci;

-- 4. Tabla complementos (bebidas, ensaladas, extras…)
CREATE TABLE IF NOT EXISTS `complementos` (
  `id_complemento` INT               NOT NULL AUTO_INCREMENT,
  `nombre`         VARCHAR(100)      NOT NULL,
  `tipo`           ENUM('bebida','guarnicion','ensalada','extra') NOT NULL,
  `precio`         DECIMAL(10,2)     NOT NULL DEFAULT '0.25',
  PRIMARY KEY (`id_complemento`)
) ENGINE=MyISAM
  AUTO_INCREMENT=9
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_0900_ai_ci;

-- 5. Tabla platos
CREATE TABLE IF NOT EXISTS `platos` (
  `id_plato`         INT            NOT NULL AUTO_INCREMENT,
  `nombre`           VARCHAR(100)   NOT NULL,
  `descripcion`      TEXT,
  `precio`           DECIMAL(10,2)  NOT NULL,
  `imagen`           LONGBLOB,
  `activo`           TINYINT(1)     NOT NULL DEFAULT '1',
  `limite`           INT            DEFAULT '0',
  `limite_disponible` INT           DEFAULT '0',
  PRIMARY KEY (`id_plato`)
) ENGINE=MyISAM
  AUTO_INCREMENT=6
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_0900_ai_ci;

-- 6. Tabla pedidos (referencia a alumnos, administradores y platos)
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id_pedido`         INT            NOT NULL AUTO_INCREMENT,
  `carnet_alumno`     VARCHAR(20)    NOT NULL,
  `descripcion_pedido` TEXT          NOT NULL,
  `monto`             DECIMAL(10,2)  NOT NULL,
  `fecha_reserva`     DATE           NOT NULL DEFAULT (CURDATE()),
  `id_admin`          INT            NOT NULL,
  `id_plato`          INT            DEFAULT NULL,
  PRIMARY KEY (`id_pedido`),
  KEY `carnet_alumno` (`carnet_alumno`),
  KEY `id_admin`      (`id_admin`)
) ENGINE=MyISAM
  AUTO_INCREMENT=11
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_0900_ai_ci;
