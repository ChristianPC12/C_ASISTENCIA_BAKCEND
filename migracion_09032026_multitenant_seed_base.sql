-- ============================================================
-- MIGRACION: Semilla base multitenant (B1-T03)
-- BD: iglesia_asistencia
-- Fecha: 2026-03-09
-- ============================================================

USE `iglesia_asistencia`;

-- 1) Tenant inicial oficial: BASECR01
SET @campo_an := (
  SELECT id
  FROM `campos`
  WHERE `codigo` = 'AN'
  LIMIT 1
);

INSERT INTO `organizaciones` (
  `campo_id`,
  `codigo_instancia`,
  `tipo_organizacion`,
  `nombre_organizacion`,
  `correo_contacto`,
  `activa`
)
SELECT
  @campo_an,
  'BASECR01',
  'IGLESIA',
  'INSTANCIA_INICIAL',
  NULL,
  1
WHERE @campo_an IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `organizaciones`
    WHERE `codigo_instancia` = 'BASECR01'
  );

SET @org_base := (
  SELECT id
  FROM `organizaciones`
  WHERE `codigo_instancia` = 'BASECR01'
  LIMIT 1
);

-- 2) Estado setup inicial (bloqueado hasta configuracion)
INSERT INTO `organizacion_config_estado` (
  `organizacion_id`,
  `estado_setup`,
  `bloqueada_operacion`,
  `setup_completado_en`,
  `ultima_revision_en`
)
SELECT
  @org_base,
  'PENDIENTE',
  1,
  NULL,
  NOW()
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `organizacion_config_estado`
    WHERE `organizacion_id` = @org_base
  );

UPDATE `organizacion_config_estado`
SET `ultima_revision_en` = NOW()
WHERE `organizacion_id` = @org_base;

-- 3) Cultos base por tenant
INSERT INTO `organizacion_cultos` (`organizacion_id`, `codigo`, `nombre`, `dia_semana`, `hora_inicio`, `activo`, `orden`)
SELECT @org_base, 'SABADO', 'Culto Sabado', 7, '09:00:00', 1, 1
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `organizacion_cultos`
    WHERE `organizacion_id` = @org_base
      AND `codigo` = 'SABADO'
  );

INSERT INTO `organizacion_cultos` (`organizacion_id`, `codigo`, `nombre`, `dia_semana`, `hora_inicio`, `activo`, `orden`)
SELECT @org_base, 'DOMINGO', 'Culto Domingo', 1, '18:30:00', 1, 2
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `organizacion_cultos`
    WHERE `organizacion_id` = @org_base
      AND `codigo` = 'DOMINGO'
  );

INSERT INTO `organizacion_cultos` (`organizacion_id`, `codigo`, `nombre`, `dia_semana`, `hora_inicio`, `activo`, `orden`)
SELECT @org_base, 'MIERCOLES', 'Culto Miercoles', 4, '18:30:00', 1, 3
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `organizacion_cultos`
    WHERE `organizacion_id` = @org_base
      AND `codigo` = 'MIERCOLES'
  );

-- 4) Procedencias base por tenant
INSERT INTO `organizacion_procedencias` (`organizacion_id`, `nombre`, `activo`, `orden`)
SELECT @org_base, 'BARRIO', 1, 1
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `organizacion_procedencias`
    WHERE `organizacion_id` = @org_base
      AND `nombre` = 'BARRIO'
  );

INSERT INTO `organizacion_procedencias` (`organizacion_id`, `nombre`, `activo`, `orden`)
SELECT @org_base, 'GUAYABO', 1, 2
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `organizacion_procedencias`
    WHERE `organizacion_id` = @org_base
      AND `nombre` = 'GUAYABO'
  );

