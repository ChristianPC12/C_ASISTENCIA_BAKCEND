-- ============================================================
-- MIGRACION: Relaciones tenant-aware sobre tablas operativas (B1-T02)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-09
-- ============================================================

USE `iglesia_asistencia`;

-- ============================================================
-- 1) Columnas organizacion_id (nullable en esta fase)
-- ============================================================

ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `organizacion_id` bigint(20) UNSIGNED NULL AFTER `rol_id`;

ALTER TABLE `asistencia_registro`
  ADD COLUMN IF NOT EXISTS `organizacion_id` bigint(20) UNSIGNED NULL AFTER `id`;

SET @tbl_presentaciones_existe := (
  SELECT COUNT(1)
  FROM information_schema.tables
  WHERE table_schema = DATABASE()
    AND table_name = 'presentaciones'
);

SET @sql_add_col_presentaciones_org := IF(
  @tbl_presentaciones_existe > 0,
  'ALTER TABLE `presentaciones` ADD COLUMN IF NOT EXISTS `organizacion_id` bigint(20) UNSIGNED NULL AFTER `id`',
  'SELECT 1'
);
PREPARE stmt_add_col_presentaciones_org FROM @sql_add_col_presentaciones_org;
EXECUTE stmt_add_col_presentaciones_org;
DEALLOCATE PREPARE stmt_add_col_presentaciones_org;

ALTER TABLE `user_tokens`
  ADD COLUMN IF NOT EXISTS `organizacion_id` bigint(20) UNSIGNED NULL AFTER `usuario_id`;

-- ============================================================
-- 2) Backfill suave desde usuario (si ya hay organizacion en usuarios)
-- ============================================================

UPDATE `asistencia_registro` ar
INNER JOIN `usuarios` u ON u.id = ar.registrado_por
SET ar.organizacion_id = u.organizacion_id
WHERE ar.organizacion_id IS NULL
  AND u.organizacion_id IS NOT NULL;

SET @sql_backfill_presentaciones := IF(
  @tbl_presentaciones_existe > 0,
  'UPDATE `presentaciones` p INNER JOIN `usuarios` u ON u.id = p.usuario_id
   SET p.organizacion_id = u.organizacion_id
   WHERE p.organizacion_id IS NULL
     AND u.organizacion_id IS NOT NULL',
  'SELECT 1'
);
PREPARE stmt_backfill_presentaciones FROM @sql_backfill_presentaciones;
EXECUTE stmt_backfill_presentaciones;
DEALLOCATE PREPARE stmt_backfill_presentaciones;

UPDATE `user_tokens` ut
INNER JOIN `usuarios` u ON u.id = ut.usuario_id
SET ut.organizacion_id = u.organizacion_id
WHERE ut.organizacion_id IS NULL
  AND u.organizacion_id IS NOT NULL;

-- ============================================================
-- 3) Indices tenant-aware
-- ============================================================

-- usuarios
SET @idx_usuarios_org := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND index_name = 'idx_usuarios_organizacion'
);
SET @sql_add_idx_usuarios_org := IF(
  @idx_usuarios_org = 0,
  'ALTER TABLE `usuarios` ADD KEY `idx_usuarios_organizacion` (`organizacion_id`)',
  'SELECT 1'
);
PREPARE stmt_add_idx_usuarios_org FROM @sql_add_idx_usuarios_org;
EXECUTE stmt_add_idx_usuarios_org;
DEALLOCATE PREPARE stmt_add_idx_usuarios_org;

SET @idx_usuarios_org_rol_activo := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND index_name = 'idx_usuarios_org_rol_activo'
);
SET @sql_add_idx_usuarios_org_rol_activo := IF(
  @idx_usuarios_org_rol_activo = 0,
  'ALTER TABLE `usuarios` ADD KEY `idx_usuarios_org_rol_activo` (`organizacion_id`, `rol_id`, `activo`)',
  'SELECT 1'
);
PREPARE stmt_add_idx_usuarios_org_rol_activo FROM @sql_add_idx_usuarios_org_rol_activo;
EXECUTE stmt_add_idx_usuarios_org_rol_activo;
DEALLOCATE PREPARE stmt_add_idx_usuarios_org_rol_activo;

