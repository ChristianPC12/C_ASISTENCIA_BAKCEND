-- ============================================================
-- MIGRACION: Campo persistente de expiracion de password
-- BD: iglesia_asistencia
-- Fecha: 06-03-2026
-- ============================================================

USE `iglesia_asistencia`;

ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `password_expira_en` timestamp NULL DEFAULT current_timestamp() AFTER `password_actualizada_en`;

UPDATE `usuarios`
SET `password_expira_en` = DATE_ADD(COALESCE(`password_actualizada_en`, `creado_en`, NOW()), INTERVAL 30 DAY)
WHERE `password_expira_en` IS NULL;

ALTER TABLE `usuarios`
  MODIFY COLUMN `password_expira_en` timestamp NOT NULL DEFAULT current_timestamp();

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND INDEX_NAME = 'idx_usuarios_password_expira'
);

SET @sql_add_idx := IF(
  @idx_exists = 0,
  'ALTER TABLE `usuarios` ADD KEY `idx_usuarios_password_expira` (`password_expira_en`)',
  'SELECT 1'
);

PREPARE stmt_add_idx FROM @sql_add_idx;
EXECUTE stmt_add_idx;
DEALLOCATE PREPARE stmt_add_idx;
