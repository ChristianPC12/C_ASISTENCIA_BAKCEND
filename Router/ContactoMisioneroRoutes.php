<?php
declare(strict_types=1);

/**
 * Rutas tenant-aware de contactos misioneros compartidos.
 */
final class ContactoMisioneroRoutes
{
    public static function resolve(string $method, string $uri): bool
    {
        $itemPattern = '#^/contactos-misioneros/(\d+)$#';

        if ($method === 'GET' && $uri === '/contactos-misioneros') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new ContactoMisioneroController();
            $controller->listar();
            return true;
        }

        if ($method === 'GET' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new ContactoMisioneroController();
            $controller->obtener((int) $matches[1]);
            return true;
        }

        if ($method === 'POST' && $uri === '/contactos-misioneros') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new ContactoMisioneroController();
            $controller->crear();
            return true;
        }

        if ($method === 'PUT' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new ContactoMisioneroController();
            $controller->actualizar((int) $matches[1]);
            return true;
        }

        if ($method === 'DELETE' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new ContactoMisioneroController();
            $controller->eliminar((int) $matches[1]);
            return true;
        }

        return false;
    }
}
