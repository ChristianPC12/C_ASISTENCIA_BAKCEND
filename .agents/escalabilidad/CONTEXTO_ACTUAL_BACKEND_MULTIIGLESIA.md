# Contexto Actual Backend Multiiglesia (2026-03-10)

## Estado backend vigente

- Backend: PHP 8 sin framework (Router -> Controller -> Service -> DAO).
- Base: `iglesia_asistencia` en modo multi-tenant.
- Roles activos: `SUPERADMIN`, `ADMIN`, `SECRETARIO`.
- Estado de ejecucion: B0..B7 cerradas a nivel tecnico.

## Resultado funcional consolidado

- Aislamiento tenant activo por `organizacion_id`.
- Auth tenant-aware en login/me/logout y middleware.
- API v2 de superadmin activa (alta/edicion organizaciones + admin temporal).
- API v2 setup activa (estado/cultos/metricas/procedencias/finalizar).
- Guard central `SETUP_REQUIRED` en modulos operativos.
- Dominio dinamico de metricas en asistencia/estadisticas/presentaciones.
- Cupos por rol por tenant + enforcement en usuarios.
- Expiracion automatica de ADMIN temporal a 5 dias.

## Estado de hardening (B7)

- B7-T01: suite aislamiento runtime ejecutada OK.
- B7-T02: migracion/rollback staging ejecutado OK.
- B7-T03: checklist final y runbooks operativos documentados.

Evidencia B7:

- `.agents/escalabilidad/EVIDENCIA_B7_T01_AISLAMIENTO_MULTI_TENANT_2026-03-09.md`
- `.agents/escalabilidad/EVIDENCIA_B7_T02_MIGRACION_ROLLBACK_STAGING_2026-03-09.md`
- `.agents/escalabilidad/EVIDENCIA_B7_T03_CHECKLIST_SALIDA_2026-03-10.md`
- `.agents/escalabilidad/CHECKLIST_SALIDA_PRODUCCION_B7_T03.md`

## Cambios recientes (2026-03-10)

- CORS endurecido:
  - `Config/Global.php` usa `CORS_ALLOWED_ORIGINS` configurable (sin `*` por defecto).
  - `Middleware/CorsMiddleware.php` valida origen y bloquea preflight no permitido.
- Lint completo de backend:
  - `php -l` OK en todo el proyecto.
- Re-ejecucion de scripts B7:
  - aislamiento: OK
  - migracion/rollback staging: OK

## Runbooks operativos disponibles

- `.agents/escalabilidad/PLAN_BACKUP_CUTOVER_ROLLBACK_B7.md`
- `.agents/escalabilidad/MONITOREO_POST_CUTOVER_B7.md`
- `.agents/escalabilidad/MANUAL_INCIDENTES_B7.md`

## Riesgos residuales

- Cutover productivo depende de ejecucion real de ventana de mantenimiento.
- Token bearer sigue en modelo stateless (seguridad depende de higiene cliente/servidor).

## Decision de gate

- GO tecnico para preproduccion.
- Produccion final condicionada a aprobacion operativa del owner.

