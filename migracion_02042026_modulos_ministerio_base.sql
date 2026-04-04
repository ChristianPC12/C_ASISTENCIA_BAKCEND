-- ============================================================
-- MIGRACION: Modulos misioneros y juntas - base compartida
-- BD: iglesia_asistencia
-- Fecha: 2026-04-02
-- ============================================================

USE `iglesia_asistencia`;

CREATE TABLE IF NOT EXISTS `organizacion_origenes_misioneros` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `clave` varchar(50) NOT NULL,
  `etiqueta` varchar(120) NOT NULL,
  `modulo_base` varchar(30) NOT NULL DEFAULT 'COMPARTIDO',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_origenes_misioneros` (`organizacion_id`, `clave`),
  KEY `idx_org_origenes_misioneros_activo` (`organizacion_id`, `modulo_base`, `activo`, `orden`),
  CONSTRAINT `fk_org_origenes_misioneros_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `organizacion_origenes_misioneros` (`organizacion_id`, `clave`, `etiqueta`, `modulo_base`, `orden`)
SELECT o.id, src.clave, src.etiqueta, src.modulo_base, src.orden
FROM `organizaciones` o
INNER JOIN (
  SELECT 'CAMPANA' AS clave, 'Campana evangelistica' AS etiqueta, 'COMPARTIDO' AS modulo_base, 1 AS orden
  UNION ALL SELECT 'PC', 'Pequena Congregacion (PC)', 'COMPARTIDO', 2
  UNION ALL SELECT 'VISITA_IGLESIA', 'Visita a la iglesia', 'COMPARTIDO', 3
  UNION ALL SELECT 'REFERENCIA_MIEMBRO', 'Referencia de miembro', 'COMPARTIDO', 4
  UNION ALL SELECT 'PAREJA_MISIONERA', 'Pareja misionera', 'COMPARTIDO', 5
  UNION ALL SELECT 'CLASE_BIBLICA', 'Clase biblica', 'COMPARTIDO', 6
  UNION ALL SELECT 'WHATSAPP', 'WhatsApp', 'COMPARTIDO', 7
  UNION ALL SELECT 'OTRO', 'Otro', 'COMPARTIDO', 8
) src
WHERE NOT EXISTS (
  SELECT 1
  FROM `organizacion_origenes_misioneros` oom
  WHERE oom.organizacion_id = o.id
    AND oom.clave = src.clave
);

CREATE TABLE IF NOT EXISTS `organizacion_decisiones_misioneras` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `clave` varchar(60) NOT NULL,
  `etiqueta` varchar(160) NOT NULL,
  `aplica_a` varchar(30) NOT NULL DEFAULT 'COMPARTIDO',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_decisiones_misioneras` (`organizacion_id`, `clave`, `aplica_a`),
  KEY `idx_org_decisiones_misioneras_activo` (`organizacion_id`, `aplica_a`, `activo`, `orden`),
  CONSTRAINT `fk_org_decisiones_misioneras_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `organizacion_decisiones_misioneras` (`organizacion_id`, `clave`, `etiqueta`, `aplica_a`, `orden`)
SELECT o.id, src.clave, src.etiqueta, src.aplica_a, src.orden
FROM `organizaciones` o
INNER JOIN (
  SELECT 'ACEPTO_ORACION' AS clave, 'Acepto oracion' AS etiqueta, 'COMPARTIDO' AS aplica_a, 1 AS orden
  UNION ALL SELECT 'ACEPTO_ESTUDIAR', 'Acepto continuar estudiando', 'ESTUDIO', 2
  UNION ALL SELECT 'ACEPTO_IGLESIA', 'Acepto asistir a la iglesia', 'COMPARTIDO', 3
  UNION ALL SELECT 'ACEPTO_CLASE_BIBLICA', 'Acepto clase biblica', 'ESTUDIO', 4
  UNION ALL SELECT 'ACEPTO_LLAMADO', 'Acepto llamado', 'COMPARTIDO', 5
  UNION ALL SELECT 'ACEPTO_PREPARACION_BAUTISMAL', 'Acepto preparacion bautismal', 'ESTUDIO', 6
  UNION ALL SELECT 'DECISION_BAUTISMO', 'Decision para bautismo', 'COMPARTIDO', 7
  UNION ALL SELECT 'BAUTIZADO', 'Bautizado', 'COMPARTIDO', 8
  UNION ALL SELECT 'NO_CONTINUA', 'No continuo', 'COMPARTIDO', 9
  UNION ALL SELECT 'REQUIERE_VISITA', 'Requiere nueva visita', 'COMPARTIDO', 10
) src
WHERE NOT EXISTS (
  SELECT 1
  FROM `organizacion_decisiones_misioneras` odm
  WHERE odm.organizacion_id = o.id
    AND odm.clave = src.clave
    AND odm.aplica_a = src.aplica_a
);

