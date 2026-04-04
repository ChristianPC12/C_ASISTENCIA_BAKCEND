# Contexto Actual Backend Multiiglesia (2026-03-12)

## Estado backend vigente

- Backend: PHP 8 sin framework (Router -> Controller -> Service -> DAO).
- Base: `iglesia_asistencia` en modo multi-tenant.
- Roles activos: `SUPERADMIN`, `ADMIN`, `SECRETARIO`.
- Estado de ejecucion: B0..B7 cerradas a nivel tecnico.

## Resultado funcional consolidado

- Aislamiento tenant activo por `organizacion_id`.
- Auth tenant-aware en login/me/logout y middleware.
- API v2 de superadmin consolidada:
  - organizaciones (alta/listado/edicion),
  - admin temporal,
  - catalogos globales de campos y distritos (CRUD).
- API v2 setup activa (estado/cultos/metricas/procedencias/finalizar).
- Guard central `SETUP_REQUIRED` en modulos operativos.
- Dominio dinamico de metricas en asistencia/estadisticas/presentaciones.
- Cupos por rol por tenant + enforcement en usuarios.
- Expiracion automatica de ADMIN temporal a 5 dias.

## Superadmin consolidado (2026-03-11)

- Organizacion requiere `campo` y `distrito` validos y activos al crear/editar.
- Listado de organizaciones devuelve `distrito_codigo` y `distrito_nombre`.
- Endpoints de catalogo v2 activos:
  - `GET/POST/PUT/DELETE /v2/superadmin/campos`
  - `GET/POST/PUT/DELETE /v2/superadmin/distritos`
- Logica de estado de admin ajustada para distinguir correctamente `ADMIN expirado` vs `Sin ADMIN`.
- Envio opcional de credenciales por Brevo integrado en `crear admin temporal`.

## Configuracion de correo Brevo

- Configurable por constantes/env:
  - `BREVO_API_KEY`
  - `BREVO_SENDER_EMAIL`
  - `BREVO_SENDER_NAME`
  - `BREVO_API_URL`
  - `BREVO_TIMEOUT_SECONDS`
- `Config/Global.php` permite override local via `Config/Global.local.php`.
- Respuesta de correo incluye `detalle` y, cuando aplica, `message_id`.

## Estado de hardening (B7)

- B7-T01: suite aislamiento runtime ejecutada OK.
- B7-T02: migracion/rollback staging ejecutado OK.
- B7-T03: checklist final y runbooks operativos documentados.

## Ajustes recientes de setup/metricas (2026-03-12)

- Contrato de metricas simplificado a `categoria`:
  - sin `depende_de_clave`,
  - sin `regla_dependencia`,
  - sin `orden` en contrato operativo.
- `SetupValidator::validateMetricas` valida categorias permitidas y conserva reglas base de `habilitado/obligatorio`.
- `SetupService` migra validacion de dependencias a consistencia de metricas base:
  - puntualidad en par (`llegaron_antes_hora` y `llegaron_despues_hora`);
  - coherencia de `total_asistentes` con puntualidad.
- `AsistenciaService` normaliza metricas con reglas internas (sin dependencia configurable por usuario).
- Setup editable aun con estado completo (sin bloqueo artificial de actualizacion post-finalizacion).
- DAO ajustado para persistir/leer `categoria` y preservar estado completo al refrescar marcas internas.
- Migraciones nuevas:
  - `migracion_12032026_metricas_categoria.sql`
  - `rollback_12032026_metricas_categoria.sql`

## Validaciones tecnicas recientes

- `php -l` OK en archivos backend modificados (`SetupDAO`, `SetupValidator`, `SetupService`, `AsistenciaService`).
- Contratos v2 usados por frontend sincronizados para superadmin.

## Runbooks operativos disponibles

- `.agents/escalabilidad/PLAN_BACKUP_CUTOVER_ROLLBACK_B7.md`
- `.agents/escalabilidad/MONITOREO_POST_CUTOVER_B7.md`
- `.agents/escalabilidad/MANUAL_INCIDENTES_B7.md`

