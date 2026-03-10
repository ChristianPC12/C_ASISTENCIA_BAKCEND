# Evidencia B4-T02 - Guard Central de Setup Pendiente

Fecha: 2026-03-09  
Entorno: `http://localhost/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND`

## Implementacion validada

1. Guard central implementado en middleware:
   - `RoleMiddleware::requireSetupCompletedForOperative()`
2. Verificacion de estado setup centralizada:
   - `SetupDAO::isOperativeBlocked(int $organizacionId)`
3. Enlace del guard en rutas operativas:
   - `GET /cultos`
   - `GET/POST/PUT/DELETE /asistencias*`
   - `GET/POST /presentaciones*`
   - `GET/POST/PUT/DELETE /usuarios*`
4. Contrato de error aplicado:
   - HTTP `403`
   - `codigo = SETUP_REQUIRED`
   - mensaje orientado a completar setup inicial.

## Casos HTTP ejecutados

1. Login `SUPERADMIN` temporal de prueba  
   Resultado: `200`.

2. Alta de organizacion v2 de prueba y creacion de ADMIN temporal  
   Resultado: `201` y `201`.

3. Login ADMIN temporal de la organizacion pendiente  
   Resultado: `200`.

4. Modulos operativos con setup pendiente  
   - `GET /cultos` -> `403 SETUP_REQUIRED`  
   - `GET /asistencias` -> `403 SETUP_REQUIRED`  
   - `GET /presentaciones` -> `403 SETUP_REQUIRED`  
   - `GET /usuarios` -> `403 SETUP_REQUIRED`

5. Completar setup v2 (procedencias, cultos, metricas, finalizar)  
   - `PUT /v2/setup/procedencias` -> `200`  
   - `PUT /v2/setup/cultos` -> `200`  
   - `PUT /v2/setup/metricas` -> `200`  
   - `POST /v2/setup/finalizar` -> `200`

6. Modulos operativos luego de setup completo  
   - `GET /cultos` -> `200`  
   - `GET /asistencias` -> `200`  
   - `GET /presentaciones` -> `200`  
   - `GET /usuarios` -> `200`

## Limpieza

- Logout de tokens temporales ejecutado (`/auth/logout`).
- Usuarios temporales eliminados.
- Organizacion temporal eliminada.
- Verificacion posterior en BD:
  - `usuarios_temp = 0`
  - `organizaciones_temp = 0`
