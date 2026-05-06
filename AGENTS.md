# AGENTS - C_ASISTENCIA_BAKCEND (Actualizado 2026-03-11)

## Objetivo

Mantener estable el backend multi-tenant ya consolidado y habilitar nuevos modulos
con ejecucion controlada (discovery -> implementacion), sin romper contratos vigentes.

## Estado base confirmado (2026-03-11)

- API v2 de superadmin consolidada (organizaciones + admin temporal + catalogos de campos/distritos).
- Soporte de estados de admin (`activo`, `expirado`, `sin admin`) disponible para UI.
- Integracion de envio de credenciales via Brevo lista (configurable por entorno/local).
- Validaciones backend activas para `campo`, `distrito`, correos y reglas de unicidad.

## Lectura obligatoria al iniciar

1. `.agents/escalabilidad/CONTEXTO_ACTUAL_BACKEND_MULTIIGLESIA.md`
2. `.agents/escalabilidad/ROADMAP_BACKEND_MULTIIGLESIA.md`
3. `.agents/escalabilidad/TICKETS_BACKEND_MULTIIGLESIA.md`
4. `contexto_general.md`
5. `Contexto_actual_bd.md`

## Modo de arranque obligatorio (sin programar)

Antes de tocar codigo, el agente debe:

1. Leer los documentos obligatorios.
2. Entregar un brief corto con alcance, riesgos y archivos impactados.
3. Esperar autorizacion explicita del owner para implementar.

Si no hay autorizacion explicita, el agente permanece en modo analisis.

## Reglas operativas

- `organizacion_id` es obligatorio en dominio tenant-aware.
- Ejecutar por ticket, no por fase completa en una sola entrega.
- Solo un ticket por fase puede estar en `[~]`.
- No cerrar fase sin cumplir Definition of Done.
- Ningun ticket puede marcarse en `[x]` sin aprobacion explicita del owner funcional.

## Convencion de estado

- `[ ]` pendiente
- `[~]` en progreso
- `[x]` completado

## Sincronizacion obligatoria con frontend

Cualquier cambio backend que altere auth, roles, rutas o contrato de datos debe reflejarse tambien en:

- `C_ASISTENCIA_FRONTEND/.agents/react-doctor/CONTEXTO_ACTUAL.md`
- `C_ASISTENCIA_FRONTEND/.agents/react-doctor/ROADMAP_ESCALABILIDAD_NACIONAL.md`
- `C_ASISTENCIA_FRONTEND/.agents/react-doctor/TICKETS_ESCALABILIDAD_NACIONAL.md`
- `C_ASISTENCIA_FRONTEND/.agents/react-doctor/prompt_frontend.md`

## Nota operativa

El siguiente bloque de trabajo lo define el owner funcional segun prioridad de modulo.

## Estado actual de modulos misioneros (2026-04-04)
- Ya estan creadas y aplicadas las migraciones base y operativas de contactos, seguimiento, auditoria, Campanas, Estudios Biblicos, PC y Juntas.
- Ya existen endpoints tenant-aware funcionales para los 4 modulos nuevos.
- Ya existe integracion cruzada Campana -> Estudio Biblico y PC -> Estudio Biblico con auditoria y persistencia de estado en el modulo origen.
- Si otro chat recibe la instruccion 'estudia los agents', debe asumir como siguiente bloque natural reportes/exportaciones, pruebas funcionales mas profundas y mas integraciones cruzadas, sin romper el aislamiento por organizacion_id.

## Continuidad operativa actual (2026-05-06)

Si otro chat recibe "ponte al tanto" o "continua desde donde quedamos", debe leer primero:

1. `.agents/escalabilidad/CONTEXTO_ACTUAL_BACKEND_MULTIIGLESIA.md`
2. `.agents/escalabilidad/TICKETS_BACKEND_MULTIIGLESIA.md`
3. `Contexto_actual_bd.md`
4. Frontend: `C_ASISTENCIA_FRONTEND/C_ASISTENCIA_FRONTEND/.agents/react-doctor/CONTEXTO_ACTUAL.md`

Estado real del trabajo:

- `Campanas` esta estable para esta etapa y aporta visitas compartidas para Estudios Biblicos.
- `Estudios Biblicos` es el modulo activo actual y ya soporta:
  - rol `INSTRUCTOR_BIBLICO`;
  - CRUD de instructores como usuarios reales;
  - asignacion multiple de visitas e instructores;
  - validacion para impedir visitas con estudio biblico activo duplicado;
  - sesiones registradas por instructor responsable;
  - justificacion de faltas por periodo;
  - estados de estudio: `ASIGNADO`, `EN_PROCESO`, `PAUSADO`, `FINALIZADO`.
- Migraciones relacionadas:
  - `migracion_04052026_estudios_biblicos_instructores.sql`
  - `migracion_05052026_estudios_biblicos_multiples_visitas_instructores.sql`
- Cualquier cambio debe conservar aislamiento por `organizacion_id` y sincronizar contrato con frontend.

