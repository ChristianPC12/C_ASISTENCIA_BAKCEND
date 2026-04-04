<?php
declare(strict_types=1);

/**
 * Clase SuperadminUsuarioValidator
 *
 * Valida payloads del mantenimiento de cuentas SUPERADMIN.
 */
final class SuperadminUsuarioValidator
{
    private const NOMBRE_MIN = 5;
    private const NOMBRE_MAX = 40;
    private const USUARIO_MIN = 3;
    private const USUARIO_MAX = 20;
    private const PASSWORD_MIN = 12;
    private const PASSWORD_MAX = 64;
    private const NOMBRE_REGEX = '/^[\p{L}.,\'()\- ]+$/u';
    private const USUARIO_REGEX = '/^[a-z0-9._-]+$/';

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateCreate(array $data): array
    {
        return [
            'nombre_completo' => self::validateNombre($data['nombre_completo'] ?? null),
            'usuario' => self::validateUsuario($data['usuario'] ?? null),
            'password' => self::validatePassword($data['password'] ?? null)
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateUpdate(array $data): array
    {
        return [
            'nombre_completo' => self::validateNombre($data['nombre_completo'] ?? null),
            'usuario' => self::validateUsuario($data['usuario'] ?? null),
            'activo' => self::validateActivo($data['activo'] ?? null, true)
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validatePasswordUpdate(array $data): array
    {
        return [
            'password' => self::validatePassword($data['password'] ?? null)
        ];
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function validateNombre(mixed $value): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('El campo "nombre_completo" es obligatorio.');
        }

        $nombre = Sanitizer::cleanString($value);
        $len = strlen($nombre);
        if ($len < self::NOMBRE_MIN || $len > self::NOMBRE_MAX) {
            throw new InvalidArgumentException('El nombre completo debe tener entre 5 y 40 caracteres.');
        }

        if (preg_match(self::NOMBRE_REGEX, $nombre) !== 1) {
            throw new InvalidArgumentException('El nombre completo no permite números ni símbolos no válidos.');
        }

        return $nombre;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function validateUsuario(mixed $value): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('El campo "usuario" es obligatorio.');
        }

        $usuario = strtolower(Sanitizer::cleanString($value));
        $len = strlen($usuario);
        if ($len < self::USUARIO_MIN || $len > self::USUARIO_MAX) {
            throw new InvalidArgumentException('El usuario debe tener entre 3 y 20 caracteres.');
        }

        if (preg_match(self::USUARIO_REGEX, $usuario) !== 1) {
            throw new InvalidArgumentException('El usuario solo permite letras, números, punto, guion y guion bajo.');
        }

        return $usuario;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function validatePassword(mixed $value): string
    {
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException('El campo "password" es obligatorio.');
        }

        $len = strlen($value);
        if ($len < self::PASSWORD_MIN || $len > self::PASSWORD_MAX) {
            throw new InvalidArgumentException('La contraseña debe tener entre 12 y 64 caracteres.');
        }

        if (preg_match('/\s/', $value)) {
            throw new InvalidArgumentException('La contraseña no puede contener espacios.');
        }

        if (!preg_match('/[a-z]/', $value)) {
            throw new InvalidArgumentException('La contraseña debe incluir al menos una letra minúscula.');
        }

        if (!preg_match('/[A-Z]/', $value)) {
            throw new InvalidArgumentException('La contraseña debe incluir al menos una letra mayúscula.');
        }

        if (!preg_match('/\d/', $value)) {
            throw new InvalidArgumentException('La contraseña debe incluir al menos un número.');
        }

        if (!preg_match('/[^A-Za-z0-9]/', $value)) {
            throw new InvalidArgumentException('La contraseña debe incluir al menos un carácter especial.');
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param bool  $required
     * @return bool
     */
    private static function validateActivo(mixed $value, bool $required): bool
    {
        if ($value === null || $value === '') {
            if ($required) {
                throw new InvalidArgumentException('El campo "activo" es obligatorio.');
            }
            return true;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            throw new InvalidArgumentException('El campo "activo" debe ser booleano.');
        }

        return (bool) $parsed;
    }
}
