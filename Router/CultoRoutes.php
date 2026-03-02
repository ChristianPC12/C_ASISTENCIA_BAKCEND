<?php
declare(strict_types=1);

/**
 * Clase CultoRoutes
 *
 * Enrutador para endpoints de cultos.
 * Solo enruta; NO valida, NO BD, NO logica de negocio.
 */
final class CultoRoutes
{
    /**
     * Resuelve la ruta de cultos.
     *
     * @param string $method Metodo HTTP.
     * @param string $uri    URI normalizada.
     * @return bool true si la ruta fue resuelta, false si no coincide.
     */
    public static function resolve(string $method, string $uri): bool
    {
        // GET /cultos
        if ($method === 'GET' && $uri === '/cultos') {
            AuthMiddleware::handle();
            $controller = new CultoController();
            $controller->listar();
            return true;
        }

        return false;
    }
}
