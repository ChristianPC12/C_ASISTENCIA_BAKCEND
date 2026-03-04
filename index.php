<?php
declare(strict_types=1);

/**
 * Punto de entrada del backend (Front Controller).
 *
 * Flujo: .htaccess -> index.php -> ErrorMiddleware -> CorsMiddleware
 *        -> Utils -> Router -> Controller -> Validator -> Service -> DAO -> JsonResponse
 */

// ============================================================
// 1) Configuracion global
// ============================================================
require_once __DIR__ . '/Config/Global.php';
require_once __DIR__ . '/Config/Conexion.php';

// ============================================================
// 2) Utils (deben cargarse antes que todo lo demas)
// ============================================================
require_once __DIR__ . '/Utils/JsonResponse.php';
require_once __DIR__ . '/Utils/Sanitizer.php';
require_once __DIR__ . '/Utils/AuthContext.php';

// ============================================================
// 3) Middleware
// ============================================================
require_once __DIR__ . '/Middleware/ErrorMiddleware.php';
require_once __DIR__ . '/Middleware/CorsMiddleware.php';
require_once __DIR__ . '/Middleware/AuthMiddleware.php';

// ============================================================
// 4) Modelo: DTOs, Mappers, DAOs
// ============================================================
// Usuario
require_once __DIR__ . '/Modelo/Usuario/UsuarioDTO.php';
require_once __DIR__ . '/Modelo/Usuario/UsuarioMapper.php';
require_once __DIR__ . '/Modelo/Usuario/UsuarioDAO.php';

// Token
require_once __DIR__ . '/Modelo/Token/TokenDTO.php';
require_once __DIR__ . '/Modelo/Token/TokenMapper.php';
require_once __DIR__ . '/Modelo/Token/TokenDAO.php';

// Culto
require_once __DIR__ . '/Modelo/Culto/CultoDTO.php';
require_once __DIR__ . '/Modelo/Culto/CultoMapper.php';
require_once __DIR__ . '/Modelo/Culto/CultoDAO.php';

// Asistencia
require_once __DIR__ . '/Modelo/Asistencia/AsistenciaDTO.php';
require_once __DIR__ . '/Modelo/Asistencia/AsistenciaMapper.php';
require_once __DIR__ . '/Modelo/Asistencia/AsistenciaDAO.php';

// ============================================================
// 5) Validators
// ============================================================
require_once __DIR__ . '/Validator/AuthValidator.php';
require_once __DIR__ . '/Validator/UsuarioValidator.php';
require_once __DIR__ . '/Validator/AsistenciaValidator.php';

// ============================================================
// 6) Services
// ============================================================
require_once __DIR__ . '/Services/AuthService.php';
require_once __DIR__ . '/Services/CultoService.php';
require_once __DIR__ . '/Services/UsuarioService.php';
require_once __DIR__ . '/Services/AsistenciaService.php';
require_once __DIR__ . '/Services/AsistenciaExportService.php';

// ============================================================
// 7) Controllers
// ============================================================
require_once __DIR__ . '/Controller/AuthController.php';
require_once __DIR__ . '/Controller/CultoController.php';
require_once __DIR__ . '/Controller/UsuarioController.php';
require_once __DIR__ . '/Controller/AsistenciaController.php';

// ============================================================
// 8) Routers
// ============================================================
require_once __DIR__ . '/Router/AuthRoutes.php';
require_once __DIR__ . '/Router/CultoRoutes.php';
require_once __DIR__ . '/Router/UsuarioRoutes.php';
require_once __DIR__ . '/Router/AsistenciaRoutes.php';

// ============================================================
// EJECUCION
// ============================================================

// Registrar manejador de errores global
ErrorMiddleware::register();

// Aplicar CORS
CorsMiddleware::handle();

// Obtener metodo y URI normalizada
$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

// Quitar query string
if (($pos = strpos($uri, '?')) !== false) {
    $uri = substr($uri, 0, $pos);
}

// Quitar base path
$uri = str_replace(BASE_PATH, '', $uri);

// Normalizar: quitar trailing slash (excepto raiz)
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

// Intentar resolver la ruta en cada grupo de rutas
$resolved = AuthRoutes::resolve($method, $uri)
         || CultoRoutes::resolve($method, $uri)
         || AsistenciaRoutes::resolve($method, $uri)
         || UsuarioRoutes::resolve($method, $uri);

// Si ninguna ruta coincidio: 404
if (!$resolved) {
    JsonResponse::send(404, false, 'Recurso no encontrado.');
}