-- 5) Metricas base por tenant
INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'llegaron_antes_hora', 'Llegaron antes de la hora', 1, 1, NULL, NULL, 10
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'llegaron_antes_hora'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'llegaron_despues_hora', 'Llegaron despues de la hora', 1, 1, NULL, NULL, 20
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'llegaron_despues_hora'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'ninos', 'Ninos', 1, 1, NULL, NULL, 30
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'ninos'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'jovenes', 'Jovenes', 1, 1, NULL, NULL, 40
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'jovenes'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'total_asistentes', 'Total de asistentes', 1, 1, NULL, NULL, 50
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'total_asistentes'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'proc_barrio', 'Procedencia Barrio', 1, 1, NULL, NULL, 60
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'proc_barrio'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'proc_guayabo', 'Procedencia Guayabo', 1, 1, NULL, NULL, 70
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'proc_guayabo'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'visitas_barrio', 'Visitas Barrio', 1, 0, NULL, NULL, 80
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'visitas_barrio'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'nombres_visitas_barrio', 'Nombres visitas Barrio', 1, 0, 'visitas_barrio', 'SI_MAYOR_CERO', 90
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'nombres_visitas_barrio'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'visitas_guayabo', 'Visitas Guayabo', 1, 0, NULL, NULL, 100
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'visitas_guayabo'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'nombres_visitas_guayabo', 'Nombres visitas Guayabo', 1, 0, 'visitas_guayabo', 'SI_MAYOR_CERO', 110
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'nombres_visitas_guayabo'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'retiros_antes_terminar', 'Retiros antes de terminar', 1, 1, NULL, NULL, 120
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'retiros_antes_terminar'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'se_quedaron_todo', 'Se quedaron hasta el final', 1, 1, NULL, NULL, 130
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'se_quedaron_todo'
  );

INSERT INTO `organizacion_metricas_config`
(`organizacion_id`, `clave`, `etiqueta`, `habilitado`, `obligatorio`, `depende_de_clave`, `regla_dependencia`, `orden`)
SELECT @org_base, 'observaciones', 'Observaciones', 1, 0, NULL, NULL, 140
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_metricas_config`
    WHERE `organizacion_id` = @org_base AND `clave` = 'observaciones'
  );

-- 6) Cupos base por rol
INSERT INTO `organizacion_roles_cupos` (`organizacion_id`, `rol_nombre`, `cupo_maximo`, `activo`)
SELECT @org_base, 'ADMIN', 2, 1
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_roles_cupos`
    WHERE `organizacion_id` = @org_base
      AND `rol_nombre` = 'ADMIN'
  );

INSERT INTO `organizacion_roles_cupos` (`organizacion_id`, `rol_nombre`, `cupo_maximo`, `activo`)
SELECT @org_base, 'SECRETARIO', 2, 1
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_roles_cupos`
    WHERE `organizacion_id` = @org_base
      AND `rol_nombre` = 'SECRETARIO'
  );

INSERT INTO `organizacion_roles_cupos` (`organizacion_id`, `rol_nombre`, `cupo_maximo`, `activo`)
SELECT @org_base, 'MINISTERIO_PERSONAL', 3, 1
WHERE @org_base IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `organizacion_roles_cupos`
    WHERE `organizacion_id` = @org_base
      AND `rol_nombre` = 'MINISTERIO_PERSONAL'
  );

-- 7) Usuario ADMIN inicial vinculado al tenant base
SET @rol_admin := (
  SELECT id
  FROM `roles`
  WHERE `nombre` = 'ADMIN'
  LIMIT 1
);

INSERT INTO `usuarios` (
  `nombre_completo`,
  `usuario`,
  `password_hash`,
  `password_actualizada_en`,
  `password_expira_en`,
  `rol_id`,
  `organizacion_id`,
  `activo`
)
SELECT
  'Administrador General',
  'admin',
  '$2y$10$MxWFkCGW30rMt/8/tO4KuuoFKipqIFET8yJ6SDR9FemGTzlbpHEHC',
  NOW(),
  DATE_ADD(NOW(), INTERVAL 30 DAY),
  @rol_admin,
  @org_base,
  1
WHERE @org_base IS NOT NULL
  AND @rol_admin IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `usuarios`
    WHERE `usuario` = 'admin'
  );

UPDATE `usuarios`
SET `organizacion_id` = @org_base,
    `rol_id` = COALESCE(@rol_admin, `rol_id`),
    `activo` = 1
WHERE `usuario` = 'admin'
  AND @org_base IS NOT NULL;

UPDATE `user_tokens` ut
INNER JOIN `usuarios` u ON u.id = ut.usuario_id
SET ut.organizacion_id = u.organizacion_id
WHERE u.usuario = 'admin'
  AND u.organizacion_id IS NOT NULL;
