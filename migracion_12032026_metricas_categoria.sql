-- MIGRACION: Simplificar configuracion de metricas por categoria (sin depende/regla/orden)
-- Fecha: 2026-03-12

START TRANSACTION;

ALTER TABLE `organizacion_metricas_config`
  ADD COLUMN IF NOT EXISTS `categoria` varchar(50) NULL AFTER `etiqueta`;

UPDATE `organizacion_metricas_config`
SET `categoria` = CASE
    WHEN LOWER(`clave`) IN ('llegaron_antes_hora', 'llegaron_despues_hora') THEN 'informacion_culto'
    WHEN LOWER(`clave`) IN ('ninos', 'jovenes') THEN 'composicion_asistentes'
    WHEN LOWER(`clave`) = 'total_asistentes' THEN 'total_asistentes'
    WHEN LOWER(`clave`) LIKE 'proc\_%' THEN 'procedencia'
    WHEN LOWER(`clave`) LIKE 'visitas\_%' OR LOWER(`clave`) LIKE 'nombres_visitas\_%' THEN 'visitas'
    WHEN LOWER(`clave`) IN ('retiros_antes_terminar', 'se_quedaron_todo') THEN 'permanencia'
    WHEN LOWER(`clave`) = 'observaciones' THEN 'observaciones'
    ELSE 'adicionales'
END
WHERE `categoria` IS NULL OR TRIM(`categoria`) = '';

ALTER TABLE `organizacion_metricas_config`
  MODIFY COLUMN `categoria` varchar(50) NOT NULL DEFAULT 'adicionales';

ALTER TABLE `organizacion_metricas_config`
  DROP COLUMN IF EXISTS `depende_de_clave`,
  DROP COLUMN IF EXISTS `regla_dependencia`,
  DROP COLUMN IF EXISTS `orden`;

SET @idx_old_exists := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'organizacion_metricas_config'
    AND index_name = 'idx_org_metricas_estado'
);
SET @sql_drop_old := IF(
  @idx_old_exists > 0,
  'ALTER TABLE `organizacion_metricas_config` DROP INDEX `idx_org_metricas_estado`',
  'SELECT 1'
);
PREPARE stmt_drop_old FROM @sql_drop_old;
EXECUTE stmt_drop_old;
DEALLOCATE PREPARE stmt_drop_old;

SET @idx_new_exists := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'organizacion_metricas_config'
    AND index_name = 'idx_org_metricas_categoria'
);
SET @sql_add_new := IF(
  @idx_new_exists = 0,
  'ALTER TABLE `organizacion_metricas_config` ADD KEY `idx_org_metricas_categoria` (`organizacion_id`, `categoria`, `habilitado`)',
  'SELECT 1'
);
PREPARE stmt_add_new FROM @sql_add_new;
EXECUTE stmt_add_new;
DEALLOCATE PREPARE stmt_add_new;

COMMIT;
