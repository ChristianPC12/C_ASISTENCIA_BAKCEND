# Roadmap Backend Multiiglesia / Multigrupo (IASD CR)

Fecha base: 2026-03-09  
Ultima actualizacion: 2026-03-10  
Estado global backend: B0..B7 cerradas

## 1) Objetivo tecnico

Backend tenant-aware nacional para multiples organizaciones (IGLESIA/GRUPO), con seguridad de aislamiento y onboarding centralizado.

## 2) Estado por fase

| Fase | Pri | Estado | Entrega principal |
|---|---|---|---|
| B0 - Contrato y decisiones base | P0 | [x] | Identidad tenant, login canonico y versionado |
| B1 - Migraciones base multitenant | P0 | [x] | Estructura, relaciones, seed y sanity checks |
| B2 - Auth y contexto tenant-aware | P0 | [x] | Sesion con tenant y middleware estricto |
| B3 - API de superadmin | P0 | [x] | Alta/edicion organizaciones + admin temporal |
| B4 - Setup inicial y bloqueo operativo | P0 | [x] | API setup + guard `SETUP_REQUIRED` |
| B5 - Dominio dinamico de registro/reportes | P1 | [x] | Metricas dinamicas tenant-aware |
| B6 - Cupos y ciclo de vida de usuarios | P1 | [x] | Cupos por rol + expiracion admin temporal |
| B7 - Hardening y salida | P0 | [x] | Suites, checklist final, runbooks operativos |

## 3) Hitos B7 cerrados

### B7-T01

- suite automatizada de aislamiento multitenant.
- evidencia: `.agents/escalabilidad/EVIDENCIA_B7_T01_AISLAMIENTO_MULTI_TENANT_2026-03-09.md`.

### B7-T02

- prueba automatizada de migracion/rollback en staging temporal.
- evidencia: `.agents/escalabilidad/EVIDENCIA_B7_T02_MIGRACION_ROLLBACK_STAGING_2026-03-09.md`.

### B7-T03

- checklist final de salida consolidado.
- CORS endurecido por allowlist configurable.
- runbooks de backup/cutover/monitoreo/incidentes documentados.
- evidencia:
  - `.agents/escalabilidad/EVIDENCIA_B7_T03_CHECKLIST_SALIDA_2026-03-10.md`
  - `.agents/escalabilidad/CHECKLIST_SALIDA_PRODUCCION_B7_T03.md`

## 4) Riesgos residuales

- ejecucion operativa real de cutover y rollback depende del owner/infra.
- monitoreo post-cutover debe activarse en ventana productiva.

## 5) Decision de salida

- backend listo tecnicamente para preproduccion nacional.
- produccion final requiere validacion operativa de ventana y backup real.

