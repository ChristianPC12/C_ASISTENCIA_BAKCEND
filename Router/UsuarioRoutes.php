<?php
declare(strict_types=1);

/**
 * Clase UsuarioRoutes
 *
 * Enrutador para endpoints de usuarios (CRUD, solo ADMIN).
 * Solo enruta; NO valida, NO BD, NO logica de negocio.
 */
final class UsuarioRoutes
{
    /**
     * Resuelve la ruta de usuarios.
     *
     * @param string $method Metodo HTTP.
     * @param string $uri    URI normalizada.
     * @return bool true si la ruta fue resuelta, false si no coincide.
     */
    public static function resolve(string $method, string $uri): bool
    {
        $itemPattern = '#^/usuarios/(\d+)$#';
        $v2CuposRoute = '/v2/usuarios/cupos';

        // GET /v2/usuarios/cupos
        if ($method === 'GET' && $uri === $v2CuposRoute) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new UsuarioController();
            $controller->obtenerCuposV2();
            return true;
        }

        // PUT /v2/usuarios/cupos
        if ($method === 'PUT' && $uri === $v2CuposRoute) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new UsuarioController();
            $controller->actualizarCuposV2();
            return true;
        }

        // GET /usuarios
        if ($method === 'GET' && $uri === '/usuarios') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new UsuarioController();
            $controller->listar();
            return true;
        }

        // GET /usuarios/{id}
        if ($method === 'GET' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new UsuarioController();
            $controller->obtener((int) $matches[1]);
            return true;
        }

        // POST /usuarios
        if ($method === 'POST' && $uri === '/usuarios') {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new UsuarioController();
            $controller->crear();
            return true;
        }

        // PUT /usuarios/{id}
        if ($method === 'PUT' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new UsuarioController();
            $controller->actualizar((int) $matches[1]);
            return true;
        }

        // DELETE /usuarios/{id}
        if ($method === 'DELETE' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            RoleMiddleware::denySuperadminInOperative();
            $controller = new UsuarioController();
            $controller->eliminar((int) $matches[1]);
            return true;
        }

        return false;
    }
}
