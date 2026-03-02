<?php
declare(strict_types=1);

/**
 * Clase AuthValidator
 *
 * Valida los datos de entrada para autenticacion (login).
 * Solo valida/sanitiza; no toca BD ni logica de negocio.
 */
final class AuthValidator
{
    /**
     * Valida los datos de login.
     *
     * @param array<string, mixed> $data Datos del body JSON.
     * @return array{usuario: string, password: string} Datos validados.
     * @throws InvalidArgumentException Si algún campo es invalido.
     */
    public static function validateLogin(array $data): array
    {
        if (empty($data['usuario']) || !is_string($data['usuario'])) {
            throw new InvalidArgumentException('El campo "usuario" es obligatorio.');
        }

        if (empty($data['password']) || !is_string($data['password'])) {
            throw new InvalidArgumentException('El campo "password" es obligatorio.');
        }

        $usuario = Sanitizer::cleanString($data['usuario']);

        if (strlen($usuario) < 3 || strlen($usuario) > 50) {
            throw new InvalidArgumentException('El usuario debe tener entre 3 y 50 caracteres.');
        }

        return [
            'usuario'  => $usuario,
            'password' => $data['password']
        ];
    }
}
