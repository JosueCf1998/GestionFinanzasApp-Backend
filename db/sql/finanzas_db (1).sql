-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-09-2025 a las 08:17:19
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
(45, 60, 'Transport', 'Gasto', 'fa-bus', '#00FF00'),
(47, 61, 'Ropa', 'Gasto', 'fa-utensils', '#FF0000');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas`
--

CREATE TABLE `cuentas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `saldo` decimal(10,2) DEFAULT 0.00,
  `icon` varchar(100) NOT NULL,
  `color` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cuentas`
--

INSERT INTO `cuentas` (`id`, `usuario_id`, `nombre`, `saldo`, `icon`, `color`) VALUES
(12, 62, 'Cuenta Credito', 8500.00, '', '');

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
(81, 60, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjYwLCJlbWFpbCI6ImdyZWdAc3NkYy5jb20ifSwiaWF0IjoxNzU2MzUwNzU4LCJleHAiOjE3NTYzNTEwNTh9.VHKgL2kqMAbG_BjWVVQzlWzmCSV6yCBqUnydA2vPXnc', '2025-08-28 05:12:38', '2025-08-28 05:12:38'),
(82, 60, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjYwLCJlbWFpbCI6ImdyZWdAc3NkYy5jb20ifSwiaWF0IjoxNzU2MzUxNjI3LCJleHAiOjE3NTYzNTE5Mjd9.FEBQZsuorK-v1g0DDjKYtJ0Bl-cz3JHNtB7tzsULX-8', '2025-08-28 05:27:07', '2025-08-28 05:27:07'),
(83, 60, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjYwLCJlbWFpbCI6ImdyZWdAc3NkYy5jb20ifSwiaWF0IjoxNzU2MzUzNDgwLCJleHAiOjE4NTYzNTM0ODB9.5pCo01I4O15ZExUnW86BHOGWA017j5s1aDaIMB0lamY', '2025-08-28 05:58:00', '2025-08-28 05:58:00'),
(84, 61, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjYxLCJlbWFpbCI6Impvc3VlMTIzQGdtYWlsLmNvbSJ9LCJpYXQiOjE3NTY3ODc0MzAsImV4cCI6MTg1Njc4NzQzMH0.L4qAvaZTblRbROlVJAwuJzne6vVe5M7mDCZI8j0iylE', '2025-09-02 06:30:30', '2025-09-02 06:30:30'),
(85, 60, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjYwLCJlbWFpbCI6ImdyZWdAc3NkYy5jb20ifSwiaWF0IjoxNzU2Nzg3NjEzLCJleHAiOjE4NTY3ODc2MTN9.O_lwk3gakQDoWuq1-UgT66LsB8JbwwRDWI9E0y7b1Z0', '2025-09-02 06:33:33', '2025-09-02 06:33:33'),
(86, 62, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjYyLCJlbWFpbCI6Imx1aXNnQGdtYWlsLmNvbSJ9LCJpYXQiOjE3NTc2NTYwMzYsImV4cCI6MTg1NzY1NjAzNn0.jwI6n_qQrUqUfOu7nBqGm7f63BnhFmjfIa3sIGjkbaA', '2025-09-12 07:47:16', '2025-09-12 07:47:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transacciones`
--

CREATE TABLE `transacciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `cuenta_id` int(11) NOT NULL,
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
  `usuario_id` int(11) NOT NULL,
  `cuenta_id` int(11) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `cuenta_origen` int(11) DEFAULT NULL,
  `cuenta_destino` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(60, 'Alexia', 'Gutierrez', 'DeA4CPmrd9wF5B2GuXUA2xPp0rzfnHaq1cvwKBYhOgg=', '$2y$10$H7zJVwzsSItcqZcavG5JSOOJump91wDhjOrIt5bXX6j4kksdb3OI6', '2025-08-27 12:59:19'),
(61, 'josue', 'coronel', 'F5liRgWdUB2gbrubdEtIcynYHrGvZPsAMJxZDGBfTus=', '$2y$10$m3sh6nKTKGVzjNIrEwSMvuT6a4vfekw5I5MoOhq/yvIc.dCFWDPeq', '2025-09-02 11:29:35'),
(62, 'luisg', 'coronel', 'C2iNdtf/xkI4UOURmSSDEg==', '$2y$10$7Xy89WIakLVdAc0WMOOoduZdEqr984SBTovjzOmNsWCAcO5fzn5fe', '2025-09-12 12:45:33');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cuentas`
--
ALTER TABLE `cuentas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cuenta_id` (`cuenta_id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `transferencias`
--
ALTER TABLE `transferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cuenta_id` (`cuenta_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `cuentas`
--
ALTER TABLE `cuentas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `transferencias`
--
ALTER TABLE `transferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cuentas`
--
ALTER TABLE `cuentas`
  ADD CONSTRAINT `cuentas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD CONSTRAINT `transacciones_ibfk_1` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transacciones_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `transferencias`
--
ALTER TABLE `transferencias`
  ADD CONSTRAINT `transferencias_ibfk_1` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