-- asistencia_registro
SET @idx_asistencia_org := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'asistencia_registro'
    AND index_name = 'idx_asistencia_organizacion'
);
SET @sql_add_idx_asistencia_org := IF(
  @idx_asistencia_org = 0,
  'ALTER TABLE `asistencia_registro` ADD KEY `idx_asistencia_organizacion` (`organizacion_id`)',
  'SELECT 1'
);
PREPARE stmt_add_idx_asistencia_org FROM @sql_add_idx_asistencia_org;
EXECUTE stmt_add_idx_asistencia_org;
DEALLOCATE PREPARE stmt_add_idx_asistencia_org;

SET @idx_asistencia_org_fecha := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'asistencia_registro'
    AND index_name = 'idx_asistencia_org_fecha'
);
SET @sql_add_idx_asistencia_org_fecha := IF(
  @idx_asistencia_org_fecha = 0,
  'ALTER TABLE `asistencia_registro` ADD KEY `idx_asistencia_org_fecha` (`organizacion_id`, `fecha`)',
  'SELECT 1'
);
PREPARE stmt_add_idx_asistencia_org_fecha FROM @sql_add_idx_asistencia_org_fecha;
EXECUTE stmt_add_idx_asistencia_org_fecha;
DEALLOCATE PREPARE stmt_add_idx_asistencia_org_fecha;

SET @idx_asistencia_org_anio_trim_culto := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'asistencia_registro'
    AND index_name = 'idx_asistencia_org_anio_trim_culto'
);
SET @sql_add_idx_asistencia_org_anio_trim_culto := IF(
  @idx_asistencia_org_anio_trim_culto = 0,
  'ALTER TABLE `asistencia_registro` ADD KEY `idx_asistencia_org_anio_trim_culto` (`organizacion_id`, `anio`, `trimestre`, `culto_id`)',
  'SELECT 1'
);
PREPARE stmt_add_idx_asistencia_org_anio_trim_culto FROM @sql_add_idx_asistencia_org_anio_trim_culto;
EXECUTE stmt_add_idx_asistencia_org_anio_trim_culto;
DEALLOCATE PREPARE stmt_add_idx_asistencia_org_anio_trim_culto;

-- Mantener un indice dedicado para FK `fk_asistencia_culto` antes de quitar la unique vieja.
SET @idx_asistencia_culto_fk := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'asistencia_registro'
    AND index_name = 'idx_asistencia_culto_fk'
);
SET @sql_add_idx_asistencia_culto_fk := IF(
  @idx_asistencia_culto_fk = 0,
  'ALTER TABLE `asistencia_registro` ADD KEY `idx_asistencia_culto_fk` (`culto_id`)',
  'SELECT 1'
);
PREPARE stmt_add_idx_asistencia_culto_fk FROM @sql_add_idx_asistencia_culto_fk;
EXECUTE stmt_add_idx_asistencia_culto_fk;
DEALLOCATE PREPARE stmt_add_idx_asistencia_culto_fk;

SET @idx_uq_asistencia_culto_fecha := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'asistencia_registro'
    AND index_name = 'uq_asistencia_culto_fecha'
);
SET @sql_drop_uq_asistencia_culto_fecha := IF(
  @idx_uq_asistencia_culto_fecha > 0,
  'ALTER TABLE `asistencia_registro` DROP INDEX `uq_asistencia_culto_fecha`',
  'SELECT 1'
);
PREPARE stmt_drop_uq_asistencia_culto_fecha FROM @sql_drop_uq_asistencia_culto_fecha;
EXECUTE stmt_drop_uq_asistencia_culto_fecha;
DEALLOCATE PREPARE stmt_drop_uq_asistencia_culto_fecha;

SET @idx_uq_asistencia_org_culto_fecha := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'asistencia_registro'
    AND index_name = 'uq_asistencia_org_culto_fecha'
);
SET @sql_add_uq_asistencia_org_culto_fecha := IF(
  @idx_uq_asistencia_org_culto_fecha = 0,
  'ALTER TABLE `asistencia_registro` ADD UNIQUE KEY `uq_asistencia_org_culto_fecha` (`organizacion_id`, `culto_id`, `fecha`)',
  'SELECT 1'
);
PREPARE stmt_add_uq_asistencia_org_culto_fecha FROM @sql_add_uq_asistencia_org_culto_fecha;
EXECUTE stmt_add_uq_asistencia_org_culto_fecha;
DEALLOCATE PREPARE stmt_add_uq_asistencia_org_culto_fecha;

