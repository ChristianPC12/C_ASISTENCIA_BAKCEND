<?php
declare(strict_types=1);

/**
 * Clase JsonResponse
 *
 * Unica forma de enviar respuestas JSON al cliente.
 * Estandariza el formato: { exito, mensaje, datos? }
 */
final class JsonResponse
{
    /** @var string|null */
    private static ?string $requestIdV2 = null;

    /**
     * Envia una respuesta JSON y termina la ejecucion.
     *
     * @param int         $httpCode Codigo HTTP (200, 201, 400, 401, 404, 500...).
     * @param bool        $exito    Indica si la operacion fue exitosa.
     * @param string      $mensaje  Mensaje descriptivo en espanol.
     * @param mixed|null  $datos    Datos opcionales (array, objeto, null).
     * @return never
     */
    public static function send(int $httpCode, bool $exito, string $mensaje, mixed $datos = null): never
    {
        $respuesta = [
            'exito'   => $exito,
            'mensaje' => $mensaje
        ];

        if ($datos !== null) {
            $respuesta['datos'] = $datos;
        }

        self::emit($httpCode, $respuesta);
    }

    /**
     * Envia respuesta de exito para API v2.
     *
     * @param int        $httpCode Codigo HTTP de exito.
     * @param string     $mensaje  Mensaje descriptivo.
     * @param mixed|null $datos    Payload opcional.
     * @return never
     */
    public static function sendV2Success(int $httpCode, string $mensaje, mixed $datos = null): never
    {
        $respuesta = [
            'exito'   => true,
            'mensaje' => $mensaje
        ];

        if ($datos !== null) {
            $respuesta['datos'] = $datos;
        }

        $respuesta['meta'] = [
            'version' => 'v2',
            'request_id' => self::getRequestIdV2()
        ];

        self::emit($httpCode, $respuesta);
    }

    /**
     * Envia respuesta de error para API v2.
     *
     * @param int        $httpCode Codigo HTTP de error.
     * @param string     $codigo   Codigo canonico del error.
     * @param string     $mensaje  Mensaje descriptivo.
     * @param mixed|null $detalles Detalles opcionales para debug funcional.
     * @return never
     */
    public static function sendV2Error(
        int $httpCode,
        string $codigo,
        string $mensaje,
        mixed $detalles = null
    ): never {
        $respuesta = [
            'exito'   => false,
            'codigo'  => $codigo,
            'mensaje' => $mensaje
        ];

        if ($detalles !== null) {
            $respuesta['detalles'] = $detalles;
        }

        $respuesta['meta'] = [
            'version' => 'v2',
            'request_id' => self::getRequestIdV2()
        ];

        self::emit($httpCode, $respuesta);
    }

    /**
     * Emite JSON y finaliza ejecucion.
     *
     * @param int                  $httpCode
     * @param array<string, mixed> $payload
     * @return never
     */
    private static function emit(int $httpCode, array $payload): never
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Obtiene request_id estable por request para respuestas v2.
     *
     * @return string
     */
    private static function getRequestIdV2(): string
    {
        if (self::$requestIdV2 !== null) {
            return self::$requestIdV2;
        }

        $header = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
        if (is_string($header)) {
            $header = trim($header);
            if (preg_match('/^[A-Za-z0-9._-]{6,80}$/', $header) === 1) {
                self::$requestIdV2 = $header;
                return self::$requestIdV2;
            }
        }

        try {
            self::$requestIdV2 = 'req_' . bin2hex(random_bytes(6));
        } catch (\Throwable $e) {
            self::$requestIdV2 = 'req_' . str_replace('.', '', uniqid('', true));
        }

        return self::$requestIdV2;
    }
}
