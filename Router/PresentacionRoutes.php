<?php
declare(strict_types=1);

/**
 * Enrutador para endpoints de presentaciones.
 */
final class PresentacionRoutes
{
    public static function resolve(string $method, string $uri): bool
    {
        $itemPattern = '#^/presentaciones/(\d+)$#';

        if ($method === 'GET' && $uri === '/presentaciones') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new PresentacionController();
            $controller->listar();
            return true;
        }

        if ($method === 'POST' && $uri === '/presentaciones/generar') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new PresentacionController();
            $controller->generar();
            return true;
        }

        if ($method === 'GET' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            $controller = new PresentacionController();
            $controller->obtener((int) $matches[1]);
            return true;
        }

        return false;
    }
}
