# Plan Operativo B7 - Backup, Cutover y Rollback

Fecha: 2026-03-10  
Alcance: salida nacional multi-tenant (backend + frontend)

## 1) Backup previo obligatorio

1. Generar snapshot SQL completo:
   - `mysqldump -u root -p iglesia_asistencia > backup_pre_cutover_<fecha>.sql`
2. Verificar integridad basica:
   - tamaño archivo > 0
   - prueba de restore en BD temporal.
3. Guardar checksum:
   - `certutil -hashfile backup_pre_cutover_<fecha>.sql SHA256`
4. Registrar responsable, fecha y ruta de backup.

## 2) Ventana de cutover

1. Activar ventana de mantenimiento.
2. Congelar altas/cambios operativos de usuarios.
3. Aplicar despliegue backend y frontend version nacional.
4. Ejecutar smoke minimo:
   - login valido,
   - `/v2/setup/estado`,
   - registro de asistencia,
   - listado de presentaciones,
   - cupos usuarios.
5. Si smoke falla en P0, ejecutar rollback inmediato.

## 3) Criterio de rollback (P0)

Rollback obligatorio si ocurre cualquiera:

- error de autenticacion generalizada (401/403 no esperados),
- fuga de datos entre tenants,
- setup guard bloquea tenants con setup completo,
- caida sostenida de endpoints criticos > 5 minutos.

## 4) Procedimiento de rollback

1. Revertir codigo frontend/backend a version estable previa.
2. Restaurar backup SQL pre-cutover en entorno objetivo.
3. Ejecutar smoke post-rollback:
   - login,
   - registro,
   - usuarios.
4. Confirmar servicio estable y cerrar incidente.

