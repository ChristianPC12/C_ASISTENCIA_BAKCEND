# Evidencia B6-T01/B6-T02/B6-T03 - Cupos y ciclo de vida de usuarios

Fecha: 2026-03-09  
Estado: Validado localmente (codigo + runtime)

## 1) Tickets cubiertos

- `B6-T01` Configurar cupos por rol por tenant.
- `B6-T02` Enforcement de cupos en `UsuarioService`.
- `B6-T03` Expiracion automatica de admin temporal (5 dias).

## 2) Codigo aplicado

- `Modelo/Usuario/UsuarioDAO.php`
- `Services/UsuarioService.php`
- `Validator/UsuarioValidator.php`
- `Controller/UsuarioController.php`
- `Router/UsuarioRoutes.php`
- `Services/AuthService.php`
- `Middleware/AuthMiddleware.php`

## 3) Validacion de sintaxis

Comandos ejecutados:

- `php -l Modelo/Usuario/UsuarioDAO.php`
- `php -l Services/UsuarioService.php`
- `php -l Validator/UsuarioValidator.php`
- `php -l Controller/UsuarioController.php`
- `php -l Router/UsuarioRoutes.php`
- `php -l Services/AuthService.php`
- `php -l Middleware/AuthMiddleware.php`

Resultado:

- Todos los archivos reportan `No syntax errors detected`.

## 4) Validacion runtime (B6)

Se ejecuto prueba local con organizacion A y B temporales (limpieza al final), validando:

- cupo por rol en tenant A (`ADMIN=1`, `SECRETARIO=1`),
- bloqueo al intentar exceder cupo,
- aislamiento por tenant en listado y detalle de usuarios,
- desactivacion automatica de ADMIN temporal vencido.

Salida de la prueba:

```text
bloqueo_secretario=1
bloqueo_admin=1
aislamiento_lista=1
aislamiento_detalle=1
expiracion_admin_temporal=1
desactivados_temp=1
```

Notas de la prueba:

- Para `B6-T03`, la fecha de expiracion usada en test se genero con `NOW()` de MySQL para evitar desfase de zona horaria entre PHP y BD.
- El script temporal de prueba fue eliminado al terminar (`tmp_b6_runtime.php`).

## 5) Resultado

- `B6-T01`: OK
- `B6-T02`: OK
- `B6-T03`: OK

Bloque B6 listo para pasar a fase de hardening (`B7`), manteniendo sincronizacion documental frontend/backend.
