# Backend Escalabilidad - Agent Guide

Este paquete define la ejecucion backend de la escalabilidad nacional por tickets.

## Objetivo

- Ejecutar cambios incrementales y auditables.
- Mantener aislamiento por tenant como regla no negociable.
- Sincronizar contrato con frontend en cada avance.

## Orden de lectura obligatorio

1. `CONTEXTO_ACTUAL_BACKEND_MULTIIGLESIA.md`
2. `ROADMAP_BACKEND_MULTIIGLESIA.md`
3. `TICKETS_BACKEND_MULTIIGLESIA.md`
4. `contexto_general.md`
5. `Contexto_actual_bd.md`

## Regla de activacion

Usar estos agentes cuando la tarea incluya:

- tenant/multiiglesia/multigrupo,
- superadmin y onboarding de instancias,
- login tenant-aware,
- bloqueo por setup inicial,
- parametros dinamicos por organizacion,
- cupos por rol,
- correo de credenciales temporales.

## Reglas de ejecucion

- No ejecutar fase completa en un solo cambio.
- Marcar `[~]` antes de tocar codigo.
- Marcar `[x]` con fecha y evidencia al cerrar.
- No abrir fase siguiente con bloqueadores P0 pendientes.
- No marcar `[x]` sin aprobacion explicita del owner funcional.

## Regla de mantenimiento

Actualizar estos archivos cuando cambien contrato, tablas, roles, seguridad o prioridades:

- `CONTEXTO_ACTUAL_BACKEND_MULTIIGLESIA.md`
- `ROADMAP_BACKEND_MULTIIGLESIA.md`
- `TICKETS_BACKEND_MULTIIGLESIA.md`

## Control de calidad documental

Antes de cerrar cambios en agentes backend:

- no hay contradiccion con agentes frontend,
- estado `[ ]/[~]/[x]` coincide con codigo real,
- dependencias entre tickets estan claras,
- se deja visible el siguiente ticket recomendado.
