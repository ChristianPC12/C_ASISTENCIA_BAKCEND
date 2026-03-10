<?php
declare(strict_types=1);

/**
 * Clase SetupRoutes
 *
 * Enrutador para API v2 de setup inicial por tenant.
 */
final class SetupRoutes
{
    /**
     * Resuelve rutas v2/setup.
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public static function resolve(string $method, string $uri): bool
    {
        $controller = new SetupController();

        // GET /v2/setup/estado
        if ($method === 'GET' && $uri === '/v2/setup/estado') {
            AuthMiddleware::handle();
            RoleMiddleware::requireAdminV2();
            $controller->estado();
            return true;
        }

        // PUT /v2/setup/cultos
        if ($method === 'PUT' && $uri === '/v2/setup/cultos') {
            AuthMiddleware::handle();
            RoleMiddleware::requireAdminV2();
            $controller->cultos();
            return true;
        }

        // PUT /v2/setup/metricas
        if ($method === 'PUT' && $uri === '/v2/setup/metricas') {
            AuthMiddleware::handle();
            RoleMiddleware::requireAdminV2();
            $controller->metricas();
            return true;
        }

        // PUT /v2/setup/procedencias
        if ($method === 'PUT' && $uri === '/v2/setup/procedencias') {
            AuthMiddleware::handle();
            RoleMiddleware::requireAdminV2();
            $controller->procedencias();
            return true;
        }

        // POST /v2/setup/finalizar
        if ($method === 'POST' && $uri === '/v2/setup/finalizar') {
            AuthMiddleware::handle();
            RoleMiddleware::requireAdminV2();
            $controller->finalizar();
            return true;
        }

        return false;
    }
}

