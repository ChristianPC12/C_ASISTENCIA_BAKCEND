-- ============================================================
-- Base de datos: iglesia_asistencia
-- Sistema de control de asistencia - Iglesia Adventista
-- Generado: 02-03-2026
-- Motor: MariaDB 10.4+ / MySQL 8+
-- ============================================================

CREATE DATABASE IF NOT EXISTS `iglesia_asistencia`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `iglesia_asistencia`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================
-- 1) TABLA: roles
-- ============================================================
CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'ADMIN'),
(2, 'SECRETARIO');

-- ============================================================
-- 2) TABLA: usuarios
-- ============================================================
CREATE TABLE `usuarios` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(120) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `password_actualizada_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `rol_id` tinyint(3) UNSIGNED NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_usuario` (`usuario`),
  KEY `idx_usuarios_rol` (`rol_id`),
  CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: Admin inicial (usuario: admin / password: admin123)
INSERT INTO `usuarios` (`id`, `nombre_completo`, `usuario`, `password_hash`, `password_actualizada_en`, `rol_id`, `activo`) VALUES
(1, 'Administrador General', 'admin', '$2y$10$MxWFkCGW30rMt/8/tO4KuuoFKipqIFET8yJ6SDR9FemGTzlbpHEHC', NOW(), 1, 1);

-- ============================================================
-- 3) TABLA: user_tokens (autenticacion Bearer)
-- ============================================================
CREATE TABLE `user_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` varchar(64) NOT NULL COMMENT 'SHA-256 del token plano',
  `ultimo_uso_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `expira_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  KEY `idx_token_usuario` (`usuario_id`),
  KEY `idx_token_expira` (`expira_en`),
  KEY `idx_token_ultimo_uso` (`ultimo_uso_en`),
  CONSTRAINT `fk_token_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4) TABLA: cultos
-- ============================================================
CREATE TABLE `cultos` (
  `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `dia_semana` tinyint(1) UNSIGNED NOT NULL COMMENT 'DAYOFWEEK: 1=Dom,2=Lun,...,7=Sab',
  `hora_inicio` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cultos_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cultos` (`id`, `codigo`, `nombre`, `dia_semana`, `hora_inicio`) VALUES
(1, 'SABADO',    'Culto Sabado',    7, '09:00:00'),
(2, 'DOMINGO',   'Culto Domingo',   1, '18:30:00'),
(3, 'MIERCOLES', 'Culto Miercoles', 4, '18:30:00');

-- ============================================================
-- 5) TABLA: asistencia_registro
-- ============================================================
CREATE TABLE `asistencia_registro` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `culto_id` tinyint(3) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `anio` smallint(6) GENERATED ALWAYS AS (YEAR(`fecha`)) STORED,
  `trimestre` tinyint(4) GENERATED ALWAYS AS (QUARTER(`fecha`)) STORED,
  `llegaron_antes_hora` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `llegaron_despues_hora` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ninos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `jovenes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_asistentes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `proc_barrio` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `proc_guayabo` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `visitas_barrio` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `nombres_visitas_barrio` text DEFAULT NULL COMMENT 'Nombres de las visitas del barrio',
  `visitas_guayabo` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `nombres_visitas_guayabo` text DEFAULT NULL COMMENT 'Nombres de las visitas de Guayabo',
  `retiros_antes_terminar` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `se_quedaron_todo` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `registrado_por` bigint(20) UNSIGNED NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asistencia_culto_fecha` (`culto_id`, `fecha`),
  KEY `idx_asistencia_usuario` (`registrado_por`),
  KEY `idx_asistencia_fecha` (`fecha`),
  KEY `idx_asistencia_anio_trim` (`anio`, `trimestre`, `culto_id`),
  CONSTRAINT `fk_asistencia_culto` FOREIGN KEY (`culto_id`) REFERENCES `cultos` (`id`),
  CONSTRAINT `fk_asistencia_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `chk_total_vs_composicion` CHECK (`total_asistentes` >= `ninos` + `jovenes`),
  CONSTRAINT `chk_permanencia_vs_total` CHECK (`retiros_antes_terminar` + `se_quedaron_todo` <= `total_asistentes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6) VISTAS por culto (sin JOIN innecesario, filtran por culto_id)
-- ============================================================

CREATE OR REPLACE VIEW `asistencia_sabado` AS
SELECT `id`, `culto_id`, `fecha`, `anio`, `trimestre`,
       `llegaron_antes_hora`, `llegaron_despues_hora`,
       `ninos`, `jovenes`, `total_asistentes`,
       `proc_barrio`, `proc_guayabo`,
       `visitas_barrio`, `nombres_visitas_barrio`,
       `visitas_guayabo`, `nombres_visitas_guayabo`,
       `retiros_antes_terminar`, `se_quedaron_todo`,
       `observaciones`, `registrado_por`, `creado_en`, `actualizado_en`
FROM `asistencia_registro`
WHERE `culto_id` = 1;

CREATE OR REPLACE VIEW `asistencia_domingo` AS
SELECT `id`, `culto_id`, `fecha`, `anio`, `trimestre`,
       `llegaron_antes_hora`, `llegaron_despues_hora`,
       `ninos`, `jovenes`, `total_asistentes`,
       `proc_barrio`, `proc_guayabo`,
       `visitas_barrio`, `nombres_visitas_barrio`,
       `visitas_guayabo`, `nombres_visitas_guayabo`,
       `retiros_antes_terminar`, `se_quedaron_todo`,
       `observaciones`, `registrado_por`, `creado_en`, `actualizado_en`
FROM `asistencia_registro`
WHERE `culto_id` = 2;

CREATE OR REPLACE VIEW `asistencia_miercoles` AS
SELECT `id`, `culto_id`, `fecha`, `anio`, `trimestre`,
       `llegaron_antes_hora`, `llegaron_despues_hora`,
       `ninos`, `jovenes`, `total_asistentes`,
       `proc_barrio`, `proc_guayabo`,
       `visitas_barrio`, `nombres_visitas_barrio`,
       `visitas_guayabo`, `nombres_visitas_guayabo`,
       `retiros_antes_terminar`, `se_quedaron_todo`,
       `observaciones`, `registrado_por`, `creado_en`, `actualizado_en`
FROM `asistencia_registro`
WHERE `culto_id` = 3;
