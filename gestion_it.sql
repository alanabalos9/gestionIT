-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-04-2026 a las 04:13:03
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gestion_it`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(1, 'Notebook'),
(2, 'Monitor'),
(3, 'Servidor'),
(4, 'Impresora'),
(5, 'Desktop'),
(6, 'Periférico'),
(7, 'Tablet'),
(8, 'Redes'),
(9, 'Energía');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id` int(11) NOT NULL,
  `codigo_patrimonial` varchar(50) DEFAULT NULL,
  `tipo_id` int(11) DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `serie` varchar(100) DEFAULT NULL,
  `estado` enum('Administrador','Tecnico','Operativo') DEFAULT 'Operativo',
  `usuario_asignado_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id`, `codigo_patrimonial`, `tipo_id`, `marca`, `modelo`, `serie`, `estado`, `usuario_asignado_id`) VALUES
(61, 'NBK-2024-001', 1, 'Dell', 'Latitude 3420', NULL, '', NULL),
(62, 'NBK-2024-002', 1, 'Lenovo', 'ThinkPad E14', NULL, '', NULL),
(63, 'MON-24-015', 2, 'Samsung', 'Odyssey G3 24\"', NULL, '', NULL),
(64, 'MON-27-008', 2, 'LG', 'UltraGear 27\"', NULL, '', NULL),
(65, 'SRV-DB-01', 3, 'HP', 'ProLiant DL380', NULL, '', NULL),
(66, 'PRN-OFF-03', 4, 'Brother', 'HL-L2350DW', NULL, '', NULL),
(67, 'NBK-2023-088', 1, 'Apple', 'MacBook Air M2', NULL, '', NULL),
(68, 'PC-DESK-010', 5, 'Banghó', 'Cross B24', NULL, '', NULL),
(69, 'MON-24-019', 2, 'Dell', 'P2422H', NULL, '', NULL),
(70, 'PER-KYB-005', 6, 'Logitech', 'K120 USB', NULL, '', NULL),
(71, 'PER-MOU-012', 6, 'Genius', 'DX-110', NULL, '', NULL),
(72, 'SRV-WEB-02', 3, 'Dell', 'PowerEdge T150', NULL, '', NULL),
(73, 'NBK-2024-005', 1, 'ASUS', 'Vivobook 15', NULL, '', NULL),
(74, 'TAB-01-001', 7, 'Samsung', 'Galaxy Tab S9', NULL, '', NULL),
(75, 'PRN-LOG-01', 4, 'Epson', 'EcoTank L3250', NULL, '', NULL),
(76, 'SWT-CORE-01', 8, 'Cisco', 'Catalyst 2960', NULL, '', NULL),
(77, 'WAP-PISO1-02', 8, 'Ubiquiti', 'UniFi AP AC Pro', NULL, '', NULL),
(78, 'NBK-2024-010', 1, 'HP', 'ProBook 440 G9', NULL, '', NULL),
(79, 'MON-19-002', 2, 'ViewSonic', 'VA1903H', NULL, '', NULL),
(80, 'UPS-SVR-01', 9, 'APC', 'Smart-UPS 1500VA', NULL, '', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `asunto` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `prioridad` enum('Baja','Media','Alta','Urgente') DEFAULT 'Media',
  `estado` enum('Nuevo','En curso','Resuelto','Cerrado') DEFAULT 'Nuevo',
  `solicitante_id` int(11) NOT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tickets`
--

INSERT INTO `tickets` (`id`, `asunto`, `descripcion`, `prioridad`, `estado`, `solicitante_id`, `tecnico_id`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Error en Outlook', 'No puedo enviar correos desde esta mañana, arroja error de servidor.', 'Media', 'Nuevo', 3, NULL, '2026-04-09 10:38:58', '2026-04-09 10:38:58'),
(3, 'inicio pc', 'No enciende', 'Media', 'Nuevo', 1, NULL, '2026-04-14 14:36:18', '2026-04-14 14:36:18'),
(4, 'conexion a internet', 'Estoy teniendo incovenientes con la red ', 'Media', 'Nuevo', 1, NULL, '2026-04-14 14:38:56', '2026-04-14 14:38:56'),
(5, 'falla impresion', 'mi impresora no conecta', 'Media', 'Nuevo', 1, NULL, '2026-04-14 14:42:03', '2026-04-14 14:42:03'),
(7, 'acceso a carpeta compartida', 'Intento ingresar a la carpeta de Finanzas y me pide una contraseña que no reconozco', 'Media', 'Nuevo', 1, NULL, '2026-04-15 12:46:55', '2026-04-15 12:46:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('administrador','operativo','tecnico') DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `usuario`, `email`, `password`, `rol`, `ultimo_login`, `creado_en`) VALUES
(1, 'Administrador TI', 'admin', 'testadministrador@gmail.com', 'admin123', 'administrador', NULL, '2026-04-09 10:38:58'),
(3, 'Operador de Turno', 'operativo02', 'testoperativo02@gmail.com', 'pass123', 'operativo', NULL, '2026-04-09 10:38:58'),
(5, 'Operador de Turno', 'operativo01', 'testoperativo01@gmail.com', 'op123', 'operativo', NULL, '2026-04-21 14:03:03'),
(6, 'Soporte Técnico', 'tecnico01', 'testtecnico@gmail.com', 'tec123', 'tecnico', NULL, '2026-04-21 14:03:03');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_patrimonial` (`codigo_patrimonial`),
  ADD KEY `tipo_id` (`tipo_id`),
  ADD KEY `usuario_asignado_id` (`usuario_asignado_id`);

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicitante_id` (`solicitante_id`),
  ADD KEY `tecnico_id` (`tecnico_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`tipo_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `inventario_ibfk_2` FOREIGN KEY (`usuario_asignado_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
