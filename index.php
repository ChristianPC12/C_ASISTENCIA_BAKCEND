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
require_once __DIR__ . '/Middleware/RoleMiddleware.php';

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

// Seguridad
require_once __DIR__ . '/Modelo/Seguridad/LoginIntentoDAO.php';

// Culto
require_once __DIR__ . '/Modelo/Culto/CultoDTO.php';
require_once __DIR__ . '/Modelo/Culto/CultoMapper.php';
require_once __DIR__ . '/Modelo/Culto/CultoDAO.php';

// Asistencia
require_once __DIR__ . '/Modelo/Asistencia/AsistenciaDTO.php';
require_once __DIR__ . '/Modelo/Asistencia/AsistenciaMapper.php';
require_once __DIR__ . '/Modelo/Asistencia/AsistenciaDAO.php';

// Presentacion
require_once __DIR__ . '/Modelo/Presentacion/PresentacionDTO.php';
require_once __DIR__ . '/Modelo/Presentacion/PresentacionMapper.php';
require_once __DIR__ . '/Modelo/Presentacion/PresentacionDAO.php';

// Campana
require_once __DIR__ . '/Modelo/Campana/CampanaDTO.php';
require_once __DIR__ . '/Modelo/Campana/CampanaMapper.php';
require_once __DIR__ . '/Modelo/Campana/CampanaDAO.php';

// Estudio biblico
require_once __DIR__ . '/Modelo/EstudioBiblico/EstudioBiblicoDTO.php';
require_once __DIR__ . '/Modelo/EstudioBiblico/EstudioBiblicoMapper.php';
require_once __DIR__ . '/Modelo/EstudioBiblico/EstudioBiblicoDAO.php';

// PC
require_once __DIR__ . '/Modelo/Pc/PcDTO.php';
require_once __DIR__ . '/Modelo/Pc/PcMapper.php';
require_once __DIR__ . '/Modelo/Pc/PcDAO.php';

// Junta
require_once __DIR__ . '/Modelo/Junta/JuntaDTO.php';
require_once __DIR__ . '/Modelo/Junta/JuntaMapper.php';
require_once __DIR__ . '/Modelo/Junta/JuntaDAO.php';

// Contacto misionero
require_once __DIR__ . '/Modelo/ContactoMisionero/ContactoMisioneroDTO.php';
require_once __DIR__ . '/Modelo/ContactoMisionero/ContactoMisioneroMapper.php';
require_once __DIR__ . '/Modelo/ContactoMisionero/ContactoMisioneroDAO.php';

// Auditoria
require_once __DIR__ . '/Modelo/Auditoria/AuditoriaEventoDAO.php';

// Seguimiento
require_once __DIR__ . '/Modelo/Seguimiento/SeguimientoTareaDAO.php';

// Organizacion
require_once __DIR__ . '/Modelo/Organizacion/OrganizacionDTO.php';
require_once __DIR__ . '/Modelo/Organizacion/OrganizacionMapper.php';
require_once __DIR__ . '/Modelo/Organizacion/OrganizacionDAO.php';

// Setup
require_once __DIR__ . '/Modelo/Setup/SetupDAO.php';

// ============================================================
// 5) Validators
// ============================================================
require_once __DIR__ . '/Validator/AuthValidator.php';
require_once __DIR__ . '/Validator/UsuarioValidator.php';
require_once __DIR__ . '/Validator/AsistenciaValidator.php';
require_once __DIR__ . '/Validator/PresentacionValidator.php';
require_once __DIR__ . '/Validator/CampanaValidator.php';
require_once __DIR__ . '/Validator/EstudioBiblicoValidator.php';
require_once __DIR__ . '/Validator/PcValidator.php';
require_once __DIR__ . '/Validator/JuntaValidator.php';
require_once __DIR__ . '/Validator/OrganizacionValidator.php';
require_once __DIR__ . '/Validator/SetupValidator.php';
require_once __DIR__ . '/Validator/ContactoMisioneroValidator.php';
require_once __DIR__ . '/Validator/SuperadminCatalogoValidator.php';
require_once __DIR__ . '/Validator/SuperadminUsuarioValidator.php';

