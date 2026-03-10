-- ============================================================
-- ROLLBACK: Relaciones tenant-aware sobre tablas operativas (B1-T02)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-09
-- ============================================================

USE `iglesia_asistencia`;

-- ============================================================
-- 1) Soltar llaves foraneas nuevas
-- ============================================================

SET @fk_usuarios_org := (
  SELECT COUNT(1)
  FROM information_schema.table_constraints
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND constraint_name = 'fk_usuarios_organizacion'
    AND constraint_type = 'FOREIGN KEY'
);
SET @sql_drop_fk_usuarios_org := IF(
  @fk_usuarios_org > 0,
  'ALTER TABLE `usuarios` DROP FOREIGN KEY `fk_usuarios_organizacion`',
  'SELECT 1'
);
PREPARE stmt_drop_fk_usuarios_org FROM @sql_drop_fk_usuarios_org;
EXECUTE stmt_drop_fk_usuarios_org;
DEALLOCATE PREPARE stmt_drop_fk_usuarios_org;

SET @fk_asistencia_org := (
  SELECT COUNT(1)
  FROM information_schema.table_constraints
  WHERE table_schema = DATABASE()
    AND table_name = 'asistencia_registro'
    AND constraint_name = 'fk_asistencia_organizacion'
    AND constraint_type = 'FOREIGN KEY'
);
SET @sql_drop_fk_asistencia_org := IF(
  @fk_asistencia_org > 0,
  'ALTER TABLE `asistencia_registro` DROP FOREIGN KEY `fk_asistencia_organizacion`',
  'SELECT 1'
);
PREPARE stmt_drop_fk_asistencia_org FROM @sql_drop_fk_asistencia_org;
EXECUTE stmt_drop_fk_asistencia_org;
DEALLOCATE PREPARE stmt_drop_fk_asistencia_org;

SET @tbl_presentaciones_existe := (
  SELECT COUNT(1)
  FROM information_schema.tables
  WHERE table_schema = DATABASE()
    AND table_name = 'presentaciones'
);

SET @fk_presentaciones_org := (
  SELECT COUNT(1)
  FROM information_schema.table_constraints
  WHERE table_schema = DATABASE()
    AND table_name = 'presentaciones'
    AND constraint_name = 'fk_presentaciones_organizacion'
    AND constraint_type = 'FOREIGN KEY'
);
SET @sql_drop_fk_presentaciones_org := IF(
  @tbl_presentaciones_existe > 0 AND @fk_presentaciones_org > 0,
  'ALTER TABLE `presentaciones` DROP FOREIGN KEY `fk_presentaciones_organizacion`',
  'SELECT 1'
);
PREPARE stmt_drop_fk_presentaciones_org FROM @sql_drop_fk_presentaciones_org;
EXECUTE stmt_drop_fk_presentaciones_org;
DEALLOCATE PREPARE stmt_drop_fk_presentaciones_org;

SET @fk_tokens_org := (
  SELECT COUNT(1)
  FROM information_schema.table_constraints
  WHERE table_schema = DATABASE()
    AND table_name = 'user_tokens'
    AND constraint_name = 'fk_tokens_organizacion'
    AND constraint_type = 'FOREIGN KEY'
);
SET @sql_drop_fk_tokens_org := IF(
  @fk_tokens_org > 0,
  'ALTER TABLE `user_tokens` DROP FOREIGN KEY `fk_tokens_organizacion`',
  'SELECT 1'
);
PREPARE stmt_drop_fk_tokens_org FROM @sql_drop_fk_tokens_org;
EXECUTE stmt_drop_fk_tokens_org;
DEALLOCATE PREPARE stmt_drop_fk_tokens_org;

-- ============================================================
-- 2) Restaurar unicidad anterior de asistencia
-- ============================================================

SET @idx_uq_asistencia_org_culto_fecha := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'asistencia_registro'
    AND index_name = 'uq_asistencia_org_culto_fecha'
);
SET @sql_drop_uq_asistencia_org_culto_fecha := IF(
  @idx_uq_asistencia_org_culto_fecha > 0,
  'ALTER TABLE `asistencia_registro` DROP INDEX `uq_asistencia_org_culto_fecha`',
  'SELECT 1'
);
PREPARE stmt_drop_uq_asistencia_org_culto_fecha FROM @sql_drop_uq_asistencia_org_culto_fecha;
EXECUTE stmt_drop_uq_asistencia_org_culto_fecha;
DEALLOCATE PREPARE stmt_drop_uq_asistencia_org_culto_fecha;

SET @idx_uq_asistencia_culto_fecha := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'asistencia_registro'
    AND index_name = 'uq_asistencia_culto_fecha'
);
SET @sql_add_uq_asistencia_culto_fecha := IF(
  @idx_uq_asistencia_culto_fecha = 0,
  'ALTER TABLE `asistencia_registro` ADD UNIQUE KEY `uq_asistencia_culto_fecha` (`culto_id`, `fecha`)',
  'SELECT 1'
);
PREPARE stmt_add_uq_asistencia_culto_fecha FROM @sql_add_uq_asistencia_culto_fecha;
EXECUTE stmt_add_uq_asistencia_culto_fecha;
DEALLOCATE PREPARE stmt_add_uq_asistencia_culto_fecha;

