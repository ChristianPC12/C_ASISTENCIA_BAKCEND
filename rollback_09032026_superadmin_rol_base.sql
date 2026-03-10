-- ============================================================
-- ROLLBACK: Rol SUPERADMIN base (B3-T01)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-09
-- ============================================================

USE `iglesia_asistencia`;

SET @superadmin_role_id := (
  SELECT id
  FROM `roles`
  WHERE `nombre` = 'SUPERADMIN'
  LIMIT 1
);

DELETE FROM `roles`
WHERE id = @superadmin_role_id
  AND @superadmin_role_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `usuarios`
    WHERE `rol_id` = @superadmin_role_id
  );
