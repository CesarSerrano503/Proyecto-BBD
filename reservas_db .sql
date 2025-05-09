-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 08-05-2025 a las 10:24:46
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `reservas_db`
--

DELIMITER $$
--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `sp_crear_plato`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_crear_plato` (IN `p_nombre` VARCHAR(255), IN `p_descripcion` TEXT, IN `p_precio` DECIMAL(10,2), IN `p_limite` INT, IN `p_imagen` BLOB, IN `p_activo` TINYINT, IN `p_usuario` VARCHAR(255))   BEGIN
    -- Asignar valor predeterminado a p_activo si no se ha proporcionado
    IF p_activo IS NULL THEN
        SET p_activo = 1;
    END IF;

    -- 1) Inserta en platos
    INSERT INTO platos 
        (nombre, descripcion, precio, limite_disponible, imagen, activo)
    VALUES 
        (p_nombre, p_descripcion, p_precio, p_limite, p_imagen, p_activo);

    -- 2) Registra en historial
    INSERT INTO historial 
        (fecha, usuario, accion, detalle)
    VALUES 
        (NOW(), p_usuario, 'crear', CONCAT('Creó plato: ', p_nombre));
END$$

DROP PROCEDURE IF EXISTS `sp_editar_plato`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_editar_plato` (IN `p_id` INT, IN `p_nombre` VARCHAR(100), IN `p_descripcion` TEXT, IN `p_precio` DECIMAL(10,2), IN `p_limite` INT, IN `p_imagen` MEDIUMBLOB, IN `p_activo` TINYINT, IN `p_usuario` VARCHAR(100))   BEGIN
  -- Actualiza con o sin imagen nueva
  IF p_imagen IS NULL THEN
    UPDATE platos
      SET nombre = p_nombre,
          descripcion = p_descripcion,
          precio = p_precio,
          limite_disponible = p_limite,
          activo = p_activo
    WHERE id_plato = p_id;
  ELSE
    UPDATE platos
      SET nombre = p_nombre,
          descripcion = p_descripcion,
          precio = p_precio,
          limite_disponible = p_limite,
          activo = p_activo,
          imagen = p_imagen
    WHERE id_plato = p_id;
  END IF;

  INSERT INTO historial 
    (fecha, usuario, accion, detalle)
  VALUES 
    (NOW(), p_usuario, 'editar', CONCAT('Editó plato: ', p_nombre));
END$$

DROP PROCEDURE IF EXISTS `sp_eliminar_plato`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_eliminar_plato` (IN `p_id` INT, IN `p_usuario` VARCHAR(100))   BEGIN
  DECLARE v_nombre VARCHAR(255);

  -- 1) Obtener el nombre del plato antes de borrarlo
  SELECT nombre
    INTO v_nombre
    FROM platos
   WHERE id_plato = p_id;

  -- 2) Eliminar el plato
  DELETE FROM platos
   WHERE id_plato = p_id;

  -- 3) Registrar la acción en historial
  INSERT INTO historial (fecha, usuario, accion, detalle)
  VALUES (
    NOW(),
    p_usuario,
    'eliminar',
    CONCAT('Eliminó plato: ', v_nombre)
  );
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

DROP TABLE IF EXISTS `administradores`;
CREATE TABLE IF NOT EXISTS `administradores` (
  `id_admin` int NOT NULL AUTO_INCREMENT,
  `carnet` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `usuario` (`carnet`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id_admin`, `carnet`, `contrasena`, `nombre_completo`) VALUES
(1, 'Cesar', '12345', 'Cesar'),
(2, 'Antonio', '123', 'Cesar Antonio');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

