-- ============================================================
-- MIGRACION: Modelo dinamico de metricas por registro (B5-T01)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-09
-- ============================================================

USE `iglesia_asistencia`;

ALTER TABLE `asistencia_registro`
  ADD COLUMN IF NOT EXISTS `metricas_json` LONGTEXT NULL AFTER `observaciones`;
