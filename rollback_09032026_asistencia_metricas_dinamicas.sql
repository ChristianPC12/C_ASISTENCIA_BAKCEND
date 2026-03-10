-- ============================================================
-- ROLLBACK: Modelo dinamico de metricas por registro (B5-T01)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-09
-- ============================================================

USE `iglesia_asistencia`;

ALTER TABLE `asistencia_registro`
  DROP COLUMN IF EXISTS `metricas_json`;
