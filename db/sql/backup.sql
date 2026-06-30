-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
use u992910078_finanzas_db;-
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 30-06-2026 a las 04:41:43
-- Versión del servidor: 11.8.8-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u992910078_finanzas_db`
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
(48, 64, 'Ropa', 'gasto', 'fa-utensils', '#FF0000'),
(50, NULL, 'Salario', 'ingreso', 'salary', '#1976d2'),
(51, NULL, 'Regalo', 'ingreso', 'gift', '#ad1457'),
(52, NULL, 'Interés', 'ingreso', 'bank', '#388e3c'),
(53, NULL, 'Otros', 'ingreso', 'question', '#616161'),
(57, 66, 'Universidad', 'gasto', 'bank', '#1976d2');

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
(24, 66, 'Principal', 1800.00, 'bills', '#afb42b'),
(25, 66, 'Ahorros', 500.00, 'bank', '#388e3c'),
(26, 66, 'sueldo', 3000.00, 'money-bag', '#f57c00'),
(27, 68, 'Principal', 3000.00, 'bills', '#afb42b'),
(28, 68, 'ahrro', 100.00, 'bank', '#ad1457'),
(29, 69, 'Principal', 100.00, 'bills', '#afb42b');

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
(98, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODA5MTY4MjEsImV4cCI6MTg4MDkxNjgyMX0.6DoFvfUPIFT1bz4oUhxwf4qnIXRsizpPiQRZOcNdQ5M', '2026-06-08 13:07:01', '2026-06-08 13:07:01'),
(99, 67, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY3LCJlbWFpbCI6ImhlbHdhaXRwYWNrYmVsbDdAZ21haWwuY29tIn0sImlhdCI6MTc4MDk4NzY5OCwiZXhwIjoxODgwOTg3Njk4fQ.24fHXQOxorbeCmavgPs7SiF8waIGigwAwe0yZx5IHOo', '2026-06-09 06:48:18', '2026-06-09 06:48:18'),
(100, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODA5ODc4MDYsImV4cCI6MTg4MDk4NzgwNn0.StQv8hoxlF4EFuiHX1DUlC9FHlXiT_oLwDnA-wrucdo', '2026-06-09 06:50:06', '2026-06-09 06:50:06'),
(101, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEyNDE0MjMsImV4cCI6MTg4MTI0MTQyM30.ZcBIi9RRyJQZ1YuM5ystfj0uZNzBpsaq3BmZ8gslnAo', '2026-06-12 05:17:03', '2026-06-12 05:17:03'),
(102, 68, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY4LCJlbWFpbCI6Imx1aXMxNDEwQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEyNDE0NDUsImV4cCI6MTg4MTI0MTQ0NX0.Yum1mVoDcs0AokiE-XHNtPIGN1H9bAE_aY-whFWv0QI', '2026-06-12 05:17:25', '2026-06-12 05:17:25'),
(103, 68, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY4LCJlbWFpbCI6Imx1aXMxNDEwQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEyNDE0NjIsImV4cCI6MTg4MTI0MTQ2Mn0.bSwRoZqGW8aPNaSq2GxAQVFXwypLYIKVgRoNdJI6oFk', '2026-06-12 05:17:42', '2026-06-12 05:17:42'),
(104, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEyNDE2MzgsImV4cCI6MTg4MTI0MTYzOH0.JOASpetiWS2bfM3lapFBsDjKFYa6nrsYNHemsleTSRA', '2026-06-12 05:20:38', '2026-06-12 05:20:38'),
(105, 68, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY4LCJlbWFpbCI6Imx1aXMxNDEwQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEyNDI0OTMsImV4cCI6MTg4MTI0MjQ5M30.j7QF1wte2HvvjdLWyhZ2YpQJ6yNRwsbObl4cFXajnc0', '2026-06-12 05:34:53', '2026-06-12 05:34:53'),
(106, 68, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY4LCJlbWFpbCI6Imx1aXMxNDEwQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEyNDI4MjQsImV4cCI6MTg4MTI0MjgyNH0.NLXEyXXpZT6sEkzGt8QX8GOzspdGxF2YBqa9mHOizcE', '2026-06-12 05:40:24', '2026-06-12 05:40:24'),
(107, 68, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY4LCJlbWFpbCI6Imx1aXMxNDEwQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEyNDMzODEsImV4cCI6MTg4MTI0MzM4MX0.a8Xjzz-D1g2GgSh5H2QK9urAfYf6ce6n_Ij2Ir7yOag', '2026-06-12 05:49:41', '2026-06-12 05:49:41'),
(108, 69, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY5LCJlbWFpbCI6Impvc3VlMUBnbWFpbC5jb20ifSwiaWF0IjoxNzgxMzQ2MTAyLCJleHAiOjE4ODEzNDYxMDJ9.QFw9dqng5p6exBIzqCeGptP49baqtu5yPswrRuM4XZs', '2026-06-13 10:21:42', '2026-06-13 10:21:42'),
(109, 69, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY5LCJlbWFpbCI6Impvc3VlMUBnbWFpbC5jb20ifSwiaWF0IjoxNzgxMzQ2MjA1LCJleHAiOjE4ODEzNDYyMDV9.XojRUPN95OpDXQNEIPs0WkE6QJ8KLtE4XZLmKtgTxEo', '2026-06-13 10:23:25', '2026-06-13 10:23:25'),
(110, 69, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY5LCJlbWFpbCI6Impvc3VlMUBnbWFpbC5jb20ifSwiaWF0IjoxNzgxMzQ2MzI1LCJleHAiOjE4ODEzNDYzMjV9.sILPm1HhbIR-GI6csRkjq8wNg5AFJPzIfQPOJ1LqDO4', '2026-06-13 10:25:25', '2026-06-13 10:25:25'),
(111, 69, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY5LCJlbWFpbCI6Impvc3VlMUBnbWFpbC5jb20ifSwiaWF0IjoxNzgxMzQ2MzY1LCJleHAiOjE4ODEzNDYzNjV9.AGxz-Ka9-MM3WQbtyeFjtMub3parbQgHH73g24MEggc', '2026-06-13 10:26:05', '2026-06-13 10:26:05'),
(112, 69, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY5LCJlbWFpbCI6Impvc3VlMUBnbWFpbC5jb20ifSwiaWF0IjoxNzgxMzQ5OTQyLCJleHAiOjE4ODEzNDk5NDJ9.ULGEwAu2AeL69i_dTi5CxMJrlMc2xbYBN6WRst5B4o0', '2026-06-13 11:25:42', '2026-06-13 11:25:42'),
(113, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEzNTIxNzksImV4cCI6MTg4MTM1MjE3OX0.I6nJ8N4wEHhQgGyYAP-pO463zT-vXsmDHbARCmnkQv4', '2026-06-13 12:02:59', '2026-06-13 12:02:59'),
(114, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEzNTIyOTQsImV4cCI6MTg4MTM1MjI5NH0.H21ZXW2SVe9Vk8rx7h4UU0DQSBEFLbBJ10yV-NVP79Y', '2026-06-13 12:04:54', '2026-06-13 12:04:54'),
(115, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEzNTI4OTAsImV4cCI6MTg4MTM1Mjg5MH0.RPKb91YAZO-MW8Dh17XYTAbh8TaXMCi6JL_SDB9YYYU', '2026-06-13 12:14:50', '2026-06-13 12:14:50'),
(116, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEzNTI5MjYsImV4cCI6MTg4MTM1MjkyNn0.sT-yEtF8-Po9jcOyy6G8rKSPl6aZXyOZvvwhldvqXUA', '2026-06-13 12:15:26', '2026-06-13 12:15:26'),
(117, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEzNTMxMTAsImV4cCI6MTg4MTM1MzExMH0.wWdbP6sWkBK0GNC0mZxhfZ5H0vcrajYkv3yFALSBtfM', '2026-06-13 12:18:30', '2026-06-13 12:18:30'),
(118, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEzNTM3ODYsImV4cCI6MTg4MTM1Mzc4Nn0.muWS3t_ahYsj09yvnHgGgH4IGckDjwbqPMIPnOvTVxU', '2026-06-13 12:29:46', '2026-06-13 12:29:46'),
(119, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEzNjUzMDYsImV4cCI6MTg4MTM2NTMwNn0.ODLjBfLSBzDwHOhbYedzWT9grvMACT3M3MJPJcD55kQ', '2026-06-13 15:41:46', '2026-06-13 15:41:46'),
(120, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEzNzAzOTksImV4cCI6MTg4MTM3MDM5OX0.BxE35PFwOxIQwiCuEJZMyeIW4Le3jf7XNR56urEcm1A', '2026-06-13 17:06:39', '2026-06-13 17:06:39'),
(121, 67, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY3LCJlbWFpbCI6ImhlbHdhaXRwYWNrYmVsbDdAZ21haWwuY29tIn0sImlhdCI6MTc4MTM3MDY0NywiZXhwIjoxODgxMzcwNjQ3fQ.YN2kQ1yGcPzip4Uj08Xuoq6YTnZ53j5gss_Js4fBE2E', '2026-06-13 17:10:47', '2026-06-13 17:10:47'),
(122, 67, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY3LCJlbWFpbCI6ImhlbHdhaXRwYWNrYmVsbDdAZ21haWwuY29tIn0sImlhdCI6MTc4MTM3MDY1NCwiZXhwIjoxODgxMzcwNjU0fQ.PRWMk1bnIzD_URo-rK0h8UW1sEUQMLOBpt7ia_o6sCU', '2026-06-13 17:10:54', '2026-06-13 17:10:54'),
(123, 67, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY3LCJlbWFpbCI6ImhlbHdhaXRwYWNrYmVsbDdAZ21haWwuY29tIn0sImlhdCI6MTc4MTM3MDY2NywiZXhwIjoxODgxMzcwNjY3fQ.vUAof6b7G0Ww00DTNe_ezp4lVlKm9cWh3hgn73zJPT4', '2026-06-13 17:11:07', '2026-06-13 17:11:07'),
(124, 67, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY3LCJlbWFpbCI6ImhlbHdhaXRwYWNrYmVsbDdAZ21haWwuY29tIn0sImlhdCI6MTc4MTM3MDY2OSwiZXhwIjoxODgxMzcwNjY5fQ.0QefQ0kfcoZ9SINa7upGLOG9hinAtJOqMM9Dt3rln6s', '2026-06-13 17:11:09', '2026-06-13 17:11:09'),
(125, 67, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY3LCJlbWFpbCI6ImhlbHdhaXRwYWNrYmVsbDdAZ21haWwuY29tIn0sImlhdCI6MTc4MTM3MDY3MCwiZXhwIjoxODgxMzcwNjcwfQ.CCcBMh_1ZWAWmv9SUcG65YhvyUQ4czxiwug8_2zJLeo', '2026-06-13 17:11:10', '2026-06-13 17:11:10'),
(126, 67, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY3LCJlbWFpbCI6ImhlbHdhaXRwYWNrYmVsbDdAZ21haWwuY29tIn0sImlhdCI6MTc4MTM3MDY3MiwiZXhwIjoxODgxMzcwNjcyfQ.K1JjHmMojk3qWcI2C9ssVwoaxFwRZ_ZKDkbGiVKSxWs', '2026-06-13 17:11:12', '2026-06-13 17:11:12'),
(127, 67, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY3LCJlbWFpbCI6ImhlbHdhaXRwYWNrYmVsbDdAZ21haWwuY29tIn0sImlhdCI6MTc4MTM3MDcxOSwiZXhwIjoxODgxMzcwNzE5fQ.DxaDiDDqOqicc95AJadbtsyO9iBvplIaRga05KRrXpI', '2026-06-13 17:11:59', '2026-06-13 17:11:59'),
(128, 67, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY3LCJlbWFpbCI6ImhlbHdhaXRwYWNrYmVsbDdAZ21haWwuY29tIn0sImlhdCI6MTc4MTM3MDc1MCwiZXhwIjoxODgxMzcwNzUwfQ.aZtOwx_hNDu-W9jqLf-lGN8282AIVylJQgaHkrKSZpE', '2026-06-13 17:12:30', '2026-06-13 17:12:30'),
(129, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODEzNzEzOTUsImV4cCI6MTg4MTM3MTM5NX0.sne3VDf7Il5IE7w1nnUKCQGzjTz10I--ONUHgjEysd0', '2026-06-13 17:23:15', '2026-06-13 17:23:15'),
(130, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODE1OTIyOTUsImV4cCI6MTg4MTU5MjI5NX0.OLrYUD2avVoQpmWww4OZ69BHTGA8tIF6p6TbIS0_k4A', '2026-06-16 06:44:55', '2026-06-16 06:44:55'),
(131, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODE2MDMxNjYsImV4cCI6MTg4MTYwMzE2Nn0.9MVPeGaMHvzbbRdXqeeYY9XrJbNJ9dyh2mFePBBXzIA', '2026-06-16 09:46:06', '2026-06-16 09:46:06'),
(132, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODE2MTIwMTYsImV4cCI6MTg4MTYxMjAxNn0.T48C5_hEYZMdyM9vmKUOkiRdEyA4LgsKTEdiCIFCxPE', '2026-06-16 12:13:36', '2026-06-16 12:13:36'),
(133, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODE2MjM4NDQsImV4cCI6MTg4MTYyMzg0NH0.5bkpbSbehTSiKH3kYzKI0h62wCp-MYYsuPeFQQfYtX8', '2026-06-16 15:30:44', '2026-06-16 15:30:44'),
(134, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODE2MjY4OTUsImV4cCI6MTg4MTYyNjg5NX0.0n4NU2O8WLyx_nmwJPkAPnFm9um-meT166Tv1FGj_fs', '2026-06-16 16:21:35', '2026-06-16 16:21:35'),
(135, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODE2MjczMTksImV4cCI6MTg4MTYyNzMxOX0.wFUKRJoq1nNPUcR-kWPElkpGNs2-p6NDg5eQNSbP050', '2026-06-16 16:28:39', '2026-06-16 16:28:39'),
(136, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODE2MjczMzUsImV4cCI6MTg4MTYyNzMzNX0.wkaOr9vzwqciGhS_WPIOrg0I4BKC453ce2-QBa3wuVQ', '2026-06-16 16:28:55', '2026-06-16 16:28:55'),
(137, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODE2NjAwOTUsImV4cCI6MTg4MTY2MDA5NX0.8Z8h0Z8QKLPtWA9a0ZB8qoTfcxIQU9RV79vjtD6qBC8', '2026-06-17 01:34:55', '2026-06-17 01:34:55'),
(138, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODE3MTQxMTYsImV4cCI6MTg4MTcxNDExNn0.xO2C-QZF8QPgcSOTbAaJp8MlJEXfE2S9f7e4RjgLcrU', '2026-06-17 16:35:16', '2026-06-17 16:35:16'),
(139, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODIyNDcwNzgsImV4cCI6MTg4MjI0NzA3OH0.34nq4vLglajwxLbq_ojvtdDKObt__w8f5QjV1Bj4H-U', '2026-06-23 20:37:58', '2026-06-23 20:37:58'),
(140, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODIyNDc2MDcsImV4cCI6MTg4MjI0NzYwN30.Ih6K4cydybnLwQ3YjfiTEOLHeXreXpqtb7rm64T7Quk', '2026-06-23 20:46:47', '2026-06-23 20:46:47'),
(141, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI0NDY2OTMsImV4cCI6MTg4MjQ0NjY5M30.kO6QMiS_VBc-kc8OzugDiIYkRHnL2JuC1D4Ya_p3TIw', '2026-06-26 04:04:53', '2026-06-26 04:04:53'),
(142, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI0NDY4NDIsImV4cCI6MTg4MjQ0Njg0Mn0.l7BfeiT1gw5D1zswfWbuvxlKw2Oyi3EzhLkKzHcKvEU', '2026-06-26 04:07:22', '2026-06-26 04:07:22'),
(143, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI0NDY5MDksImV4cCI6MTg4MjQ0NjkwOX0.7c82FOdq2PDQEpfgaKvwWCGrqkLR28N3YBs5negtivE', '2026-06-26 04:08:29', '2026-06-26 04:08:29'),
(144, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI0NTM0MTAsImV4cCI6MTg4MjQ1MzQxMH0.VHw9WGY5T8zma9xPcBcIlcr5pc0MnscpsHSb3T0IYHM', '2026-06-26 05:56:50', '2026-06-26 05:56:50'),
(145, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI0NTgzMTAsImV4cCI6MTg4MjQ1ODMxMH0.84cmpdhreCfDhsQNoVygN4OHUFnue1A4szPq4FExn7I', '2026-06-26 07:18:30', '2026-06-26 07:18:30'),
(146, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI0ODY4NTMsImV4cCI6MTg4MjQ4Njg1M30.ZCL8JH5GQwbIa1mRrOeqZC91OEcCnwwiyo2Tzn7Xx2c', '2026-06-26 15:14:13', '2026-06-26 15:14:13'),
(147, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI1MTE1MTEsImV4cCI6MTg4MjUxMTUxMX0.O63SaKjM-u4n94CbBWWd_jWHBhpZt4LKjtXDweLitWU', '2026-06-26 22:05:11', '2026-06-26 22:05:11'),
(148, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI1NzcwMjgsImV4cCI6MTg4MjU3NzAyOH0.ICCZmprT-Zdj57_YC39cAP5WHNgmZ9TdJ4w8o3X4GPE', '2026-06-27 16:17:08', '2026-06-27 16:17:08'),
(149, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI1NzgyOTUsImV4cCI6MTg4MjU3ODI5NX0.wDtHZ1U7OnU8_I1NgjgnmzJ-m4wOjFaO95-AsxHBRcE', '2026-06-27 16:38:15', '2026-06-27 16:38:15'),
(150, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI1ODMxNTksImV4cCI6MTg4MjU4MzE1OX0.m-C4DsxZ5a-4MRJ-UxuPG5fkAqlwHbHRVOta2KOQKeg', '2026-06-27 17:59:19', '2026-06-27 17:59:19'),
(151, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI3ODk1NjAsImV4cCI6MTg4Mjc4OTU2MH0.1fqjG1iPPpPUQ_1Gq4q5yer3fjuOdkAO-6-56CvEM2Q', '2026-06-30 03:19:20', '2026-06-30 03:19:20'),
(152, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI3OTA2OTUsImV4cCI6MTg4Mjc5MDY5NX0.br296bW2gu3F8ou6E6355XLMOLFx3xquXs_1TV7dro0', '2026-06-30 03:38:15', '2026-06-30 03:38:15'),
(153, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI3OTA3NTcsImV4cCI6MTg4Mjc5MDc1N30.FKlHBdVa4TLI5Am8gyt-A7a0imSj4-zNzz--z7dxiq0', '2026-06-30 03:39:17', '2026-06-30 03:39:17'),
(154, 66, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJfaWQiOjY2LCJlbWFpbCI6Impvc3VlQGdtYWlsLmNvbSJ9LCJpYXQiOjE3ODI3OTI0MzUsImV4cCI6MTg4Mjc5MjQzNX0.jGdk35BIJV6RAGyB_avRRGKVN4Q5MW8jiZWq91akMLE', '2026-06-30 04:07:15', '2026-06-30 04:07:15');

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
(16, 66, '2026-06-08', 24, NULL, 100.00, 'Saldo inicial de la cuenta', 'Inicial'),
(17, 66, '2026-06-12', 24, NULL, 1700.00, 'Ajuste de saldo (incremento)', 'Ajuste'),
(18, 66, '2026-06-12', 25, NULL, 500.00, 'Saldo inicial de la cuenta', 'Inicial'),
(19, 66, '2026-06-12', 26, NULL, 3000.00, 'Saldo inicial de la cuenta', 'Inicial'),
(20, 68, '2026-06-12', 27, NULL, 3000.00, 'Saldo inicial de la cuenta', 'Inicial'),
(21, 68, '2026-06-12', 28, NULL, 100.00, 'Saldo inicial de la cuenta', 'Inicial'),
(22, 69, '2026-06-13', 29, NULL, 100.00, 'Saldo inicial de la cuenta', 'Inicial');

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
(66, 'josue1', 'Coronel', 'z5nqAkS+SXGeZvlStxYNfA==', '$2y$10$2NnyKrLQbxEFk.Lc3uKaEuHtFLFwS0O0wL8bQyatjyURzKNMp2WY.', '2026-06-08 14:12:22'),
(67, 'samir', 'gutierrez', 'w1/GCnBO7cv7sYiY0QHdtvE43DEMuJgNOqfhSb0Yb0o=', '$2y$10$88bnRqp9LhfEX5R2h9we..16WVhse84OGmfVkUgU7JplA/t6qfeRK', '2026-06-09 06:47:30'),
(68, 'Luis', 'Chujutalli', 'SxwqI5nH5LNL/HVXwgars91AsiqpKlBYaZkcNfu5dFk=', '$2y$10$I07eFHAiqpDVefSct7tb1esli8Dqlcd5lO5zx3olihpv2PGWEIQBa', '2026-06-12 05:14:55'),
(69, 'Josue', 'Coronel', 'weT+Z3+N6WzGrg/+Jqcolcl9vRZVvWWQdneVBGkKtuo=', '$2y$10$ip84aPqYxvKV8y0I3ydii.yPpqocS8xeKmn5HVZZNW9U/Vjvhcfqi', '2026-06-13 10:21:02');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `cuentas`
--
ALTER TABLE `cuentas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `transferencias`
--
ALTER TABLE `transferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

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
