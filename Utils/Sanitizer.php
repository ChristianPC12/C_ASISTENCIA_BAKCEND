<?php
declare(strict_types=1);

/**
 * Clase Sanitizer
 *
 * Utilidades para leer y sanitizar datos de entrada.
 */
final class Sanitizer
{
    /**
     * Lee y decodifica el body JSON de la peticion.
     *
     * @return array<string, mixed> Datos del body como array asociativo.
     * @throws InvalidArgumentException Si el body no es JSON valido.
     */
    public static function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === '' || $raw === false) {
            throw new InvalidArgumentException('El cuerpo de la peticion esta vacio.');
        }

        $data = json_decode($raw, true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('El cuerpo de la peticion no es JSON valido.');
        }

        return $data;
    }

    /**
     * Sanitiza un string eliminando tags HTML y espacios extremos.
     *
     * @param string $value Valor a sanitizar.
     * @return string Valor sanitizado.
     */
    public static function cleanString(string $value): string
    {
        return trim(strip_tags($value));
    }

    /**
     * Sanitiza un entero desde un valor mixto.
     *
     * @param mixed $value Valor a convertir.
     * @return int Valor entero.
     * @throws InvalidArgumentException Si no es numerico.
     */
    public static function cleanInt(mixed $value): int
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Se espera un valor numerico entero.');
        }

        return (int) $value;
    }
}
