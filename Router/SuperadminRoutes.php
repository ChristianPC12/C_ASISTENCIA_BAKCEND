<?php
declare(strict_types=1);

/**
 * Clase SuperadminRoutes
 *
 * Enrutador para API v2 de superadmin.
 */
final class SuperadminRoutes
{
    /**
     * Resuelve rutas v2/superadmin.
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public static function resolve(string $method, string $uri): bool
    {
        $itemPattern = '#^/v2/superadmin/organizaciones/(\d+)$#';
        $adminTemporalPattern = '#^/v2/superadmin/organizaciones/(\d+)/admin-temporal$#';

        // GET /v2/superadmin/organizaciones
        if ($method === 'GET' && $uri === '/v2/superadmin/organizaciones') {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminOrganizacionController();
            $controller->listar();
            return true;
        }

        // POST /v2/superadmin/organizaciones
        if ($method === 'POST' && $uri === '/v2/superadmin/organizaciones') {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminOrganizacionController();
            $controller->crear();
            return true;
        }

        // PUT /v2/superadmin/organizaciones/{organizacion_id}
        if ($method === 'PUT' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminOrganizacionController();
            $controller->actualizar((int) $matches[1]);
            return true;
        }

        // POST /v2/superadmin/organizaciones/{organizacion_id}/admin-temporal
        if ($method === 'POST' && preg_match($adminTemporalPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminOrganizacionController();
            $controller->crearAdminTemporal((int) $matches[1]);
            return true;
        }

        return false;
    }
}
