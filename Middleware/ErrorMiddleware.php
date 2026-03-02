<?php
declare(strict_types=1);

/**
 * Clase ErrorMiddleware
 *
 * Registra un manejador global de errores y excepciones no capturadas.
 * Convierte errores fatales en excepciones y devuelve JSON generico.
 */
final class ErrorMiddleware
{
    /**
     * Registra los handlers de error y excepcion.
     *
     * @return void
     */
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    /**
     * Convierte errores PHP en ErrorException.
     *
     * @param int    $severity Nivel de severidad.
     * @param string $message  Mensaje del error.
     * @param string $file     Archivo donde ocurrio.
     * @param int    $line     Linea donde ocurrio.
     * @return bool
     * @throws ErrorException
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Maneja excepciones no capturadas y devuelve JSON 500.
     *
     * @param Throwable $e Excepcion no capturada.
     * @return void
     */
    public static function handleException(\Throwable $e): void
    {
        error_log('[ERROR NO CAPTURADO] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode([
            'exito'   => false,
            'mensaje' => 'Error interno del servidor.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}
