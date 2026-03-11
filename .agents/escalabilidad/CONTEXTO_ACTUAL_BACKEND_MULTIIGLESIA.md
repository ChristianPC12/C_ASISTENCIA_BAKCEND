# Contexto Actual Backend Multiiglesia (2026-03-11)

## Estado backend vigente

- Backend: PHP 8 sin framework (Router -> Controller -> Service -> DAO).
- Base: `iglesia_asistencia` en modo multi-tenant.
- Roles activos: `SUPERADMIN`, `ADMIN`, `SECRETARIO`.
- Estado de ejecucion: B0..B7 cerradas a nivel tecnico.

## Resultado funcional consolidado

- Aislamiento tenant activo por `organizacion_id`.
- Auth tenant-aware en login/me/logout y middleware.
- API v2 de superadmin consolidada:
  - organizaciones (alta/listado/edicion),
  - admin temporal,
  - catalogos globales de campos y distritos (CRUD).
- API v2 setup activa (estado/cultos/metricas/procedencias/finalizar).
- Guard central `SETUP_REQUIRED` en modulos operativos.
- Dominio dinamico de metricas en asistencia/estadisticas/presentaciones.
- Cupos por rol por tenant + enforcement en usuarios.
- Expiracion automatica de ADMIN temporal a 5 dias.

## Superadmin consolidado (2026-03-11)

- Organizacion requiere `campo` y `distrito` validos y activos al crear/editar.
- Listado de organizaciones devuelve `distrito_codigo` y `distrito_nombre`.
- Endpoints de catalogo v2 activos:
  - `GET/POST/PUT/DELETE /v2/superadmin/campos`
  - `GET/POST/PUT/DELETE /v2/superadmin/distritos`
- Logica de estado de admin ajustada para distinguir correctamente `ADMIN expirado` vs `Sin ADMIN`.
- Envio opcional de credenciales por Brevo integrado en `crear admin temporal`.

## Configuracion de correo Brevo

- Configurable por constantes/env:
  - `BREVO_API_KEY`
  - `BREVO_SENDER_EMAIL`
  - `BREVO_SENDER_NAME`
  - `BREVO_API_URL`
  - `BREVO_TIMEOUT_SECONDS`
- `Config/Global.php` permite override local via `Config/Global.local.php`.
- Respuesta de correo incluye `detalle` y, cuando aplica, `message_id`.

## Estado de hardening (B7)

- B7-T01: suite aislamiento runtime ejecutada OK.
- B7-T02: migracion/rollback staging ejecutado OK.
- B7-T03: checklist final y runbooks operativos documentados.

## Validaciones tecnicas recientes

- `php -l` OK en archivos backend modificados.
- Contratos v2 usados por frontend sincronizados para superadmin.

## Runbooks operativos disponibles

- `.agents/escalabilidad/PLAN_BACKUP_CUTOVER_ROLLBACK_B7.md`
- `.agents/escalabilidad/MONITOREO_POST_CUTOVER_B7.md`
- `.agents/escalabilidad/MANUAL_INCIDENTES_B7.md`

## Riesgos residuales

- Cutover productivo depende de ejecucion real de ventana de mantenimiento.
- Token bearer sigue en modelo stateless (seguridad depende de higiene cliente/servidor).
- Entregabilidad de correo depende de dominio/remitente correctamente verificado en Brevo.

## Decision de gate

- GO tecnico para preproduccion.
- Produccion final condicionada a aprobacion operativa del owner.
