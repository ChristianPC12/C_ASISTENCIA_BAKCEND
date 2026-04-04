# Integracion Tecnica - Modulos Misioneros y de Junta

## Objetivo
Integrar 4 modulos nuevos al sistema actual sin romper la arquitectura existente:

1. Pequenas Congregaciones (PC)
2. Estudios Biblicos
3. Campanas
4. Juntas de Iglesia

La integracion se apoya en una base compartida para evitar duplicados, conservar trazabilidad y mantener el aislamiento por `organizacion_id`.

## Enfoque de integracion

### 1. Entidad maestra compartida
Se crea `contactos_misioneros` como entidad central reutilizable para:
- visitas de campanas
- interesados
- participantes de PC
- instructores biblicos
- personas en estudio
- personas que luego avanzan a decision o bautismo

Esto evita volver a capturar nombre y telefono en cada modulo y deja trazabilidad del origen.

### 2. Catalogos compartidos
Se crean catalogos tenant-aware para:
- origenes misioneros
- decisiones misioneras

Eso permite reutilizar la misma logica entre Campanas, Estudios Biblicos y PC sin listas duras repartidas por el sistema.

### 3. Seguimiento transversal
Se crea `seguimiento_tareas` para centralizar acciones pendientes entre modulos:
- visita pendiente
- llamada pendiente
- confirmar proxima sesion
- seguimiento de decision espiritual
- punto de junta con responsable

### 4. Auditoria comun
Se crea `auditoria_eventos` para registrar cambios relevantes:
- creacion
- actualizacion
- cambio de estado
- cierre
- conversion entre modulos
- reasignaciones y decisiones

### 5. Modulos conectados entre si
- `campanas` puede originar `estudios_biblicos`
- `pc_grupos` puede originar `estudios_biblicos`
- `estudios_biblicos` puede reflejar decisiones y bautismo
- `pc_resultados` puede enlazar estudios o contactos generados
- `junta_puntos_agenda` puede referenciar entidades de otros modulos para seguimiento administrativo

## Estructura de tablas

### Base compartida
- `organizacion_origenes_misioneros`
- `organizacion_decisiones_misioneras`
- `contactos_misioneros`
- `seguimiento_tareas`
- `auditoria_eventos`

### Campanas
- `campanas`
- `campana_sesiones`
- `campana_asistentes`
- `campana_asistencia_sesiones`
- `campana_decisiones`

### Pequenas Congregaciones (PC)
- `pc_grupos`
- `pc_lideres_historial`
- `pc_participantes`
- `pc_reuniones`
- `pc_reunion_participantes`
- `pc_resultados`

### Estudios Biblicos
- `estudios_biblicos`
- `estudio_asignaciones`
- `estudio_sesiones`
- `estudio_decisiones`

### Juntas de Iglesia
- `juntas_iglesia`
- `junta_puntos_agenda`
- `junta_votaciones`
- `junta_adjuntos`

## Relaciones principales

### Contactos
- `contactos_misioneros.organizacion_id -> organizaciones.id`
- `pc_participantes.contacto_id -> contactos_misioneros.id`
- `pc_lideres_historial.contacto_id -> contactos_misioneros.id`
- `estudios_biblicos.contacto_id -> contactos_misioneros.id`
- `campana_asistentes.contacto_id -> contactos_misioneros.id`

### Origenes y conversiones
- `estudios_biblicos.campana_origen_id -> campanas.id`
- `estudios_biblicos.pc_origen_id -> pc_grupos.id`
- `campana_decisiones.estudio_biblico_id -> estudios_biblicos.id`
- `pc_resultados.estudio_biblico_id -> estudios_biblicos.id`

### Seguimiento y auditoria
- `seguimiento_tareas.contacto_id -> contactos_misioneros.id`
- `seguimiento_tareas.responsable_usuario_id -> usuarios.id`
- `auditoria_eventos.actor_usuario_id -> usuarios.id`

### Junta de iglesia
- `junta_puntos_agenda.junta_id -> juntas_iglesia.id`
- `junta_votaciones.punto_agenda_id -> junta_puntos_agenda.id`
- `junta_adjuntos.junta_id -> juntas_iglesia.id`

## Reglas tecnicas adoptadas

### Tenant-aware
Todas las tablas nuevas usan `organizacion_id` como eje de aislamiento.
Todos los indices principales empiezan por `organizacion_id` cuando aplica:
- fecha
- estado
- responsable
- modulo

### Trazabilidad
Las tablas operativas nuevas incorporan:
- `creado_en`
- `actualizado_en`
- `eliminado_en`
- `creado_por`
- `actualizado_por`
- `eliminado_por`

### Soft delete
No se borra historial operativo sensible:
- PC
- estudios
- campanas
- juntas
- puntos de agenda
- contactos misioneros

### Dedupe de contactos
La deduplicacion se trabajara con servicio compartido usando:
- `organizacion_id`
- nombre normalizado
- telefono normalizado

No se fuerza una `UNIQUE` rigida porque en iglesia local puede haber:
- telefonos compartidos
- nombres repetidos
- registros incompletos

## Integracion con frontend actual

### Nuevas paginas previstas
- `PcPage.jsx`
- `EstudiosBiblicosPage.jsx`
- `CampanasPage.jsx`
- `JuntasPage.jsx`

### Nuevos hooks previstos
- `usePc.js`
- `useEstudiosBiblicos.js`
- `useCampanas.js`
- `useJuntas.js`

### Nuevas APIs previstas
- `pcApi.js`
- `estudiosBiblicosApi.js`
- `campanasApi.js`
- `juntasApi.js`

La implementacion seguira el patron ya usado en:
- `useAsistencia.js`
- `usePresentaciones.js`
- `superadminApi.js`
- paginas con filtros + tabla + modal/detalle

## Permisos preparados para futuro sistema de roles

No se implementa el sistema completo todavia, pero la base queda lista para:
- modulo: `pc`
- modulo: `estudios_biblicos`
- modulo: `campanas`
- modulo: `juntas`

Acciones futuras previstas:
- `ver`
- `crear`
- `editar`
- `cerrar`
- `exportar`
- `aprobar`
- `auditar`

## Orden recomendado de desarrollo

1. Base compartida:
   - contactos
   - origenes
   - decisiones
   - seguimiento
   - auditoria
2. Campanas
3. Estudios Biblicos
4. Pequenas Congregaciones
5. Juntas de Iglesia
6. Dashboards y reportes cruzados

## Resultado esperado
Con esta base:
- no se duplican personas entre modulos
- los origenes se mantienen consistentes
- se conserva trazabilidad historica
- se prepara el terreno para reportes cruzados y permisos futuros
- la logica queda alineada con uso adventista real en iglesia local
