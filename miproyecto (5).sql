-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-07-2025 a las 04:16:53
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
-- Base de datos: `miproyecto`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre_categoria`) VALUES
(11, 'categoria de prueba'),
(4, 'Deportes'),
(3, 'Educación'),
(1, 'Entretenimiento'),
(5, 'General'),
(6, 'Noticias'),
(10, 'Social');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `id_comentario` int(11) NOT NULL,
  `contenido` text NOT NULL,
  `fecha_comentario` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_post` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_comentario_padre` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `comentarios`
--

INSERT INTO `comentarios` (`id_comentario`, `contenido`, `fecha_comentario`, `id_post`, `id_usuario`, `id_comentario_padre`) VALUES
(12, 'Excelente aporte', '2025-07-14 22:22:36', 1, 1, NULL),
(13, 'nuevo comentario', '2025-07-14 22:30:34', 3, 1, NULL),
(14, 'otro nuevo comentario', '2025-07-14 22:30:34', 3, 1, NULL),
(15, 'ultimo comentario', '2025-07-14 22:30:34', 3, 1, NULL),
(16, 'Hola', '2025-07-15 23:46:05', 3, 26, NULL),
(17, 'Hola', '2025-07-15 23:48:03', 8, 1, NULL),
(18, 'como estas', '2025-07-15 23:48:36', 8, 26, NULL),
(19, 'hoalsg', '2025-07-15 23:58:48', 7, 26, NULL),
(20, 'funciona', '2025-07-16 01:37:41', 3, 26, NULL),
(21, 'hola', '2025-07-16 02:04:46', 3, 26, NULL),
(22, 'Purueba', '2025-07-16 02:19:28', 3, 1, 15),
(23, 'hola', '2025-07-16 02:19:42', 3, 1, NULL),
(24, 'jolml', '2025-07-16 02:22:48', 3, 1, 22),
(25, 'prueba', '2025-07-16 02:28:46', 3, 1, NULL),
(27, 'respondo a mi cometnario', '2025-07-16 02:30:34', 3, 26, 26),
(28, 'ahroa a este', '2025-07-16 02:30:42', 3, 26, 27),
(29, 'si esta bien aparece', '2025-07-16 02:31:16', 3, 26, 27),
(30, 'sigue el hilo', '2025-07-16 02:37:51', 3, 26, 29),
(31, 'prueba', '2025-07-16 02:39:44', 3, 26, 29),
(32, 'prueba', '2025-07-16 02:42:39', 3, 26, 29),
(33, 'prueba', '2025-07-16 02:42:50', 3, 26, NULL),
(34, 'deberia aparecer abajo de si esta bien aparece', '2025-07-16 02:43:14', 3, 26, 29),
(35, 'ver', '2025-07-16 02:49:50', 3, 26, 33),
(37, 'apareciendo', '2025-07-16 02:50:01', 3, 26, NULL),
(38, 'Franco Caneda', '2025-07-16 02:58:44', 3, 31, 13),
(40, 'y sigue apareciendo', '2025-07-16 16:10:30', 3, 26, 29),
(42, 'Fer mann', '2025-07-16 17:00:05', 3, 26, NULL),
(43, 'me autocontesto', '2025-07-17 23:10:56', 10, 26, NULL),
(44, 'me autorespondo', '2025-07-17 23:11:04', 10, 26, 43),
(45, 'jnsfjnszifisafksdjgs', '2025-07-17 23:27:03', 2, 1, NULL),
(48, 'funciona', '2025-07-18 16:31:06', 8, 26, NULL),
(49, 'hola', '2025-07-18 16:50:54', 9, 26, NULL),
(50, 'como estas', '2025-07-18 16:50:58', 9, 26, NULL),
(51, 'vos decis ?', '2025-07-19 21:39:24', 1, 26, 12),
(52, 'hola', '2025-07-20 22:26:16', 2, 1, NULL),
(53, 'Este es un comentario de prueba', '2025-07-21 00:45:30', 1, 26, NULL),
(65, 'algo', '2025-07-21 02:08:17', 16, 26, NULL),
(66, 'respondio', '2025-07-21 02:13:53', 16, 1, 65);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `likes_post`
--

CREATE TABLE `likes_post` (
  `id_like` int(11) NOT NULL,
  `id_post` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_like` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `likes_post`
