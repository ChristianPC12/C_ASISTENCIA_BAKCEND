-- ============================================================
-- MIGRACION: Seguridad de usuarios y sesiones
-- BD: iglesia_asistencia
-- Fecha: 05-03-2026
-- ============================================================

USE `iglesia_asistencia`;

-- 1) PASSWORD: fecha de ultima actualizacion (para expiracion a 30 dias)
ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `password_actualizada_en` timestamp NOT NULL DEFAULT current_timestamp() AFTER `password_hash`;

UPDATE `usuarios`
SET `password_actualizada_en` = COALESCE(`actualizado_en`, `creado_en`, NOW())
WHERE `password_actualizada_en` IS NULL;

-- 2) TOKENS: control de inactividad y duracion maxima
ALTER TABLE `user_tokens`
  ADD COLUMN IF NOT EXISTS `ultimo_uso_en` timestamp NOT NULL DEFAULT current_timestamp() AFTER `token_hash`,
  ADD COLUMN IF NOT EXISTS `expira_en` timestamp NULL DEFAULT NULL AFTER `ultimo_uso_en`;

UPDATE `user_tokens`
SET `ultimo_uso_en` = COALESCE(`creado_en`, NOW()),
    `expira_en` = DATE_ADD(COALESCE(`creado_en`, NOW()), INTERVAL 8 HOUR);

ALTER TABLE `user_tokens`
  MODIFY COLUMN `expira_en` timestamp NOT NULL DEFAULT current_timestamp();

ALTER TABLE `user_tokens`
  ADD KEY `idx_token_expira` (`expira_en`),
  ADD KEY `idx_token_ultimo_uso` (`ultimo_uso_en`);

-- 3) Limpieza inicial de sesiones no validas
DELETE ut
FROM `user_tokens` ut
INNER JOIN `usuarios` u ON u.id = ut.usuario_id
WHERE u.activo = 0
   OR DATE_ADD(u.password_actualizada_en, INTERVAL 30 DAY) <= NOW();
