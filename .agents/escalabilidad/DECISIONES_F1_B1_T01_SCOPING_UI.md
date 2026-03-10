# Decisiones F1/B1-T01 - Scoping Tenant en UI Operativa

Fecha de propuesta: 2026-03-09  
Estado: Aprobado por owner (2026-03-09)

## 1) Objetivo

Confirmar que la UI operativa siempre trabaje dentro del tenant correcto y no permita
cruces de datos entre iglesias/grupos.

## 2) Regla principal de scoping

- La UI NO envia `organizacion_id` manualmente en modulos operativos.
- El backend resuelve el tenant desde la sesion autenticada.

En simple:

- el usuario inicia sesion,
- el sistema ya sabe a que iglesia/grupo pertenece,
- todas las consultas y guardados se filtran automaticamente por esa iglesia/grupo.

## 3) Fuente oficial del tenant en frontend

- Fuente unica: respuesta de sesion (`/v2/auth/me`) cuando se implemente B2/F3.
- Uso en frontend: lectura/visualizacion (badge, contexto, trazabilidad UI).
- Prohibido: permitir que usuario operativo cambie tenant por formulario o query manual.

## 4) Excepciones permitidas

- Modulo `SUPERADMIN`: si puede operar sobre otras organizaciones.
- En ese caso, `organizacion_id` debe ir en ruta/endpoint administrativo, no en formularios operativos.

## 5) Alcance UI afectado

Aplica a:

- `/registro`
- `/registros`
- `/estadisticas`
- `/comparaciones`
- `/presentaciones`
- `/usuarios` (admin de instancia)

## 6) Checklist simple de validacion

- usuario operativo no ve selector de tenant,
- filtros UI no incluyen tenant editable,
- crear/editar/eliminar registros opera solo sobre tenant de sesion,
- exportaciones/reportes respetan tenant de sesion.

## 7) Estado de cierre

- Documento aprobado para cierre de `F1-T01`.
- No marca implementacion completa de B2/F3; solo cierra regla de scoping UI.

## 8) Sincronizacion obligatoria

Documento espejo frontend:

- `.agents/react-doctor/DECISIONES_F1_B1_T01_SCOPING_UI.md`
