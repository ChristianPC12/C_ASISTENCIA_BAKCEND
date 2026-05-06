-- MIGRACION: Estudios biblicos con instructores, frecuencia y registro de sesiones
-- Fecha: 2026-05-04

INSERT INTO `roles` (`id`, `nombre`)
SELECT 4, 'MINISTERIO_PERSONAL'
WHERE NOT EXISTS (
  SELECT 1 FROM `roles` WHERE `nombre` = 'MINISTERIO_PERSONAL'
);

INSERT INTO `roles` (`id`, `nombre`)
SELECT 5, 'INSTRUCTOR_BIBLICO'
WHERE NOT EXISTS (
  SELECT 1 FROM `roles` WHERE `nombre` = 'INSTRUCTOR_BIBLICO'
);

ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `cargo` varchar(120) NULL AFTER `usuario`;

ALTER TABLE `estudios_biblicos`
  ADD COLUMN IF NOT EXISTS `visita_asistente_id` bigint(20) unsigned NULL AFTER `contacto_id`,
  ADD COLUMN IF NOT EXISTS `frecuencia_periodo` varchar(20) NOT NULL DEFAULT 'SEMANA' AFTER `fecha_inicio`,
  ADD COLUMN IF NOT EXISTS `frecuencia_cantidad` tinyint(3) unsigned NOT NULL DEFAULT 1 AFTER `frecuencia_periodo`;

ALTER TABLE `estudio_sesiones`
  ADD COLUMN IF NOT EXISTS `progreso_bautismo` tinyint(3) unsigned NULL AFTER `percepcion_avance`;

SET @fk_estudio_visita := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'estudios_biblicos'
    AND CONSTRAINT_NAME = 'fk_estudios_biblicos_visita_asistente'
);

SET @sql_fk_estudio_visita := IF(
  @fk_estudio_visita = 0,
  'ALTER TABLE `estudios_biblicos`
     ADD KEY `idx_estudios_biblicos_visita_asistente` (`visita_asistente_id`),
     ADD CONSTRAINT `fk_estudios_biblicos_visita_asistente`
       FOREIGN KEY (`visita_asistente_id`) REFERENCES `campana_asistentes` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt_fk_estudio_visita FROM @sql_fk_estudio_visita;
EXECUTE stmt_fk_estudio_visita;
DEALLOCATE PREPARE stmt_fk_estudio_visita;

UPDATE `organizacion_roles_cupos`
SET `cupo_maximo` = GREATEST(`cupo_maximo`, 2), `activo` = 1
WHERE `rol_nombre` = 'MINISTERIO_PERSONAL';

INSERT INTO `organizacion_roles_cupos` (`organizacion_id`, `rol_nombre`, `cupo_maximo`, `activo`)
SELECT o.`id`, 'MINISTERIO_PERSONAL', 2, 1
FROM `organizaciones` o
WHERE NOT EXISTS (
  SELECT 1
  FROM `organizacion_roles_cupos` c
  WHERE c.`organizacion_id` = o.`id`
    AND c.`rol_nombre` = 'MINISTERIO_PERSONAL'
);

INSERT INTO `organizacion_roles_cupos` (`organizacion_id`, `rol_nombre`, `cupo_maximo`, `activo`)
SELECT o.`id`, 'INSTRUCTOR_BIBLICO', 50, 1
FROM `organizaciones` o
WHERE NOT EXISTS (
  SELECT 1
  FROM `organizacion_roles_cupos` c
  WHERE c.`organizacion_id` = o.`id`
    AND c.`rol_nombre` = 'INSTRUCTOR_BIBLICO'
);
