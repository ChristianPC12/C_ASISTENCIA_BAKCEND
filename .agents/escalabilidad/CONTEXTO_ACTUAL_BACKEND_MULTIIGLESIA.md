# Contexto Actual Backend Multiiglesia (2026-03-09)

## Estado vigente

- Backend actual: PHP 8 sin framework, arquitectura Router -> Controller -> Service -> DAO.
- Base de datos actual: `iglesia_asistencia` en modo single-tenant.
- Roles implementados hoy: `ADMIN`, `SECRETARIO`.
- Aislamiento por tenant: no implementado aun.

## Implicacion directa del estado actual

- Restriccion de unicidad de asistencia es global por `(culto_id, fecha)`.
- Si dos iglesias intentan registrar mismo culto/fecha, hoy choca como duplicado.
- Usuarios y sesiones no tienen `organizacion_id`.

## Objetivo de evolucion backend

Migrar a modelo tenant-aware para permitir:

- aislamiento total de datos por organizacion (iglesia/grupo),
- onboarding de nuevas organizaciones por `SUPERADMIN`,
- configuracion inicial de instancia antes de habilitar modulos operativos.

## Fuente de verdad para ejecucion

- `ROADMAP_BACKEND_MULTIIGLESIA.md` (detallado por fases y tareas).

## Archivos backend criticos que se afectaran

- `Config/Global.php`
- `Utils/AuthContext.php`
- `Middleware/AuthMiddleware.php`
- `Services/AuthService.php`
- `Services/UsuarioService.php`
- `Services/AsistenciaService.php`
- `Services/PresentacionService.php`
- `Modelo/Usuario/UsuarioDAO.php`
- `Modelo/Token/TokenDAO.php`
- `Modelo/Asistencia/AsistenciaDAO.php`
- `Modelo/Presentacion/PresentacionDAO.php`
- `Router/AuthRoutes.php`
- `Router/UsuarioRoutes.php`
- `Router/AsistenciaRoutes.php`
- `Router/PresentacionRoutes.php`

## Notas de control

- Este archivo no describe features futuras como implementadas.
- Cada cierre de fase debe reflejarse aqui con fecha real.

