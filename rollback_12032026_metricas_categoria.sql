-- ROLLBACK: Restaurar columnas depende/regla/orden en metricas
-- Fecha: 2026-03-12

START TRANSACTION;

ALTER TABLE `organizacion_metricas_config`
  ADD COLUMN IF NOT EXISTS `depende_de_clave` varchar(80) DEFAULT NULL AFTER `obligatorio`,
  ADD COLUMN IF NOT EXISTS `regla_dependencia` varchar(60) DEFAULT NULL AFTER `depende_de_clave`,
  ADD COLUMN IF NOT EXISTS `orden` int unsigned NOT NULL DEFAULT 10 AFTER `regla_dependencia`;

UPDATE `organizacion_metricas_config`
SET `orden` = `id`
WHERE `orden` IS NULL OR `orden` = 0;

ALTER TABLE `organizacion_metricas_config`
  DROP COLUMN IF EXISTS `categoria`;

SET @idx_new_exists := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'organizacion_metricas_config'
    AND index_name = 'idx_org_metricas_categoria'
);
SET @sql_drop_new := IF(
  @idx_new_exists > 0,
  'ALTER TABLE `organizacion_metricas_config` DROP INDEX `idx_org_metricas_categoria`',
  'SELECT 1'
);
PREPARE stmt_drop_new FROM @sql_drop_new;
EXECUTE stmt_drop_new;
DEALLOCATE PREPARE stmt_drop_new;

SET @idx_old_exists := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'organizacion_metricas_config'
    AND index_name = 'idx_org_metricas_estado'
);
SET @sql_add_old := IF(
  @idx_old_exists = 0,
  'ALTER TABLE `organizacion_metricas_config` ADD KEY `idx_org_metricas_estado` (`organizacion_id`, `habilitado`, `orden`)',
  'SELECT 1'
);
PREPARE stmt_add_old FROM @sql_add_old;
EXECUTE stmt_add_old;
DEALLOCATE PREPARE stmt_add_old;

COMMIT;
