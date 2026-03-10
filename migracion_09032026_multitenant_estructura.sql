-- ============================================================
-- MIGRACION: Estructura base multitenant (B1-T01)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-09
-- ============================================================

USE `iglesia_asistencia`;

-- 1) Tabla de campos IASD CR
CREATE TABLE IF NOT EXISTS `campos` (
  `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_campos_codigo` (`codigo`),
  UNIQUE KEY `uq_campos_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `campos` (`codigo`, `nombre`, `activo`)
SELECT 'AN', 'Asociacion Norte', 1
WHERE NOT EXISTS (
  SELECT 1 FROM `campos` WHERE `codigo` = 'AN'
);

INSERT INTO `campos` (`codigo`, `nombre`, `activo`)
SELECT 'ACS', 'Asociacion Central Sur', 1
WHERE NOT EXISTS (
  SELECT 1 FROM `campos` WHERE `codigo` = 'ACS'
);

INSERT INTO `campos` (`codigo`, `nombre`, `activo`)
SELECT 'MC', 'Mision Caribe', 1
WHERE NOT EXISTS (
  SELECT 1 FROM `campos` WHERE `codigo` = 'MC'
);

-- 2) Tabla de organizaciones (tenant)
CREATE TABLE IF NOT EXISTS `organizaciones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `campo_id` tinyint(3) UNSIGNED NOT NULL,
  `codigo_instancia` varchar(12) NOT NULL,
  `tipo_organizacion` varchar(10) NOT NULL,
  `nombre_organizacion` varchar(160) NOT NULL,
  `correo_contacto` varchar(160) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_organizaciones_codigo_instancia` (`codigo_instancia`),
  KEY `idx_organizaciones_campo` (`campo_id`),
  KEY `idx_organizaciones_tipo_activa` (`tipo_organizacion`, `activa`),
  CONSTRAINT `fk_organizaciones_campo`
    FOREIGN KEY (`campo_id`) REFERENCES `campos` (`id`),
  CONSTRAINT `chk_organizaciones_tipo`
    CHECK (`tipo_organizacion` IN ('IGLESIA', 'GRUPO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Estado de configuracion inicial por organizacion
CREATE TABLE IF NOT EXISTS `organizacion_config_estado` (
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `estado_setup` varchar(20) NOT NULL DEFAULT 'PENDIENTE',
  `bloqueada_operacion` tinyint(1) NOT NULL DEFAULT 1,
  `setup_completado_en` timestamp NULL DEFAULT NULL,
  `ultima_revision_en` timestamp NULL DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`organizacion_id`),
  KEY `idx_org_config_estado` (`estado_setup`, `bloqueada_operacion`),
  CONSTRAINT `fk_org_config_estado_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_org_config_estado_setup`
    CHECK (`estado_setup` IN ('PENDIENTE', 'COMPLETO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Catalogo de cultos por organizacion
CREATE TABLE IF NOT EXISTS `organizacion_cultos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `dia_semana` tinyint(3) UNSIGNED NOT NULL,
  `hora_inicio` time NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_cultos_codigo` (`organizacion_id`, `codigo`),
  KEY `idx_org_cultos_estado` (`organizacion_id`, `activo`, `orden`),
  CONSTRAINT `fk_org_cultos_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_org_cultos_dia_semana`
    CHECK (`dia_semana` BETWEEN 1 AND 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) Catalogo de procedencias por organizacion
CREATE TABLE IF NOT EXISTS `organizacion_procedencias` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_procedencias_nombre` (`organizacion_id`, `nombre`),
  KEY `idx_org_procedencias_estado` (`organizacion_id`, `activo`, `orden`),
  CONSTRAINT `fk_org_procedencias_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) Configuracion de metricas por organizacion
CREATE TABLE IF NOT EXISTS `organizacion_metricas_config` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `clave` varchar(80) NOT NULL,
  `etiqueta` varchar(120) NOT NULL,
  `habilitado` tinyint(1) NOT NULL DEFAULT 1,
  `obligatorio` tinyint(1) NOT NULL DEFAULT 0,
  `depende_de_clave` varchar(80) DEFAULT NULL,
  `regla_dependencia` varchar(60) DEFAULT NULL,
  `orden` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_metricas_clave` (`organizacion_id`, `clave`),
  KEY `idx_org_metricas_estado` (`organizacion_id`, `habilitado`, `orden`),
  CONSTRAINT `fk_org_metricas_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7) Cupos de usuarios por rol y organizacion
CREATE TABLE IF NOT EXISTS `organizacion_roles_cupos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `rol_nombre` varchar(40) NOT NULL,
  `cupo_maximo` int(10) UNSIGNED NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_roles_cupo` (`organizacion_id`, `rol_nombre`),
  KEY `idx_org_roles_cupo_estado` (`organizacion_id`, `activo`),
  CONSTRAINT `fk_org_roles_cupo_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_org_roles_cupo_maximo`
    CHECK (`cupo_maximo` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
