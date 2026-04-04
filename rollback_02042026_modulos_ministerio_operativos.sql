-- ============================================================
-- ROLLBACK: Modulos misioneros y juntas - tablas operativas
-- BD: iglesia_asistencia
-- Fecha: 2026-04-02
-- ============================================================

USE `iglesia_asistencia`;

DROP TABLE IF EXISTS `junta_adjuntos`;
DROP TABLE IF EXISTS `junta_votaciones`;
DROP TABLE IF EXISTS `junta_puntos_agenda`;
DROP TABLE IF EXISTS `juntas_iglesia`;
DROP TABLE IF EXISTS `campana_decisiones`;
DROP TABLE IF EXISTS `pc_resultados`;
DROP TABLE IF EXISTS `estudio_decisiones`;
DROP TABLE IF EXISTS `estudio_sesiones`;
DROP TABLE IF EXISTS `estudio_asignaciones`;
DROP TABLE IF EXISTS `estudios_biblicos`;
DROP TABLE IF EXISTS `pc_reunion_participantes`;
DROP TABLE IF EXISTS `pc_reuniones`;
DROP TABLE IF EXISTS `pc_participantes`;
DROP TABLE IF EXISTS `pc_lideres_historial`;
DROP TABLE IF EXISTS `pc_grupos`;
DROP TABLE IF EXISTS `campana_asistencia_sesiones`;
DROP TABLE IF EXISTS `campana_asistentes`;
DROP TABLE IF EXISTS `campana_sesiones`;
DROP TABLE IF EXISTS `campanas`;
