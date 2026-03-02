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
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        $respuesta = [
            'exito'   => $exito,
            'mensaje' => $mensaje
        ];

        if ($datos !== null) {
            $respuesta['datos'] = $datos;
        }

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