// ============================================================
// 6) Services
// ============================================================
require_once __DIR__ . '/Services/AuthService.php';
require_once __DIR__ . '/Services/CultoService.php';
require_once __DIR__ . '/Services/UsuarioService.php';
require_once __DIR__ . '/Services/AsistenciaService.php';
require_once __DIR__ . '/Services/AsistenciaExportService.php';
require_once __DIR__ . '/Services/PresentacionService.php';
require_once __DIR__ . '/Services/CampanaService.php';
require_once __DIR__ . '/Services/EstudioBiblicoService.php';
require_once __DIR__ . '/Services/PcService.php';
require_once __DIR__ . '/Services/JuntaService.php';
require_once __DIR__ . '/Services/CorreoService.php';
require_once __DIR__ . '/Services/AuditoriaService.php';
require_once __DIR__ . '/Services/ContactoMisioneroService.php';
require_once __DIR__ . '/Services/SeguimientoTareaService.php';
require_once __DIR__ . '/Services/OrganizacionService.php';
require_once __DIR__ . '/Services/SetupService.php';
require_once __DIR__ . '/Services/SuperadminCatalogoService.php';
require_once __DIR__ . '/Services/SuperadminUsuarioService.php';

// ============================================================
// 7) Controllers
// ============================================================
require_once __DIR__ . '/Controller/AuthController.php';
require_once __DIR__ . '/Controller/CultoController.php';
require_once __DIR__ . '/Controller/UsuarioController.php';
require_once __DIR__ . '/Controller/AsistenciaController.php';
require_once __DIR__ . '/Controller/PresentacionController.php';
require_once __DIR__ . '/Controller/CampanaController.php';
require_once __DIR__ . '/Controller/EstudioBiblicoController.php';
require_once __DIR__ . '/Controller/PcController.php';
require_once __DIR__ . '/Controller/JuntaController.php';
require_once __DIR__ . '/Controller/ContactoMisioneroController.php';
require_once __DIR__ . '/Controller/SuperadminOrganizacionController.php';
require_once __DIR__ . '/Controller/SuperadminCatalogoController.php';
require_once __DIR__ . '/Controller/SuperadminUsuarioController.php';
require_once __DIR__ . '/Controller/SetupController.php';

// ============================================================
// 8) Routers
// ============================================================
require_once __DIR__ . '/Router/AuthRoutes.php';
require_once __DIR__ . '/Router/CultoRoutes.php';
require_once __DIR__ . '/Router/UsuarioRoutes.php';
require_once __DIR__ . '/Router/AsistenciaRoutes.php';
require_once __DIR__ . '/Router/PresentacionRoutes.php';
require_once __DIR__ . '/Router/CampanaRoutes.php';
require_once __DIR__ . '/Router/EstudioBiblicoRoutes.php';
require_once __DIR__ . '/Router/PcRoutes.php';
require_once __DIR__ . '/Router/JuntaRoutes.php';
require_once __DIR__ . '/Router/ContactoMisioneroRoutes.php';
require_once __DIR__ . '/Router/SuperadminRoutes.php';
require_once __DIR__ . '/Router/SetupRoutes.php';

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
         || SuperadminRoutes::resolve($method, $uri)
         || SetupRoutes::resolve($method, $uri)
         || CultoRoutes::resolve($method, $uri)
         || AsistenciaRoutes::resolve($method, $uri)
         || PresentacionRoutes::resolve($method, $uri)
         || CampanaRoutes::resolve($method, $uri)
         || EstudioBiblicoRoutes::resolve($method, $uri)
         || PcRoutes::resolve($method, $uri)
         || JuntaRoutes::resolve($method, $uri)
         || ContactoMisioneroRoutes::resolve($method, $uri)
         || UsuarioRoutes::resolve($method, $uri);

// Si ninguna ruta coincidio: 404
if (!$resolved) {
    JsonResponse::send(404, false, 'Recurso no encontrado.');
}

