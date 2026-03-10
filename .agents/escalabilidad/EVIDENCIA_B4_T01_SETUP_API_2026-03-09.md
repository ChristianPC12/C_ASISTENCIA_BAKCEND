# Evidencia B4-T01 - API Setup Inicial por Tenant

Fecha: 2026-03-09  
Entorno: `http://localhost/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND`

## Implementacion validada

1. Endpoints v2 de setup activos:
   - `GET /v2/setup/estado`
   - `PUT /v2/setup/cultos`
   - `PUT /v2/setup/metricas`
   - `PUT /v2/setup/procedencias`
   - `POST /v2/setup/finalizar`

2. Control de permisos:
   - Requiere rol `ADMIN` de tenant (`FORBIDDEN_ROLE` para no-ADMIN).
   - SUPERADMIN queda bloqueado de setup tenant.

3. Contrato de errores v2 de setup:
   - `SETUP_ALREADY_COMPLETED` (`403`)
   - `SETUP_INCONSISTENT` (`409`)
   - `VALIDATION_ERROR` (`400`)
   - `FORBIDDEN_ROLE` (`403`)

## Casos HTTP ejecutados

1. `POST /auth/login` con `b4t1_super`  
   Resultado: `200`.

2. Creacion de organizaciones de prueba y admin temporal por organizacion  
   Resultado: `201` en alta de organizaciones y `201` en admin temporal.

3. `GET /v2/setup/estado` con ADMIN tenant (org A)  
   Resultado: `200`, estado `PENDIENTE`.

4. `PUT /v2/setup/procedencias` (org A)  
   Resultado: `200`.

5. `PUT /v2/setup/cultos` (org A)  
   Resultado: `200`.

6. `PUT /v2/setup/metricas` (org A)  
   Resultado: `200`.

7. `POST /v2/setup/finalizar` (org A)  
   Resultado: `200`, estado `COMPLETO`.

8. `POST /v2/setup/finalizar` repetido (org A)  
   Resultado: `403`, `codigo=SETUP_ALREADY_COMPLETED`.

9. `PUT /v2/setup/cultos` despues de completar (org A)  
   Resultado: `403`, `codigo=SETUP_ALREADY_COMPLETED`.

10. `POST /v2/setup/finalizar` con setup incompleto (org B)  
    Resultado: `409`, `codigo=SETUP_INCONSISTENT`.

11. `PUT /v2/setup/procedencias` invalido (lista vacia, org B)  
    Resultado: `400`, `codigo=VALIDATION_ERROR`.

12. `GET /v2/setup/estado` con token SUPERADMIN  
    Resultado: `403`, `codigo=FORBIDDEN_ROLE`.

13. `POST /auth/logout` para tokens de prueba  
    Resultado: `200` en todos los casos.

## Limpieza

- Usuarios temporales eliminados:
  - `b4t1_super`
  - `b4t1_admin_a`
  - `b4t1_admin_b`
- Organizaciones temporales eliminadas: `B4T1 ORG%`
- Verificacion posterior en BD:
  - usuarios temporales = `0`
  - organizaciones temporales = `0`

