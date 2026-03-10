# AGENTS - C_ASISTENCIA_BAKCEND

## Objetivo

Guiar la evolucion del backend a multiiglesia/multigrupo por tickets,
sin perder aislamiento de datos ni alineacion con frontend.

## Lectura obligatoria al iniciar

1. `.agents/escalabilidad/CONTEXTO_ACTUAL_BACKEND_MULTIIGLESIA.md`
2. `.agents/escalabilidad/ROADMAP_BACKEND_MULTIIGLESIA.md`
3. `.agents/escalabilidad/TICKETS_BACKEND_MULTIIGLESIA.md`
4. `contexto_general.md`
5. `Contexto_actual_bd.md`

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

## Primer ticket recomendado para iniciar

- `B0-T01` en `.agents/escalabilidad/TICKETS_BACKEND_MULTIIGLESIA.md`
