# Evidencia B3-T02 - API v2 Alta/Edicion de Organizaciones

Fecha: 2026-03-09  
Entorno: `http://localhost/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND`

## Implementacion validada

1. Rutas v2 activas para superadmin:
   - `GET /v2/superadmin/organizaciones`
   - `POST /v2/superadmin/organizaciones`
   - `PUT /v2/superadmin/organizaciones/{organizacion_id}`

2. Guard de rol:
   - Solo `SUPERADMIN` puede acceder.
   - Rol no permitido responde `403` con `codigo=FORBIDDEN_ROLE`.

3. Contrato v2 en estas rutas:
   - Exitos con `meta.version=v2`.
   - Errores con `codigo` canonico (`VALIDATION_ERROR`, `CONFLICT_DUPLICATE`, `FORBIDDEN_ROLE`).

4. Regla de negocio GRUPO -> IGLESIA:
   - La edicion conserva la misma organizacion y el mismo `codigo_instancia`.

## Casos HTTP ejecutados

1. `POST /auth/login` con usuario temporal `b3t2_super`  
   Resultado: `200`, `rol=SUPERADMIN`.

2. `GET /v2/superadmin/organizaciones?page=1&limit=5`  
   Resultado: `200`, `exito=true`, `meta.version=v2`.

3. `POST /v2/superadmin/organizaciones`  
   Payload: `campo=AN`, `tipo_organizacion=GRUPO`, `nombre_organizacion=B3T2 ORG ALFA`, correo opcional  
   Resultado: `201`, `exito=true`, `codigo_instancia=ANGB3T2001`.

4. `PUT /v2/superadmin/organizaciones/{id}`  
   Cambio: `GRUPO -> IGLESIA`, nombre editado  
   Resultado: `200`, `exito=true`, `tipo_organizacion=IGLESIA`, `codigo_instancia` se mantiene.

5. `POST /v2/superadmin/organizaciones` (duplicado negocio)  
   Resultado: `409`, `exito=false`, `codigo=CONFLICT_DUPLICATE`, `meta.version=v2`.

6. `POST /v2/superadmin/organizaciones` (payload invalido)  
   Resultado: `400`, `exito=false`, `codigo=VALIDATION_ERROR`, `meta.version=v2`.

7. `GET /v2/superadmin/organizaciones` con token de `ADMIN` local  
   Resultado: `403`, `exito=false`, `codigo=FORBIDDEN_ROLE`, `meta.version=v2`.

8. `POST /auth/logout` (superadmin y admin temporal)  
   Resultado: `200` en ambos casos.

9. Smoke de regresion auth v1 (`admin`) despues de cambios v2  
   Resultado: `POST /auth/login` `200`, `GET /auth/me` `200`, `POST /auth/logout` `200`.

## Limpieza de datos temporales

- Usuarios de prueba eliminados: `b3t2_super`, `b3t2_admin`.
- Organizaciones temporales eliminadas: prefijo `B3T2 ORG%`.
- Verificacion posterior: conteo en BD = `0` para usuarios/organizaciones temporales.
