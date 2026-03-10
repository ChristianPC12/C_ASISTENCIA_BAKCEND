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
     * @return array<int, string>
     */
    private static function obtenerOrigenesPermitidos(): array
    {
        $raw = CORS_ALLOWED_ORIGINS;
        $partes = array_map('trim', explode(',', (string)$raw));
        return array_values(array_filter($partes, static fn ($item) => $item !== ''));
    }

    private static function resolverOrigenPermitido(): ?string
    {
        $origenRequest = isset($_SERVER['HTTP_ORIGIN']) ? trim((string)$_SERVER['HTTP_ORIGIN']) : '';
        if ($origenRequest === '') {
            return null;
        }

        $permitidos = self::obtenerOrigenesPermitidos();
        if (in_array('*', $permitidos, true)) {
            return '*';
        }

        if (in_array($origenRequest, $permitidos, true)) {
            return $origenRequest;
        }

        return '';
    }

    /**
     * Aplica los headers CORS a la respuesta.
     * Si es preflight (OPTIONS), responde 204 y termina.
     *
     * @return void
     */
    public static function handle(): void
    {
        $origenPermitido = self::resolverOrigenPermitido();

        if ($origenPermitido === '') {
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(403);
                exit;
            }
        } elseif ($origenPermitido !== null) {
            header('Access-Control-Allow-Origin: ' . $origenPermitido);
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: ' . CORS_METHODS);
        header('Access-Control-Allow-Headers: ' . CORS_HEADERS);
        header('Access-Control-Max-Age: 86400');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