CREATE TABLE IF NOT EXISTS `contactos_misioneros` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `nombre_completo` varchar(160) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `telefono_normalizado` varchar(30) NOT NULL DEFAULT '',
  `correo` varchar(160) DEFAULT NULL,
  `direccion` varchar(220) DEFAULT NULL,
  `barrio_comunidad` varchar(120) DEFAULT NULL,
  `clasificacion_principal` varchar(30) NOT NULL DEFAULT 'INTERESADO',
  `es_miembro` tinyint(1) NOT NULL DEFAULT 0,
  `estado_contacto` varchar(25) NOT NULL DEFAULT 'ACTIVO',
  `fecha_primer_contacto` date DEFAULT NULL,
  `fecha_ultimo_contacto` date DEFAULT NULL,
  `origen_principal_clave` varchar(50) DEFAULT NULL,
  `modulo_origen` varchar(30) DEFAULT NULL,
  `referencia_origen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `observaciones_generales` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `eliminado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `eliminado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contactos_misioneros_org_estado` (`organizacion_id`, `estado_contacto`, `eliminado_en`),
  KEY `idx_contactos_misioneros_org_nombre` (`organizacion_id`, `nombre_completo`),
  KEY `idx_contactos_misioneros_org_telefono` (`organizacion_id`, `telefono_normalizado`),
  KEY `idx_contactos_misioneros_org_origen` (`organizacion_id`, `origen_principal_clave`),
  CONSTRAINT `fk_contactos_misioneros_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_contactos_misioneros_clasificacion`
    CHECK (`clasificacion_principal` IN ('MIEMBRO','VISITA','INTERESADO','AMIGO','NINO','JOVEN','ADULTO','LIDER','ANFITRION','INSTRUCTOR_BIBLICO','OTRO')),
  CONSTRAINT `chk_contactos_misioneros_estado`
    CHECK (`estado_contacto` IN ('ACTIVO', 'INACTIVO', 'NO_LOCALIZABLE', 'BAUTIZADO', 'ARCHIVADO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seguimiento_tareas` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `modulo` varchar(30) NOT NULL,
  `entidad_tipo` varchar(40) NOT NULL,
  `entidad_id` bigint(20) UNSIGNED NOT NULL,
  `contacto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `titulo` varchar(180) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `responsable_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_programada` datetime DEFAULT NULL,
  `fecha_limite` datetime DEFAULT NULL,
  `prioridad` varchar(15) NOT NULL DEFAULT 'MEDIA',
  `estado` varchar(20) NOT NULL DEFAULT 'PENDIENTE',
  `resultado` text DEFAULT NULL,
  `completado_en` datetime DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `eliminado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `eliminado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_seguimiento_tareas_org_estado` (`organizacion_id`, `estado`, `fecha_limite`, `eliminado_en`),
  KEY `idx_seguimiento_tareas_org_responsable` (`organizacion_id`, `responsable_usuario_id`, `estado`),
  KEY `idx_seguimiento_tareas_org_modulo` (`organizacion_id`, `modulo`, `entidad_tipo`, `entidad_id`),
  CONSTRAINT `fk_seguimiento_tareas_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seguimiento_tareas_contacto`
    FOREIGN KEY (`contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_seguimiento_tareas_responsable`
    FOREIGN KEY (`responsable_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_seguimiento_tareas_prioridad`
    CHECK (`prioridad` IN ('BAJA', 'MEDIA', 'ALTA', 'URGENTE')),
  CONSTRAINT `chk_seguimiento_tareas_estado`
    CHECK (`estado` IN ('PENDIENTE', 'EN_PROCESO', 'COMPLETADA', 'CANCELADA', 'VENCIDA'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auditoria_eventos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `modulo` varchar(30) NOT NULL,
  `entidad_tipo` varchar(40) NOT NULL,
  `entidad_id` bigint(20) UNSIGNED DEFAULT NULL,
  `accion` varchar(30) NOT NULL,
  `resumen` varchar(220) NOT NULL,
  `antes_json` longtext DEFAULT NULL,
  `despues_json` longtext DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `actor_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `actor_nombre` varchar(160) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_eventos_org_modulo` (`organizacion_id`, `modulo`, `creado_en`),
  KEY `idx_auditoria_eventos_entidad` (`entidad_tipo`, `entidad_id`, `creado_en`),
  CONSTRAINT `fk_auditoria_eventos_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_auditoria_eventos_actor`
    FOREIGN KEY (`actor_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
