-- ============================================================
-- MIGRACION: Catalogos superadmin (campos y distritos persistentes)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-10
-- ============================================================

USE `iglesia_asistencia`;

-- 1) Catalogo de distritos
CREATE TABLE IF NOT EXISTS `distritos` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` varchar(24) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_distritos_codigo` (`codigo`),
  UNIQUE KEY `uq_distritos_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Columna distrito_id en organizaciones (si aun no existe)
SET @existe_col_distrito := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organizaciones'
    AND COLUMN_NAME = 'distrito_id'
);

SET @sql_col_distrito := IF(
  @existe_col_distrito = 0,
  'ALTER TABLE `organizaciones` ADD COLUMN `distrito_id` smallint(5) UNSIGNED NULL AFTER `campo_id`',
  'SELECT 1'
);

PREPARE stmt_col_distrito FROM @sql_col_distrito;
EXECUTE stmt_col_distrito;
DEALLOCATE PREPARE stmt_col_distrito;

-- 3) Indice para distrito_id
SET @existe_idx_distrito := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organizaciones'
    AND INDEX_NAME = 'idx_organizaciones_distrito'
);

SET @sql_idx_distrito := IF(
  @existe_idx_distrito = 0,
  'ALTER TABLE `organizaciones` ADD KEY `idx_organizaciones_distrito` (`distrito_id`)',
  'SELECT 1'
);

PREPARE stmt_idx_distrito FROM @sql_idx_distrito;
EXECUTE stmt_idx_distrito;
DEALLOCATE PREPARE stmt_idx_distrito;

-- 4) FK organizaciones -> distritos
SET @existe_fk_distrito := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_organizaciones_distrito'
);

SET @sql_fk_distrito := IF(
  @existe_fk_distrito = 0,
  'ALTER TABLE `organizaciones`
     ADD CONSTRAINT `fk_organizaciones_distrito`
     FOREIGN KEY (`distrito_id`) REFERENCES `distritos` (`id`)
     ON UPDATE RESTRICT
     ON DELETE RESTRICT',
  'SELECT 1'
);

PREPARE stmt_fk_distrito FROM @sql_fk_distrito;
EXECUTE stmt_fk_distrito;
DEALLOCATE PREPARE stmt_fk_distrito;
