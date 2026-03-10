-- ============================================================
-- SANITY CHECKS: Arranque limpio multitenant (B1-T03)
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

SELECT @org_base AS organizacion_base_id;

SELECT
  'ORG_BASE_EXISTE' AS check_name,
  CASE WHEN @org_base IS NOT NULL THEN 'OK' ELSE 'ERROR' END AS estado;

SELECT
  'ORG_BASE_ACTIVA' AS check_name,
  CASE
    WHEN EXISTS (
      SELECT 1
      FROM `organizaciones`
      WHERE `id` = @org_base
        AND `activa` = 1
    ) THEN 'OK'
    ELSE 'ERROR'
  END AS estado;

SELECT
  'SETUP_ESTADO_BASE' AS check_name,
  CASE
    WHEN EXISTS (
      SELECT 1
      FROM `organizacion_config_estado`
      WHERE `organizacion_id` = @org_base
        AND `estado_setup` IN ('PENDIENTE', 'COMPLETO')
    ) THEN 'OK'
    ELSE 'ERROR'
  END AS estado;

SELECT
  'CULTOS_BASE_COUNT' AS check_name,
  COUNT(*) AS valor,
  CASE WHEN COUNT(*) >= 3 THEN 'OK' ELSE 'ERROR' END AS estado
FROM `organizacion_cultos`
WHERE `organizacion_id` = @org_base
  AND `activo` = 1;

SELECT
  'PROCEDENCIAS_BASE_COUNT' AS check_name,
  COUNT(*) AS valor,
  CASE WHEN COUNT(*) BETWEEN 1 AND 10 THEN 'OK' ELSE 'ERROR' END AS estado
FROM `organizacion_procedencias`
WHERE `organizacion_id` = @org_base
  AND `activo` = 1;

SELECT
  'METRICAS_BASE_COUNT' AS check_name,
  COUNT(*) AS valor,
  CASE WHEN COUNT(*) >= 10 THEN 'OK' ELSE 'ERROR' END AS estado
FROM `organizacion_metricas_config`
WHERE `organizacion_id` = @org_base
  AND `habilitado` = 1;

SELECT
  'CUPOS_BASE_COUNT' AS check_name,
  COUNT(*) AS valor,
  CASE WHEN COUNT(*) >= 3 THEN 'OK' ELSE 'ERROR' END AS estado
FROM `organizacion_roles_cupos`
WHERE `organizacion_id` = @org_base
  AND `activo` = 1;

SELECT
  'ADMIN_BASE_VINCULADO' AS check_name,
  CASE
    WHEN EXISTS (
      SELECT 1
      FROM `usuarios`
      WHERE `usuario` = 'admin'
        AND `organizacion_id` = @org_base
        AND `activo` = 1
    ) THEN 'OK'
    ELSE 'ERROR'
  END AS estado;