-- presentaciones
SET @idx_presentaciones_org := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'presentaciones'
    AND index_name = 'idx_presentaciones_organizacion'
);
SET @sql_add_idx_presentaciones_org := IF(
  @tbl_presentaciones_existe > 0 AND @idx_presentaciones_org = 0,
  'ALTER TABLE `presentaciones` ADD KEY `idx_presentaciones_organizacion` (`organizacion_id`)',
  'SELECT 1'
);
PREPARE stmt_add_idx_presentaciones_org FROM @sql_add_idx_presentaciones_org;
EXECUTE stmt_add_idx_presentaciones_org;
DEALLOCATE PREPARE stmt_add_idx_presentaciones_org;

SET @idx_presentaciones_org_periodo := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'presentaciones'
    AND index_name = 'idx_presentaciones_org_periodo'
);
SET @sql_add_idx_presentaciones_org_periodo := IF(
  @tbl_presentaciones_existe > 0 AND @idx_presentaciones_org_periodo = 0,
  'ALTER TABLE `presentaciones` ADD KEY `idx_presentaciones_org_periodo` (`organizacion_id`, `anio`, `mes`, `culto_codigo`)',
  'SELECT 1'
);
PREPARE stmt_add_idx_presentaciones_org_periodo FROM @sql_add_idx_presentaciones_org_periodo;
EXECUTE stmt_add_idx_presentaciones_org_periodo;
DEALLOCATE PREPARE stmt_add_idx_presentaciones_org_periodo;

SET @idx_presentaciones_org_usuario_creado := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'presentaciones'
    AND index_name = 'idx_presentaciones_org_usuario_creado'
);
SET @sql_add_idx_presentaciones_org_usuario_creado := IF(
  @tbl_presentaciones_existe > 0 AND @idx_presentaciones_org_usuario_creado = 0,
  'ALTER TABLE `presentaciones` ADD KEY `idx_presentaciones_org_usuario_creado` (`organizacion_id`, `usuario_id`, `creado_en`)',
  'SELECT 1'
);
PREPARE stmt_add_idx_presentaciones_org_usuario_creado FROM @sql_add_idx_presentaciones_org_usuario_creado;
EXECUTE stmt_add_idx_presentaciones_org_usuario_creado;
DEALLOCATE PREPARE stmt_add_idx_presentaciones_org_usuario_creado;

-- user_tokens
SET @idx_tokens_org := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'user_tokens'
    AND index_name = 'idx_tokens_organizacion'
);
SET @sql_add_idx_tokens_org := IF(
  @idx_tokens_org = 0,
  'ALTER TABLE `user_tokens` ADD KEY `idx_tokens_organizacion` (`organizacion_id`)',
  'SELECT 1'
);
PREPARE stmt_add_idx_tokens_org FROM @sql_add_idx_tokens_org;
EXECUTE stmt_add_idx_tokens_org;
DEALLOCATE PREPARE stmt_add_idx_tokens_org;

SET @idx_tokens_usuario_org := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'user_tokens'
    AND index_name = 'idx_tokens_usuario_organizacion'
);
SET @sql_add_idx_tokens_usuario_org := IF(
  @idx_tokens_usuario_org = 0,
  'ALTER TABLE `user_tokens` ADD KEY `idx_tokens_usuario_organizacion` (`usuario_id`, `organizacion_id`)',
  'SELECT 1'
);
PREPARE stmt_add_idx_tokens_usuario_org FROM @sql_add_idx_tokens_usuario_org;
EXECUTE stmt_add_idx_tokens_usuario_org;
DEALLOCATE PREPARE stmt_add_idx_tokens_usuario_org;

-- Normalizar referencias invalidas antes de crear FK (si existieran cargas parciales previas)
UPDATE `usuarios` u
LEFT JOIN `organizaciones` o ON o.id = u.organizacion_id
SET u.organizacion_id = NULL
WHERE u.organizacion_id IS NOT NULL
  AND o.id IS NULL;

