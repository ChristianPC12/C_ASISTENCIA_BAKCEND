# Tickets Backend Multiiglesia / Multigrupo

Fecha base: 2026-03-09  
Ultima actualizacion: 2026-03-10  
Estado global: B0..B7 cerradas

## Convenciones

- `[ ]` pendiente
- `[~]` en progreso
- `[x]` completado

Reglas:

- Solo un ticket `[~]` por fase.
- Cada cierre `[x]` incluye fecha y evidencia.
- Cambios de contrato deben sincronizar frontend.
- Cierre final sujeto a aprobacion owner funcional.

## Tablero rapido

| ID | Fase | Pri | Estado | Resumen | Dependencia |
|---|---|---|---|---|---|
| B0-T01 | B0 | P0 | [x] | Definir identidad tenant y login canonico | - |
| B0-T02 | B0 | P0 | [x] | Definir versionado API y contrato de errores | B0-T01 |
| B0-T03 | B0 | P0 | [x] | Definir arranque limpio preproduccion y rollback | B0-T02 |
| B1-T01 | B1 | P0 | [x] | Crear tablas base multitenant | B0-T03 |
| B1-T02 | B1 | P0 | [x] | Agregar `organizacion_id` e indices/FK | B1-T01 |
| B1-T03 | B1 | P0 | [x] | Semilla y validacion de arranque limpio por tenant inicial | B1-T02 |
| B2-T01 | B2 | P0 | [x] | Extender `AuthContext` y `AuthMiddleware` con tenant | B1-T03 |
| B2-T02 | B2 | P0 | [x] | Login/me/logout tenant-aware | B2-T01 |
| B2-T03 | B2 | P0 | [x] | Pruebas de auth tenant correcto/incorrecto | B2-T02 |
| B3-T01 | B3 | P0 | [x] | Crear rol `SUPERADMIN` y permisos base | B2-T03 |
| B3-T02 | B3 | P0 | [x] | API alta/edicion de organizaciones | B3-T01 |
| B3-T03 | B3 | P0 | [x] | API admin temporal + correo opcional | B3-T02 |
| B4-T01 | B4 | P0 | [x] | API setup inicial por tenant | B3-T03 |
| B4-T02 | B4 | P0 | [x] | Guard central de bloqueo por setup pendiente | B4-T01 |
| B4-T03 | B4 | P0 | [x] | Validaciones de dependencias entre metricas | B4-T01 |
| B5-T01 | B5 | P1 | [x] | Modelo dinamico de metricas por registro | B4-T03 |
| B5-T02 | B5 | P1 | [x] | Estadisticas/comparaciones dinamicas | B5-T01 |
| B5-T03 | B5 | P1 | [x] | Presentaciones dinamicas y consultas tenant-aware | B5-T02 |
| B6-T01 | B6 | P1 | [x] | Configurar cupos por rol por tenant | B5-T03 |
| B6-T02 | B6 | P1 | [x] | Enforcement de cupos en UsuarioService | B6-T01 |
| B6-T03 | B6 | P1 | [x] | Expiracion automatica admin temporal (5 dias) | B6-T02 |
| B7-T01 | B7 | P0 | [x] | Suite de pruebas de aislamiento multi-tenant | B6-T03 |
| B7-T02 | B7 | P0 | [x] | Pruebas de migracion + rollback en staging | B7-T01 |
| B7-T03 | B7 | P0 | [x] | Checklist final de salida y gate tecnico | B7-T02 |

## Evidencias principales por bloque

### B0/B1/B2/B3/B4/B5/B6 (2026-03-09)

Evidencia consolidada en:

- `.agents/escalabilidad/CONTEXTO_ACTUAL_BACKEND_MULTIIGLESIA.md`
- `.agents/escalabilidad/ROADMAP_BACKEND_MULTIIGLESIA.md`
- archivos `EVIDENCIA_B2_*`, `EVIDENCIA_B3_*`, `EVIDENCIA_B4_*`, `EVIDENCIA_B5_*`, `EVIDENCIA_B6_*`

### [x] B7-T01/B7-T02/B7-T03

Evidencia:

- `.agents/escalabilidad/EVIDENCIA_B7_T01_AISLAMIENTO_MULTI_TENANT_2026-03-09.md`
- `.agents/escalabilidad/EVIDENCIA_B7_T02_MIGRACION_ROLLBACK_STAGING_2026-03-09.md`
- `.agents/escalabilidad/EVIDENCIA_B7_T03_CHECKLIST_SALIDA_2026-03-10.md`
- `.agents/escalabilidad/CHECKLIST_SALIDA_PRODUCCION_B7_T03.md`

Soporte operativo/documental B7-T03:

- `.agents/escalabilidad/PLAN_BACKUP_CUTOVER_ROLLBACK_B7.md`
- `.agents/escalabilidad/MONITOREO_POST_CUTOVER_B7.md`
- `.agents/escalabilidad/MANUAL_INCIDENTES_B7.md`

## Reglas funcionales obligatorias

- SUPERADMIN no consume endpoints operativos de instancia.
- ADMIN/SECRETARIO no operan fuera de su tenant.
- Setup pendiente bloquea modulos operativos.
- Procedencias configurables por tenant (max 10).
- GRUPO -> IGLESIA conserva la misma cuenta.
- ADMIN temporal vence a 5 dias.

## Gate final

- Backend: listo para preproduccion nacional.
- Produccion final: condicionada a ejecucion operativa de cutover aprobada por owner.