-- ============================================================
-- 3) Soltar indices nuevos
-- ============================================================

SET @idx_usuarios_organizacion := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND index_name = 'idx_usuarios_organizacion'
);
SET @sql_drop_idx_usuarios_organizacion := IF(
  @idx_usuarios_organizacion > 0,
  'ALTER TABLE `usuarios` DROP INDEX `idx_usuarios_organizacion`',
  'SELECT 1'
);
PREPARE stmt_drop_idx_usuarios_organizacion FROM @sql_drop_idx_usuarios_organizacion;
EXECUTE stmt_drop_idx_usuarios_organizacion;
DEALLOCATE PREPARE stmt_drop_idx_usuarios_organizacion;

SET @idx_usuarios_org_rol_activo := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND index_name = 'idx_usuarios_org_rol_activo'
);
SET @sql_drop_idx_usuarios_org_rol_activo := IF(
  @idx_usuarios_org_rol_activo > 0,
  'ALTER TABLE `usuarios` DROP INDEX `idx_usuarios_org_rol_activo`',
  'SELECT 1'
);
PREPARE stmt_drop_idx_usuarios_org_rol_activo FROM @sql_drop_idx_usuarios_org_rol_activo;
EXECUTE stmt_drop_idx_usuarios_org_rol_activo;
DEALLOCATE PREPARE stmt_drop_idx_usuarios_org_rol_activo;

SET @idx_asistencia_organizacion := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'asistencia_registro' AND index_name = 'idx_asistencia_organizacion'
);
SET @sql_drop_idx_asistencia_organizacion := IF(
  @idx_asistencia_organizacion > 0,
  'ALTER TABLE `asistencia_registro` DROP INDEX `idx_asistencia_organizacion`',
  'SELECT 1'
);
PREPARE stmt_drop_idx_asistencia_organizacion FROM @sql_drop_idx_asistencia_organizacion;
EXECUTE stmt_drop_idx_asistencia_organizacion;
DEALLOCATE PREPARE stmt_drop_idx_asistencia_organizacion;

SET @idx_asistencia_org_fecha := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'asistencia_registro' AND index_name = 'idx_asistencia_org_fecha'
);
SET @sql_drop_idx_asistencia_org_fecha := IF(
  @idx_asistencia_org_fecha > 0,
  'ALTER TABLE `asistencia_registro` DROP INDEX `idx_asistencia_org_fecha`',
  'SELECT 1'
);
PREPARE stmt_drop_idx_asistencia_org_fecha FROM @sql_drop_idx_asistencia_org_fecha;
EXECUTE stmt_drop_idx_asistencia_org_fecha;
DEALLOCATE PREPARE stmt_drop_idx_asistencia_org_fecha;

SET @idx_asistencia_org_anio_trim_culto := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'asistencia_registro' AND index_name = 'idx_asistencia_org_anio_trim_culto'
);
SET @sql_drop_idx_asistencia_org_anio_trim_culto := IF(
  @idx_asistencia_org_anio_trim_culto > 0,
  'ALTER TABLE `asistencia_registro` DROP INDEX `idx_asistencia_org_anio_trim_culto`',
  'SELECT 1'
);
PREPARE stmt_drop_idx_asistencia_org_anio_trim_culto FROM @sql_drop_idx_asistencia_org_anio_trim_culto;
EXECUTE stmt_drop_idx_asistencia_org_anio_trim_culto;
DEALLOCATE PREPARE stmt_drop_idx_asistencia_org_anio_trim_culto;

SET @idx_asistencia_culto_fk := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'asistencia_registro' AND index_name = 'idx_asistencia_culto_fk'
);
SET @sql_drop_idx_asistencia_culto_fk := IF(
  @idx_asistencia_culto_fk > 0,
  'ALTER TABLE `asistencia_registro` DROP INDEX `idx_asistencia_culto_fk`',
  'SELECT 1'
);
PREPARE stmt_drop_idx_asistencia_culto_fk FROM @sql_drop_idx_asistencia_culto_fk;
EXECUTE stmt_drop_idx_asistencia_culto_fk;
DEALLOCATE PREPARE stmt_drop_idx_asistencia_culto_fk;

SET @idx_presentaciones_organizacion := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'presentaciones' AND index_name = 'idx_presentaciones_organizacion'
);
SET @sql_drop_idx_presentaciones_organizacion := IF(
  @tbl_presentaciones_existe > 0 AND @idx_presentaciones_organizacion > 0,
  'ALTER TABLE `presentaciones` DROP INDEX `idx_presentaciones_organizacion`',
  'SELECT 1'
);
PREPARE stmt_drop_idx_presentaciones_organizacion FROM @sql_drop_idx_presentaciones_organizacion;
EXECUTE stmt_drop_idx_presentaciones_organizacion;
DEALLOCATE PREPARE stmt_drop_idx_presentaciones_organizacion;