## Riesgos residuales

- Cutover productivo depende de ejecucion real de ventana de mantenimiento.
- Token bearer sigue en modelo stateless (seguridad depende de higiene cliente/servidor).
- Entregabilidad de correo depende de dominio/remitente correctamente verificado en Brevo.

## Decision de gate

- GO tecnico para preproduccion.
- Produccion final condicionada a aprobacion operativa del owner.
## Nota post-B7: presentaciones determinísticas (2026-04-01)

- `PresentacionService` deja de exponer narrativa técnica heredada (`kpis_clave`, `Conclusiones y Acciones`, métricas dinámicas destacadas).
- Las presentaciones legadas se reconstruyen al leerlas con los filtros originales y corte temporal por `creado_en`, para respetar el snapshot histórico sin mantener texto o cálculos incompletos.
- La agregación de presentación ahora incluye categorías dinámicas completas (`permanencia`, procedencias y visitas por zona) y evita duplicar nombres de visitas.

## Superadmin: mantenimiento de cuentas globales (2026-04-02)

- `PASSWORD_EXPIRY_DAYS` ya gobierna también las cuentas SUPERADMIN; la expiración efectiva se mantiene en 30 días.
- Nuevos endpoints v2 activos:
  - `GET /v2/superadmin/usuarios-superadmin`
  - `POST /v2/superadmin/usuarios-superadmin`
  - `PUT /v2/superadmin/usuarios-superadmin/{id}`
  - `PUT /v2/superadmin/usuarios-superadmin/{id}/password`
- `UsuarioDAO` ya soporta listado/alta/edición de cuentas SUPERADMIN globales.
- Al cambiar la contraseña de un SUPERADMIN se revocan sus tokens activos.
- `SuperadminCatalogoService::crearCampo` ya puede generar el código automáticamente cuando la UI solo envía el nombre.
## Modulos misioneros: base compartida + Campanas (2026-04-02)

- Se agrego documento de integracion tecnica `modulos_misioneros_integracion.md` y dos paquetes SQL:
  - `migracion_02042026_modulos_ministerio_base.sql`
  - `migracion_02042026_modulos_ministerio_operativos.sql`
- La base nueva define catálogos/origenes compartidos, `contactos_misioneros`, `seguimiento_tareas` y `auditoria_eventos` para reutilizacion futura entre `Campanas`, `Estudios Biblicos`, `PC` y `Juntas`.
- Quedo operativo el backend base de `ContactoMisionero` (DAO, service, validator, controller, routes) para deduplicacion y reutilizacion cross-modulo.
- Quedo operativo el primer slice de `Campanas`:
  - CRUD tenant-aware
  - dashboard
  - sesiones
  - asistentes
  - asistencia por noche con upsert
  - decisiones
  - auditoria de eventos relevantes
- `Campanas` ya resuelve/crea `contactos_misioneros` para preparar la conversion futura `Campana -> Interesado -> Estudio Biblico`.
## Modulos misioneros: Estudios Biblicos (2026-04-03)

- Quedo operativo el backend tenant-aware de `Estudios Biblicos` sobre la base compartida de `contactos_misioneros`, `seguimiento_tareas` y `auditoria_eventos`.
- Endpoints activos:
  - `GET /estudios-biblicos`
  - `GET /estudios-biblicos/dashboard`
  - `GET /estudios-biblicos/{id}`
  - `POST /estudios-biblicos`
  - `PUT /estudios-biblicos/{id}`
  - `DELETE /estudios-biblicos/{id}`
  - `POST /estudios-biblicos/{id}/sesiones`
  - `POST /estudios-biblicos/{id}/decisiones`
  - `POST /estudios-biblicos/{id}/asignaciones`
