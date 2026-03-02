<?php
declare(strict_types=1);

/**
 * Clase UsuarioService
 *
 * Logica de negocio para la entidad Usuario.
 * No toca $_SERVER, $_POST, $_GET.
 */
final class UsuarioService
{
    /** @var UsuarioDAO */
    private UsuarioDAO $usuarioDAO;

    public function __construct()
    {
        $this->usuarioDAO = new UsuarioDAO();
    }

    /**
     * Lista todos los usuarios.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(): array
    {
        $usuarios  = $this->usuarioDAO->findAll();
        $resultado = [];

        foreach ($usuarios as $usuario) {
            $resultado[] = UsuarioMapper::toArray($usuario);
        }

        return $resultado;
    }

    /**
     * Obtiene un usuario por ID.
     *
     * @param int $id ID del usuario.
     * @return array<string, mixed>
     * @throws RuntimeException Si no se encuentra.
     */
    public function obtenerPorId(int $id): array
    {
        $usuario = $this->usuarioDAO->findById($id);

        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        return UsuarioMapper::toArray($usuario);
    }

    /**
     * Crea un nuevo usuario.
     *
     * @param array<string, mixed> $data Datos validados.
     * @return array<string, mixed> Datos del usuario creado.
     * @throws RuntimeException Si el usuario ya existe.
     */
    public function crear(array $data): array
    {
        if ($this->usuarioDAO->existsByUsuario($data['usuario'])) {
            throw new RuntimeException('El nombre de usuario ya esta registrado.');
        }

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $id = $this->usuarioDAO->insert(
            $data['nombre_completo'],
            $data['usuario'],
            $passwordHash,
            $data['rol_id']
        );

        return $this->obtenerPorId($id);
    }

    /**
     * Actualiza un usuario existente.
     *
     * @param int                  $id   ID del usuario.
     * @param array<string, mixed> $data Datos validados.
     * @return array<string, mixed> Datos del usuario actualizado.
     * @throws RuntimeException Si no se encuentra o hay duplicado.
     */
    public function actualizar(int $id, array $data): array
    {
        $usuario = $this->usuarioDAO->findById($id);
        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        if ($this->usuarioDAO->existsByUsuario($data['usuario'], $id)) {
            throw new RuntimeException('El nombre de usuario ya esta registrado por otro usuario.');
        }

        $this->usuarioDAO->update(
            $id,
            $data['nombre_completo'],
            $data['usuario'],
            $data['rol_id'],
            $data['activo']
        );

        // Actualizar password si se envio
        if (!empty($data['password'])) {
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            $this->usuarioDAO->updatePassword($id, $passwordHash);
        }

        return $this->obtenerPorId($id);
    }

    /**
     * Desactiva un usuario (soft delete).
     *
     * @param int $id ID del usuario.
     * @return void
     * @throws RuntimeException Si no se encuentra.
     */
    public function eliminar(int $id): void
    {
        $usuario = $this->usuarioDAO->findById($id);
        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        $this->usuarioDAO->deactivate($id);
    }
}
