-- ============================================================
-- MIGRACION: Actualizar vistas - BD iglesia_asistencia
-- Ejecutar en HeidiSQL sobre la BD iglesia_asistencia
-- Fecha: 02-03-2026
-- Nota: columnas y CHECK constraints ya existen.
--       Solo se recrean las 3 vistas con las nuevas columnas.
-- ============================================================

USE `iglesia_asistencia`;
CREATE OR REPLACE VIEW `asistencia_sabado` AS
SELECT `id`, `culto_id`, `fecha`, `anio`, `trimestre`,
       `llegaron_antes_hora`, `llegaron_despues_hora`,
       `ninos`, `jovenes`, `total_asistentes`,
       `proc_barrio`, `proc_guayabo`,
       `visitas_barrio`, `nombres_visitas_barrio`,
       `visitas_guayabo`, `nombres_visitas_guayabo`,
       `retiros_antes_terminar`, `se_quedaron_todo`,
       `observaciones`, `registrado_por`, `creado_en`, `actualizado_en`
FROM `asistencia_registro`
WHERE `culto_id` = 1;

CREATE OR REPLACE VIEW `asistencia_domingo` AS
SELECT `id`, `culto_id`, `fecha`, `anio`, `trimestre`,
       `llegaron_antes_hora`, `llegaron_despues_hora`,
       `ninos`, `jovenes`, `total_asistentes`,
       `proc_barrio`, `proc_guayabo`,
       `visitas_barrio`, `nombres_visitas_barrio`,
       `visitas_guayabo`, `nombres_visitas_guayabo`,
       `retiros_antes_terminar`, `se_quedaron_todo`,
       `observaciones`, `registrado_por`, `creado_en`, `actualizado_en`
FROM `asistencia_registro`
WHERE `culto_id` = 2;

CREATE OR REPLACE VIEW `asistencia_miercoles` AS
SELECT `id`, `culto_id`, `fecha`, `anio`, `trimestre`,
       `llegaron_antes_hora`, `llegaron_despues_hora`,
       `ninos`, `jovenes`, `total_asistentes`,
       `proc_barrio`, `proc_guayabo`,
       `visitas_barrio`, `nombres_visitas_barrio`,
       `visitas_guayabo`, `nombres_visitas_guayabo`,
       `retiros_antes_terminar`, `se_quedaron_todo`,
       `observaciones`, `registrado_por`, `creado_en`, `actualizado_en`
FROM `asistencia_registro`
WHERE `culto_id` = 3;
