# Decisiones Canonicas F0/B0-T01 - Identidad Tenant y Login

Fecha de cierre: 2026-03-09  
Estado: Aprobado para implementacion de siguientes tickets (sin cambios de codigo aun)

## 1) Objetivo de esta decision

Cerrar el contrato base de identidad de instancia y login canonico multi-tenant
para desbloquear F0-T02/B0-T02.

## 2) Identidad canonica de instancia

Toda instancia nacional queda definida por:

- `campo` (AN, ACS, MC),
- `tipo_organizacion` (`IGLESIA` o `GRUPO`),
- `nombre_organizacion` (editable),
- `codigo_instancia` (identificador publico de instancia).

Decision:

- `codigo_instancia` identifica la instancia, pero NO se pedira en login de usuario final.
- `campo`, `tipo_organizacion` y `nombre_organizacion` son metadatos de negocio.
- Cambio de nombre o cambio `GRUPO -> IGLESIA` NO crea nueva cuenta ni cambia el `codigo_instancia`.

## 3) Convenio de `codigo_instancia`

- Formato: `^[A-Z0-9]{6,12}$`
- Normalizacion de entrada: `trim()` + `uppercase`.
- Unicidad: global nacional.
- Mutabilidad: inmutable despues de crear la instancia.
- Generacion: por backend en alta de instancia (superadmin), sin semantica dependiente de nombre.

## 4) Login canonico (v2 planificado)

Payload de login multi-tenant:

```json
{
  "usuario": "admin_local",
  "password": "********"
}
```

Reglas de validacion de entrada:

- `usuario` requerido, 3..50.
- `password` requerido.

Regla clave:

- `usuario` debe ser unico a nivel nacional (global), no por tenant.

## 5) Estrategia de lookup de tenant en login

Orden canonico:

1. Buscar usuario global por `usuario`.
2. Validar `password`.
3. Resolver tenant desde `organizacion_id` del usuario autenticado.
4. Si tenant esta inactivo: bloquear login.

## 6) Error estandar minimo para tenant

Contrato base acordado para casos de tenant:

- Tenant inactivo: `403` con codigo `TENANT_INACTIVE`.

Nota:

- El contrato completo de errores (incluyendo auth y setup) se cierra en F0-T02/B0-T02.
- Si en el futuro se cambia a usuario unico por tenant, se debe reabrir F0/B0.

## 7) Regla de compatibilidad con estado actual

- El sistema actual sigue en single-tenant y login `{ usuario, password }`.
- Esta decision NO implica que el cambio ya este implementado.
- La implementacion tecnica empieza en tickets de fases siguientes segun dependencia oficial.

## 8) Sincronizacion obligatoria

Esta decision se replica en frontend en:

- `.agents/react-doctor/DECISIONES_F0_B0_T01_IDENTIDAD_LOGIN.md`

Y se refleja en:

- `TICKETS_ESCALABILIDAD_NACIONAL.md`
- `TICKETS_BACKEND_MULTIIGLESIA.md`
- `CONTEXTO_ACTUAL.md`
- `CONTEXTO_ACTUAL_BACKEND_MULTIIGLESIA.md`

## 9) Aprobacion de owner

- Aprobado por owner funcional (Christian) el 2026-03-09.
