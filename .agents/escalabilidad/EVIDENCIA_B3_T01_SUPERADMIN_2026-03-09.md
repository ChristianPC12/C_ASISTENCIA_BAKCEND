# Evidencia B3-T01 - Rol SUPERADMIN y Permisos Base

Fecha: 2026-03-09  
Entorno: `http://localhost/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND`

## Implementacion validada

1. Migracion aplicada:
   - `migracion_09032026_superadmin_rol_base.sql`
   - Resultado: rol `SUPERADMIN` creado en tabla `roles` (`id=3`).

2. Permiso base aplicado:
   - SUPERADMIN autenticado queda bloqueado en endpoints operativos (`/cultos`, `/asistencias`, `/presentaciones`, `/usuarios`).
   - Mensaje de bloqueo: `Acceso denegado. SUPERADMIN solo puede operar en el modulo de administracion nacional.`

3. Sesion global validada:
   - SUPERADMIN puede hacer `login` y `me` sin `organizacion_id` asociado.
   - `tenant.organizacion_id` en respuesta: `null`.

## Casos HTTP ejecutados

1. `POST /auth/login` con `superadmin_b3t1`  
   Resultado: `200`, `rol=SUPERADMIN`, `tenant.organizacion_id=null`.

2. `GET /auth/me` con token SUPERADMIN  
   Resultado: `200`, `rol=SUPERADMIN`, `tenant.organizacion_id=null`.

3. `GET /asistencias` con token SUPERADMIN  
   Resultado: `403` (bloqueado por permiso base).

4. `GET /usuarios` con token SUPERADMIN  
   Resultado: `403` (bloqueado por permiso base).

5. `POST /auth/logout` con token SUPERADMIN  
   Resultado: `200`.

6. Smoke de regresion de tenant regular (posterior a cambio SUPERADMIN)  
   Resultado: login/me/logout de usuario tenant activo en `200`; login de tenant inactivo en `403`.

## Limpieza

- Usuario temporal de prueba `superadmin_b3t1` eliminado.
- Rol `SUPERADMIN` se mantiene en BD (esperado por ticket B3-T01).