--

INSERT INTO `likes_post` (`id_like`, `id_post`, `id_usuario`, `fecha_like`) VALUES
(39, 2, 26, '2025-07-18 03:27:29'),
(41, 6, 26, '2025-07-18 03:32:33'),
(49, 9, 26, '2025-07-18 16:51:01'),
(50, 1, 26, '2025-07-19 21:38:54'),
(53, 8, 26, '2025-07-21 00:33:06'),
(54, 3, 26, '2025-07-21 00:36:12'),
(55, 1, 1, '2025-07-21 00:49:25'),
(62, 16, 26, '2025-07-21 02:08:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario_destino` int(11) NOT NULL,
  `id_usuario_origen` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario_destino`, `id_usuario_origen`, `mensaje`, `leido`, `fecha_envio`) VALUES
(8, 1, 0, 'Alguien le dio Me gusta a tu publicación: \"nuevaaaaaaaaaaaaaaaaaaaaaaaaaaa\"', 1, '2025-07-21 00:36:12'),
(9, 1, 0, 'Alguien comentó tu publicación: \"publicacion de prueba franco\"', 1, '2025-07-21 00:45:30'),
(10, 1, 0, 'Alguien le dio Me gusta a tu publicación: \"Prueba de notifiaciones \"', 1, '2025-07-21 00:51:02'),
(11, 1, 0, 'Alguien comentó tu publicación: \"Prueba de notifiaciones \"', 1, '2025-07-21 00:51:15'),
(12, 31, 0, 'Alguien respondió a tu comentario en el post: \"Prueba de notifiaciones \"', 1, '2025-07-21 00:53:55'),
(13, 1, 0, 'Alguien respondió a tu comentario en el post: \"Prueba de notifiaciones \"', 1, '2025-07-21 00:54:38'),
(14, 1, 0, 'Alguien le dio Me gusta a tu publicación: \"Prueba de notifiaciones \"', 1, '2025-07-21 00:56:51'),
(15, 1, 0, 'Alguien comentó tu publicación: \"Prueba de notifiaciones \"', 1, '2025-07-21 00:57:00'),
(16, 1, 0, 'Alguien comentó tu publicación: \"Prueba de notifiaciones \"', 1, '2025-07-21 01:11:11'),
(17, 26, 0, 'Alguien respondió a tu comentario en el post: \"Prueba de notifiaciones \"', 1, '2025-07-21 01:16:02'),
(18, 1, 0, 'Alguien comentó tu publicación: \"Prueba de notifiaciones \"', 0, '2025-07-21 01:47:12'),
(19, 1, 0, 'Alguien le dio Me gusta a tu publicación: \"Prueba de notifiaciones \"', 0, '2025-07-21 01:47:35'),
(20, 1, 0, 'Alguien le dio Me gusta a tu publicación: \"Gano River 3 a 1 \"', 0, '2025-07-21 01:53:31'),
(21, 1, 0, 'Alguien comentó tu publicación: \"Gano River 3 a 1 \"', 0, '2025-07-21 01:53:38'),
(22, 1, 0, 'Alguien le dio Me gusta a tu publicación: \"ChatGPT ¿Es bueno o malo?\"', 0, '2025-07-21 01:56:22'),
(23, 1, 0, 'Alguien comentó tu publicación: \"ChatGPT ¿Es bueno o malo?\"', 0, '2025-07-21 01:56:28'),
(24, 1, 26, 'Alguien le dio Me gusta a tu publicación: \"ChatGPT ¿Es bueno o malo?\"', 1, '2025-07-21 02:00:44'),
(25, 1, 26, 'Alguien comentó tu publicación: \"ChatGPT ¿Es bueno o malo?\"', 1, '2025-07-21 02:00:51'),
(26, 26, 1, 'Alguien respondió a tu comentario en el post: \"ChatGPT ¿Es bueno o malo?\"', 1, '2025-07-21 02:03:32'),
(27, 1, 26, 'Alguien le dio Me gusta a tu publicación: \"ChatGPT ¿Es bueno o malo?\"', 1, '2025-07-21 02:08:13'),
(28, 1, 26, 'Alguien comentó tu publicación: \"ChatGPT ¿Es bueno o malo?\"', 1, '2025-07-21 02:08:17'),
(29, 26, 1, ' respondió a tu comentario en el post: \"ChatGPT ¿Es bueno o malo?\"', 0, '2025-07-21 02:13:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`) VALUES
(62, 29, 'f54c468c6b05f45699ffc4c60e1efe2d', '2025-07-13 22:08:24'),
(63, 26, 'b0c1497ee72bb9692dbf0fc96f694554', '2025-07-16 14:58:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posts`
--

CREATE TABLE `posts` (
  `id_post` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` text NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `imagen_destacada` varchar(255) DEFAULT NULL,
  `estado` enum('publicado','borrador','eliminado') DEFAULT 'publicado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `posts`
--

INSERT INTO `posts` (`id_post`, `titulo`, `contenido`, `fecha_creacion`, `id_usuario`, `id_categoria`, `imagen_destacada`, `estado`) VALUES
(1, 'publicacion de prueba franco', 'asdasdasdasdasd', '2025-07-14 21:50:21', 1, 3, NULL, 'publicado'),
(2, 'nueva publicacion en deportes pa', 'asdasdadasdasdasd', '2025-07-14 22:15:09', 26, 4, NULL, 'publicado'),
(3, 'nuevaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaa', '2025-07-14 22:27:48', 1, 3, NULL, 'publicado'),
(4, 'Prueba de Posteo', 'asadadadssadfsdfssafsd', '2025-07-15 01:46:29', 26, 5, NULL, 'publicado'),
(6, 'asdadadasdsfas', 'asdfasfsdafasd', '2025-07-15 22:23:58', 26, 5, NULL, 'publicado'),
(7, 'prueba828238328', 'asdjbfjsdfsdaiofnsdkjnfksd', '2025-07-15 22:37:19', 26, 1, NULL, 'publicado'),
(8, '23829u39239', 'h23uiehiwehiwei9ihni32ninsd', '2025-07-15 23:47:33', 1, 4, NULL, 'publicado'),
(9, 'noti32234232332', 'ksanknsd ksdkfksda fm sdkf sd flsd', '2025-07-16 00:11:56', 26, 6, NULL, 'publicado'),
(10, 'prueba de entrenenimiento', 'JSHJDFBDSAUBJSDNBJDSA', '2025-07-17 23:10:39', 26, 1, NULL, 'publicado'),
(11, 'drhfdbfhfhffh999999', 'asfsfsdfdsfs', '2025-07-17 23:29:53', 1, 1, NULL, 'publicado'),
(16, 'ChatGPT ¿Es bueno o malo?', 'saafsdfadsfsdfsda', '2025-07-21 02:07:49', 1, 11, NULL, 'publicado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `user_nameweb` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `clave` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'assets/Access.ico',
  `fecha_nacimiento` date DEFAULT NULL,
  `bio` text DEFAULT 'Escribi algo de vos',
  `rol` varchar(10) DEFAULT 'normaluser',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `user_nameweb`, `email`, `clave`, `nombre_completo`, `avatar`, `fecha_nacimiento`, `bio`, `rol`, `fecha_registro`) VALUES
(1, 'admin', 'admin@admin.com', '$2y$10$Sv35a0QoV5DvwBFpZEs5m.15HaQFszUEEMR.RCQBQN.F8IMF93jjW', 'Administrador General', 'assets/Access.ico', '1988-07-07', 'Administrador del Sistema', 'admin', '2025-07-14 02:47:27'),
(26, 'GermanEros96', 'german_eros@hotmail.com', '$2y$10$7.rDm50MlUz3kv.vExFZlON62mEyHWGxt6Wl3bjJ5zBUYl1/JlPFu', 'German Chiozzini', 'uploads/avatar_6872c942877b68.80013763.png', '1996-06-24', 'Estudiante de sistemas', 'normaluser', '2025-07-14 02:47:27'),
(27, 'Prueba', 'Prueba@prueba.com', '$2y$10$Nf0R2Cgbz7YjU6.3lbGIp.lJuEp/ijl/oQzLzQIJYpL.FKNhmkXUi', 'prueba prueba', 'uploads/avatar_6872d419084e34.59243170.png', '1111-11-11', 'Prueba de usuario', 'normaluser', '2025-07-14 02:47:27'),
(28, 'Luchod9', 'Luchodominguez@gmail.com', '$2y$10$.nD/ZGsW9f1boTkyO9jcq.FGGmPt3pm3.F.1mJqRRgVcsPrLcOJP.', 'Luciano Dominguez', 'uploads/avatar_687431f9c37d04.36299885.png', '1995-06-24', 'Lucho Gato', 'normaluser', '2025-07-14 02:47:27'),
(29, 'Tamila', 'tamidomi@yahoo.com.ar', '$2y$10$Asp.qpVJqHXg7erRVF9KGOZFlyejSXAw25a6xo4rFhD6m86wEPbCy', 'Tamara dominguez', 'uploads/avatar_68744a6be864e2.52982495.png', '2025-07-02', 'Jugadora de CS', 'normaluser', '2025-07-14 02:47:27'),
(31, 'Franquitohuracan', 'franco@gmail.com', '$2y$10$xAemVDvaQLSk4BjQOz7VueLCM.HtafcDUpOsjci8Kr5qoEN7W34su', 'Franco Chiozzini', 'uploads/avatar_68747002e3ab03.31916980.png', '1111-12-21', 'sarasa', 'normaluser', '2025-07-14 02:48:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visitas_post`
--

CREATE TABLE `visitas_post` (
  `id_visita` int(11) NOT NULL,
  `id_post` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_visita` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `visitas_post`
--

INSERT INTO `visitas_post` (`id_visita`, `id_post`, `id_usuario`, `fecha_visita`) VALUES
(2, 3, 1, '2025-07-18 02:11:54'),
(5, 3, 26, '2025-07-21 00:36:11'),
(22, 8, 26, '2025-07-21 00:33:04'),
(24, 2, 26, '2025-07-19 22:50:18'),
(25, 10, 26, '2025-07-21 00:45:42'),
(26, 9, 1, '2025-07-17 23:16:39'),
(27, 2, 1, '2025-07-20 22:26:09'),
(29, 8, 1, '2025-07-17 23:37:41'),
(30, 11, 26, '2025-07-20 23:19:05'),
(34, 1, 26, '2025-07-21 00:45:59'),
(61, 6, 26, '2025-07-18 03:32:30'),
(62, 9, 26, '2025-07-18 16:50:48'),
(77, 4, 26, '2025-07-19 22:49:31'),
(88, 11, 1, '2025-07-20 22:25:55'),
(94, 11, 31, '2025-07-20 23:30:54'),
(99, 1, 1, '2025-07-21 00:49:21'),
(112, 16, 26, '2025-07-21 02:08:10'),
(113, 16, 1, '2025-07-21 02:13:45');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre_categoria` (`nombre_categoria`);

--
-- Indices de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `id_post` (`id_post`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `likes_post`
--
ALTER TABLE `likes_post`
  ADD PRIMARY KEY (`id_like`),
  ADD UNIQUE KEY `id_post` (`id_post`,`id_usuario`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario_destino` (`id_usuario_destino`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id_post`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `visitas_post`
--
ALTER TABLE `visitas_post`
  ADD PRIMARY KEY (`id_visita`),
  ADD UNIQUE KEY `unique_visita_usuario_post` (`id_post`,`id_usuario`),
  ADD KEY `id_post` (`id_post`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT de la tabla `likes_post`
--
ALTER TABLE `likes_post`
  MODIFY `id_like` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT de la tabla `posts`
--
ALTER TABLE `posts`
  MODIFY `id_post` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `visitas_post`
--
ALTER TABLE `visitas_post`
  MODIFY `id_visita` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`id_post`) REFERENCES `posts` (`id_post`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `likes_post`
--
ALTER TABLE `likes_post`
  ADD CONSTRAINT `likes_post_ibfk_1` FOREIGN KEY (`id_post`) REFERENCES `posts` (`id_post`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_post_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario_destino`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE;

--
-- Filtros para la tabla `visitas_post`
--
ALTER TABLE `visitas_post`
  ADD CONSTRAINT `visitas_post_ibfk_1` FOREIGN KEY (`id_post`) REFERENCES `posts` (`id_post`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
