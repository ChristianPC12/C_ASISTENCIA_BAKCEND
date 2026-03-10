# Manual de Incidentes y Escalamiento B7

Fecha: 2026-03-10  
Ambito: operacion nacional multi-tenant

## 1) Clasificacion

- P0: auth caido, fuga entre tenants, perdida de disponibilidad critica.
- P1: errores funcionales severos con workaround limitado.
- P2: errores puntuales sin bloqueo operativo total.

## 2) Cadena de escalamiento

1. Responsable on-call tecnico.
2. Lider tecnico backend/frontend.
3. Owner funcional (decision de negocio y comunicacion).

## 3) Runbook rapido por tipo

### Auth degradado (401/429 anomalo)

1. Verificar estado de `AuthService`, middleware y DB.
2. Validar reloj/expiraciones.
3. Mitigar y comunicar impacto.

### 403 no esperado

1. Revisar rol del usuario y tenant.
2. Revisar `RoleMiddleware` y guard setup.
3. Validar cambios recientes de rutas.

### Posible fuga de datos

1. Activar protocolo P0 inmediato.
2. Congelar operaciones afectadas.
3. Auditar consultas por `organizacion_id`.
4. Ejecutar rollback si no hay contencion inmediata.

## 4) Registro minimo obligatorio por incidente

- fecha/hora inicio y cierre,
- severidad y modulo impactado,
- sintomas y alcance,
- accion aplicada,
- decision final (mitigado/rollback),
- lecciones aprendidas.

