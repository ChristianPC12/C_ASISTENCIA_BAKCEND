-- ============================================================
-- MIGRACION: Rol SUPERADMIN base (B3-T01)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-09
-- ============================================================

USE `iglesia_asistencia`;

INSERT INTO `roles` (`nombre`)
SELECT 'SUPERADMIN'
WHERE NOT EXISTS (
  SELECT 1
  FROM `roles`
  WHERE `nombre` = 'SUPERADMIN'
);
