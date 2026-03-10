-- ============================================================
-- ROLLBACK: Semilla base multitenant (B1-T03)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-09
-- ============================================================

USE `iglesia_asistencia`;

SET @org_base := (
  SELECT id
  FROM `organizaciones`
  WHERE `codigo_instancia` = 'BASECR01'
  LIMIT 1
);

-- Quitar referencias en tablas operativas antes de eliminar la organizacion base.
UPDATE `user_tokens`
SET `organizacion_id` = NULL
WHERE `organizacion_id` = @org_base;

UPDATE `asistencia_registro`
SET `organizacion_id` = NULL
WHERE `organizacion_id` = @org_base;

SET @tbl_presentaciones_existe := (
  SELECT COUNT(1)
  FROM information_schema.tables
  WHERE table_schema = DATABASE()
    AND table_name = 'presentaciones'
);

SET @sql_null_presentaciones_org := IF(
  @tbl_presentaciones_existe > 0,
  'UPDATE `presentaciones` SET `organizacion_id` = NULL WHERE `organizacion_id` = @org_base',
  'SELECT 1'
);
PREPARE stmt_null_presentaciones_org FROM @sql_null_presentaciones_org;
EXECUTE stmt_null_presentaciones_org;
DEALLOCATE PREPARE stmt_null_presentaciones_org;

UPDATE `usuarios`
SET `organizacion_id` = NULL
WHERE `organizacion_id` = @org_base;

-- Eliminar tenant base; tablas configurables dependientes se eliminan por ON DELETE CASCADE.
DELETE FROM `organizaciones`
WHERE `id` = @org_base;
