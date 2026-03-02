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
        if (strlen($data['password']) < 6) {
            throw new InvalidArgumentException('El password debe tener al menos 6 caracteres.');
        }
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
            if (strlen($data['password']) < 6) {
                throw new InvalidArgumentException('El password debe tener al menos 6 caracteres.');
            }
            $validated['password'] = $data['password'];
        }

        return $validated;
    }
}
