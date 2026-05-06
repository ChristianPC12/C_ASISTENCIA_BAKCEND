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
        $validated['cargo'] = self::validateCargo($data['cargo'] ?? null);

        if (empty($data['password']) || !is_string($data['password'])) {
            throw new InvalidArgumentException('El campo "password" es obligatorio.');
        }
        self::validateStrongPassword($data['password']);
        $validated['password'] = $data['password'];

        if (!isset($data['rol_id']) || !is_numeric($data['rol_id'])) {
            throw new InvalidArgumentException('El campo "rol_id" es obligatorio y debe ser numerico.');
        }
        $validated['rol_id'] = (int) $data['rol_id'];
        if ($validated['rol_id'] < 1) {
            throw new InvalidArgumentException('El rol_id no es valido.');
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
        $validated['cargo'] = self::validateCargo($data['cargo'] ?? null);

        if (!isset($data['rol_id']) || !is_numeric($data['rol_id'])) {
            throw new InvalidArgumentException('El campo "rol_id" es obligatorio y debe ser numerico.');
        }
        $validated['rol_id'] = (int) $data['rol_id'];
        if ($validated['rol_id'] < 1) {
            throw new InvalidArgumentException('El rol_id no es valido.');
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
     * Valida payload para configuracion de cupos por rol.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateCuposRoles(array $data): array
    {
        if (!isset($data['cupos']) || !is_array($data['cupos'])) {
            throw new InvalidArgumentException('El campo "cupos" es obligatorio y debe ser una lista.');
        }

        if (count($data['cupos']) < 1) {
            throw new InvalidArgumentException('Debe enviar al menos un cupo por rol.');
        }

        $cupos = [];
        $roles = [];

        foreach (array_values($data['cupos']) as $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException('Cada elemento de "cupos" debe ser un objeto valido.');
            }

            if (!isset($item['rol_nombre']) || !is_string($item['rol_nombre'])) {
                throw new InvalidArgumentException('Cada cupo requiere "rol_nombre".');
            }

            $rolNombre = strtoupper(Sanitizer::cleanString($item['rol_nombre']));
            if (preg_match('/^[A-Z_]{3,40}$/', $rolNombre) !== 1) {
                throw new InvalidArgumentException('Cada "rol_nombre" debe tener formato valido (A-Z y guion bajo).');
            }

            if ($rolNombre === 'SUPERADMIN') {
                throw new InvalidArgumentException('No se permite configurar cupos para SUPERADMIN en instancia.');
            }

            if (isset($roles[$rolNombre])) {
                throw new InvalidArgumentException('No se permiten roles duplicados en "cupos".');
            }
            $roles[$rolNombre] = true;

            if (!array_key_exists('cupo_maximo', $item) || !is_numeric($item['cupo_maximo'])) {
                throw new InvalidArgumentException('Cada cupo requiere "cupo_maximo" numerico.');
            }

            $cupoMaximo = (int) $item['cupo_maximo'];
            if ($cupoMaximo < 0 || $cupoMaximo > 999) {
                throw new InvalidArgumentException('Cada "cupo_maximo" debe estar entre 0 y 999.');
            }

            $activo = true;
            if (array_key_exists('activo', $item)) {
                $parsed = filter_var($item['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($parsed === null) {
                    throw new InvalidArgumentException('Cada "activo" de cupo debe ser booleano.');
                }
                $activo = (bool) $parsed;
            }

            $cupos[] = [
                'rol_nombre' => $rolNombre,
                'cupo_maximo' => $cupoMaximo,
                'activo' => $activo
            ];
        }

        return ['cupos' => $cupos];
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

    private static function validateCargo(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException('El cargo debe ser texto.');
        }

        $cargo = Sanitizer::cleanString($value);
        if ($cargo === '') {
            return null;
        }

        if (strlen($cargo) > 120) {
            throw new InvalidArgumentException('El cargo no puede superar 120 caracteres.');
        }

        return $cargo;
    }
}
