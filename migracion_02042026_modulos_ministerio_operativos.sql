-- ============================================================
-- MIGRACION: Modulos misioneros y juntas - tablas operativas
-- BD: iglesia_asistencia
-- Fecha: 2026-04-02
-- ============================================================

USE `iglesia_asistencia`;

CREATE TABLE IF NOT EXISTS `campanas` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(160) NOT NULL,
  `lema` varchar(180) DEFAULT NULL,
  `tipo` varchar(30) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `lugar` varchar(180) DEFAULT NULL,
  `predicador` varchar(160) DEFAULT NULL,
  `responsable_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'BORRADOR',
  `observaciones` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `eliminado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `eliminado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_campanas_org_estado` (`organizacion_id`, `estado`, `fecha_inicio`, `eliminado_en`),
  KEY `idx_campanas_org_responsable` (`organizacion_id`, `responsable_usuario_id`, `estado`),
  CONSTRAINT `fk_campanas_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campanas_responsable`
    FOREIGN KEY (`responsable_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_campanas_tipo`
    CHECK (`tipo` IN ('SEMANA_EVANGELISTICA', 'CAMPANA_2_SEMANAS', 'CAMPANA_ESPECIAL', 'SERIE_CORTA')),
  CONSTRAINT `chk_campanas_estado`
    CHECK (`estado` IN ('BORRADOR', 'ACTIVA', 'FINALIZADA', 'ARCHIVADA'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campana_sesiones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `campana_id` bigint(20) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `tema_titulo` varchar(180) NOT NULL,
  `predicador_noche` varchar(160) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado_sesion` varchar(20) NOT NULL DEFAULT 'PROGRAMADA',
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_campana_sesion_fecha` (`campana_id`, `fecha`),
  KEY `idx_campana_sesiones_org_fecha` (`organizacion_id`, `fecha`, `estado_sesion`),
  CONSTRAINT `fk_campana_sesiones_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campana_sesiones_campana`
    FOREIGN KEY (`campana_id`) REFERENCES `campanas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_campana_sesiones_estado`
    CHECK (`estado_sesion` IN ('PROGRAMADA', 'REALIZADA', 'CANCELADA'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campana_asistentes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `campana_id` bigint(20) UNSIGNED NOT NULL,
  `contacto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre_snapshot` varchar(160) NOT NULL,
  `telefono_snapshot` varchar(30) DEFAULT NULL,
  `procedencia` varchar(120) DEFAULT NULL,
  `tipo_asistente` varchar(20) NOT NULL DEFAULT 'VISITA',
  `clasificacion_etaria` varchar(20) DEFAULT NULL,
  `invitado_por_contacto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `primera_vez` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` text DEFAULT NULL,
  `estado_seguimiento` varchar(25) NOT NULL DEFAULT 'PENDIENTE',
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `eliminado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `eliminado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_campana_asistentes_org_estado` (`organizacion_id`, `estado_seguimiento`, `eliminado_en`),
  KEY `idx_campana_asistentes_org_tipo` (`organizacion_id`, `campana_id`, `tipo_asistente`),
  KEY `idx_campana_asistentes_org_contacto` (`organizacion_id`, `contacto_id`),
  CONSTRAINT `fk_campana_asistentes_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campana_asistentes_campana`
    FOREIGN KEY (`campana_id`) REFERENCES `campanas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campana_asistentes_contacto`
    FOREIGN KEY (`contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_campana_asistentes_invitado_por`
    FOREIGN KEY (`invitado_por_contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_campana_asistentes_tipo`
    CHECK (`tipo_asistente` IN ('MIEMBRO', 'VISITA', 'INTERESADO')),
  CONSTRAINT `chk_campana_asistentes_estado`
    CHECK (`estado_seguimiento` IN ('PENDIENTE', 'CONTACTADO', 'ESTUDIO_BIBLICO', 'NO_LOCALIZABLE', 'CERRADO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campana_asistencia_sesiones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `campana_id` bigint(20) UNSIGNED NOT NULL,
  `campana_sesion_id` bigint(20) UNSIGNED NOT NULL,
  `campana_asistente_id` bigint(20) UNSIGNED NOT NULL,
  `asistio` tinyint(1) NOT NULL DEFAULT 1,
  `hora_llegada` time DEFAULT NULL,
  `puntual` tinyint(1) NOT NULL DEFAULT 0,
  `elegible_premio` tinyint(1) NOT NULL DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_campana_asistencia_sesion_asistente` (`campana_sesion_id`, `campana_asistente_id`),
  KEY `idx_campana_asistencia_sesiones_org` (`organizacion_id`, `campana_id`, `campana_sesion_id`),
  CONSTRAINT `fk_campana_asistencia_sesiones_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campana_asistencia_sesiones_campana`
    FOREIGN KEY (`campana_id`) REFERENCES `campanas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campana_asistencia_sesiones_sesion`
    FOREIGN KEY (`campana_sesion_id`) REFERENCES `campana_sesiones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campana_asistencia_sesiones_asistente`
    FOREIGN KEY (`campana_asistente_id`) REFERENCES `campana_asistentes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pc_grupos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `nombre_pc` varchar(160) NOT NULL,
  `sector` varchar(120) DEFAULT NULL,
  `comunidad` varchar(120) DEFAULT NULL,
  `direccion_reunion` varchar(220) DEFAULT NULL,
  `anfitrion_contacto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lider_principal_contacto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lider_auxiliar_contacto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `dia_reunion` tinyint(3) UNSIGNED DEFAULT NULL,
  `hora_reunion` time DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'ACTIVA',
  `pc_madre_id` bigint(20) UNSIGNED DEFAULT NULL,
  `motivo_cierre` text DEFAULT NULL,
  `meta_trimestral` varchar(160) DEFAULT NULL,
  `observaciones_generales` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `eliminado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `eliminado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pc_grupos_org_estado` (`organizacion_id`, `estado`, `eliminado_en`),
  KEY `idx_pc_grupos_org_lider` (`organizacion_id`, `lider_principal_contacto_id`, `estado`),
  KEY `idx_pc_grupos_org_sector` (`organizacion_id`, `sector`, `estado`),
  CONSTRAINT `fk_pc_grupos_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_grupos_anfitrion`
    FOREIGN KEY (`anfitrion_contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pc_grupos_lider_principal`
    FOREIGN KEY (`lider_principal_contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pc_grupos_lider_auxiliar`
    FOREIGN KEY (`lider_auxiliar_contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pc_grupos_pc_madre`
    FOREIGN KEY (`pc_madre_id`) REFERENCES `pc_grupos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_pc_grupos_estado`
    CHECK (`estado` IN ('ACTIVA', 'INACTIVA', 'MULTIPLICADA', 'CERRADA', 'PAUSADA'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pc_lideres_historial` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `pc_id` bigint(20) UNSIGNED NOT NULL,
  `contacto_id` bigint(20) UNSIGNED NOT NULL,
  `rol_liderazgo` varchar(30) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `motivo_cambio` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pc_lideres_historial_org_pc` (`organizacion_id`, `pc_id`, `fecha_inicio`),
  CONSTRAINT `fk_pc_lideres_historial_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_lideres_historial_pc`
    FOREIGN KEY (`pc_id`) REFERENCES `pc_grupos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_lideres_historial_contacto`
    FOREIGN KEY (`contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_pc_lideres_historial_rol`
    CHECK (`rol_liderazgo` IN ('LIDER_PRINCIPAL', 'COLIDER', 'ANFITRION', 'INSTRUCTOR_ASOCIADO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pc_participantes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `pc_id` bigint(20) UNSIGNED NOT NULL,
  `contacto_id` bigint(20) UNSIGNED NOT NULL,
  `clasificacion` varchar(30) NOT NULL,
  `rol_pc` varchar(80) DEFAULT NULL,
  `es_miembro` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_ingreso` date NOT NULL,
  `fecha_salida` date DEFAULT NULL,
  `motivo_salida` text DEFAULT NULL,
  `estado_participacion` varchar(20) NOT NULL DEFAULT 'ACTIVO',
  `observaciones` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `eliminado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `eliminado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pc_participantes_org_pc_estado` (`organizacion_id`, `pc_id`, `estado_participacion`, `eliminado_en`),
  KEY `idx_pc_participantes_org_contacto` (`organizacion_id`, `contacto_id`),
  CONSTRAINT `fk_pc_participantes_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_participantes_pc`
    FOREIGN KEY (`pc_id`) REFERENCES `pc_grupos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_participantes_contacto`
    FOREIGN KEY (`contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_pc_participantes_clasificacion`
    CHECK (`clasificacion` IN ('MIEMBRO_IGLESIA','VISITA','AMIGO_INTERESADO','NINO','JOVEN','ADULTO','LIDER','ANFITRION','INSTRUCTOR_BIBLICO','OTRO')),
  CONSTRAINT `chk_pc_participantes_estado`
    CHECK (`estado_participacion` IN ('ACTIVO', 'PAUSADO', 'RETIRADO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pc_reuniones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `pc_id` bigint(20) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `tema_titulo` varchar(180) NOT NULL,
  `material_usado` varchar(180) DEFAULT NULL,
  `hubo_estudio_biblico` tinyint(1) NOT NULL DEFAULT 0,
  `hubo_visita` tinyint(1) NOT NULL DEFAULT 0,
  `cantidad_asistentes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_miembros` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_visitas` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_ninos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_jovenes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_adultos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `observacion_reunion` text DEFAULT NULL,
  `decisiones_tomadas` text DEFAULT NULL,
  `proximos_pasos` text DEFAULT NULL,
  `responsable_seguimiento_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pc_reuniones_fecha` (`pc_id`, `fecha`),
  KEY `idx_pc_reuniones_org_fecha` (`organizacion_id`, `fecha`, `pc_id`),
  CONSTRAINT `fk_pc_reuniones_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_reuniones_pc`
    FOREIGN KEY (`pc_id`) REFERENCES `pc_grupos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_reuniones_responsable`
    FOREIGN KEY (`responsable_seguimiento_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pc_reunion_participantes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `reunion_id` bigint(20) UNSIGNED NOT NULL,
  `contacto_id` bigint(20) UNSIGNED NOT NULL,
  `asistio` tinyint(1) NOT NULL DEFAULT 1,
  `clasificacion_dia` varchar(30) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pc_reunion_participante` (`reunion_id`, `contacto_id`),
  KEY `idx_pc_reunion_participantes_org` (`organizacion_id`, `reunion_id`),
  CONSTRAINT `fk_pc_reunion_participantes_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_reunion_participantes_reunion`
    FOREIGN KEY (`reunion_id`) REFERENCES `pc_reuniones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_reunion_participantes_contacto`
    FOREIGN KEY (`contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `estudios_biblicos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `contacto_id` bigint(20) UNSIGNED NOT NULL,
  `origen_clave` varchar(50) DEFAULT NULL,
  `campana_origen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pc_origen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `instructor_principal_contacto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `instructor_secundario_contacto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `responsable_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `modalidad` varchar(20) NOT NULL DEFAULT 'INDIVIDUAL',
  `material_estudio` varchar(160) DEFAULT NULL,
  `leccion_actual` varchar(80) DEFAULT NULL,
  `total_lecciones_completadas` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `fecha_inicio` date NOT NULL,
  `fecha_ultima_sesion` date DEFAULT NULL,
  `proxima_sesion` datetime DEFAULT NULL,
  `estado_general` varchar(25) NOT NULL DEFAULT 'NUEVO',
  `observaciones` text DEFAULT NULL,
  `motivo_cierre_pausa` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `eliminado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `eliminado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_estudios_biblicos_org_estado` (`organizacion_id`, `estado_general`, `eliminado_en`),
  KEY `idx_estudios_biblicos_org_responsable` (`organizacion_id`, `responsable_usuario_id`, `estado_general`),
  KEY `idx_estudios_biblicos_org_origen` (`organizacion_id`, `origen_clave`, `estado_general`),
  CONSTRAINT `fk_estudios_biblicos_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estudios_biblicos_contacto`
    FOREIGN KEY (`contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estudios_biblicos_campana`
    FOREIGN KEY (`campana_origen_id`) REFERENCES `campanas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_estudios_biblicos_pc`
    FOREIGN KEY (`pc_origen_id`) REFERENCES `pc_grupos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_estudios_biblicos_instructor_principal`
    FOREIGN KEY (`instructor_principal_contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_estudios_biblicos_instructor_secundario`
    FOREIGN KEY (`instructor_secundario_contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_estudios_biblicos_responsable`
    FOREIGN KEY (`responsable_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_estudios_biblicos_modalidad`
    CHECK (`modalidad` IN ('INDIVIDUAL', 'CLASE_BIBLICA', 'HOGAR', 'TEMPLO', 'VIRTUAL', 'OTRO')),
  CONSTRAINT `chk_estudios_biblicos_estado`
    CHECK (`estado_general` IN ('NUEVO','ASIGNADO','CONTACTADO','EN_PROCESO','PAUSADO','NO_CONTINUA','LISTO_DECISION','CANDIDATO_BAUTISMAL','BAUTIZADO','CERRADO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estudio_asignaciones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `estudio_id` bigint(20) UNSIGNED NOT NULL,
  `instructor_principal_contacto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `instructor_secundario_contacto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `responsable_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_asignacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_fin` datetime DEFAULT NULL,
  `motivo_cambio` text DEFAULT NULL,
  `vigente` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_estudio_asignaciones_org_estudio` (`organizacion_id`, `estudio_id`, `vigente`),
  CONSTRAINT `fk_estudio_asignaciones_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estudio_asignaciones_estudio`
    FOREIGN KEY (`estudio_id`) REFERENCES `estudios_biblicos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estudio_asignaciones_instr_principal`
    FOREIGN KEY (`instructor_principal_contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_estudio_asignaciones_instr_secundario`
    FOREIGN KEY (`instructor_secundario_contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_estudio_asignaciones_responsable`
    FOREIGN KEY (`responsable_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estudio_sesiones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `estudio_id` bigint(20) UNSIGNED NOT NULL,
  `fecha` datetime NOT NULL,
  `tema_leccion` varchar(180) NOT NULL,
  `resumen_breve` text DEFAULT NULL,
  `dudas_surgidas` text DEFAULT NULL,
  `asistencia` varchar(20) DEFAULT NULL,
  `percepcion_avance` varchar(20) DEFAULT NULL,
  `proxima_accion` text DEFAULT NULL,
  `proxima_fecha_sugerida` datetime DEFAULT NULL,
  `responsable_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_estudio_sesiones_org_estudio_fecha` (`organizacion_id`, `estudio_id`, `fecha`),
  CONSTRAINT `fk_estudio_sesiones_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estudio_sesiones_estudio`
    FOREIGN KEY (`estudio_id`) REFERENCES `estudios_biblicos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estudio_sesiones_responsable`
    FOREIGN KEY (`responsable_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estudio_decisiones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `estudio_id` bigint(20) UNSIGNED NOT NULL,
  `decision_clave` varchar(60) NOT NULL,
  `decision_etiqueta` varchar(180) NOT NULL,
  `fecha_decision` datetime NOT NULL,
  `observaciones` text DEFAULT NULL,
  `requiere_seguimiento` tinyint(1) NOT NULL DEFAULT 0,
  `seguimiento_tarea_id` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_estudio_decisiones_org_estudio` (`organizacion_id`, `estudio_id`, `fecha_decision`),
  CONSTRAINT `fk_estudio_decisiones_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estudio_decisiones_estudio`
    FOREIGN KEY (`estudio_id`) REFERENCES `estudios_biblicos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estudio_decisiones_seguimiento`
    FOREIGN KEY (`seguimiento_tarea_id`) REFERENCES `seguimiento_tareas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pc_resultados` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `pc_id` bigint(20) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `tipo_resultado` varchar(35) NOT NULL,
  `contacto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estudio_biblico_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cantidad` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `descripcion` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pc_resultados_org_pc` (`organizacion_id`, `pc_id`, `fecha`, `tipo_resultado`),
  CONSTRAINT `fk_pc_resultados_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_resultados_pc`
    FOREIGN KEY (`pc_id`) REFERENCES `pc_grupos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_resultados_contacto`
    FOREIGN KEY (`contacto_id`) REFERENCES `contactos_misioneros` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pc_resultados_estudio`
    FOREIGN KEY (`estudio_biblico_id`) REFERENCES `estudios_biblicos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_pc_resultados_tipo`
    CHECK (`tipo_resultado` IN ('ESTUDIO_BIBLICO_GENERADO','INTERESADO_NUEVO','DECISION_ESPIRITUAL','BAUTISMO_RELACIONADO','MIEMBRO_REACTIVADO','MULTIPLICACION','CIERRE','OTRO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campana_decisiones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `campana_id` bigint(20) UNSIGNED NOT NULL,
  `campana_asistente_id` bigint(20) UNSIGNED NOT NULL,
  `decision_clave` varchar(60) NOT NULL,
  `decision_etiqueta` varchar(180) NOT NULL,
  `fecha_decision` datetime NOT NULL,
  `observaciones` text DEFAULT NULL,
  `estudio_biblico_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seguimiento_tarea_id` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_campana_decisiones_org_campana` (`organizacion_id`, `campana_id`, `fecha_decision`),
  CONSTRAINT `fk_campana_decisiones_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campana_decisiones_campana`
    FOREIGN KEY (`campana_id`) REFERENCES `campanas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campana_decisiones_asistente`
    FOREIGN KEY (`campana_asistente_id`) REFERENCES `campana_asistentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campana_decisiones_estudio`
    FOREIGN KEY (`estudio_biblico_id`) REFERENCES `estudios_biblicos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_campana_decisiones_seguimiento`
    FOREIGN KEY (`seguimiento_tarea_id`) REFERENCES `seguimiento_tareas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `juntas_iglesia` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `tipo` varchar(25) NOT NULL DEFAULT 'ORDINARIA',
  `moderador` varchar(160) DEFAULT NULL,
  `secretario` varchar(160) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'BORRADOR',
  `observaciones_generales` text DEFAULT NULL,
  `resumen_general` text DEFAULT NULL,
  `quorum_texto` varchar(180) DEFAULT NULL,
  `junta_anterior_id` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `eliminado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `eliminado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_juntas_iglesia_org_estado` (`organizacion_id`, `estado`, `fecha`, `eliminado_en`),
  CONSTRAINT `fk_juntas_iglesia_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_juntas_iglesia_anterior`
    FOREIGN KEY (`junta_anterior_id`) REFERENCES `juntas_iglesia` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_juntas_iglesia_tipo`
    CHECK (`tipo` IN ('ORDINARIA', 'EXTRAORDINARIA', 'SEGUIMIENTO', 'WHATSAPP', 'CONTINUACION')),
  CONSTRAINT `chk_juntas_iglesia_estado`
    CHECK (`estado` IN ('BORRADOR', 'EN_PROCESO', 'CERRADA', 'APROBADA', 'ARCHIVADA'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `junta_puntos_agenda` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `junta_id` bigint(20) UNSIGNED NOT NULL,
  `numero_orden` smallint(5) UNSIGNED NOT NULL,
  `titulo` varchar(180) NOT NULL,
  `departamento_origen` varchar(120) DEFAULT NULL,
  `presentado_por` varchar(160) DEFAULT NULL,
  `tipo_punto` varchar(30) NOT NULL DEFAULT 'NUEVO',
  `descripcion_base` text DEFAULT NULL,
  `observacion_secretaria` text DEFAULT NULL,
  `discusion_resumen` text DEFAULT NULL,
  `decision_final` text DEFAULT NULL,
  `estado` varchar(25) NOT NULL DEFAULT 'PENDIENTE',
  `prioridad` varchar(15) NOT NULL DEFAULT 'MEDIA',
  `confidencial` tinyint(1) NOT NULL DEFAULT 0,
  `responsable_seguimiento_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_limite` date DEFAULT NULL,
  `punto_anterior_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pasar_proxima_junta` tinyint(1) NOT NULL DEFAULT 0,
  `referencia_modulo` varchar(30) DEFAULT NULL,
  `referencia_entidad_id` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `eliminado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `eliminado_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_junta_puntos_orden` (`junta_id`, `numero_orden`),
  KEY `idx_junta_puntos_org_estado` (`organizacion_id`, `estado`, `fecha_limite`, `eliminado_en`),
  KEY `idx_junta_puntos_org_departamento` (`organizacion_id`, `departamento_origen`, `estado`),
  CONSTRAINT `fk_junta_puntos_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_junta_puntos_junta`
    FOREIGN KEY (`junta_id`) REFERENCES `juntas_iglesia` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_junta_puntos_responsable`
    FOREIGN KEY (`responsable_seguimiento_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_junta_puntos_anterior`
    FOREIGN KEY (`punto_anterior_id`) REFERENCES `junta_puntos_agenda` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_junta_puntos_tipo`
    CHECK (`tipo_punto` IN ('INFORMATIVO', 'VOTACION', 'SEGUIMIENTO', 'PENDIENTE_ANTERIOR', 'APROBADO_WHATSAPP', 'NUEVO')),
  CONSTRAINT `chk_junta_puntos_estado`
    CHECK (`estado` IN ('PENDIENTE', 'DISCUTIDO', 'VOTADO', 'APROBADO', 'RECHAZADO', 'POSPUESTO', 'TRASLADADO', 'EJECUTADO', 'RESUELTO_WHATSAPP')),
  CONSTRAINT `chk_junta_puntos_prioridad`
    CHECK (`prioridad` IN ('BAJA', 'MEDIA', 'ALTA'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `junta_votaciones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `punto_agenda_id` bigint(20) UNSIGNED NOT NULL,
  `requirio_voto` tinyint(1) NOT NULL DEFAULT 1,
  `tipo_voto` varchar(20) NOT NULL DEFAULT 'MAYORIA',
  `texto_voto` text DEFAULT NULL,
  `votos_favor` int(10) UNSIGNED DEFAULT NULL,
  `votos_contra` int(10) UNSIGNED DEFAULT NULL,
  `abstenciones` int(10) UNSIGNED DEFAULT NULL,
  `fecha_voto` datetime DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `actualizado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_junta_votaciones_org_punto` (`organizacion_id`, `punto_agenda_id`, `fecha_voto`),
  CONSTRAINT `fk_junta_votaciones_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_junta_votaciones_punto`
    FOREIGN KEY (`punto_agenda_id`) REFERENCES `junta_puntos_agenda` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_junta_votaciones_tipo`
    CHECK (`tipo_voto` IN ('UNANIME', 'MAYORIA', 'CONSENSO', 'SOLO_INFORMADO'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `junta_adjuntos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizacion_id` bigint(20) UNSIGNED NOT NULL,
  `junta_id` bigint(20) UNSIGNED NOT NULL,
  `punto_agenda_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre_archivo` varchar(200) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `tamano_bytes` bigint(20) UNSIGNED DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_junta_adjuntos_org_junta` (`organizacion_id`, `junta_id`, `creado_en`),
  CONSTRAINT `fk_junta_adjuntos_org`
    FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_junta_adjuntos_junta`
    FOREIGN KEY (`junta_id`) REFERENCES `juntas_iglesia` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_junta_adjuntos_punto`
    FOREIGN KEY (`punto_agenda_id`) REFERENCES `junta_puntos_agenda` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
