# Backend Escalabilidad - Agent Guide

Este paquete de agentes define la ruta backend para escalar C_ASISTENCIA a un modelo multiiglesia/multigrupo con aislamiento por tenant.

## Objetivo

- Guiar cambios por fases sin romper operacion actual.
- Mantener trazabilidad de tareas y prioridades.
- Evitar contradicciones entre backend, frontend y base de datos.

## Orden de lectura recomendado

1. `CONTEXTO_ACTUAL_BACKEND_MULTIIGLESIA.md`
2. `ROADMAP_BACKEND_MULTIIGLESIA.md`
3. `contexto_general.md`
4. `Contexto_actual_bd.md`

## Regla de activacion

Usar estos agentes cuando la tarea incluya alguno de estos temas:

- superadmin
- multitenancy / tenant isolation
- migraciones para campo + iglesia/grupo
- login tenant-aware
- bloqueo de modulos por configuracion inicial
- cuotas de usuarios por rol
- onboarding por correo con credenciales temporales

## Regla de mantenimiento

Actualizar `CONTEXTO_ACTUAL_BACKEND_MULTIIGLESIA.md` y `ROADMAP_BACKEND_MULTIIGLESIA.md` cuando cambie:

- contrato de autenticacion/sesion
- estructura de tablas, constraints o indices
- politica de permisos por rol
- politicas de expiracion/revocacion/limites
- alcance o prioridad de fases del programa de escalabilidad

## Calidad documental minima

Antes de cerrar una actualizacion de agentes backend, verificar:

- no hay contradiccion con los agentes del frontend
- el estado `[ ]/[~]/[x]` del roadmap coincide con codigo real
- las rutas de archivo referenciadas existen
- los riesgos y bloqueadores siguen vigentes o se actualizan

