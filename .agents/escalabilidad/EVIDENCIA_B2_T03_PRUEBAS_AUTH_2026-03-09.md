# Evidencia B2-T03 - Pruebas Auth Tenant Correcto/Incorrecto

Fecha: 2026-03-09  
Entorno: `http://localhost/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND`

## Casos ejecutados

1. `POST /auth/login` con usuario tenant activo (`b2t3_ok`)  
   Resultado: `200`, `exito=true`, `tenant.codigo_instancia=B2T3ACT1`.

2. `GET /auth/me` con token valido del caso 1  
   Resultado: `200`, `exito=true`, `tenant.codigo_instancia=B2T3ACT1`.

3. `POST /auth/logout` con token valido del caso 1  
   Resultado: `200`, `exito=true`, mensaje `Sesion cerrada correctamente.`

4. `GET /auth/me` luego de logout (token revocado)  
   Resultado: `401`, `exito=false`, mensaje `Token invalido o revocado.`

5. `POST /auth/login` con usuario de tenant inactivo (`b2t3_inac`)  
   Resultado: `403`, `exito=false`, mensaje `La organizacion de esta cuenta se encuentra inactiva.`

6. `POST /auth/login` con usuario sin organizacion (`b2t3_noorg`)  
   Resultado: `403`, `exito=false`, mensaje `La cuenta autenticada no tiene una organizacion valida asignada. Contacta al administrador.`

7. `GET /auth/me` con token cuyo `organizacion_id` fue alterado manualmente en BD antes de consumir `me`  
   Resultado: `200`, `exito=true`, `tenant.codigo_instancia=B2T3ACT1`.  
   Verificacion de sincronizacion token:
   - antes: `organizacion_id=8`
   - despues: `organizacion_id=7`

## Nota de limpieza

- Los usuarios y organizaciones temporales de prueba (`b2t3_ok`, `b2t3_inac`, `b2t3_noorg`, `B2T3ACT1`, `B2T3INA1`) se eliminaron al finalizar las pruebas.
