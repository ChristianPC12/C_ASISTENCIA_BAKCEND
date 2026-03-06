-- ============================================================
-- MIGRACION: Rate limit de login por usuario + IP
-- BD: iglesia_asistencia
-- Fecha: 06-03-2026
-- ============================================================

USE `iglesia_asistencia`;

CREATE TABLE IF NOT EXISTS `login_intentos` (
  `usuario` varchar(50) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `intentos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `primer_intento_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_intento_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `bloqueado_hasta` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`usuario`, `ip`),
  KEY `idx_login_intentos_bloqueado` (`bloqueado_hasta`),
  KEY `idx_login_intentos_ultimo` (`ultimo_intento_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limpieza inicial de filas antiguas
DELETE FROM `login_intentos`
WHERE (bloqueado_hasta IS NOT NULL
       AND bloqueado_hasta <= DATE_SUB(NOW(), INTERVAL 1 DAY))
   OR (bloqueado_hasta IS NULL
       AND ultimo_intento_en <= DATE_SUB(NOW(), INTERVAL 15 MINUTE));
