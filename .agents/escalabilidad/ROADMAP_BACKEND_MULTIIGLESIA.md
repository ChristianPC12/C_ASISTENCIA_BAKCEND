# Roadmap Backend Multiiglesia / Multigrupo (IASD CR)

Fecha base: 2026-03-09  
Estado global: Planificado (sin fases cerradas)

## 1) Meta tecnica backend

Convertir el backend actual a tenant-aware para soportar multiples organizaciones (iglesias/grupos) dentro del mismo sistema, con separacion de datos por `organizacion_id` y controles fuertes de permisos.

## 2) Convenciones de estado

- `[ ]` pendiente
- `[~]` en progreso
- `[x]` completado

Regla operativa:
- Marcar una sola tarea como `[~]` por fase al iniciar.
- No abrir nueva fase si la anterior no cumple Definition of Done.

## 3) Arquitectura objetivo resumida

Entidades nuevas/minimas:
- `campos` (catalogo nacional IASD CR)
- `organizaciones` (tenant: iglesia/grupo)
- `organizacion_config_estado` (setup inicial bloqueado/listo)
- `organizacion_cultos` (dias/horas por tenant)
- `organizacion_procedencias` (hasta 10 por tenant)
- `organizacion_metricas_config` (habilitado/obligatorio/dependencias)
- `organizacion_roles_cupos` (limites por rol)

Ajustes de entidades existentes:
- `usuarios` -> agregar `organizacion_id`, banderas de temporalidad, expiracion temporal.
- `user_tokens` -> asociar tenant o validar tenant por join en middleware.
- `asistencia_registro` -> agregar `organizacion_id` y cambiar unique key.
- `presentaciones` -> agregar `organizacion_id`.

## 4) Fases backend

| Fase | Prioridad | Estado | Resultado clave |
|---|---|---|---|
| B0 - Contrato y decisiones base | P0 | [ ] | Definiciones firmes de login tenant-aware y llaves unicas |
| B1 - Migraciones base multitenant | P0 | [ ] | Estructura BD lista para aislamiento |
| B2 - Auth y contexto tenant-aware | P0 | [ ] | Sesiones y auth con `organizacion_id` |
| B3 - API de superadmin | P0 | [ ] | Alta/edicion de organizaciones y admin temporal |
| B4 - Setup inicial y bloqueo de modulos | P0 | [ ] | Guard backend bloquea uso hasta configuracion completa |
| B5 - Dominio dinamico de registro/reportes | P1 | [ ] | Backend soporta parametros dinamicos por tenant |
| B6 - Cuotas y ciclo de vida de usuarios | P1 | [ ] | Limites por rol + expiracion de admin temporal |
| B7 - Endurecimiento y salida a produccion | P0 | [ ] | Plan de migracion, pruebas, monitoreo y rollback |

---

### B0 - Contrato y decisiones base (P0)

Checklist:
- [ ] Confirmar formato de login tenant-aware (recomendado: `codigo_instancia + usuario + password`).
- [ ] Definir unicidad de `usuario`:
  - opcion A: unico global
  - opcion B: unico por tenant (recomendado para escalabilidad)
- [ ] Definir estrategia de versionado de API para cambios rompientes.
- [ ] Definir nombres finales de tablas multitenant.
- [ ] Definir regla de auditoria minima para eventos de superadmin.

Definition of Done:
- Decisiones documentadas en este archivo y alineadas con roadmap frontend.

---

### B1 - Migraciones base multitenant (P0)

Checklist SQL:
- [ ] Crear migracion `migracion_YYYYMMDD_multitenant_base.sql` con tablas nuevas.
- [ ] Insertar seed de `campos` (3 registros oficiales).
- [ ] Agregar `organizacion_id` a `usuarios`, `asistencia_registro`, `presentaciones`.
- [ ] Ajustar indices:
  - asistencia unique -> `(organizacion_id, culto_id, fecha)`
  - usuarios unique -> segun decision B0
- [ ] Crear FKs e indices de busqueda por tenant.
- [ ] Crear script de backfill para tenant inicial desde datos actuales.

Checklist codigo:
- [ ] Actualizar `iglesia_asistencia.sql` para entornos nuevos.
- [ ] Documentar impacto en `Contexto_actual_bd.md`.

Definition of Done:
- Migraciones aplican en limpio y en base existente sin perdida de datos.
- Caso de prueba: dos tenants distintos guardan mismo culto/fecha sin conflicto.

---

### B2 - Auth y contexto tenant-aware (P0)

Archivos foco:
- `Utils/AuthContext.php`
- `Middleware/AuthMiddleware.php`
- `Services/AuthService.php`
- `Modelo/Token/TokenDAO.php`
- `Modelo/Usuario/UsuarioDAO.php`

