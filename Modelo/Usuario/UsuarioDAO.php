<?php
declare(strict_types=1);

/**
 * Clase UsuarioDAO
 *
 * Acceso a datos para la entidad Usuario.
 * Solo retorna UsuarioDTO o booleanos.
 */
final class UsuarioDAO
{
    /** @var PDO */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    /**
     * Busca un usuario por su nombre de usuario (login).
     *
     * @param string $usuario Nombre de usuario.
     * @return UsuarioDTO|null
     */
    public function findByUsuario(string $usuario): ?UsuarioDTO
    {
        $sql = "SELECT u.id, u.nombre_completo, u.usuario, u.password_hash,
                       u.password_actualizada_en, u.rol_id, r.nombre AS rol_nombre, u.activo,
                       u.creado_en, u.actualizado_en
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.usuario = :usuario";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario' => $usuario]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return UsuarioMapper::fromRow($row);
    }

    /**
     * Busca un usuario por ID.
     *
     * @param int $id ID del usuario.
     * @return UsuarioDTO|null
     */
    public function findById(int $id): ?UsuarioDTO
    {
        $sql = "SELECT u.id, u.nombre_completo, u.usuario, u.password_hash,
                       u.password_actualizada_en, u.rol_id, r.nombre AS rol_nombre, u.activo,
                       u.creado_en, u.actualizado_en
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return UsuarioMapper::fromRow($row);
    }

    /**
     * Lista todos los usuarios activos.
     *
     * @return UsuarioDTO[]
     */
    public function findAll(): array
    {
        $sql = "SELECT u.id, u.nombre_completo, u.usuario, u.password_hash,
                       u.password_actualizada_en, u.rol_id, r.nombre AS rol_nombre, u.activo,
                       u.creado_en, u.actualizado_en
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                ORDER BY u.id ASC";

        $stmt = $this->pdo->query($sql);
        $usuarios = [];

        while ($row = $stmt->fetch()) {
            $usuarios[] = UsuarioMapper::fromRow($row);
        }

        return $usuarios;
    }

    /**
     * Inserta un nuevo usuario.
     *
     * @param string $nombreCompleto Nombre completo.
     * @param string $usuario        Nombre de usuario (login).
     * @param string $passwordHash   Hash del password.
     * @param int    $rolId          ID del rol.
     * @return int ID del usuario insertado.
     */
    public function insert(string $nombreCompleto, string $usuario, string $passwordHash, int $rolId): int
    {
        $sql = "INSERT INTO usuarios (nombre_completo, usuario, password_hash, password_actualizada_en, rol_id)
                VALUES (:nombre_completo, :usuario, :password_hash, NOW(), :rol_id)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nombre_completo' => $nombreCompleto,
            ':usuario'         => $usuario,
            ':password_hash'   => $passwordHash,
            ':rol_id'          => $rolId
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un usuario existente (nombre, usuario, rol, activo).
     *
     * @param int    $id              ID del usuario.
     * @param string $nombreCompleto  Nombre completo.
     * @param string $usuario         Nombre de usuario.
     * @param int    $rolId           ID del rol.
     * @param bool   $activo          Estado activo.
     * @return bool
     */
    public function update(int $id, string $nombreCompleto, string $usuario, int $rolId, bool $activo): bool
    {
        $sql = "UPDATE usuarios
                SET nombre_completo = :nombre_completo,
                    usuario = :usuario,
                    rol_id = :rol_id,
                    activo = :activo
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nombre_completo' => $nombreCompleto,
            ':usuario'         => $usuario,
            ':rol_id'          => $rolId,
            ':activo'          => $activo ? 1 : 0,
            ':id'              => $id
        ]);
    }

    /**
     * Actualiza el password de un usuario.
     *
     * @param int    $id           ID del usuario.
     * @param string $passwordHash Nuevo hash del password.
     * @return bool
     */
    public function updatePassword(int $id, string $passwordHash): bool
    {
        $sql = "UPDATE usuarios
                SET password_hash = :password_hash,
                    password_actualizada_en = NOW()
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':password_hash' => $passwordHash,
            ':id'            => $id
        ]);
    }

    /**
     * Desactiva un usuario (soft delete).
     *
     * @param int $id ID del usuario.
     * @return bool
     */
    public function deactivate(int $id): bool
    {
        $sql = "UPDATE usuarios SET activo = 0 WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Verifica si un nombre de usuario ya existe (excluyendo un ID).
     *
     * @param string   $usuario    Nombre de usuario.
     * @param int|null $excludeId  ID a excluir (para updates).
     * @return bool
     */
    public function existsByUsuario(string $usuario, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(id) FROM usuarios WHERE usuario = :usuario";
        $params = [':usuario' => $usuario];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Desactiva usuarios con password vencido.
     *
     * @return int Cantidad de filas afectadas.
     */
    public function deactivateExpiredPasswords(): int
    {
        $sql = "UPDATE usuarios
                SET activo = 0
                WHERE activo = 1
                  AND password_actualizada_en IS NOT NULL
                  AND DATE_ADD(password_actualizada_en, INTERVAL " . (int) PASSWORD_EXPIRY_DAYS . " DAY) <= NOW()";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Valida si el password de un usuario vencio por fecha.
     *
     * @param UsuarioDTO $usuario
     * @return bool
     */
    public function isPasswordExpired(UsuarioDTO $usuario): bool
    {
        $fecha = $usuario->passwordActualizadaEn !== ''
            ? $usuario->passwordActualizadaEn
            : $usuario->creadoEn;

        if ($fecha === '') {
            return true;
        }

        try {
            $base = new DateTimeImmutable($fecha);
            $limite = $base->modify('+' . PASSWORD_EXPIRY_DAYS . ' days');
            $ahora = new DateTimeImmutable('now');
            return $ahora >= $limite;
        } catch (\Throwable $e) {
            return true;
        }
    }
}
