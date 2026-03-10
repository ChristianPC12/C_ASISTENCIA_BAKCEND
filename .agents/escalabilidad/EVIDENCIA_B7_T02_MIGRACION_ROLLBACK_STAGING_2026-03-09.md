# Evidencia B7-T02 - Pruebas de migracion + rollback en staging

Fecha: 2026-03-09  
Estado: Ejecutado y validado en entorno temporal de staging local

## 1) Objetivo del ticket

Probar de punta a punta el ciclo de migracion y rollback del bloque multitenant (09-03-2026)
en una base temporal aislada tipo staging.

## 2) Script de prueba

- Script: `.agents/escalabilidad/scripts/b7_t02_prueba_migracion_rollback_staging.php`
- Sintaxis:
  - `php -l .agents/escalabilidad/scripts/b7_t02_prueba_migracion_rollback_staging.php`
  - Resultado: `No syntax errors detected`
- Ejecucion:
  - `php .agents/escalabilidad/scripts/b7_t02_prueba_migracion_rollback_staging.php`

## 3) Flujo validado por la prueba

1. Crear BD temporal staging.
2. Cargar base `iglesia_asistencia.sql`.
3. Aplicar bloque 09-03:
   - `migracion_09032026_multitenant_estructura.sql`
   - `migracion_09032026_multitenant_relaciones.sql`
   - `migracion_09032026_multitenant_seed_base.sql`
   - `migracion_09032026_superadmin_rol_base.sql`
   - `migracion_09032026_asistencia_metricas_dinamicas.sql`
   - `migracion_09032026_multitenant_sanity_checks.sql`
4. Verificar asserts estructurales (`organizaciones`, `organizacion_roles_cupos`, `usuarios.organizacion_id`, rol `SUPERADMIN`).
5. Ejecutar rollback 09-03:
   - `rollback_09032026_asistencia_metricas_dinamicas.sql`
   - `rollback_09032026_superadmin_rol_base.sql`
   - `rollback_09032026_multitenant_seed_base.sql`
   - `rollback_09032026_multitenant_relaciones.sql`
   - `rollback_09032026_multitenant_estructura.sql`
6. Verificar retorno de estado (sin `organizaciones`, sin `usuarios.organizacion_id`).
7. Reaplicar bloque 09-03 y validar nuevamente.
8. Eliminar BD temporal.

## 4) Salida de ejecucion

```text
db_temp=iglesia_asistencia_b7_stg_<timestamp>_<rand>
apply_0903_ok=1
rollback_0903_ok=1
reapply_0903_ok=1
```

## 5) Hallazgos

- El ciclo `apply -> rollback -> reapply` del bloque 09-03 resulto estable en staging temporal.
- La prueba limpia la BD temporal al finalizar (no deja residuos).

## 6) Resultado

`B7-T02` validado: migracion y rollback del bloque multitenant probados en entorno staging temporal.
