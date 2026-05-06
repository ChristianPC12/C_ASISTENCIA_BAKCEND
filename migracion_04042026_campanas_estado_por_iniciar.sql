-- Migracion: 04-04-2026
-- Actualiza el CHECK CONSTRAINT de campanas.estado:
--   Antes: BORRADOR, ACTIVA, FINALIZADA, ARCHIVADA
--   Despues: POR_INICIAR, ACTIVA, FINALIZADA

-- IMPORTANTE: Si existen registros con estado BORRADOR o ARCHIVADA,
-- actualizarlos antes de cambiar el constraint.

UPDATE `campanas` SET `estado` = 'POR_INICIAR' WHERE `estado` = 'BORRADOR';
UPDATE `campanas` SET `estado` = 'FINALIZADA' WHERE `estado` = 'ARCHIVADA';

ALTER TABLE `campanas`
  DROP CONSTRAINT `chk_campanas_estado`;

ALTER TABLE `campanas`
  ADD CONSTRAINT `chk_campanas_estado`
    CHECK (`estado` IN ('POR_INICIAR', 'ACTIVA', 'FINALIZADA'));