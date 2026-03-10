# Evidencia B3-T03 - API Admin Temporal + Correo Opcional

Fecha: 2026-03-09  
Entorno: `http://localhost/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND`

## Implementacion validada

1. Endpoint v2 activo:
   - `POST /v2/superadmin/organizaciones/{organizacion_id}/admin-temporal`

2. Reglas implementadas:
   - Solo `SUPERADMIN` puede invocar el endpoint.
   - Crea usuario `ADMIN` ligado a la organizacion indicada.
   - Credencial temporal con vencimiento a 5 dias (`expira_en`).
   - Evita duplicado de `usuario` global.
   - Evita crear segundo `ADMIN` activo en la misma organizacion.

3. Correo opcional:
   - Si `enviar_correo=true`, intenta envio via Brevo.
   - Si Brevo no esta configurado, no rompe la creacion; responde `correo.enviado=false` con detalle.

## Casos HTTP ejecutados

1. `POST /auth/login` con usuario temporal `b3t3_super`  
   Resultado: `200`, `exito=true`.

2. `POST /v2/superadmin/organizaciones` (org de prueba)  
   Resultado: `201`, `exito=true`.

3. `POST /v2/superadmin/organizaciones/{id}/admin-temporal`  
   Payload: `nombre_completo`, `usuario`, `enviar_correo=true`  
   Resultado: `201`, `exito=true`, incluye `admin_temporal.password_temporal` y `admin_temporal.expira_en`.

4. `POST /auth/login` con ADMIN temporal creado  
   Resultado: `200`, `rol=ADMIN`.

5. Reintento con mismo `usuario` en admin temporal  
   Resultado: `409`, `codigo=CONFLICT_DUPLICATE`.

6. Intento de crear segundo `ADMIN` activo en misma organizacion  
   Resultado: `409`, `codigo=CONFLICT_DUPLICATE`.

7. Payload invalido (`nombre_completo` corto / `usuario` vacio)  
   Resultado: `400`, `codigo=VALIDATION_ERROR`.

8. Intento con token de `ADMIN` local (no SUPERADMIN)  
   Resultado: `403`, `codigo=FORBIDDEN_ROLE`.

9. `POST /auth/logout` de tokens de prueba  
   Resultado: `200` en todos los casos.

## Limpieza

- Usuarios temporales eliminados:
  - `b3t3_super`
  - `b3t3_admin_temp`
  - `b3t3_admin_temp_dup`
- Organizaciones temporales eliminadas: `B3T3 ORG%`
- Verificacion posterior en BD:
  - usuarios temporales = `0`
  - organizaciones temporales = `0`

