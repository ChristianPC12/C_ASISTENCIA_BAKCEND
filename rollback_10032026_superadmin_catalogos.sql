-- ============================================================
-- ROLLBACK: Catalogos superadmin (campos y distritos persistentes)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-10
-- ============================================================

USE `iglesia_asistencia`;

-- 1) Quitar FK organizaciones -> distritos
SET @existe_fk_distrito := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_organizaciones_distrito'
);

SET @sql_drop_fk := IF(
  @existe_fk_distrito > 0,
  'ALTER TABLE `organizaciones` DROP FOREIGN KEY `fk_organizaciones_distrito`',
  'SELECT 1'
);

PREPARE stmt_drop_fk FROM @sql_drop_fk;
EXECUTE stmt_drop_fk;
DEALLOCATE PREPARE stmt_drop_fk;

-- 2) Quitar indice de distrito en organizaciones
SET @existe_idx_distrito := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organizaciones'
    AND INDEX_NAME = 'idx_organizaciones_distrito'
);

SET @sql_drop_idx := IF(
  @existe_idx_distrito > 0,
  'ALTER TABLE `organizaciones` DROP INDEX `idx_organizaciones_distrito`',
  'SELECT 1'
);

PREPARE stmt_drop_idx FROM @sql_drop_idx;
EXECUTE stmt_drop_idx;
DEALLOCATE PREPARE stmt_drop_idx;

-- 3) Quitar columna distrito_id
SET @existe_col_distrito := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organizaciones'
    AND COLUMN_NAME = 'distrito_id'
);

SET @sql_drop_col := IF(
  @existe_col_distrito > 0,
  'ALTER TABLE `organizaciones` DROP COLUMN `distrito_id`',
  'SELECT 1'
);

PREPARE stmt_drop_col FROM @sql_drop_col;
EXECUTE stmt_drop_col;
DEALLOCATE PREPARE stmt_drop_col;

-- 4) Quitar tabla distritos
DROP TABLE IF EXISTS `distritos`;
