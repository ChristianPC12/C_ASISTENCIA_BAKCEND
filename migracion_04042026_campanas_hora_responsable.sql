-- Migracion: 04-04-2026
-- Agrega campos hora y responsable a la tabla campanas

-- DESCRIPCION:
--   - `hora` (TIME): La hora a la que comienza la campaña
--   - `responsable` (VARCHAR): Nombre de texto libre del responsable de la campaña
--   El campo responsable_usuario_id ya existe como FK, pero se agregan estos campos
--   para permitir texto libre en lugar de solo IDs de usuario.

ALTER TABLE `campanas`
  ADD COLUMN `hora` time DEFAULT NULL AFTER `lugar`,
  ADD COLUMN `responsable` varchar(60) DEFAULT NULL AFTER `predicador`;

-- Nota: Se permite NULL para ambos campos para compatibilidad con campanas existentes.
-- El frontend valida que sean obligatorios, así que en la práctica siempre tendrán valor.