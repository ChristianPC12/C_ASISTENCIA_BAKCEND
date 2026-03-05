<?php
declare(strict_types=1);

/**
 * Clase UsuarioValidator
 *
 * Valida los datos de entrada para operaciones de usuarios.
 * Solo valida/sanitiza; no toca BD ni logica de negocio.
 */
final class UsuarioValidator
{
    private const PASSWORD_MIN = 12;
    private const PASSWORD_MAX = 64;

    /**
     * Valida los datos para crear un usuario.
     *
     * @param array<string, mixed> $data Datos del body JSON.
     * @return array<string, mixed> Datos validados.
     * @throws InvalidArgumentException Si algun campo es invalido.
     */
    public static function validateCreate(array $data): array
    {
        $validated = [];

        if (empty($data['nombre_completo']) || !is_string($data['nombre_completo'])) {
            throw new InvalidArgumentException('El campo "nombre_completo" es obligatorio.');
        }
        $validated['nombre_completo'] = Sanitizer::cleanString($data['nombre_completo']);
        if (strlen($validated['nombre_completo']) < 3 || strlen($validated['nombre_completo']) > 120) {
            throw new InvalidArgumentException('El nombre completo debe tener entre 3 y 120 caracteres.');
        }

        if (empty($data['usuario']) || !is_string($data['usuario'])) {
            throw new InvalidArgumentException('El campo "usuario" es obligatorio.');
        }
        $validated['usuario'] = Sanitizer::cleanString($data['usuario']);
        if (strlen($validated['usuario']) < 3 || strlen($validated['usuario']) > 50) {
            throw new InvalidArgumentException('El usuario debe tener entre 3 y 50 caracteres.');
        }

        if (empty($data['password']) || !is_string($data['password'])) {
            throw new InvalidArgumentException('El campo "password" es obligatorio.');
        }
        self::validateStrongPassword($data['password']);
        $validated['password'] = $data['password'];

        if (!isset($data['rol_id']) || !is_numeric($data['rol_id'])) {
            throw new InvalidArgumentException('El campo "rol_id" es obligatorio y debe ser numerico.');
        }
        $validated['rol_id'] = (int) $data['rol_id'];
        if ($validated['rol_id'] < 1 || $validated['rol_id'] > 2) {
            throw new InvalidArgumentException('El rol_id debe ser 1 (ADMIN) o 2 (SECRETARIO).');
        }

        return $validated;
    }

    /**
     * Valida los datos para actualizar un usuario.
     *
     * @param array<string, mixed> $data Datos del body JSON.
     * @return array<string, mixed> Datos validados.
     * @throws InvalidArgumentException Si algun campo es invalido.
     */
    public static function validateUpdate(array $data): array
    {
        $validated = [];

        if (empty($data['nombre_completo']) || !is_string($data['nombre_completo'])) {
            throw new InvalidArgumentException('El campo "nombre_completo" es obligatorio.');
        }
        $validated['nombre_completo'] = Sanitizer::cleanString($data['nombre_completo']);
        if (strlen($validated['nombre_completo']) < 3 || strlen($validated['nombre_completo']) > 120) {
            throw new InvalidArgumentException('El nombre completo debe tener entre 3 y 120 caracteres.');
        }

        if (empty($data['usuario']) || !is_string($data['usuario'])) {
            throw new InvalidArgumentException('El campo "usuario" es obligatorio.');
        }
        $validated['usuario'] = Sanitizer::cleanString($data['usuario']);
        if (strlen($validated['usuario']) < 3 || strlen($validated['usuario']) > 50) {
            throw new InvalidArgumentException('El usuario debe tener entre 3 y 50 caracteres.');
        }

        if (!isset($data['rol_id']) || !is_numeric($data['rol_id'])) {
            throw new InvalidArgumentException('El campo "rol_id" es obligatorio y debe ser numerico.');
        }
        $validated['rol_id'] = (int) $data['rol_id'];
        if ($validated['rol_id'] < 1 || $validated['rol_id'] > 2) {
            throw new InvalidArgumentException('El rol_id debe ser 1 (ADMIN) o 2 (SECRETARIO).');
        }

        $validated['activo'] = isset($data['activo']) ? (bool) $data['activo'] : true;

        // Password opcional en update
        if (!empty($data['password'])) {
            self::validateStrongPassword($data['password']);
            $validated['password'] = $data['password'];
        }

        return $validated;
    }

    /**
     * Valida politicas de password fuerte.
     *
     * @param string $password
     * @return void
     */
    private static function validateStrongPassword(string $password): void
    {
        $len = strlen($password);
        if ($len < self::PASSWORD_MIN || $len > self::PASSWORD_MAX) {
            throw new InvalidArgumentException('El password debe tener entre 12 y 64 caracteres.');
        }

        if (preg_match('/\s/', $password)) {
            throw new InvalidArgumentException('El password no puede contener espacios.');
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException('El password debe incluir al menos una letra minuscula.');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException('El password debe incluir al menos una letra mayuscula.');
        }

        if (!preg_match('/\d/', $password)) {
            throw new InvalidArgumentException('El password debe incluir al menos un numero.');
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new InvalidArgumentException('El password debe incluir al menos un caracter especial.');
        }
    }
}
