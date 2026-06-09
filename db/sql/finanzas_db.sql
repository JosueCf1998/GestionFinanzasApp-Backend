-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 09-06-2026 a las 08:27:23
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `finanzas_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `icono` varchar(100) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `usuario_id`, `nombre`, `tipo`, `icono`, `color`) VALUES
(20, NULL, 'Salud', 'gasto', 'heart', '#c62828'),
(21, NULL, 'Educación', 'gasto', 'study', '#388e3c'),
(22, NULL, 'Alquiler', 'gasto', 'wallet', '#222'),
(23, NULL, 'Regalo', 'gasto', 'gift', '#1976d2'),
(24, NULL, 'Transporte', 'gasto', 'bus', '#fbc02d'),
(25, NULL, 'Comida', 'gasto', 'restaurant', '#ad1457'),
(26, NULL, 'Otros', 'gasto', 'question', '#616161'),
(44, NULL, 'Alimentos', 'Gasto', 'fa-utensils', '#FF0000'),
(48, 64, 'Ropa', 'Gasto', 'fa-utensils', '#FF0000');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas`
--

CREATE TABLE `cuentas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `saldo` decimal(10,2) DEFAULT 0.00,
  `icon` varchar(100) NOT NULL,
  `color` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cuentas`
--

INSERT INTO `cuentas` (`id`, `usuario_id`, `nombre`, `saldo`, `icon`, `color`) VALUES
(19, 64, 'Ahorro', 3500.00, 'money', '#afb42b'),
(20, 64, 'Sueldo', 8500.00, 'money', '#afb42b'),
(24, 66, 'Principal', 100.00, 'bills', '#afb42b');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` text DEFAULT NULL,
  `ultimo_uso` datetime DEFAULT NULL,
  `creado_en` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`id`, `user_id`, `token`, `ultimo_uso`, `creado_en`) VALUES
(91, 64, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY0LCJlbWFpbCI6ImdveWl0bzE0MTBAZ21haWwuY29tIn0sImlhdCI6MTc1OTgwOTQ0NywiZXhwIjoxODU5ODA5NDQ3fQ.GYCMeVxEEZItpShIaiprECz38TK7EyerVE07WXZQhHA', '2025-10-07 05:57:27', '2025-10-07 05:57:27'),
(92, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODA5MDg0NTAsImV4cCI6MTg4MDkwODQ1MH0.0vrjsuGuhPCUqFcJKLo0SSppC8rzWiWl5f0nVx40C1g', '2026-06-08 10:47:30', '2026-06-08 10:47:30'),
(93, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODA5MDk5MDQsImV4cCI6MTg4MDkwOTkwNH0.wfmy4kn9PpGbZd6usJwWAqu0qCcTJdyNPrA7JQiL1a0', '2026-06-08 11:11:44', '2026-06-08 11:11:44'),
(94, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODA5MTAxMDIsImV4cCI6MTg4MDkxMDEwMn0.EegfFNPvVz9ZFrKznOesiK-4cjyf69HQAyJNhxK8POw', '2026-06-08 11:15:02', '2026-06-08 11:15:02'),
(95, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODA5MTE1MDksImV4cCI6MTg4MDkxMTUwOX0.w4m99HxYVP4tjnB-FaiK7JNv-AZ7EZLYpcCxHq0rzq4', '2026-06-08 11:38:29', '2026-06-08 11:38:29'),
(96, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODA5MTQxNjMsImV4cCI6MTg4MDkxNDE2M30.TxT5a0oCZRUFLqLdwwJk4A4cRIkT3dG1mk4fY0bD6Ac', '2026-06-08 12:22:43', '2026-06-08 12:22:43'),
(97, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODA5MTQ0ODgsImV4cCI6MTg4MDkxNDQ4OH0.FnxFqp-7l9yXwxwW_3XXc7kMBHsy23SZDjkeGo8VTzw', '2026-06-08 12:39:31', '2026-06-08 12:28:08'),
(98, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODA5MTY4MjEsImV4cCI6MTg4MDkxNjgyMX0.6DoFvfUPIFT1bz4oUhxwf4qnIXRsizpPiQRZOcNdQ5M', '2026-06-08 13:07:01', '2026-06-08 13:07:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transacciones`
--

CREATE TABLE `transacciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `cuenta_id` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transferencias`
--

CREATE TABLE `transferencias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `cuenta_id_destino` int(11) DEFAULT NULL,
  `cuenta_id_origen` int(11) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `tipo_transferencia` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `transferencias`
--

INSERT INTO `transferencias` (`id`, `usuario_id`, `fecha`, `cuenta_id_destino`, `cuenta_id_origen`, `monto`, `comentario`, `tipo_transferencia`) VALUES
(12, 64, '2025-10-07', 20, 19, 150.75, 'Transferencia de prueba', ''),
(16, 66, '2026-06-08', 24, NULL, 100.00, 'Saldo inicial de la cuenta', 'Inicial');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellidos`, `email`, `password`, `fecha_registro`) VALUES
(64, 'Goyito', 'Bonifas', '+nZsw72WYeRHcpVXyOSsRueBJkd+P6BgvgYV0TNrQNQ=', '$2y$10$oAve7HnwmuMPmsCO47C9qOjUXxqQGcPiZS5hM0hnb0uLl5cS.5b.S', '2025-10-07 10:55:06'),
(66, 'josue1', 'Coronel', 'z5nqAkS+SXGeZvlStxYNfA==', '$2y$10$2NnyKrLQbxEFk.Lc3uKaEuHtFLFwS0O0wL8bQyatjyURzKNMp2WY.', '2026-06-08 14:12:22');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categoria_usuario` (`usuario_id`);

--
-- Indices de la tabla `cuentas`
--
ALTER TABLE `cuentas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_usuario_id` (`usuario_id`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_sesiones_usuario` (`user_id`);

--
-- Indices de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cuenta_id` (`cuenta_id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `idx_transacciones_usuario` (`usuario_id`),
  ADD KEY `idx_transacciones_cuenta` (`cuenta_id`);

--
-- Indices de la tabla `transferencias`
--
ALTER TABLE `transferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transferencias_usuario` (`usuario_id`),
  ADD KEY `idx_transferencias_origen` (`cuenta_id_origen`),
  ADD KEY `idx_transferencias_destino` (`cuenta_id_destino`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `cuentas`
--
ALTER TABLE `cuentas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `transferencias`
--
ALTER TABLE `transferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `fk_categorias_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cuentas`
--
ALTER TABLE `cuentas`
  ADD CONSTRAINT `fk_cuentas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD CONSTRAINT `fk_transacciones_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transacciones_cuenta` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transacciones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `transferencias`
--
ALTER TABLE `transferencias`
  ADD CONSTRAINT `fk_transferencias_destino` FOREIGN KEY (`cuenta_id_destino`) REFERENCES `cuentas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transferencias_origen` FOREIGN KEY (`cuenta_id_origen`) REFERENCES `cuentas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transferencias_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