Checklist:
- [ ] Extender `AuthContext` con `organizacion_id`.
- [ ] Asegurar que `AuthMiddleware` siempre hidrata tenant del usuario autenticado.
- [ ] Ajustar login para validar `codigo_instancia` (o equivalente definido en B0).
- [ ] Incluir tenant en respuesta de `login` y `me`.
- [ ] Ajustar revocacion/limpieza de tokens considerando tenant.
- [ ] Mantener excepcion controlada para `SUPERADMIN` global.

Definition of Done:
- Ningun endpoint protegido opera sin tenant en contexto (excepto superadmin).
- Pruebas de auth cubren tenant correcto/incorrecto y role checks.

---

### B3 - API de superadmin (P0)

Archivos foco:
- `Router/*`, `Controller/*`, `Services/*`, `Modelo/*` (nuevo modulo superadmin)

Checklist:
- [ ] Agregar rol `SUPERADMIN` en semilla y validaciones.
- [ ] Crear endpoints para:
  - alta de organizacion (campo + tipo + nombre + correo opcional)
  - edicion de organizacion (incluye cambio GRUPO -> IGLESIA)
  - creacion de ADMIN temporal inicial
  - consulta de estado de onboarding por organizacion
- [ ] Prohibir acceso de superadmin a modulos operativos de instancia.
- [ ] Registrar auditoria minima de acciones superadmin.

Definition of Done:
- Se puede crear una nueva instancia completa por API sin SQL manual.

---

### B4 - Setup inicial y bloqueo de modulos (P0)

Objetivo:
- Si la organizacion no completo setup inicial, bloquear endpoints operativos.

Checklist:
- [ ] Crear tabla/estado de setup (`pendiente`, `completo`).
- [ ] Crear endpoints para guardar configuracion inicial de tenant:
  - cultos habilitados (dia/hora)
  - metricas habilitadas/obligatorias
  - dependencias de metricas
  - procedencias (1..10)
- [ ] Implementar guard reusable para bloquear:
  - asistencias
  - estadisticas
  - comparaciones
  - presentaciones
  mientras setup este pendiente.
- [ ] Permitir solo endpoints de configuracion durante estado pendiente.

Definition of Done:
- Tenant sin setup no puede crear registros aunque este autenticado como ADMIN.

---

### B5 - Dominio dinamico de registro/reportes (P1)

Objetivo:
- Soportar parametros variables por tenant sin hardcode rigido.

Checklist:
- [ ] Definir esquema para persistir metricas dinamicas por registro.
- [ ] Implementar validacion dinamica backend por configuracion de tenant.
- [ ] Adaptar servicios de estadisticas/comparaciones/presentaciones para lectura dinamica.
- [ ] Mantener capa de compatibilidad temporal para datos legacy durante migracion.

Definition of Done:
- Dos tenants con configuraciones diferentes obtienen reportes correctos sin ramas ad-hoc por tenant.

---

### B6 - Cuotas y ciclo de vida de usuarios (P1)

Objetivo:
- Limitar usuarios por rol y gestionar admin temporal de onboarding.

Checklist:
- [ ] Implementar `organizacion_roles_cupos`.
- [ ] Enforzar cupos en creacion/edicion de usuarios (backend source of truth).
- [ ] Agregar atributos de temporalidad a usuario admin inicial:
  - `es_temporal`
  - `expira_en`
- [ ] Implementar limpieza automatica de usuarios temporales vencidos (cron/job).
- [ ] Integrar envio opcional de correo con Brevo via adaptador de proveedor.

Definition of Done:
- El admin temporal expira automaticamente a 5 dias.
- No se puede exceder cupo por rol.

---

### B7 - Endurecimiento y salida a produccion (P0)

Checklist:
- [ ] Suite de pruebas de aislamiento tenant.
- [ ] Pruebas de permisos por rol (SUPERADMIN, ADMIN, SECRETARIO y nuevos roles).
- [ ] Pruebas de regresion de auth (401, 403, 429).
- [ ] Pruebas de migracion + rollback en entorno staging.
- [ ] Ajuste de CORS para produccion (no `*`).
- [ ] Checklist de despliegue y observabilidad minima.

Definition of Done:
- Aprobacion de salida con evidencia de pruebas + plan de rollback validado.

## 5) Riesgos criticos

- Riesgo de fuga de datos por queries sin `organizacion_id`.
- Riesgo de deuda tecnica si se mezcla dinamismo con schema actual sin fase de compatibilidad.
- Riesgo operativo si setup inicial bloquea de forma irreversible por errores de validacion.

Mitigacion minima:
- pruebas automatizadas de aislamiento por tenant,
- guard backend centralizado para setup,
- rollout por etapas con tenant piloto antes de despliegue masivo.

## 6) Regla de sincronizacion con frontend

Cada vez que se cierre una tarea backend con impacto funcional, actualizar:
- `C_ASISTENCIA_FRONTEND/.agents/react-doctor/ROADMAP_ESCALABILIDAD_NACIONAL.md`
- `C_ASISTENCIA_FRONTEND/.agents/react-doctor/CONTEXTO_ACTUAL.md`