- El service ya resuelve/crea contactos para la persona e instructores, evitando duplicacion basica y dejando trazabilidad cross-modulo.
- `EstudioBiblicoService` registra sesiones, decisiones y asignaciones con auditoria; si una decision requiere seguimiento, crea tarea en `seguimiento_tareas`.
- La reasignacion cierra asignaciones vigentes previas y abre una nueva, conservando historial.
- El modulo queda listo para la integracion futura `Campana -> Interesado -> Estudio Biblico` y `PC -> Estudio Biblico` sin crear otra entidad de persona.
## Modulos misioneros: Pequenas Congregaciones (PC) (2026-04-03)

- Quedo operativo el backend tenant-aware de `PC` sobre la base compartida de `contactos_misioneros`, `seguimiento_tareas` y `auditoria_eventos`.
- Endpoints activos:
  - `GET /pequenas-congregaciones`
  - `GET /pequenas-congregaciones/dashboard`
  - `GET /pequenas-congregaciones/{id}`
  - `POST /pequenas-congregaciones`
  - `PUT /pequenas-congregaciones/{id}`
  - `DELETE /pequenas-congregaciones/{id}`
  - `POST /pequenas-congregaciones/{id}/participantes`
  - `PUT /pequenas-congregaciones/participantes/{id}`
  - `POST /pequenas-congregaciones/{id}/reuniones`
  - `PUT /pequenas-congregaciones/reuniones/{id}`
  - `POST /pequenas-congregaciones/reuniones/{id}/asistencia`
  - `POST /pequenas-congregaciones/{id}/resultados`
  - `PUT /pequenas-congregaciones/resultados/{id}`
  - `POST /pequenas-congregaciones/{id}/liderazgo`
  - `PUT /pequenas-congregaciones/liderazgo/{id}`
- `PcService` reutiliza `contactos_misioneros` para lideres, anfitriones, participantes y responsables de seguimiento, evitando crear otra entidad de persona.
- La auditoria registra altas, cambios, cierres, resultados y liderazgo historico del modulo.
- Los resultados ministeriales ya pueden impactar el estado de la PC (`MULTIPLICADA`, `CERRADA`) sin perder historial.## Modulos misioneros y administrativos: Juntas de Iglesia (2026-04-03)

- Quedo operativo el backend tenant-aware de `Juntas de Iglesia` sobre las tablas `juntas_iglesia`, `junta_puntos_agenda` y `junta_votaciones` ya definidas en la migracion operativa.
- Endpoints activos:
  - `GET /juntas-iglesia`
  - `GET /juntas-iglesia/dashboard`
  - `GET /juntas-iglesia/pendientes`
  - `GET /juntas-iglesia/{id}`
  - `POST /juntas-iglesia`
  - `PUT /juntas-iglesia/{id}`
  - `DELETE /juntas-iglesia/{id}`
  - `POST /juntas-iglesia/{id}/puntos`
  - `PUT /juntas-iglesia/puntos/{id}`
  - `POST /juntas-iglesia/puntos/{id}/votaciones`
  - `PUT /juntas-iglesia/votaciones/{id}`
- `JuntaService` ya entrega detalle enriquecido con puntos, votaciones, resumen, timeline y acta resumida.
- El modulo ya soporta busqueda textual, filtros por estado/tipo/departamento/responsable y calculo de pendientes/vencidos.
- La auditoria registra creacion, actualizacion, archivo, puntos y votaciones del modulo.
## 2026-04-03 - Conversiones cruzadas listas
- Campanas puede convertir asistentes no miembro a estudio biblico y deja decision automatica ACEPTO_ESTUDIO_BIBLICO.
- PC puede convertir participantes no miembro a estudio biblico y deja resultado ESTUDIO_BIBLICO_GENERADO.
- Ambas rutas usan contactos_misioneros compartidos, auditoria_eventos y aislamiento por organizacion_id.
- Smoke test real validado en org 61 con endpoints HTTP protegidos.


## 2026-04-04 - Estado listo para continuidad por otros chats
- La base tenant-aware de los 4 modulos nuevos ya esta lista para que otro chat continue desde agents sin re-descubrir arquitectura.
- Los siguientes pasos recomendados ya no son de estructura base, sino de refinamiento: reportes, exportaciones, endurecimiento de validaciones y smoke tests funcionales mas amplios.