SET @idx_presentaciones_org_periodo := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'presentaciones' AND index_name = 'idx_presentaciones_org_periodo'
);
SET @sql_drop_idx_presentaciones_org_periodo := IF(
  @tbl_presentaciones_existe > 0 AND @idx_presentaciones_org_periodo > 0,
  'ALTER TABLE `presentaciones` DROP INDEX `idx_presentaciones_org_periodo`',
  'SELECT 1'
);
PREPARE stmt_drop_idx_presentaciones_org_periodo FROM @sql_drop_idx_presentaciones_org_periodo;
EXECUTE stmt_drop_idx_presentaciones_org_periodo;
DEALLOCATE PREPARE stmt_drop_idx_presentaciones_org_periodo;

SET @idx_presentaciones_org_usuario_creado := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'presentaciones' AND index_name = 'idx_presentaciones_org_usuario_creado'
);
SET @sql_drop_idx_presentaciones_org_usuario_creado := IF(
  @tbl_presentaciones_existe > 0 AND @idx_presentaciones_org_usuario_creado > 0,
  'ALTER TABLE `presentaciones` DROP INDEX `idx_presentaciones_org_usuario_creado`',
  'SELECT 1'
);
PREPARE stmt_drop_idx_presentaciones_org_usuario_creado FROM @sql_drop_idx_presentaciones_org_usuario_creado;
EXECUTE stmt_drop_idx_presentaciones_org_usuario_creado;
DEALLOCATE PREPARE stmt_drop_idx_presentaciones_org_usuario_creado;

SET @idx_tokens_organizacion := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'user_tokens' AND index_name = 'idx_tokens_organizacion'
);
SET @sql_drop_idx_tokens_organizacion := IF(
  @idx_tokens_organizacion > 0,
  'ALTER TABLE `user_tokens` DROP INDEX `idx_tokens_organizacion`',
  'SELECT 1'
);
PREPARE stmt_drop_idx_tokens_organizacion FROM @sql_drop_idx_tokens_organizacion;
EXECUTE stmt_drop_idx_tokens_organizacion;
DEALLOCATE PREPARE stmt_drop_idx_tokens_organizacion;

SET @idx_tokens_usuario_organizacion := (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'user_tokens' AND index_name = 'idx_tokens_usuario_organizacion'
);
SET @sql_drop_idx_tokens_usuario_organizacion := IF(
  @idx_tokens_usuario_organizacion > 0,
  'ALTER TABLE `user_tokens` DROP INDEX `idx_tokens_usuario_organizacion`',
  'SELECT 1'
);
PREPARE stmt_drop_idx_tokens_usuario_organizacion FROM @sql_drop_idx_tokens_usuario_organizacion;
EXECUTE stmt_drop_idx_tokens_usuario_organizacion;
DEALLOCATE PREPARE stmt_drop_idx_tokens_usuario_organizacion;

-- ============================================================
-- 4) Quitar columnas organizacion_id
-- ============================================================

SET @col_usuarios_org := (
  SELECT COUNT(1) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'organizacion_id'
);
SET @sql_drop_col_usuarios_org := IF(
  @col_usuarios_org > 0,
  'ALTER TABLE `usuarios` DROP COLUMN `organizacion_id`',
  'SELECT 1'
);
PREPARE stmt_drop_col_usuarios_org FROM @sql_drop_col_usuarios_org;
EXECUTE stmt_drop_col_usuarios_org;
DEALLOCATE PREPARE stmt_drop_col_usuarios_org;

SET @col_asistencia_org := (
  SELECT COUNT(1) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'asistencia_registro' AND column_name = 'organizacion_id'
);
SET @sql_drop_col_asistencia_org := IF(
  @col_asistencia_org > 0,
  'ALTER TABLE `asistencia_registro` DROP COLUMN `organizacion_id`',
  'SELECT 1'
);
PREPARE stmt_drop_col_asistencia_org FROM @sql_drop_col_asistencia_org;
EXECUTE stmt_drop_col_asistencia_org;
DEALLOCATE PREPARE stmt_drop_col_asistencia_org;

SET @col_presentaciones_org := (
  SELECT COUNT(1) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'presentaciones' AND column_name = 'organizacion_id'
);
SET @sql_drop_col_presentaciones_org := IF(
  @tbl_presentaciones_existe > 0 AND @col_presentaciones_org > 0,
  'ALTER TABLE `presentaciones` DROP COLUMN `organizacion_id`',
  'SELECT 1'
);
PREPARE stmt_drop_col_presentaciones_org FROM @sql_drop_col_presentaciones_org;
EXECUTE stmt_drop_col_presentaciones_org;
DEALLOCATE PREPARE stmt_drop_col_presentaciones_org;

SET @col_tokens_org := (
  SELECT COUNT(1) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'user_tokens' AND column_name = 'organizacion_id'
);
SET @sql_drop_col_tokens_org := IF(
  @col_tokens_org > 0,
  'ALTER TABLE `user_tokens` DROP COLUMN `organizacion_id`',
  'SELECT 1'
);
PREPARE stmt_drop_col_tokens_org FROM @sql_drop_col_tokens_org;
EXECUTE stmt_drop_col_tokens_org;
DEALLOCATE PREPARE stmt_drop_col_tokens_org;
