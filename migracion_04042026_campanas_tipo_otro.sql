-- Migracion: 04-04-2026
-- Alinea el CHECK de campanas.tipo con los tipos permitidos por el servicio y el formulario.

ALTER TABLE `campanas`
  DROP CONSTRAINT `chk_campanas_tipo`;

ALTER TABLE `campanas`
  ADD CONSTRAINT `chk_campanas_tipo`
    CHECK (`tipo` IN ('SEMANA_EVANGELISTICA', 'CAMPANA_2_SEMANAS', 'CAMPANA_ESPECIAL', 'SERIE_CORTA', 'OTRO'));
