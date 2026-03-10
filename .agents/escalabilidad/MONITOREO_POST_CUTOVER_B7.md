# Monitoreo Post-Cutover B7

Fecha: 2026-03-10  
Ventana critica: primeras 24 horas post despliegue

## 1) Indicadores minimos

- tasa de errores `401`, `403`, `429`, `500`,
- latencia p95 de endpoints criticos,
- volumen de login exitoso/fallido,
- errores de setup (`SETUP_REQUIRED`, `SETUP_INCONSISTENT`),
- errores de cupo (`CUPOS_EXCEEDED`).

## 2) Endpoints criticos a vigilar

- `POST /auth/login`
- `GET /auth/me`
- `GET /cultos`
- `POST /asistencias`
- `GET /asistencias/estadisticas`
- `GET /presentaciones`
- `GET /v2/usuarios/cupos`
- `GET /v2/setup/estado`

## 3) Umbrales de alerta inicial

- `500` > 1% por 5 minutos -> alerta P1.
- `401` anomalo (>3x baseline) -> revisar expiracion/sesion.
- `403` anomalo (>3x baseline) -> revisar roles/setup guard.
- `429` anomalo sostenido -> revisar abuso o configuracion de login.
- latencia p95 > 2s por 10 minutos -> alerta de performance.

## 4) Accion de primera respuesta

1. Confirmar alcance (tenant unico o multiple).
2. Revisar logs de backend y ultima version desplegada.
3. Si impacta P0 (auth/fuga datos), escalar a plan rollback.
4. Documentar incidente con timestamp y evidencia.

