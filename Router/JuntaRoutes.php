<?php
declare(strict_types=1);

/**
 * Rutas del modulo de juntas de iglesia.
 */
final class JuntaRoutes
{
    public static function resolve(string $method, string $uri): bool
    {
        $itemPattern = '#^/juntas-iglesia/(\d+)$#';
        $dashboardPattern = '#^/juntas-iglesia/dashboard$#';
        $pendientesPattern = '#^/juntas-iglesia/pendientes$#';
        $puntoPattern = '#^/juntas-iglesia/(\d+)/puntos$#';
        $puntoItemPattern = '#^/juntas-iglesia/puntos/(\d+)$#';
        $votacionPattern = '#^/juntas-iglesia/puntos/(\d+)/votaciones$#';
        $votacionItemPattern = '#^/juntas-iglesia/votaciones/(\d+)$#';

        if ($method === 'GET' && $uri === '/juntas-iglesia') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new JuntaController())->listar();
            return true;
        }

        if ($method === 'GET' && preg_match($dashboardPattern, $uri)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new JuntaController())->dashboard();
            return true;
        }

        if ($method === 'GET' && preg_match($pendientesPattern, $uri)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new JuntaController())->pendientes();
            return true;
        }

        if ($method === 'GET' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new JuntaController())->obtener((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && $uri === '/juntas-iglesia') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new JuntaController())->crear();
            return true;
        }

        if ($method === 'PUT' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new JuntaController())->actualizar((int) $matches[1]);
            return true;
        }

        if ($method === 'DELETE' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new JuntaController())->eliminar((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && preg_match($puntoPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new JuntaController())->crearPunto((int) $matches[1]);
            return true;
        }

        if ($method === 'PUT' && preg_match($puntoItemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new JuntaController())->actualizarPunto((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && preg_match($votacionPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new JuntaController())->crearVotacion((int) $matches[1]);
            return true;
        }

        if ($method === 'PUT' && preg_match($votacionItemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            RoleMiddleware::requireSetupCompletedForOperative();
            (new JuntaController())->actualizarVotacion((int) $matches[1]);
            return true;
        }

        return false;
    }
}
