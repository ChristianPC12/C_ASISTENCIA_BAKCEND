# Evidencia B5-T03 - Presentaciones dinamicas y tenant-aware

Fecha: 2026-03-09  
Entorno: validacion local en CLI sobre `iglesia_asistencia`

## Implementacion validada

1. `PresentacionDAO` ahora es tenant-aware en operaciones criticas:
   - `insert(...)` persiste `organizacion_id`.
   - `findById(...)` exige `id + organizacion_id`.
   - `existsByPeriodoCulto(...)` valida duplicado por tenant.
   - `findAll(...)` y `countAll(...)` filtran por tenant.

2. `PresentacionService` usa tenant de contexto (`AuthContext::getOrganizacionId()`) para:
   - generar presentaciones por tenant,
   - listar por tenant,
   - obtener por ID por tenant.

3. Se agrego bloque dinamico en metricas de presentacion:
   - `metricas.metricas_dinamicas[]` con agregaciones numericas por clave.
   - KPIs incluyen referencia a metricas dinamicas destacadas.

## Casos de prueba ejecutados

Prueba runtime ejecutada con datos temporales en dos tenants:

- tenant A genera presentacion para `anio=2026, mes=3, culto=SABADO` -> OK
- tenant B genera misma combinacion de periodo/culto -> OK (sin conflicto global)
- tenant A lista presentaciones del filtro -> `total=1` (sin fuga de tenant B)
- tenant A intenta leer ID de presentacion de tenant B -> bloqueado (`404` equivalente)
- salida de tenant A contiene metrica dinamica custom `extra_horas`

Salida resumida registrada:

- `presentacion_a_id=6`
- `presentacion_b_id=7`
- `tenant_a_total=1`
- `cross_tenant_blocked=1`
- `metrica_dinamica_extra_horas=1`

## Limpieza

- presentaciones temporales eliminadas,
- registros de asistencia temporales eliminados,
- usuarios/organizaciones temporales eliminados,
- no quedaron residuos de prueba en tablas operativas.
