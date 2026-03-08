-- MIGRACION: Renombrar columna openai_response_id a ia_response_id
-- Fecha: 2026-03-07

USE `iglesia_asistencia`;

SET @tabla_existe := (
  SELECT COUNT(*)
  FROM information_schema.tables
  WHERE table_schema = DATABASE()
    AND table_name = 'presentaciones'
);

SET @columna_openai_existe := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'presentaciones'
    AND column_name = 'openai_response_id'
);

SET @columna_ia_existe := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'presentaciones'
    AND column_name = 'ia_response_id'
);

SET @sql_rename := IF(
  @tabla_existe > 0 AND @columna_openai_existe > 0 AND @columna_ia_existe = 0,
  'ALTER TABLE `presentaciones` CHANGE COLUMN `openai_response_id` `ia_response_id` varchar(120) NOT NULL',
  'SELECT 1'
);

PREPARE stmt_rename FROM @sql_rename;
EXECUTE stmt_rename;
DEALLOCATE PREPARE stmt_rename;
