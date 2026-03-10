# Evidencia B4-T03 - Validaciones de Dependencias entre Metricas

Fecha: 2026-03-09  
Entorno: `http://localhost/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND`

## Implementacion validada

1. Validacion semantica agregada en setup de metricas:
   - `Services/SetupService.php`
   - validacion activa al guardar metricas (`PUT /v2/setup/metricas`)
2. Reglas implementadas:
   - metrica obligatoria no puede quedar deshabilitada,
   - metrica dependiente no puede quedar habilitada si su padre esta deshabilitado,
   - deteccion de ciclos en grafo de dependencias,
   - regla de puntualidad "ambos o ninguno":
     - `llegaron_antes_hora`
     - `llegaron_despues_hora`
3. Estado de setup ahora reporta `resumen.dependencias_invalidas` segun validacion semantica.

## Casos HTTP ejecutados

1. `PUT /v2/setup/metricas` con dependencia activa y padre inactivo  
   Resultado: `400`, `codigo=VALIDATION_ERROR`.

2. `PUT /v2/setup/metricas` con puntualidad inconsistente (uno activo y otro no)  
   Resultado: `400`, `codigo=VALIDATION_ERROR`.

3. `PUT /v2/setup/metricas` con ciclo de dependencias (`metrica_a <-> metrica_b`)  
   Resultado: `400`, `codigo=VALIDATION_ERROR`.

4. `PUT /v2/setup/metricas` con dependencias coherentes  
   Resultado: `200`.

5. `GET /v2/setup/estado` posterior al payload valido  
   Resultado: `200`, `resumen.dependencias_invalidas = 0`.

6. `POST /v2/setup/finalizar` con configuracion completa valida  
   Resultado: `200`.

## Limpieza

- Logout de tokens temporales ejecutado.
- Usuarios temporales eliminados.
- Organizacion temporal eliminada.
- Verificacion posterior en BD:
  - `usuarios_temp = 0`
  - `organizaciones_temp = 0`
