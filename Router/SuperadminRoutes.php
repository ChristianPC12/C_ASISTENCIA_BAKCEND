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
        $campoPattern = '#^/v2/superadmin/campos/([A-Z0-9]{2,10})$#i';
        $distritoPattern = '#^/v2/superadmin/distritos/([A-Z0-9_]{2,24})$#i';

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

        // GET /v2/superadmin/campos
        if ($method === 'GET' && $uri === '/v2/superadmin/campos') {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminCatalogoController();
            $controller->listarCampos();
            return true;
        }

        // POST /v2/superadmin/campos
        if ($method === 'POST' && $uri === '/v2/superadmin/campos') {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminCatalogoController();
            $controller->crearCampo();
            return true;
        }

        // PUT /v2/superadmin/campos/{codigo}
        if ($method === 'PUT' && preg_match($campoPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminCatalogoController();
            $controller->actualizarCampo(strtoupper((string) $matches[1]));
            return true;
        }

        // DELETE /v2/superadmin/campos/{codigo}
        if ($method === 'DELETE' && preg_match($campoPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminCatalogoController();
            $controller->eliminarCampo(strtoupper((string) $matches[1]));
            return true;
        }

        // GET /v2/superadmin/distritos
        if ($method === 'GET' && $uri === '/v2/superadmin/distritos') {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminCatalogoController();
            $controller->listarDistritos();
            return true;
        }

        // POST /v2/superadmin/distritos
        if ($method === 'POST' && $uri === '/v2/superadmin/distritos') {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminCatalogoController();
            $controller->crearDistrito();
            return true;
        }

        // PUT /v2/superadmin/distritos/{codigo}
        if ($method === 'PUT' && preg_match($distritoPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminCatalogoController();
            $controller->actualizarDistrito(strtoupper((string) $matches[1]));
            return true;
        }

        // DELETE /v2/superadmin/distritos/{codigo}
        if ($method === 'DELETE' && preg_match($distritoPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::requireSuperadmin();
            $controller = new SuperadminCatalogoController();
            $controller->eliminarDistrito(strtoupper((string) $matches[1]));
            return true;
        }

        return false;
    }
}
