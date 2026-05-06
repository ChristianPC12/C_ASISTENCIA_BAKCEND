<?php
declare(strict_types=1);

/**
 * Rutas operativas del modulo de campanas.
 */
final class CampanaRoutes
{
    public static function resolve(string $method, string $uri): bool
    {
        $itemPattern = '#^/campanas/(\d+)$#';
        $dashboardPattern = '#^/campanas/dashboard$#';
        $sesionPattern = '#^/campanas/(\d+)/sesiones$#';
        $sesionItemPattern = '#^/campanas/sesiones/(\d+)$#';
        $asistentePattern = '#^/campanas/(\d+)/asistentes$#';
        $asistenteItemPattern = '#^/campanas/asistentes/(\d+)$#';
        $asistenteConvertirPattern = '#^/campanas/asistentes/(\d+)/convertir-estudio$#';
        $asistenteEntregarPremiosPattern = '#^/campanas/asistentes/(\d+)/entregar-premios$#';
        $asistenciaSesionPattern = '#^/campanas/sesiones/(\d+)/asistencia$#';
        $decisionPattern = '#^/campanas/(\d+)/decisiones$#';
        $decisionItemPattern = '#^/campanas/decisiones/(\d+)$#';

        if ($method === 'GET' && $uri === '/campanas') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->listar();
            return true;
        }

        if ($method === 'GET' && preg_match($dashboardPattern, $uri)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->dashboard();
            return true;
        }

        if ($method === 'GET' && $uri === '/campanas/visitas') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->listarVisitas();
            return true;
        }

        if ($method === 'GET' && $uri === '/campanas/visitas/similares') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->buscarVisitasSimilares();
            return true;
        }

        if ($method === 'POST' && $uri === '/campanas/visitas') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->crearVisitaSuelta();
            return true;
        }

        if ($method === 'GET' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->obtener((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && $uri === '/campanas') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->crear();
            return true;
        }

        if ($method === 'PUT' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->actualizar((int) $matches[1]);
            return true;
        }

        if ($method === 'DELETE' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->eliminar((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && preg_match($sesionPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->crearSesion((int) $matches[1]);
            return true;
        }

        if ($method === 'PUT' && preg_match($sesionItemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->actualizarSesion((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && preg_match($asistentePattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->crearAsistente((int) $matches[1]);
            return true;
        }

        if ($method === 'PUT' && preg_match($asistenteItemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->actualizarAsistente((int) $matches[1]);
            return true;
        }

        if ($method === 'DELETE' && preg_match($asistenteItemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->eliminarAsistente((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && preg_match($asistenteConvertirPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->convertirAsistenteAEstudio((int) $matches[1]);
            return true;
        }

        if ($method === 'PUT' && preg_match($asistenteEntregarPremiosPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->entregarPremiosAsistente((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && preg_match($asistenciaSesionPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->registrarAsistenciaSesion((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && preg_match($decisionPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->crearDecision((int) $matches[1]);
            return true;
        }

        if ($method === 'DELETE' && preg_match($decisionItemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new CampanaController();
            $controller->eliminarDecision((int) $matches[1]);
            return true;
        }

        return false;
    }
}
