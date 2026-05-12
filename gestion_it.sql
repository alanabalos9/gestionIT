-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-05-2026 a las 04:40:32
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
  `usuario_asignado_id` int(11) DEFAULT NULL,
  `sector` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id`, `codigo_patrimonial`, `tipo_id`, `marca`, `modelo`, `serie`, `estado`, `usuario_asignado_id`, `sector`) VALUES
(61, 'NBK-2024-001', 1, 'Dell', 'Latitude 3420', NULL, '', 1, 'Administración'),
(62, 'NBK-2024-002', 1, 'Lenovo', 'ThinkPad E14', NULL, '', 3, 'Sistemas'),
(63, 'MON-24-015', 2, 'Samsung', 'Odyssey G3 24\"', NULL, '', 5, 'Ventas'),
(64, 'MON-27-008', 2, 'LG', 'UltraGear 27\"', NULL, '', 6, 'Sistemas'),
(65, 'SRV-DB-01', 3, 'HP', 'ProLiant DL380', NULL, '', 10, 'Data Center'),
(66, 'PRN-OFF-03', 4, 'Brother', 'HL-L2350DW', NULL, '', 13, 'Recepción'),
(67, 'NBK-2023-088', 1, 'Apple', 'MacBook Air M2', NULL, '', NULL, NULL),
(68, 'PC-DESK-010', 5, 'Banghó', 'Cross B24', NULL, '', NULL, NULL),
(69, 'MON-24-019', 2, 'Dell', 'P2422H', NULL, '', NULL, NULL),
(70, 'PER-KYB-005', 6, 'Logitech', 'K120 USB', NULL, '', 14, 'Ventas'),
(71, 'PER-MOU-012', 6, 'Genius', 'DX-110', NULL, '', 15, 'Ventas'),
(72, 'SRV-WEB-02', 3, 'Dell', 'PowerEdge T150', NULL, '', NULL, NULL),
(73, 'NBK-2024-005', 1, 'ASUS', 'Vivobook 15', NULL, '', NULL, NULL),
(74, 'TAB-01-001', 7, 'Samsung', 'Galaxy Tab S9', NULL, '', NULL, NULL),
(75, 'PRN-LOG-01', 4, 'Epson', 'EcoTank L3250', NULL, '', 12, 'Contabilidad'),
(76, 'SWT-CORE-01', 8, 'Cisco', 'Catalyst 2960', NULL, '', 11, 'Infraestructura'),
(77, 'WAP-PISO1-02', 8, 'Ubiquiti', 'UniFi AP AC Pro', NULL, '', NULL, NULL),
(78, 'NBK-2024-010', 1, 'HP', 'ProBook 440 G9', NULL, '', NULL, NULL),
(79, 'MON-19-002', 2, 'ViewSonic', 'VA1903H', NULL, '', 12, 'Administración'),
(80, 'UPS-SVR-01', 9, 'APC', 'Smart-UPS 1500VA', NULL, '', NULL, '');

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
  `detalle_resolucion` text DEFAULT NULL,
  `solicitante_id` int(11) NOT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_mantenimiento` date DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fecha_limite` datetime DEFAULT NULL,
  `tipo` enum('Incidencia','Solicitud') DEFAULT 'Incidencia',
  `origen` varchar(50) DEFAULT NULL,
  `archivo_adjunto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tickets`
--

INSERT INTO `tickets` (`id`, `asunto`, `descripcion`, `prioridad`, `estado`, `detalle_resolucion`, `solicitante_id`, `tecnico_id`, `fecha_creacion`, `fecha_mantenimiento`, `fecha_actualizacion`, `fecha_limite`, `tipo`, `origen`, `archivo_adjunto`) VALUES
(1, 'Error en Outlook', 'No puedo enviar correos desde esta mañana, arroja error de servidor.', 'Media', 'Nuevo', NULL, 3, NULL, '2026-04-09 10:38:58', NULL, '2026-04-09 10:38:58', NULL, 'Incidencia', NULL, NULL),
(3, 'inicio pc', 'No enciende', 'Media', 'Nuevo', NULL, 1, NULL, '2026-04-14 14:36:18', NULL, '2026-04-14 14:36:18', NULL, 'Incidencia', NULL, NULL),
(4, 'conexion a internet', 'Estoy teniendo incovenientes con la red ', 'Media', 'Nuevo', NULL, 1, NULL, '2026-04-14 14:38:56', NULL, '2026-04-14 14:38:56', NULL, 'Incidencia', NULL, NULL),
(5, 'falla impresion', 'mi impresora no conecta', 'Media', '', NULL, 1, 6, '2026-04-14 14:42:03', NULL, '2026-04-30 00:23:44', NULL, 'Incidencia', NULL, NULL),
(7, 'acceso a carpeta compartida', 'Intento ingresar a la carpeta de Finanzas y me pide una contraseña que no reconozco', 'Media', '', NULL, 1, 6, '2026-04-15 12:46:55', NULL, '2026-04-30 00:05:30', NULL, 'Incidencia', NULL, NULL);

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
(1, 'Tatiana Daian', 'admin', 'testadministrador@gmail.com', 'admin123', 'administrador', NULL, '2026-04-09 10:38:58'),
(3, 'Franco Calizaya', 'operativo02', 'testoperativo02@gmail.com', 'pass123', 'operativo', NULL, '2026-04-09 10:38:58'),
(5, 'Juan Abalos', 'operativo01', 'testoperativo01@gmail.com', 'op123', 'operativo', NULL, '2026-04-21 14:03:03'),
(6, 'Samuel Toscano', 'tecnico01', 'testtecnico@gmail.com', 'tec123', 'tecnico', NULL, '2026-04-21 14:03:03'),
(10, 'Carlos Mendez', 'admin02', 'admin02@neoadmin.com', 'admin456', 'administrador', NULL, '2026-04-30 00:29:35'),
(11, 'Lucia Fernandez', 'admin03', 'admin03@neoadmin.com', 'admin789', 'administrador', NULL, '2026-04-30 00:29:35'),
(12, 'Marcos Ruiz', 'tecnico02', 'mruiz@neoadmin.com', 'tec456', 'tecnico', NULL, '2026-04-30 00:29:35'),
(13, 'Elena Gomez', 'tecnico03', 'egomez@neoadmin.com', 'tec789', 'tecnico', NULL, '2026-04-30 00:29:35'),
(14, 'Roberto Diaz', 'tecnico04', 'rdiaz@neoadmin.com', 'tec101', 'tecnico', NULL, '2026-04-30 00:29:35'),
(15, 'Julia Lopez', 'tecnico05', 'jlopez@neoadmin.com', 'tec202', 'tecnico', NULL, '2026-04-30 00:29:35'),
(16, 'Pedro Sanchez', 'op03', 'op03@test.com', 'pass03', 'operativo', NULL, '2026-04-30 00:29:35'),
(17, 'Maria Rodriguez', 'op04', 'op04@test.com', 'pass04', 'operativo', NULL, '2026-04-30 00:29:35'),
(18, 'Jose Perez', 'op05', 'op05@test.com', 'pass05', 'operativo', NULL, '2026-04-30 00:29:35'),
(19, 'Ana Martinez', 'op06', 'op06@test.com', 'pass06', 'operativo', NULL, '2026-04-30 00:29:35'),
(20, 'Luis Garcia', 'op07', 'op07@test.com', 'pass07', 'operativo', NULL, '2026-04-30 00:29:35'),
(21, 'Marta Lopez', 'op08', 'op08@test.com', 'pass08', 'operativo', NULL, '2026-04-30 00:29:35'),
(22, 'Jorge Gonzalez', 'op09', 'op09@test.com', 'pass09', 'operativo', NULL, '2026-04-30 00:29:35'),
(23, 'Sofia Hernandez', 'op10', 'op10@test.com', 'pass10', 'operativo', NULL, '2026-04-30 00:29:35'),
(24, 'Diego Silva', 'op11', 'op11@test.com', 'pass11', 'operativo', NULL, '2026-04-30 00:29:35'),
(25, 'Laura Castro', 'op12', 'op12@test.com', 'pass12', 'operativo', NULL, '2026-04-30 00:29:35'),
(26, 'Andres Morales', 'op13', 'op13@test.com', 'pass13', 'operativo', NULL, '2026-04-30 00:29:35'),
(27, 'Paula Ortiz', 'op14', 'op14@test.com', 'pass14', 'operativo', NULL, '2026-04-30 00:29:35'),
(28, 'Martin Vega', 'op15', 'op15@test.com', 'pass15', 'operativo', NULL, '2026-04-30 00:29:35'),
(29, 'Clara Rios', 'op16', 'op16@test.com', 'pass16', 'operativo', NULL, '2026-04-30 00:29:35'),
(30, 'Raul Soto', 'op17', 'op17@test.com', 'pass17', 'operativo', NULL, '2026-04-30 00:29:35'),
(31, 'Ines Luna', 'op18', 'op18@test.com', 'pass18', 'operativo', NULL, '2026-04-30 00:29:35'),
(32, 'Hugo Flores', 'op19', 'op19@test.com', 'pass19', 'operativo', NULL, '2026-04-30 00:29:35'),
(33, 'Victoria Paz', 'op20', 'op20@test.com', 'pass20', 'operativo', NULL, '2026-04-30 00:29:35');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
