-- ============================================================
-- ROLLBACK: Estructura base multitenant (B1-T01)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-09
-- ============================================================

USE `iglesia_asistencia`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `organizacion_roles_cupos`;
DROP TABLE IF EXISTS `organizacion_metricas_config`;
DROP TABLE IF EXISTS `organizacion_procedencias`;
DROP TABLE IF EXISTS `organizacion_cultos`;
DROP TABLE IF EXISTS `organizacion_config_estado`;
DROP TABLE IF EXISTS `organizaciones`;
DROP TABLE IF EXISTS `campos`;

SET FOREIGN_KEY_CHECKS = 1;
