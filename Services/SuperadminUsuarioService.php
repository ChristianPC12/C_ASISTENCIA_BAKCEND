<?php
declare(strict_types=1);

/**
 * Clase SuperadminUsuarioService
 *
 * Lógica de negocio para mantenimiento de cuentas SUPERADMIN.
 */
final class SuperadminUsuarioService
{
    /** @var UsuarioDAO */
    private UsuarioDAO $usuarioDAO;

    /** @var TokenDAO */
    private TokenDAO $tokenDAO;

    public function __construct()
    {
        $this->usuarioDAO = new UsuarioDAO();
        $this->tokenDAO = new TokenDAO();
    }

    /**
     * Lista superadministradores globales.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(): array
    {
        $items = [];
        foreach ($this->usuarioDAO->findAllSuperadmins() as $usuario) {
            $items[] = UsuarioMapper::toArray($usuario);
        }

        return $items;
    }

    /**
     * Crea un superadministrador.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crear(array $data): array
    {
        if ($this->usuarioDAO->existsByUsuario((string) $data['usuario'])) {
            throw new RuntimeException('Ese usuario ya existe.');
        }

        $rolId = $this->usuarioDAO->findRolIdByNombre('SUPERADMIN');
        if ($rolId === null) {
            throw new RuntimeException('No se encontró el rol SUPERADMIN.');
        }

        $passwordHash = password_hash((string) $data['password'], PASSWORD_BCRYPT);
        if (!is_string($passwordHash)) {
            throw new RuntimeException('No fue posible generar la contraseña segura.');
        }

        $id = $this->usuarioDAO->insertSuperadmin(
            (string) $data['nombre_completo'],
            (string) $data['usuario'],
            $passwordHash,
            $rolId
        );

        $creado = $this->usuarioDAO->findSuperadminById($id);
        if ($creado === null) {
            throw new RuntimeException('No fue posible recuperar el superadministrador creado.');
        }

        return UsuarioMapper::toArray($creado);
    }

    /**
     * Actualiza un superadministrador.
     *
     * @param int                  $id
     * @param array<string, mixed> $data
     * @param int                  $usuarioAutenticadoId
     * @return array<string, mixed>
     */
    public function actualizar(int $id, array $data, int $usuarioAutenticadoId): array
    {
        $existente = $this->usuarioDAO->findSuperadminById($id);
        if ($existente === null) {
            throw new OutOfBoundsException('Superadministrador no encontrado.');
        }

        if ($this->usuarioDAO->existsByUsuario((string) $data['usuario'], $id)) {
            throw new RuntimeException('Ese usuario ya existe.');
        }

        $activoDestino = (bool) $data['activo'];
        if (!$activoDestino && $id === $usuarioAutenticadoId) {
            throw new RuntimeException('No puedes desactivar tu propia cuenta.');
        }

        if (!$activoDestino && $this->usuarioDAO->countActivosSuperadmin($id) < 1) {
            throw new RuntimeException('Debe mantenerse al menos un superadministrador activo.');
        }

        $this->usuarioDAO->updateSuperadmin(
            $id,
            (string) $data['nombre_completo'],
            (string) $data['usuario'],
            $activoDestino
        );

        if (!$activoDestino) {
            $this->tokenDAO->deleteByUsuarioId($id);
        }

        $actualizado = $this->usuarioDAO->findSuperadminById($id);
        if ($actualizado === null) {
            throw new RuntimeException('No fue posible recuperar el superadministrador actualizado.');
        }

        return UsuarioMapper::toArray($actualizado);
    }

    /**
     * Actualiza la contraseña de un superadministrador.
     *
     * @param int                  $id
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function actualizarPassword(int $id, array $data): array
    {
        $existente = $this->usuarioDAO->findSuperadminById($id);
        if ($existente === null) {
            throw new OutOfBoundsException('Superadministrador no encontrado.');
        }

        $passwordHash = password_hash((string) $data['password'], PASSWORD_BCRYPT);
        if (!is_string($passwordHash)) {
            throw new RuntimeException('No fue posible actualizar la contraseña.');
        }

        $this->usuarioDAO->updatePassword($id, $passwordHash);
        $this->tokenDAO->deleteByUsuarioId($id);

        $actualizado = $this->usuarioDAO->findSuperadminById($id);
        if ($actualizado === null) {
            throw new RuntimeException('No fue posible recuperar el superadministrador actualizado.');
        }

        return UsuarioMapper::toArray($actualizado);
    }
}
