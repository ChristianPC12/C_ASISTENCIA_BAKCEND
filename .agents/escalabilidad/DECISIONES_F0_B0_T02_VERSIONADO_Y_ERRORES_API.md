# Decisiones Canonicas F0/B0-T02 - Versionado API y Contrato de Errores

Fecha de cierre: 2026-03-09  
Estado: Aprobado para implementacion (sin cambios de codigo funcional aun)

## 1) Objetivo

Definir un contrato API v2 estable para auth/superadmin/setup, sin romper el API actual.

## 2) Regla de versionado

- Version oficial multi-tenant: prefijo `/v2`.
- API actual sin prefijo (`/auth`, `/usuarios`, `/asistencias`, etc.) se mantiene como `v1 legacy`.
- No se mezcla contrato v1 y v2 en la misma ruta.
- Cualquier feature nueva de multiiglesia/multigrupo se publica solo en `/v2`.

## 3) Compatibilidad transitoria

- Frontend actual continua consumiendo v1 mientras no se complete F3/B2.
- v1 y v2 pueden coexistir durante la migracion por fases.
- El corte final de v1 se decide en F7/B7 con checklist de salida nacional.

## 4) Envoltorio JSON canonico

Exito (`2xx`):

```json
{
  "exito": true,
  "mensaje": "Operacion exitosa.",
  "datos": {},
  "meta": {
    "version": "v2",
    "request_id": "req_abc123"
  }
}
```

Error (`4xx/5xx`):

```json
{
  "exito": false,
  "codigo": "AUTH_INVALID_CREDENTIALS",
  "mensaje": "Credenciales invalidas.",
  "detalles": {},
  "meta": {
    "version": "v2",
    "request_id": "req_abc123"
  }
}
```

Notas:

- Se conserva `exito` + `mensaje` para continuidad con backend actual.
- En v2, `codigo` es obligatorio en respuestas de error.
- `datos`, `detalles` y `meta` son opcionales segun contexto.

## 5) Contrato auth v2

### POST `/v2/auth/login` (publico)

Request:

```json
{
  "usuario": "admin_local",
  "password": "********"
}
```

Response `200`:

- `datos.token`
- `datos.usuario`
- `datos.tenant` (`organizacion_id`, `codigo_instancia`, `tipo_organizacion`, `nombre_organizacion`, `campo`)
- `datos.session` (`expira_en`, `idle_timeout_minutos`)

Errores esperados:

- `400 VALIDATION_ERROR`
- `401 AUTH_INVALID_CREDENTIALS`
- `403 TENANT_INACTIVE`
- `429 AUTH_RATE_LIMITED`
- `500 INTERNAL_ERROR`

### GET `/v2/auth/me` (Bearer)

Response `200`:

- `datos.usuario`
- `datos.tenant`
- `datos.permisos` (incluye contexto de rol)
- `datos.setup_estado` (`pendiente|completo`) para no-superadmin

Errores esperados:

- `401 AUTH_TOKEN_REQUIRED|AUTH_TOKEN_INVALID|AUTH_SESSION_EXPIRED`
- `500 INTERNAL_ERROR`

### POST `/v2/auth/logout` (Bearer)

Response `200`: sesion revocada.

Errores esperados:

- `401 AUTH_TOKEN_REQUIRED|AUTH_TOKEN_INVALID`
- `500 INTERNAL_ERROR`

## 6) Contrato superadmin v2

### GET `/v2/superadmin/organizaciones` (solo `SUPERADMIN`)

- Lista paginada con filtros (`campo`, `tipo`, `estado`, `q`, `page`, `limit`).

### POST `/v2/superadmin/organizaciones` (solo `SUPERADMIN`)

Request minimo:

```json
{
  "campo": "AN",
  "tipo_organizacion": "GRUPO",
  "nombre_organizacion": "Guayabo Centro",
  "correo_contacto": "opcional@dominio.com"
}
```

Response `201`:

- `datos.organizacion_id`
- `datos.codigo_instancia`

### PUT `/v2/superadmin/organizaciones/{organizacion_id}` (solo `SUPERADMIN`)

- Permite edicion de `tipo_organizacion` y `nombre_organizacion` sin crear nueva cuenta.

### POST `/v2/superadmin/organizaciones/{organizacion_id}/admin-temporal` (solo `SUPERADMIN`)

- Crea ADMIN temporal inicial (vigencia se define operacionalmente en B6/F7).

Errores esperados (superadmin):

- `400 VALIDATION_ERROR`
- `401 AUTH_*`
- `403 FORBIDDEN_ROLE`
- `404 RESOURCE_NOT_FOUND`
- `409 CONFLICT_DUPLICATE`
- `500 INTERNAL_ERROR`

## 7) Contrato setup inicial v2

### GET `/v2/setup/estado` (tenant autenticado)

- Devuelve `pendiente|completo` y faltantes de configuracion.

### PUT `/v2/setup/cultos`

- Configura dia/hora de cultos por tenant.

### PUT `/v2/setup/metricas`

- Configura campos habilitados/obligatorios y dependencias.

### PUT `/v2/setup/procedencias`

- Configura procedencias editables por tenant (min 1, max 10).

### POST `/v2/setup/finalizar`

- Valida coherencia total y marca setup como `completo`.

Errores esperados (setup):

- `400 VALIDATION_ERROR`
- `401 AUTH_*`
- `403 SETUP_ALREADY_COMPLETED|FORBIDDEN_ROLE`
- `409 SETUP_INCONSISTENT`
- `500 INTERNAL_ERROR`

## 8) Guard de bloqueo por setup pendiente (contrato de error)

Cuando un tenant con setup `pendiente` accede modulos operativos, backend responde:

- HTTP `403`
- `codigo = SETUP_REQUIRED`
- mensaje orientado a completar configuracion inicial.

## 9) Matriz canonica de codigos HTTP

- `200` operacion exitosa.
- `201` recurso creado.
- `204` sin cuerpo (opcional para ciertos delete/acciones idempotentes).
- `400` error de validacion/sintaxis.
- `401` autenticacion/sesion invalida.
- `403` autorizado pero no permitido (rol/estado tenant/setup).
- `404` recurso no existe.
- `409` conflicto de negocio/duplicado/inconsistencia.
- `429` rate limit.
- `500` error interno.

## 10) Alineacion con codigo vigente (v1)

- Backend actual responde `JsonResponse` con `{ exito, mensaje, datos? }`.
- Auth actual usa:
  - `400` validacion,
  - `401` credenciales o sesion/token invalido,
  - `429` exceso de intentos.
- Esta decision mantiene v1 intacto y define la extension v2 sin contradiccion.

Regla funcional cerrada en T01:

- login de usuario final: `usuario + password` (sin `codigo_instancia`).
- tenant se resuelve internamente por el usuario autenticado.

## 11) Sincronizacion obligatoria

Este contrato se replica en frontend en:

- `.agents/react-doctor/DECISIONES_F0_B0_T02_VERSIONADO_Y_ERRORES_API.md`

Y se refleja en:

- `TICKETS_ESCALABILIDAD_NACIONAL.md`
- `TICKETS_BACKEND_MULTIIGLESIA.md`
- `CONTEXTO_ACTUAL.md`
- `CONTEXTO_ACTUAL_BACKEND_MULTIIGLESIA.md`
- `prompt_frontend.md`

## 12) Aprobacion de owner

- Aprobado por owner funcional (Christian) el 2026-03-09.
