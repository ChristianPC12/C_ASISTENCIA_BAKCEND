-- MIGRACION: Estudios biblicos con multiples visitas e instructores responsables
-- Fecha: 2026-05-05

CREATE TABLE IF NOT EXISTS `estudio_biblico_visitas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) unsigned NOT NULL,
  `estudio_id` bigint(20) unsigned NOT NULL,
  `visita_asistente_id` bigint(20) unsigned NOT NULL,
  `contacto_id` bigint(20) unsigned DEFAULT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT 0,
  `creado_por` bigint(20) unsigned DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_estudio_biblico_visita` (`estudio_id`, `visita_asistente_id`),
  KEY `idx_eb_visitas_org_estudio` (`organizacion_id`, `estudio_id`),
  KEY `idx_eb_visitas_org_visita` (`organizacion_id`, `visita_asistente_id`),
  KEY `idx_eb_visitas_org_contacto` (`organizacion_id`, `contacto_id`),
  CONSTRAINT `fk_eb_visitas_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eb_visitas_estudio`
    FOREIGN KEY (`estudio_id`) REFERENCES `estudios_biblicos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eb_visitas_visita`
    FOREIGN KEY (`visita_asistente_id`) REFERENCES `campana_asistentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eb_visitas_contacto`
    FOREIGN KEY (`contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estudio_biblico_responsables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) unsigned NOT NULL,
  `estudio_id` bigint(20) unsigned NOT NULL,
  `responsable_usuario_id` bigint(20) unsigned NOT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT 0,
  `vigente` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por` bigint(20) unsigned DEFAULT NULL,
  `actualizado_por` bigint(20) unsigned DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_estudio_biblico_responsable` (`estudio_id`, `responsable_usuario_id`),
  KEY `idx_eb_resp_org_estudio` (`organizacion_id`, `estudio_id`, `vigente`),
  KEY `idx_eb_resp_org_usuario` (`organizacion_id`, `responsable_usuario_id`, `vigente`),
  CONSTRAINT `fk_eb_resp_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eb_resp_estudio`
    FOREIGN KEY (`estudio_id`) REFERENCES `estudios_biblicos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eb_resp_usuario`
    FOREIGN KEY (`responsable_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `estudio_biblico_visitas` (
  `organizacion_id`, `estudio_id`, `visita_asistente_id`, `contacto_id`, `principal`, `creado_por`, `creado_en`
)
SELECT
  e.`organizacion_id`, e.`id`, e.`visita_asistente_id`, e.`contacto_id`, 1, e.`creado_por`, e.`creado_en`
FROM `estudios_biblicos` e
WHERE e.`visita_asistente_id` IS NOT NULL;

INSERT IGNORE INTO `estudio_biblico_responsables` (
  `organizacion_id`, `estudio_id`, `responsable_usuario_id`, `principal`, `vigente`, `creado_por`, `actualizado_por`, `creado_en`, `actualizado_en`
)
SELECT
  e.`organizacion_id`, e.`id`, e.`responsable_usuario_id`, 1, 1, e.`creado_por`, e.`actualizado_por`, e.`creado_en`, e.`actualizado_en`
FROM `estudios_biblicos` e
WHERE e.`responsable_usuario_id` IS NOT NULL;