UPDATE `asistencia_registro` ar
LEFT JOIN `organizaciones` o ON o.id = ar.organizacion_id
SET ar.organizacion_id = NULL
WHERE ar.organizacion_id IS NOT NULL
  AND o.id IS NULL;

SET @sql_normaliza_presentaciones_org := IF(
  @tbl_presentaciones_existe > 0,
  'UPDATE `presentaciones` p
   LEFT JOIN `organizaciones` o ON o.id = p.organizacion_id
   SET p.organizacion_id = NULL
   WHERE p.organizacion_id IS NOT NULL
     AND o.id IS NULL',
  'SELECT 1'
);
PREPARE stmt_normaliza_presentaciones_org FROM @sql_normaliza_presentaciones_org;
EXECUTE stmt_normaliza_presentaciones_org;
DEALLOCATE PREPARE stmt_normaliza_presentaciones_org;

UPDATE `user_tokens` ut
LEFT JOIN `organizaciones` o ON o.id = ut.organizacion_id
SET ut.organizacion_id = NULL
WHERE ut.organizacion_id IS NOT NULL
  AND o.id IS NULL;

-- ============================================================
-- 4) Llaves foraneas hacia organizaciones
-- ============================================================

SET @fk_usuarios_org := (
  SELECT COUNT(1)
  FROM information_schema.table_constraints
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND constraint_name = 'fk_usuarios_organizacion'
    AND constraint_type = 'FOREIGN KEY'
);
SET @sql_add_fk_usuarios_org := IF(
  @fk_usuarios_org = 0,
  'ALTER TABLE `usuarios` ADD CONSTRAINT `fk_usuarios_organizacion`
   FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`)',
  'SELECT 1'
);
PREPARE stmt_add_fk_usuarios_org FROM @sql_add_fk_usuarios_org;
EXECUTE stmt_add_fk_usuarios_org;
DEALLOCATE PREPARE stmt_add_fk_usuarios_org;

SET @fk_asistencia_org := (
  SELECT COUNT(1)
  FROM information_schema.table_constraints
  WHERE table_schema = DATABASE()
    AND table_name = 'asistencia_registro'
    AND constraint_name = 'fk_asistencia_organizacion'
    AND constraint_type = 'FOREIGN KEY'
);
SET @sql_add_fk_asistencia_org := IF(
  @fk_asistencia_org = 0,
  'ALTER TABLE `asistencia_registro` ADD CONSTRAINT `fk_asistencia_organizacion`
   FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`)',
  'SELECT 1'
);
PREPARE stmt_add_fk_asistencia_org FROM @sql_add_fk_asistencia_org;
EXECUTE stmt_add_fk_asistencia_org;
DEALLOCATE PREPARE stmt_add_fk_asistencia_org;

SET @fk_presentaciones_org := (
  SELECT COUNT(1)
  FROM information_schema.table_constraints
  WHERE table_schema = DATABASE()
    AND table_name = 'presentaciones'
    AND constraint_name = 'fk_presentaciones_organizacion'
    AND constraint_type = 'FOREIGN KEY'
);
SET @sql_add_fk_presentaciones_org := IF(
  @tbl_presentaciones_existe > 0 AND @fk_presentaciones_org = 0,
  'ALTER TABLE `presentaciones` ADD CONSTRAINT `fk_presentaciones_organizacion`
   FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`)',
  'SELECT 1'
);
PREPARE stmt_add_fk_presentaciones_org FROM @sql_add_fk_presentaciones_org;
EXECUTE stmt_add_fk_presentaciones_org;
DEALLOCATE PREPARE stmt_add_fk_presentaciones_org;

SET @fk_tokens_org := (
  SELECT COUNT(1)
  FROM information_schema.table_constraints
  WHERE table_schema = DATABASE()
    AND table_name = 'user_tokens'
    AND constraint_name = 'fk_tokens_organizacion'
    AND constraint_type = 'FOREIGN KEY'
);
SET @sql_add_fk_tokens_org := IF(
  @fk_tokens_org = 0,
  'ALTER TABLE `user_tokens` ADD CONSTRAINT `fk_tokens_organizacion`
   FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`)',
  'SELECT 1'
);
PREPARE stmt_add_fk_tokens_org FROM @sql_add_fk_tokens_org;
EXECUTE stmt_add_fk_tokens_org;
DEALLOCATE PREPARE stmt_add_fk_tokens_org;
