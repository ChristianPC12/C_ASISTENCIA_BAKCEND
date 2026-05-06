<?php
declare(strict_types=1);

/**
 * Rutas del modulo de estudios biblicos.
 */
final class EstudioBiblicoRoutes
{
    public static function resolve(string $method, string $uri): bool
    {
        $itemPattern = '#^/estudios-biblicos/(\d+)$#';
        $dashboardPattern = '#^/estudios-biblicos/dashboard$#';
        $sesionPattern = '#^/estudios-biblicos/(\d+)/sesiones$#';
        $estadoPattern = '#^/estudios-biblicos/(\d+)/estado$#';
        $decisionPattern = '#^/estudios-biblicos/(\d+)/decisiones$#';
        $asignacionPattern = '#^/estudios-biblicos/(\d+)/asignaciones$#';
        $instructorPattern = '#^/estudios-biblicos/instructores/(\d+)$#';

        if ($method === 'GET' && $uri === '/estudios-biblicos/instructores') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->listarInstructores();
            return true;
        }

        if ($method === 'POST' && $uri === '/estudios-biblicos/instructores') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->crearInstructor();
            return true;
        }

        if ($method === 'PUT' && preg_match($instructorPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->actualizarInstructor((int) $matches[1]);
            return true;
        }

        if ($method === 'DELETE' && preg_match($instructorPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->eliminarInstructor((int) $matches[1]);
            return true;
        }

        if ($method === 'GET' && $uri === '/estudios-biblicos') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->listar();
            return true;
        }

        if ($method === 'GET' && preg_match($dashboardPattern, $uri)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->dashboard();
            return true;
        }

        if ($method === 'GET' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->obtener((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && $uri === '/estudios-biblicos') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->crear();
            return true;
        }

        if ($method === 'POST' && $uri === '/estudios-biblicos/asignar') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->asignarDesdeVisita();
            return true;
        }

        if ($method === 'PUT' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->actualizar((int) $matches[1]);
            return true;
        }

        if ($method === 'DELETE' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->eliminar((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && preg_match($sesionPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->crearSesion((int) $matches[1]);
            return true;
        }

        if (($method === 'PATCH' || $method === 'PUT') && preg_match($estadoPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->cambiarEstado((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && preg_match($decisionPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->crearDecision((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && preg_match($asignacionPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new EstudioBiblicoController())->crearAsignacion((int) $matches[1]);
            return true;
        }

        return false;
    }
}