DROP TABLE IF EXISTS `alumnos`;
CREATE TABLE IF NOT EXISTS `alumnos` (
  `carnet` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `grado` varchar(50) NOT NULL,
  `seccion` varchar(10) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  PRIMARY KEY (`carnet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`carnet`, `nombre`, `grado`, `seccion`, `contrasena`) VALUES
('AM239540', 'Ana Michelle López Campos', '2° Bachillerato', 'B', 'ana2025'),
('JC240879', 'José Carlos Martínez Rivera', '2° Bachillerato', 'A', 'jose2408'),
('MR241105', 'María Renée González López', '1° Bachillerato', 'B', 'maria2024'),
('NP242670', 'Susan Núñez', '2', 'C', 'susan12'),
('NP242671', 'Christopher Tommy Nùñez Pineda', '2', 'C', 'Tom123'),
('SG242683', 'Cesar Antonio Serrano Gutierrez', '1° Bachillerato', 'A', '12345');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `complementos`
--

DROP TABLE IF EXISTS `complementos`;
CREATE TABLE IF NOT EXISTS `complementos` (
  `id_complemento` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('bebida','guarnicion','ensalada','extra') NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.25',
  `id_plato` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_complemento`),
  KEY `fk_complementos_plato` (`id_plato`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `complementos`
--

INSERT INTO `complementos` (`id_complemento`, `nombre`, `tipo`, `precio`, `id_plato`, `activo`) VALUES
(1, 'Bebida', 'bebida', 0.25, NULL, 0),
(2, 'Arroz', 'guarnicion', 0.25, NULL, 0),
(3, 'Casamiento', 'guarnicion', 0.25, NULL, 0),
(4, 'Papas', 'guarnicion', 0.25, NULL, 0),
(5, 'Chirmol', 'ensalada', 0.25, NULL, 0),
(6, 'Coditos', 'ensalada', 0.25, NULL, 0),
(7, 'Ensalada fresca', 'ensalada', 0.25, NULL, 0),
(8, 'Tortilla', 'extra', 0.10, NULL, 0);

--
-- Disparadores `complementos`
--
DROP TRIGGER IF EXISTS `trg_complementos_after_delete`;
DELIMITER $$
CREATE TRIGGER `trg_complementos_after_delete` AFTER DELETE ON `complementos` FOR EACH ROW BEGIN
  INSERT INTO historial(fecha, usuario, accion, detalle, nombre_tabla)
  VALUES(
    NOW(),
    @currentAdmin,
    'eliminar',
    CONCAT('Eliminó complemento: ', OLD.nombre),
    'complementos'
  );
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_complementos_after_insert`;
DELIMITER $$
CREATE TRIGGER `trg_complementos_after_insert` AFTER INSERT ON `complementos` FOR EACH ROW BEGIN
  INSERT INTO historial(fecha, usuario, accion, detalle, nombre_tabla)
  VALUES(
    NOW(),
    @currentAdmin,
    'crear',
    CONCAT('Creó complemento: ', NEW.nombre),
    'complementos'
  );
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_complementos_after_update`;
DELIMITER $$
CREATE TRIGGER `trg_complementos_after_update` AFTER UPDATE ON `complementos` FOR EACH ROW BEGIN
  IF NOT(OLD.activo <=> NEW.activo) THEN
    INSERT INTO historial(fecha, usuario, accion, detalle, nombre_tabla)
    VALUES(
      NOW(),
      @currentAdmin,
      IF(NEW.activo=1,'habilitar','deshabilitar'),
      CONCAT(
        IF(NEW.activo=1,'Habilitó complemento: ','Deshabilitó complemento: '),
        NEW.nombre
      ),
      'complementos'
    );
  ELSE
    INSERT INTO historial(fecha, usuario, accion, detalle, nombre_tabla)
    VALUES(
      NOW(),
      @currentAdmin,
      'editar',
      CONCAT('Editó complemento: ', NEW.nombre),
      'complementos'
    );
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial`
--

DROP TABLE IF EXISTS `historial`;
CREATE TABLE IF NOT EXISTS `historial` (
  `id_historial` int NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario` varchar(100) NOT NULL,
  `accion` enum('crear','editar','deshabilitar','habilitar','eliminar') NOT NULL,
  `nombre_tabla` varchar(50) NOT NULL,
  `detalle` text NOT NULL,
  PRIMARY KEY (`id_historial`),
  KEY `fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `historial`
--

INSERT INTO `historial` (`id_historial`, `fecha`, `usuario`, `accion`, `nombre_tabla`, `detalle`) VALUES
(1, '2025-05-03 23:03:04', 'Cesar', 'crear', '', 'Creó plato: Cesar'),
(2, '2025-05-03 23:03:20', 'Cesar', 'crear', '', 'Creó plato: Cesar'),
(3, '2025-05-03 23:03:38', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar'),
(4, '2025-05-03 23:03:39', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar'),
(5, '2025-05-03 23:03:40', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(6, '2025-05-03 23:03:41', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(7, '2025-05-03 23:03:42', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar'),
(8, '2025-05-03 23:03:43', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(9, '2025-05-03 23:04:24', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(10, '2025-05-03 23:04:29', 'Cesar', 'editar', '', 'Editó plato: Cesar'),
(11, '2025-05-03 23:04:41', 'Cesar', 'eliminar', '', 'Eliminó plato: Cesar'),
(12, '2025-05-03 23:05:01', 'Cesar', 'crear', '', 'Creó plato: Cesar'),
(13, '2025-05-03 23:06:47', 'Cesar', 'crear', '', 'Creó plato: Cesar'),
(14, '2025-05-03 23:07:08', 'Cesar', 'crear', '', 'Creó plato: aasdsadasdasdasda'),
(15, '2025-05-03 23:07:17', 'Cesar', 'eliminar', '', 'Eliminó plato: Cesar'),
(16, '2025-05-03 23:08:00', 'Cesar', 'eliminar', '', 'Eliminó plato: aasdsadasdasdasda'),
(17, '2025-05-03 23:10:31', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar Serrano'),
(18, '2025-05-03 23:10:40', 'Cesar', 'eliminar', '', 'Eliminó plato: Cesar Serrano'),
(19, '2025-05-03 23:10:59', 'Cesar', 'crear', '', 'Creó plato: Cesar'),
(20, '2025-05-04 17:15:46', 'Cesar', 'crear', '', 'Creó plato: Pollo'),
(21, '2025-05-04 22:41:46', 'Cesar', 'crear', '', 'Creó plato: Mlbb'),
(22, '2025-05-04 22:42:06', 'Cesar', 'crear', '', 'Creó plato: Pollo'),
(23, '2025-05-04 22:42:21', 'Cesar', 'eliminar', '', 'Eliminó plato: Pollo'),
(24, '2025-05-04 22:45:34', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Mlbb'),
(25, '2025-05-04 22:45:36', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Pollo'),
(26, '2025-05-05 18:29:28', 'Cesar', 'crear', '', 'Creó plato: Huevo'),
(27, '2025-05-05 18:29:35', 'Cesar', 'eliminar', '', 'Eliminó plato: Mlbb'),
(28, '2025-05-05 18:29:37', 'Cesar', 'eliminar', '', 'Eliminó plato: Pollo'),
(29, '2025-05-05 18:29:44', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(30, '2025-05-05 18:29:44', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(31, '2025-05-06 14:36:29', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Huevo'),
(32, '2025-05-06 19:46:56', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar'),
(33, '2025-05-06 19:46:58', 'Cesar', 'habilitar', '', 'Habilitar plato: Huevo'),
(34, '2025-05-06 19:52:51', 'Cesar', 'editar', '', 'Editó plato: Platano'),
(35, '2025-05-06 19:53:00', 'Cesar', 'eliminar', '', 'Eliminó plato: Cesar'),
(36, '2025-05-06 19:53:14', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(37, '2025-05-06 19:54:07', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar'),
(38, '2025-05-06 19:54:07', 'Cesar', 'habilitar', '', 'Habilitar plato: Platano'),
(39, '2025-05-07 22:59:07', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Cesar'),
(40, '2025-05-07 22:59:07', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(41, '2025-05-07 22:59:10', 'sistema', 'editar', '', 'Editó plato: Cesar'),
(42, '2025-05-07 22:59:10', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(43, '2025-05-07 22:59:15', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Platano'),
(44, '2025-05-07 22:59:15', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Platano'),
(45, '2025-05-07 22:59:22', 'Cesar', 'eliminar', '', 'Eliminó plato: Platano'),
(46, '2025-05-07 22:59:22', 'Cesar', 'eliminar', '', 'Eliminó plato: Platano'),
(47, '2025-05-07 23:33:55', 'sistema', 'habilitar', '', 'Habilitó plato: Cesar'),
(48, '2025-05-07 23:33:55', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar'),
(49, '2025-05-07 23:42:56', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Cesar'),
(50, '2025-05-07 23:42:56', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(51, '2025-05-07 23:42:57', 'sistema', 'habilitar', '', 'Habilitó plato: Cesar'),
(52, '2025-05-07 23:42:57', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar'),
(53, '2025-05-07 23:42:58', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Cesar'),
(54, '2025-05-07 23:42:58', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(55, '2025-05-07 23:42:59', 'sistema', 'habilitar', '', 'Habilitó plato: Cesar'),
(56, '2025-05-07 23:42:59', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar'),
(57, '2025-05-07 23:42:59', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Cesar'),
(58, '2025-05-07 23:42:59', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(59, '2025-05-07 23:43:00', 'sistema', 'habilitar', '', 'Habilitó plato: Cesar'),
(60, '2025-05-07 23:43:00', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar'),
(61, '2025-05-07 23:43:02', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Cesar'),
(62, '2025-05-07 23:43:02', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(63, '2025-05-07 23:43:03', 'sistema', 'habilitar', '', 'Habilitó plato: Cesar'),
(64, '2025-05-07 23:43:03', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar'),
(65, '2025-05-07 23:43:04', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Cesar'),
(66, '2025-05-07 23:43:04', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar'),
(67, '2025-05-07 23:43:05', 'sistema', 'habilitar', '', 'Habilitó plato: Cesar'),
(68, '2025-05-07 23:43:05', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar'),
(69, '2025-05-07 23:43:34', 'Cesar', 'crear', '', 'Creó plato: Pollo'),
(70, '2025-05-07 23:43:34', 'Cesar', 'crear', '', 'Creó plato: Pollo'),
(71, '2025-05-07 23:43:47', 'Cesar', 'deshabilitar', '', 'Deshabilitó plato: Cesar'),
(72, '2025-05-07 23:43:47', 'Cesar', 'editar', '', 'Editó plato: Cesar'),
(73, '2025-05-07 23:44:02', 'Cesar', 'crear', '', 'Creó plato: a'),
(74, '2025-05-07 23:44:02', 'Cesar', 'crear', '', 'Creó plato: a'),
(75, '2025-05-07 23:44:19', 'Cesar', 'crear', '', 'Creó plato: dasda'),
(76, '2025-05-07 23:44:19', 'Cesar', 'crear', '', 'Creó plato: dasda'),
(77, '2025-05-07 23:44:34', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Pollo'),
(78, '2025-05-07 23:44:34', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Pollo'),
(79, '2025-05-07 23:44:35', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: dasda'),
(80, '2025-05-07 23:44:35', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: dasda'),
(81, '2025-05-07 23:44:35', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: a'),
(82, '2025-05-07 23:44:35', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: a'),
(83, '2025-05-07 23:44:48', 'Cesar', 'eliminar', '', 'Eliminó plato: a'),
(84, '2025-05-07 23:44:48', 'Cesar', 'eliminar', '', 'Eliminó plato: a'),
(85, '2025-05-07 23:44:52', 'Cesar', 'eliminar', '', 'Eliminó plato: dasda'),
(86, '2025-05-07 23:44:52', 'Cesar', 'eliminar', '', 'Eliminó plato: dasda'),
(87, '2025-05-07 23:45:16', 'Cesar', 'editar', '', 'Editó plato: Cesardda'),
(88, '2025-05-07 23:45:16', 'Cesar', 'editar', '', 'Editó plato: Cesardda'),
(89, '2025-05-07 23:45:43', 'Cesar', 'editar', '', 'Editó plato: Cesardda'),
(90, '2025-05-07 23:45:43', 'Cesar', 'editar', '', 'Editó plato: Cesardda'),
(91, '2025-05-07 23:58:52', 'sistema', 'habilitar', '', 'Habilitó plato: Cesardda'),
(92, '2025-05-07 23:58:52', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesardda'),
(93, '2025-05-07 23:58:53', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Cesardda'),
(94, '2025-05-07 23:58:53', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesardda'),
(95, '2025-05-07 23:58:54', 'sistema', 'habilitar', '', 'Habilitó plato: Pollo'),
(96, '2025-05-07 23:58:54', 'Cesar', 'habilitar', '', 'Habilitar plato: Pollo'),
(97, '2025-05-07 23:58:55', 'sistema', 'habilitar', '', 'Habilitó plato: Cesardda'),
(98, '2025-05-07 23:58:55', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesardda'),
(99, '2025-05-07 23:59:17', 'Cesar', 'crear', '', 'Creó plato: Cesar Serrano'),
(100, '2025-05-07 23:59:17', 'Cesar', 'crear', '', 'Creó plato: Cesar Serrano'),
(101, '2025-05-08 00:02:10', 'Cesar', 'deshabilitar', '', 'Deshabilitó plato: Cesar Serrano'),
(102, '2025-05-08 00:02:10', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(103, '2025-05-08 00:02:15', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(104, '2025-05-08 00:02:15', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(105, '2025-05-08 00:02:19', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(106, '2025-05-08 00:02:19', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(107, '2025-05-08 00:02:23', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(108, '2025-05-08 00:02:23', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(109, '2025-05-08 00:02:28', 'Cesar', 'deshabilitar', '', 'Deshabilitó plato: Cesardda'),
(110, '2025-05-08 00:02:28', 'Cesar', 'editar', '', 'Editó plato: Cesardda'),
(111, '2025-05-08 00:02:32', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(112, '2025-05-08 00:02:32', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(113, '2025-05-08 00:06:01', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(114, '2025-05-08 00:06:01', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(115, '2025-05-08 00:06:05', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(116, '2025-05-08 00:06:05', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(117, '2025-05-08 00:06:14', 'Cesar', 'deshabilitar', '', 'Deshabilitó plato: Pollo'),
(118, '2025-05-08 00:06:14', 'Cesar', 'editar', '', 'Editó plato: Pollo'),
(119, '2025-05-08 00:09:41', 'Cesar', 'editar', '', 'Editó plato: Pollo'),
(120, '2025-05-08 00:09:41', 'Cesar', 'editar', '', 'Editó plato: Pollo'),
(121, '2025-05-08 00:09:47', 'Cesar', 'editar', '', 'Editó plato: Pollo'),
(122, '2025-05-08 00:09:47', 'Cesar', 'editar', '', 'Editó plato: Pollo'),
(123, '2025-05-08 00:09:53', 'Cesar', 'editar', '', 'Editó plato: Cesardda'),
(124, '2025-05-08 00:09:53', 'Cesar', 'editar', '', 'Editó plato: Cesardda'),
(125, '2025-05-08 00:09:58', 'sistema', 'habilitar', '', 'Habilitó plato: Cesar Serrano'),
(126, '2025-05-08 00:09:58', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar Serrano'),
(127, '2025-05-08 00:10:03', 'Cesar', 'deshabilitar', '', 'Deshabilitó plato: Cesar Serrano'),
(128, '2025-05-08 00:10:03', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(129, '2025-05-08 00:10:09', 'Cesar', 'editar', '', 'Editó plato: Cesardda'),
(130, '2025-05-08 00:10:09', 'Cesar', 'editar', '', 'Editó plato: Cesardda'),
(131, '2025-05-08 00:14:22', 'Cesar', 'crear', '', 'Creó plato: Pollo asado'),
(132, '2025-05-08 00:14:22', 'Cesar', 'crear', '', 'Creó plato: Pollo asado'),
(133, '2025-05-08 00:14:28', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Pollo asado'),
(134, '2025-05-08 00:14:28', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Pollo asado'),
(135, '2025-05-08 00:14:29', 'sistema', 'habilitar', '', 'Habilitó plato: Cesar Serrano'),
(136, '2025-05-08 00:14:29', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar Serrano'),
(137, '2025-05-08 00:15:30', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Cesar Serrano'),
(138, '2025-05-08 00:15:30', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar Serrano'),
(139, '2025-05-08 00:15:30', 'sistema', 'habilitar', '', 'Habilitó plato: Cesar Serrano'),
(140, '2025-05-08 00:15:30', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar Serrano'),
(141, '2025-05-08 00:15:31', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Cesar Serrano'),
(142, '2025-05-08 00:15:31', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar Serrano'),
(143, '2025-05-08 00:15:50', 'Cesar', 'crear', '', 'Creó plato: webo'),
(144, '2025-05-08 00:15:50', 'Cesar', 'crear', '', 'Creó plato: webo'),
(145, '2025-05-08 00:15:56', 'sistema', 'habilitar', '', 'Habilitó plato: webo'),
(146, '2025-05-08 00:15:56', 'Cesar', 'habilitar', '', 'Habilitar plato: webo'),
(147, '2025-05-08 00:15:59', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: webo'),
(148, '2025-05-08 00:15:59', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: webo'),
(149, '2025-05-08 00:16:04', 'sistema', 'habilitar', '', 'Habilitó plato: webo'),
(150, '2025-05-08 00:16:04', 'Cesar', 'habilitar', '', 'Habilitar plato: webo'),
(151, '2025-05-08 00:21:25', 'Cesar', 'editar', '', 'Editó plato: wazaaaaaaaaaaa'),
(152, '2025-05-08 00:21:25', 'Cesar', 'editar', '', 'Editó plato: wazaaaaaaaaaaa'),
(153, '2025-05-08 00:21:32', 'sistema', 'habilitar', '', 'Habilitó plato: wazaaaaaaaaaaa'),
(154, '2025-05-08 00:21:32', 'Cesar', 'habilitar', '', 'Habilitar plato: wazaaaaaaaaaaa'),
(155, '2025-05-08 00:21:54', 'Cesar', 'eliminar', '', 'Eliminó plato: Pollo asado'),
(156, '2025-05-08 00:21:54', 'Cesar', 'eliminar', '', 'Eliminó plato: Pollo asado'),
(157, '2025-05-08 00:21:59', 'Cesar', 'eliminar', '', 'Eliminó plato: Cesardda'),
(158, '2025-05-08 00:21:59', 'Cesar', 'eliminar', '', 'Eliminó plato: Cesardda'),
(159, '2025-05-08 00:34:13', 'Cesar', 'deshabilitar', '', 'Deshabilitó plato: webo'),
(160, '2025-05-08 00:34:13', 'Cesar', 'editar', '', 'Editó plato: webo'),
(161, '2025-05-08 00:34:18', 'Cesar', 'deshabilitar', '', 'Deshabilitó plato: wazaaaaaaaaaaa'),
(162, '2025-05-08 00:34:18', 'Cesar', 'editar', '', 'Editó plato: wazaaaaaaaaaaa'),
(163, '2025-05-08 00:34:21', 'sistema', 'habilitar', '', 'Habilitó plato: wazaaaaaaaaaaa'),
(164, '2025-05-08 00:34:21', 'Cesar', 'habilitar', '', 'Habilitar plato: wazaaaaaaaaaaa'),
(165, '2025-05-08 00:34:23', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: wazaaaaaaaaaaa'),
(166, '2025-05-08 00:34:23', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: wazaaaaaaaaaaa'),
(167, '2025-05-08 00:34:28', 'Cesar', 'eliminar', '', 'Eliminó plato: wazaaaaaaaaaaa'),
(168, '2025-05-08 00:34:28', 'Cesar', 'eliminar', '', 'Eliminó plato: wazaaaaaaaaaaa'),
(169, '2025-05-08 01:02:45', 'Cesar', 'editar', '', 'Editó plato: Pollo asadoa'),
(170, '2025-05-08 01:02:45', 'Cesar', 'editar', '', 'Editó plato: Pollo asadoa'),
(171, '2025-05-08 01:02:47', 'sistema', 'habilitar', '', 'Habilitó plato: Pollo asadoa'),
(172, '2025-05-08 01:02:47', 'Cesar', 'habilitar', '', 'Habilitar plato: Pollo asadoa'),
(173, '2025-05-08 01:02:55', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Pollo asadoa'),
(174, '2025-05-08 01:02:55', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Pollo asadoa'),
(175, '2025-05-08 01:03:12', 'Cesar', 'crear', '', 'Creó plato: wazaaaaaaaaaaa'),
(176, '2025-05-08 01:03:12', 'Cesar', 'crear', '', 'Creó plato: wazaaaaaaaaaaa'),
(177, '2025-05-08 01:04:50', 'Cesar', 'eliminar', '', 'Eliminó plato: wazaaaaaaaaaaa'),
(178, '2025-05-08 01:04:50', 'Cesar', 'eliminar', '', 'Eliminó plato: wazaaaaaaaaaaa'),
(179, '2025-05-08 01:05:03', 'Cesar', 'crear', '', 'Creó plato: wazaaaaaaaaaaa'),
(180, '2025-05-08 01:05:03', 'Cesar', 'crear', '', 'Creó plato: wazaaaaaaaaaaa'),
(181, '2025-05-08 01:06:31', 'sistema', 'habilitar', '', 'Habilitó plato: wazaaaaaaaaaaa'),
(182, '2025-05-08 01:06:31', 'Cesar', 'habilitar', '', 'Habilitar plato: wazaaaaaaaaaaa'),
(183, '2025-05-08 01:06:33', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: wazaaaaaaaaaaa'),
(184, '2025-05-08 01:06:33', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: wazaaaaaaaaaaa'),
(185, '2025-05-08 01:06:36', 'Cesar', 'editar', '', 'Editó plato: Pollo asadoaa'),
(186, '2025-05-08 01:06:36', 'Cesar', 'editar', '', 'Editó plato: Pollo asadoaa'),
(187, '2025-05-08 01:06:44', 'Cesar', 'eliminar', '', 'Eliminó plato: wazaaaaaaaaaaa'),
(188, '2025-05-08 01:06:44', 'Cesar', 'eliminar', '', 'Eliminó plato: wazaaaaaaaaaaa'),
(189, '2025-05-08 01:15:17', 'sistema', 'habilitar', '', 'Habilitó plato: Pollo asadoaa'),
(190, '2025-05-08 01:15:17', 'Cesar', 'habilitar', '', 'Habilitar plato: Pollo asadoaa'),
(191, '2025-05-08 01:15:18', 'sistema', 'habilitar', '', 'Habilitó plato: webo'),
(192, '2025-05-08 01:15:18', 'Cesar', 'habilitar', '', 'Habilitar plato: webo'),
(193, '2025-05-08 01:56:53', 'Cesar', 'crear', '', 'Creó plato: Cesar Serrano'),
(194, '2025-05-08 01:56:54', 'Cesar', 'crear', '', 'Creó plato: Cesar Serrano'),
(195, '2025-05-08 01:56:57', 'sistema', 'habilitar', '', 'Habilitó plato: Cesar Serrano'),
(196, '2025-05-08 01:56:57', 'Cesar', 'habilitar', '', 'Habilitar plato: Cesar Serrano'),
(197, '2025-05-08 01:57:01', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Cesar Serrano'),
(198, '2025-05-08 01:57:01', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Cesar Serrano'),
(199, '2025-05-08 01:57:07', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(200, '2025-05-08 01:57:07', 'Cesar', 'editar', '', 'Editó plato: Cesar Serrano'),
(201, '2025-05-08 01:57:27', 'Cesar', 'eliminar', '', 'Eliminó plato: Cesar Serrano'),
(202, '2025-05-08 01:57:27', 'Cesar', 'eliminar', '', 'Eliminó plato: Cesar Serrano'),
(203, '2025-05-08 02:34:17', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Pollo asadoaa'),
(204, '2025-05-08 02:34:17', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Pollo asadoaa'),
(205, '2025-05-08 02:34:18', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: webo'),
(206, '2025-05-08 02:34:18', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: webo'),
(207, '2025-05-08 02:34:21', 'sistema', 'habilitar', '', 'Habilitó plato: Pollo asadoaa'),
(208, '2025-05-08 02:34:21', 'Cesar', 'habilitar', '', 'Habilitar plato: Pollo asadoaa'),
(209, '2025-05-08 02:34:22', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Pollo asadoaa'),
(210, '2025-05-08 02:34:22', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Pollo asadoaa'),
(211, '2025-05-08 02:34:24', 'sistema', 'habilitar', '', 'Habilitó plato: Pollo asadoaa'),
(212, '2025-05-08 02:34:24', 'Cesar', 'habilitar', '', 'Habilitar plato: Pollo asadoaa'),
(213, '2025-05-08 02:34:25', 'sistema', 'deshabilitar', '', 'Deshabilitó plato: Pollo asadoaa'),
(214, '2025-05-08 02:34:25', 'Cesar', 'deshabilitar', '', 'Deshabilitar plato: Pollo asadoaa'),
(215, '2025-05-08 03:45:58', 'root@localhost', '', '', 'Creó complemento (id=12, nombre=zero two, tipo=guarnicion, precio=50.00)'),
(216, '2025-05-08 03:46:26', 'root@localhost', 'habilitar', '', 'Habilitó complemento (id=12, nombre=zero two)'),
(217, '2025-05-08 03:53:48', 'root@localhost', 'crear', 'complementos', 'Creó complemento: Pollo'),
(218, '2025-05-08 03:54:29', 'root@localhost', 'eliminar', 'complementos', 'Eliminó complemento: Pollo'),
(219, '2025-05-08 03:54:43', 'root@localhost', 'deshabilitar', 'complementos', 'Deshabilitó complemento: zero two'),
(220, '2025-05-08 03:54:46', 'root@localhost', 'eliminar', 'complementos', 'Eliminó complemento: zero two'),
(221, '2025-05-08 03:55:13', 'root@localhost', 'crear', 'complementos', 'Creó complemento: aasdsadasdasdasda'),
(222, '2025-05-08 04:11:32', 'Cesar', 'deshabilitar', 'complementos', 'Deshabilitó complemento: aasdsadasdasdasda'),
(223, '2025-05-08 04:11:43', 'Cesar', 'crear', 'complementos', 'Creó complemento: hola'),
(224, '2025-05-08 04:18:57', 'Cesar', 'habilitar', 'complementos', 'Habilitó complemento: hola'),
(225, '2025-05-08 04:19:42', 'Cesar', 'eliminar', 'complementos', 'Eliminó complemento: aasdsadasdasdasda'),
(226, '2025-05-08 04:19:44', 'Cesar', 'eliminar', 'complementos', 'Eliminó complemento: hola');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id_pedido` int NOT NULL AUTO_INCREMENT,
  `carnet_alumno` varchar(20) NOT NULL,
  `descripcion_pedido` text NOT NULL,
  `fecha_reserva` date NOT NULL DEFAULT (curdate()),
  `id_admin` int NOT NULL,
  `id_plato` int NOT NULL,
  `carnet_admin` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_pedido`),
  KEY `carnet_alumno` (`carnet_alumno`),
  KEY `id_admin` (`id_admin`),
  KEY `carnet_admin` (`carnet_admin`),
  KEY `id_plato` (`id_plato`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `carnet_alumno`, `descripcion_pedido`, `fecha_reserva`, `id_admin`, `id_plato`, `carnet_admin`) VALUES
(5, 'SG242683', 'Plato: aasdsadasdasdasda | Bebida: Sin bebida | Guarnición: Sin guarnición | Ensalada: Sin ensalada | Tortillas: 0', '2025-05-01', 1, 4, NULL),
(6, 'SG242683', 'Plato: aasdsadasdasdasda | Bebida: Sin bebida | Guarnición: Sin guarnición | Ensalada: Sin ensalada | Tortillas: 0', '2025-05-01', 1, 4, NULL),
(7, 'SG242683', 'Plato: aasdsadasdasdasda | Bebida: Sin bebida | Guarnición: Sin guarnición | Ensalada: Sin ensalada | Tortillas: 0', '2025-05-01', 1, 4, NULL),
(8, 'SG242683', 'Plato: aasdsadasdasdasda | Bebida: Sin bebida | Guarnición: Sin guarnición | Ensalada: Sin ensalada | Tortillas: 0', '2025-05-01', 1, 4, NULL),
(9, 'SG242683', 'Plato: aasdsadasdasdasda | Bebida: Sin bebida | Guarnición: Sin guarnición | Ensalada: Sin ensalada | Tortillas: 0', '2025-05-01', 1, 4, NULL),
(10, 'SG242683', 'Plato: aasdsadasdasdasda | Bebida: Sin bebida | Guarnición: Sin guarnición | Ensalada: Sin ensalada | Tortillas: 0', '2025-05-01', 1, 4, NULL),
(11, 'SG242683', 'Plato: Cesar | Tortillas:  | 1 x Tortilla', '2025-05-04', 0, 4, NULL),
(12, 'SG242683', 'Plato: Cesar | Tortillas: ', '2025-05-04', 0, 4, NULL),
(13, 'SG242683', 'Plato: Cesar | Tortillas: ', '2025-05-04', 0, 4, NULL),
(14, 'AM239540', 'Plato: Cesar | Tortillas:  | 1 x Tortilla | Bebida: Bebida | Guarnicion: Arroz | Ensalada: Chirmol', '2025-05-04', 0, 6, NULL),
(18, 'NP242671', 'Plato: Cesar | Tortillas:  | 2 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-05', 0, 6, NULL),
(19, 'NP242671', 'Plato: Cesar | Tortillas:  | 5 x Tortilla | Bebida: Bebida | Guarnicion: Papas | Ensalada: Ensalada fresca', '2025-05-05', 0, 4, NULL),
(20, 'NP242671', 'Plato: Huevo | Tortillas: ', '2025-05-05', 0, 10, NULL),
(21, 'NP242671', 'Plato: Cesar | Tortillas:  | 1 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Chirmol', '2025-05-06', 0, 0, NULL),
(22, 'NP242671', 'Plato: Cesar | Tortillas:  | 1 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Chirmol', '2025-05-06', 0, 0, NULL),
(23, 'NP242671', 'Plato: Cesar | Tortillas:  | 1 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Chirmol', '2025-05-06', 0, 0, NULL),
(24, 'NP242671', 'Plato: Cesar | Tortillas:  | 1 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Chirmol', '2025-05-06', 0, 0, NULL),
(25, 'NP242671', 'Plato: Cesar | Tortillas:  | 2 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Chirmol', '2025-05-06', 0, 0, NULL),
(26, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(27, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(28, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(29, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(30, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(31, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(32, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(33, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(34, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(35, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(36, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(37, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(38, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(39, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(40, 'NP242671', 'Plato: Cesar | Tortillas:  | 4 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(41, 'NP242671', 'Plato: Platano | Tortillas: ', '2025-05-06', 0, 0, NULL),
(42, 'NP242671', 'Plato: Platano | Tortillas: ', '2025-05-06', 0, 0, NULL),
(43, 'NP242671', 'Plato: Platano | Tortillas: ', '2025-05-06', 0, 0, NULL),
(44, 'NP242671', 'Plato: Platano | Tortillas: ', '2025-05-06', 0, 0, NULL),
(45, 'NP242671', 'Plato: Platano | Tortillas:  | 2 x Tortilla | Bebida: Bebida | Guarnicion: Papas | Ensalada: Coditos', '2025-05-06', 0, 0, NULL),
(46, 'NP242670', 'Plato: Platano | Tortillas:  | 2 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Chirmol', '2025-05-06', 0, 0, NULL),
(47, 'NP242670', 'Plato: Platano | Tortillas:  | 5 x Tortilla | Bebida: Bebida | Guarnicion: Casamiento | Ensalada: Coditos', '2025-05-06', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_plato`
--

DROP TABLE IF EXISTS `pedido_plato`;
CREATE TABLE IF NOT EXISTS `pedido_plato` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_plato` int NOT NULL,
  `id_complemento` int DEFAULT NULL,
  `monto` decimal(18,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_pedido` (`id_pedido`),
  KEY `id_plato` (`id_plato`),
  KEY `id_complemento` (`id_complemento`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `platos`
--

DROP TABLE IF EXISTS `platos`;
CREATE TABLE IF NOT EXISTS `platos` (
  `id_plato` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `limite_disponible` int NOT NULL DEFAULT '0',
  `imagen` mediumblob,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_plato`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `platos`
--

INSERT INTO `platos` (`id_plato`, `nombre`, `descripcion`, `precio`, `limite_disponible`, `imagen`, `activo`, `created_at`, `updated_at`) VALUES
(11, 'Pollo asadoaa', 'rico pollo', 3.00, 3, 0x30, 0, '2025-05-07 23:43:34', '2025-05-08 02:34:25'),
(16, 'webo', '123', 1.00, 1, 0x31, 0, '2025-05-08 00:15:50', '2025-05-08 02:34:18');

--
-- Disparadores `platos`
--
DROP TRIGGER IF EXISTS `trg_hist_platos_after_delete`;
DELIMITER $$
CREATE TRIGGER `trg_hist_platos_after_delete` AFTER DELETE ON `platos` FOR EACH ROW BEGIN
  -- Registrar en historial cuando se elimina un plato
  INSERT INTO historial (fecha, usuario, accion, detalle)
  VALUES (
    NOW(),
    COALESCE(@usuario, 'sistema'),
    'eliminar',
    CONCAT('Eliminó plato: ', OLD.nombre)
  );
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_hist_platos_after_insert`;
DELIMITER $$
CREATE TRIGGER `trg_hist_platos_after_insert` AFTER INSERT ON `platos` FOR EACH ROW BEGIN
  -- Inserta un registro en historial cada vez que alguien añade un plato
  INSERT INTO historial (fecha, usuario, accion, detalle)
  VALUES (
    NOW(),
    COALESCE(@usuario, 'sistema'),
    'crear',
    CONCAT('Creó plato: ', NEW.nombre)
  );
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_hist_platos_after_update`;
DELIMITER $$
CREATE TRIGGER `trg_hist_platos_after_update` AFTER UPDATE ON `platos` FOR EACH ROW BEGIN
  DECLARE v_accion VARCHAR(12);
  DECLARE v_detalle VARCHAR(255);

  -- Determinar tipo de acción
  IF OLD.activo = 1 AND NEW.activo = 0 THEN
    SET v_accion = 'deshabilitar';
    SET v_detalle = CONCAT('Deshabilitó plato: ', NEW.nombre);
  ELSEIF OLD.activo = 0 AND NEW.activo = 1 THEN
    SET v_accion = 'habilitar';
    SET v_detalle = CONCAT('Habilitó plato: ', NEW.nombre);
  ELSE
    SET v_accion = 'editar';
    SET v_detalle = CONCAT('Editó plato: ', NEW.nombre);
  END IF;

  -- Insertar en historial
  INSERT INTO historial (fecha, usuario, accion, detalle)
  VALUES (
    NOW(),
    COALESCE(@usuario, 'sistema'),
    v_accion,
    v_detalle
  );
END
$$
DELIMITER ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_alumno` FOREIGN KEY (`carnet_alumno`) REFERENCES `alumnos` (`carnet`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`carnet_admin`) REFERENCES `administradores` (`carnet`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedido_plato`
--
ALTER TABLE `pedido_plato`
  ADD CONSTRAINT `pedido_plato_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_plato_ibfk_2` FOREIGN KEY (`id_plato`) REFERENCES `platos` (`id_plato`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_plato_ibfk_3` FOREIGN KEY (`id_complemento`) REFERENCES `complementos` (`id_complemento`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
