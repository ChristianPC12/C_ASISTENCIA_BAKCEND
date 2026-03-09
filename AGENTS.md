# AGENTS - C_ASISTENCIA_BAKCEND

## Objetivo

Guiar agentes de IA para evolucionar este backend hacia escalabilidad nacional multiiglesia/multigrupo, manteniendo seguridad y consistencia con frontend.

## Lectura obligatoria al iniciar

1. `.agents/escalabilidad/CONTEXTO_ACTUAL_BACKEND_MULTIIGLESIA.md`
2. `.agents/escalabilidad/ROADMAP_BACKEND_MULTIIGLESIA.md`
3. `contexto_general.md`
4. `Contexto_actual_bd.md`

## Reglas operativas

- Tratar `organizacion_id` como filtro obligatorio en dominio tenant-aware.
- No marcar una fase como cerrada sin cumplir su Definition of Done.
- Actualizar estado del roadmap con `[ ]`, `[~]`, `[x]`.

## Sincronizacion obligatoria con frontend

Cualquier cambio backend que altere auth, roles, rutas o contrato de datos debe reflejarse tambien en:

- `C_ASISTENCIA_FRONTEND/.agents/react-doctor/CONTEXTO_ACTUAL.md`
- `C_ASISTENCIA_FRONTEND/.agents/react-doctor/ROADMAP_ESCALABILIDAD_NACIONAL.md`
- `C_ASISTENCIA_FRONTEND/.agents/react-doctor/prompt_frontend.md`

