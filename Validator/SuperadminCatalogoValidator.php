<?php
declare(strict_types=1);

/**
 * Clase SuperadminCatalogoValidator
 *
 * Valida payloads para CRUD de catalogos de superadmin (campos y distritos).
 */
final class SuperadminCatalogoValidator
{
    private const NOMBRE_MIN = 3;
    private const NOMBRE_MAX = 120;

    /**
     * Valida payload de creacion de campo.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateCreateCampo(array $data): array
    {
        return [
            'codigo' => self::validateCodigoCampo($data['codigo'] ?? null, false),
            'nombre' => self::validateNombre($data['nombre'] ?? null),
            'activo' => self::validateActivo($data['activo'] ?? null, false)
        ];
    }

    /**
     * Valida payload de actualizacion de campo.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateUpdateCampo(array $data): array
    {
        return [
            'nombre' => self::validateNombre($data['nombre'] ?? null),
            'activo' => self::validateActivo($data['activo'] ?? null, false)
        ];
    }

    /**
     * Valida payload de creacion de distrito.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateCreateDistrito(array $data): array
    {
        return [
            'codigo' => self::validateCodigoDistrito($data['codigo'] ?? null, false),
            'nombre' => self::validateNombre($data['nombre'] ?? null),
            'activo' => self::validateActivo($data['activo'] ?? null, false)
        ];
    }

    /**
     * Valida payload de actualizacion de distrito.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateUpdateDistrito(array $data): array
    {
        return [
            'nombre' => self::validateNombre($data['nombre'] ?? null),
            'activo' => self::validateActivo($data['activo'] ?? null, false)
        ];
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function validateNombre(mixed $value): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('El campo "nombre" es obligatorio.');
        }

        $nombre = Sanitizer::cleanString($value);
        $len = strlen($nombre);
        if ($len < self::NOMBRE_MIN || $len > self::NOMBRE_MAX) {
            throw new InvalidArgumentException('El campo "nombre" debe tener entre 3 y 120 caracteres.');
        }

        return $nombre;
    }

    /**
     * @param mixed $value
     * @param bool  $required
     * @return string|null
     */
    private static function validateCodigoCampo(mixed $value, bool $required): ?string
    {
        if ($value === null || $value === '') {
            if ($required) {
                throw new InvalidArgumentException('El campo "codigo" es obligatorio.');
            }
            return null;
        }

        if (!is_scalar($value)) {
            throw new InvalidArgumentException('El campo "codigo" debe ser texto.');
        }

        $codigo = strtoupper(Sanitizer::cleanString((string) $value));
        if (preg_match('/^[A-Z0-9]{2,10}$/', $codigo) !== 1) {
            throw new InvalidArgumentException('El codigo de campo debe ser alfanumerico (2 a 10 caracteres).');
        }

        return $codigo;
    }

    /**
     * @param mixed $value
     * @param bool  $required
     * @return string|null
     */
    private static function validateCodigoDistrito(mixed $value, bool $required): ?string
    {
        if ($value === null || $value === '') {
            if ($required) {
                throw new InvalidArgumentException('El campo "codigo" es obligatorio.');
            }
            return null;
        }

        if (!is_scalar($value)) {
            throw new InvalidArgumentException('El campo "codigo" debe ser texto.');
        }

        $codigo = strtoupper(Sanitizer::cleanString((string) $value));
        if (preg_match('/^[A-Z0-9_]{2,24}$/', $codigo) !== 1) {
            throw new InvalidArgumentException(
                'El codigo de distrito debe ser alfanumerico con guion bajo (2 a 24 caracteres).'
            );
        }

        return $codigo;
    }

    /**
     * @param mixed $value
     * @param bool  $required
     * @return bool|null
     */
    private static function validateActivo(mixed $value, bool $required): ?bool
    {
        if ($value === null || $value === '') {
            if ($required) {
                throw new InvalidArgumentException('El campo "activo" es obligatorio.');
            }
            return null;
        }

        if (!is_bool($value) && !is_numeric($value) && !is_string($value)) {
            throw new InvalidArgumentException('El campo "activo" debe ser booleano.');
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            throw new InvalidArgumentException('El campo "activo" debe ser booleano.');
        }

        return (bool) $parsed;
    }
}
