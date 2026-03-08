-- MIGRACION: Modulo de presentaciones (motor deterministico)
-- Fecha: 2026-03-06

USE `iglesia_asistencia`;

CREATE TABLE IF NOT EXISTS `presentaciones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `anio` smallint(6) NOT NULL,
  `mes` tinyint(3) UNSIGNED NOT NULL,
  `culto_codigo` varchar(20) DEFAULT NULL,
  `filtros_json` longtext NOT NULL,
  `metricas_json` longtext NOT NULL,
  `prompt_version` varchar(20) NOT NULL DEFAULT 'v1',
  `prompt_bloqueado` longtext NOT NULL,
  `modelo` varchar(80) NOT NULL,
  `ia_response_id` varchar(120) NOT NULL,
  `presentacion_json` longtext NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_presentaciones_periodo` (`anio`, `mes`, `culto_codigo`),
  KEY `idx_presentaciones_usuario` (`usuario_id`, `creado_en`),
  KEY `idx_presentaciones_creado_en` (`creado_en`),
  CONSTRAINT `fk_presentaciones_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
