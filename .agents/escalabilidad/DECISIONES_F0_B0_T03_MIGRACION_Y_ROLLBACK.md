# Decisiones F0/B0-T03 - Arranque Limpio Preproduccion, Rollback y Auditoria Minima

Fecha de propuesta: 2026-03-09  
Estado: Aprobado por owner (2026-03-09)

## 1) Objetivo

Preparar el sistema para produccion nacional con una base limpia, sin arrastrar
datos legacy de desarrollo.

## 2) Decision base del owner

- Los datos actuales pueden eliminarse.
- No se requiere conservar historial de la etapa previa.

Impacto:

- T03 deja de ser "migracion compleja de datos".
- T03 pasa a ser "reinicio controlado + estructura multitenant + tenant inicial".

## 3) Enfoque aprobado para T03

1. Respaldar base actual solo por seguridad operativa.
2. Limpiar datos funcionales actuales (usuarios operativos, asistencias, presentaciones, tokens).
3. Aplicar estructura multitenant nueva.
4. Crear tenant inicial oficial para arranque.
5. Crear usuario administrador inicial del tenant.
6. Validar que login, aislamiento y setup funcionen desde cero.

## 4) Tenant inicial de arranque

Propuesta:

- `organizacion_id`: 1
- `tipo_organizacion`: `IGLESIA`
- `nombre_organizacion`: `INSTANCIA_INICIAL`
- `codigo_instancia`: `BASECR01` (identificador de instancia, no visible en login final)
- `campo`: `AN` (ajustable luego por superadmin)
- `estado`: activo

## 5) Scripts esperados (idempotentes)

Orden propuesto:

1. `migracion_09032026_multitenant_estructura.sql`
2. `migracion_09032026_multitenant_seed_base.sql`
3. `migracion_09032026_multitenant_indices_validaciones.sql`
4. `migracion_09032026_multitenant_sanity_checks.sql`

Regla:

- Deben poder correrse mas de una vez sin romper datos ni duplicar registros semilla.

## 6) Rollback simple para preproduccion

Como no se conservara dataset actual:

- rollback tecnico: restaurar backup previo de base,
- rollback de codigo: volver al tag/commit anterior,
- rollback documental: volver estado de tickets y decisiones.

## 7) Auditoria minima obligatoria

Eventos minimos:

- `TENANT_CREATED`
- `TENANT_UPDATED`
- `TENANT_BLOCKED_ACCESS`
- `SETUP_BLOCKED_ACCESS`
- `TEMP_ADMIN_CREDENTIALS_SENT`
- `DEPLOY_STARTED`
- `DEPLOY_FINISHED`

Campos minimos por evento:

- `timestamp`
- `actor`
- `organizacion_id` (si aplica)
- `accion`
- `resultado`
- `detalle_resumido`

## 8) Checklist de aceptacion T03 (simple)

- base limpia confirmada,
- tenant inicial creado y activo,
- admin inicial creado,
- scripts idempotentes validados,
- rollback probado en entorno local/staging,
- eventos minimos de auditoria definidos,
- sincronizacion frontend/backend actualizada.

## 9) Estado de cierre

- Documento aprobado por owner funcional.
- T03 puede marcarse en `[x]` con esta evidencia.

## 10) Sincronizacion obligatoria

Documento espejo frontend:

- `.agents/react-doctor/DECISIONES_F0_B0_T03_MIGRACION_Y_ROLLBACK.md`

## 11) Aprobacion de owner

- Aprobado por Christian el 2026-03-09.
