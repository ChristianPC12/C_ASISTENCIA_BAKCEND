<?php
declare(strict_types=1);

/**
 * Clase AuthRoutes
 *
 * Enrutador para endpoints de autenticacion.
 * Solo enruta; NO valida, NO BD, NO logica de negocio.
 */
final class AuthRoutes
{
    /**
     * Resuelve la ruta de autenticacion.
     *
     * @param string $method Metodo HTTP (GET, POST, etc.).
     * @param string $uri    URI normalizada (sin base path ni query string).
     * @return bool true si la ruta fue resuelta, false si no coincide.
     */
    public static function resolve(string $method, string $uri): bool
    {
        $controller = new AuthController();

        // POST /auth/login
        if ($method === 'POST' && $uri === '/auth/login') {
            $controller->login();
            return true;
        }

        // POST /auth/logout
        if ($method === 'POST' && $uri === '/auth/logout') {
            AuthMiddleware::handle();
            $controller->logout();
            return true;
        }

        // GET /auth/me
        if ($method === 'GET' && $uri === '/auth/me') {
            AuthMiddleware::handle();
            $controller->me();
            return true;
        }

        return false;
    }
}
