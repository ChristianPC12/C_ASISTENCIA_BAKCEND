-- ============================================================
-- ROLLBACK: Modulos misioneros y juntas - base compartida
-- BD: iglesia_asistencia
-- Fecha: 2026-04-02
-- ============================================================

USE `iglesia_asistencia`;

DROP TABLE IF EXISTS `auditoria_eventos`;
DROP TABLE IF EXISTS `seguimiento_tareas`;
DROP TABLE IF EXISTS `contactos_misioneros`;
DROP TABLE IF EXISTS `organizacion_decisiones_misioneras`;
DROP TABLE IF EXISTS `organizacion_origenes_misioneros`;
