<?php
declare(strict_types=1);

/**
 * Clase CorsMiddleware
 *
 * Gestiona los encabezados CORS para permitir peticiones cross-origin.
 */
final class CorsMiddleware
{
    /**
     * Aplica los headers CORS a la respuesta.
     * Si es preflight (OPTIONS), responde 204 y termina.
     *
     * @return void
     */
    public static function handle(): void
    {
        header('Access-Control-Allow-Origin: ' . CORS_ORIGIN);
        header('Access-Control-Allow-Methods: ' . CORS_METHODS);
        header('Access-Control-Allow-Headers: ' . CORS_HEADERS);
        header('Access-Control-Max-Age: 86400');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
