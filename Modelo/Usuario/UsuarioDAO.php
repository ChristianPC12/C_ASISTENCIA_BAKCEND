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
    private const ADMIN_TEMPORAL_DIAS = 5;

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
                       u.password_actualizada_en, u.password_expira_en, u.rol_id, r.nombre AS rol_nombre, u.activo,
                       u.organizacion_id, o.codigo_instancia, o.tipo_organizacion, o.nombre_organizacion,
                       o.activa AS organizacion_activa, c.codigo AS campo_codigo, c.nombre AS campo_nombre,
                       d.codigo AS distrito_codigo, d.nombre AS distrito_nombre,
                       u.creado_en, u.actualizado_en
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                LEFT JOIN organizaciones o ON o.id = u.organizacion_id
                LEFT JOIN campos c ON c.id = o.campo_id
                LEFT JOIN distritos d ON d.id = o.distrito_id
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
                       u.password_actualizada_en, u.password_expira_en, u.rol_id, r.nombre AS rol_nombre, u.activo,
                       u.organizacion_id, o.codigo_instancia, o.tipo_organizacion, o.nombre_organizacion,
                       o.activa AS organizacion_activa, c.codigo AS campo_codigo, c.nombre AS campo_nombre,
                       d.codigo AS distrito_codigo, d.nombre AS distrito_nombre,
                       u.creado_en, u.actualizado_en
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                LEFT JOIN organizaciones o ON o.id = u.organizacion_id
                LEFT JOIN campos c ON c.id = o.campo_id
                LEFT JOIN distritos d ON d.id = o.distrito_id
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
                       u.password_actualizada_en, u.password_expira_en, u.rol_id, r.nombre AS rol_nombre, u.activo,
                       u.organizacion_id, o.codigo_instancia, o.tipo_organizacion, o.nombre_organizacion,
                       o.activa AS organizacion_activa, c.codigo AS campo_codigo, c.nombre AS campo_nombre,
                       d.codigo AS distrito_codigo, d.nombre AS distrito_nombre,
                       u.creado_en, u.actualizado_en
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                LEFT JOIN organizaciones o ON o.id = u.organizacion_id
                LEFT JOIN campos c ON c.id = o.campo_id
                LEFT JOIN distritos d ON d.id = o.distrito_id
                ORDER BY u.id ASC";

        $stmt = $this->pdo->query($sql);
        $usuarios = [];

        while ($row = $stmt->fetch()) {
            $usuarios[] = UsuarioMapper::fromRow($row);
        }

        return $usuarios;
    }

    /**
     * Lista usuarios activos/inactivos dentro de una organizacion.
     *
     * @param int $organizacionId
     * @return UsuarioDTO[]
     */
    public function findAllByOrganizacion(int $organizacionId): array
    {
        $sql = "SELECT u.id, u.nombre_completo, u.usuario, u.password_hash,
                       u.password_actualizada_en, u.password_expira_en, u.rol_id, r.nombre AS rol_nombre, u.activo,
                       u.organizacion_id, o.codigo_instancia, o.tipo_organizacion, o.nombre_organizacion,
                       o.activa AS organizacion_activa, c.codigo AS campo_codigo, c.nombre AS campo_nombre,
                       d.codigo AS distrito_codigo, d.nombre AS distrito_nombre,
                       u.creado_en, u.actualizado_en
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                LEFT JOIN organizaciones o ON o.id = u.organizacion_id
                LEFT JOIN campos c ON c.id = o.campo_id
                LEFT JOIN distritos d ON d.id = o.distrito_id
                WHERE u.organizacion_id = :organizacion_id
                  AND NOT (
                        r.nombre = 'ADMIN'
                        AND u.activo = 0
                        AND u.password_expira_en IS NOT NULL
                        AND TIMESTAMPDIFF(DAY, u.creado_en, u.password_expira_en) BETWEEN 1 AND 5
                  )
                ORDER BY u.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);

        $usuarios = [];
        while ($row = $stmt->fetch()) {
            $usuarios[] = UsuarioMapper::fromRow($row);
        }

        return $usuarios;
    }

    /**
     * Busca un usuario por ID dentro de una organizacion.
     *
     * @param int $id
     * @param int $organizacionId
     * @return UsuarioDTO|null
     */
    public function findByIdInOrganizacion(int $id, int $organizacionId): ?UsuarioDTO
    {
        $sql = "SELECT u.id, u.nombre_completo, u.usuario, u.password_hash,
                       u.password_actualizada_en, u.password_expira_en, u.rol_id, r.nombre AS rol_nombre, u.activo,
                       u.organizacion_id, o.codigo_instancia, o.tipo_organizacion, o.nombre_organizacion,
                       o.activa AS organizacion_activa, c.codigo AS campo_codigo, c.nombre AS campo_nombre,
                       d.codigo AS distrito_codigo, d.nombre AS distrito_nombre,
                       u.creado_en, u.actualizado_en
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                LEFT JOIN organizaciones o ON o.id = u.organizacion_id
                LEFT JOIN campos c ON c.id = o.campo_id
                LEFT JOIN distritos d ON d.id = o.distrito_id
                WHERE u.id = :id
                  AND u.organizacion_id = :organizacion_id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId
        ]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return UsuarioMapper::fromRow($row);
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
    public function insert(
        string $nombreCompleto,
        string $usuario,
        string $passwordHash,
        int $rolId,
        int $organizacionId
    ): int
    {
        $sql = "INSERT INTO usuarios (
                    nombre_completo, usuario, password_hash, password_actualizada_en, password_expira_en, rol_id, organizacion_id
                )
                VALUES (
                    :nombre_completo, :usuario, :password_hash, NOW(),
                    DATE_ADD(NOW(), INTERVAL " . (int) PASSWORD_EXPIRY_DAYS . " DAY),
                    :rol_id, :organizacion_id
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nombre_completo' => $nombreCompleto,
            ':usuario'         => $usuario,
            ':password_hash'   => $passwordHash,
            ':rol_id'          => $rolId,
            ':organizacion_id' => $organizacionId
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
                    password_actualizada_en = NOW(),
                    password_expira_en = DATE_ADD(NOW(), INTERVAL " . (int) PASSWORD_EXPIRY_DAYS . " DAY)
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
     * Obtiene nombre de rol por ID.
     *
     * @param int $rolId
     * @return string|null
     */
    public function findRolNombreById(int $rolId): ?string
    {
        $sql = "SELECT nombre
                FROM roles
                WHERE id = :rol_id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':rol_id' => $rolId]);
        $nombre = $stmt->fetchColumn();

        if ($nombre === false) {
            return null;
        }

        return (string) $nombre;
    }

    /**
     * Obtiene el ID de un rol por nombre.
     *
     * @param string $rolNombre
     * @return int|null
     */
    public function findRolIdByNombre(string $rolNombre): ?int
    {
        $sql = "SELECT id
                FROM roles
                WHERE nombre = :nombre
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nombre' => $rolNombre]);
        $id = $stmt->fetchColumn();

        if ($id === false) {
            return null;
        }

        return (int) $id;
    }

    /**
     * Verifica si existe algun ADMIN activo en la organizacion.
     *
     * @param int $organizacionId
     * @return bool
     */
    public function existsAdminActivoByOrganizacion(int $organizacionId): bool
    {
        $sql = "SELECT COUNT(u.id)
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.organizacion_id = :organizacion_id
                  AND r.nombre = 'ADMIN'
                  AND u.activo = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Obtiene el ADMIN activo mas reciente de una organizacion.
     *
     * @param int $organizacionId
     * @return UsuarioDTO|null
     */
    public function findAdminActivoByOrganizacion(int $organizacionId): ?UsuarioDTO
    {
        $sql = "SELECT u.id, u.nombre_completo, u.usuario, u.password_hash,
                       u.password_actualizada_en, u.password_expira_en, u.rol_id, r.nombre AS rol_nombre, u.activo,
                       u.organizacion_id, o.codigo_instancia, o.tipo_organizacion, o.nombre_organizacion,
                       o.activa AS organizacion_activa, c.codigo AS campo_codigo, c.nombre AS campo_nombre,
                       d.codigo AS distrito_codigo, d.nombre AS distrito_nombre,
                       u.creado_en, u.actualizado_en
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                LEFT JOIN organizaciones o ON o.id = u.organizacion_id
                LEFT JOIN campos c ON c.id = o.campo_id
                LEFT JOIN distritos d ON d.id = o.distrito_id
                WHERE u.organizacion_id = :organizacion_id
                  AND r.nombre = 'ADMIN'
                  AND u.activo = 1
                ORDER BY u.id DESC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return UsuarioMapper::fromRow($row);
    }

    /**
     * Cuenta usuarios activos por rol y organizacion.
     *
     * @param int      $organizacionId
     * @param string   $rolNombre
     * @param int|null $excludeUsuarioId
     * @return int
     */
    public function countActivosPorRolEnOrganizacion(
        int $organizacionId,
        string $rolNombre,
        ?int $excludeUsuarioId = null
    ): int {
        $sql = "SELECT COUNT(u.id)
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.organizacion_id = :organizacion_id
                  AND r.nombre = :rol_nombre
                  AND u.activo = 1";

        $params = [
            ':organizacion_id' => $organizacionId,
            ':rol_nombre' => $rolNombre
        ];

        if ($excludeUsuarioId !== null) {
            $sql .= " AND u.id <> :exclude_usuario_id";
            $params[':exclude_usuario_id'] = $excludeUsuarioId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Retorna consumo actual de usuarios activos por rol (sin SUPERADMIN) en la organizacion.
     *
     * @param int $organizacionId
     * @return array<string, int>
     */
    public function countActivosPorRolMapEnOrganizacion(int $organizacionId): array
    {
        $sql = "SELECT r.nombre AS rol_nombre,
                       COUNT(u.id) AS activos
                FROM roles r
                LEFT JOIN usuarios u
                  ON u.rol_id = r.id
                 AND u.organizacion_id = :organizacion_id
                 AND u.activo = 1
                WHERE r.nombre <> 'SUPERADMIN'
                GROUP BY r.id, r.nombre
                ORDER BY r.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);

        $map = [];
        while ($row = $stmt->fetch()) {
            $map[(string) $row['rol_nombre']] = (int) $row['activos'];
        }

        return $map;
    }

    /**
     * Obtiene cupo de un rol por organizacion.
     *
     * @param int    $organizacionId
     * @param string $rolNombre
     * @return array<string, mixed>|null
     */
    public function getCupoByRolEnOrganizacion(int $organizacionId, string $rolNombre): ?array
    {
        $sql = "SELECT rol_nombre, cupo_maximo, activo
                FROM organizacion_roles_cupos
                WHERE organizacion_id = :organizacion_id
                  AND rol_nombre = :rol_nombre
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $organizacionId,
            ':rol_nombre' => $rolNombre
        ]);

        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Lista cupos configurados por rol para una organizacion.
     *
     * @param int $organizacionId
     * @return array<int, array<string, mixed>>
     */
    public function getRolesCuposByOrganizacion(int $organizacionId): array
    {
        $sql = "SELECT rol_nombre, cupo_maximo, activo
                FROM organizacion_roles_cupos
                WHERE organizacion_id = :organizacion_id
                ORDER BY rol_nombre ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Reemplaza cupos por rol de la organizacion.
     *
     * @param int                                $organizacionId
     * @param array<int, array<string, mixed>>   $cupos
     * @return void
     */
    public function replaceRolesCuposByOrganizacion(int $organizacionId, array $cupos): void
    {
        try {
            $this->pdo->beginTransaction();

            $delete = $this->pdo->prepare(
                "DELETE FROM organizacion_roles_cupos
                 WHERE organizacion_id = :organizacion_id"
            );
            $delete->execute([':organizacion_id' => $organizacionId]);

            $insert = $this->pdo->prepare(
                "INSERT INTO organizacion_roles_cupos
                    (organizacion_id, rol_nombre, cupo_maximo, activo)
                 VALUES
                    (:organizacion_id, :rol_nombre, :cupo_maximo, :activo)"
            );

            foreach ($cupos as $cupo) {
                $insert->execute([
                    ':organizacion_id' => $organizacionId,
                    ':rol_nombre' => (string) $cupo['rol_nombre'],
                    ':cupo_maximo' => (int) $cupo['cupo_maximo'],
                    ':activo' => !empty($cupo['activo']) ? 1 : 0
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Inserta un ADMIN temporal vinculado a una organizacion.
     *
     * @param string $nombreCompleto
     * @param string $usuario
     * @param string $passwordHash
     * @param int    $rolId
     * @param int    $organizacionId
     * @param string $passwordExpiraEn Formato Y-m-d H:i:s
     * @return int
     */
    public function insertAdminTemporal(
        string $nombreCompleto,
        string $usuario,
        string $passwordHash,
        int $rolId,
        int $organizacionId,
        string $passwordExpiraEn
    ): int {
        $sql = "INSERT INTO usuarios (
                    nombre_completo,
                    usuario,
                    password_hash,
                    password_actualizada_en,
                    password_expira_en,
                    rol_id,
                    organizacion_id,
                    activo
                )
                VALUES (
                    :nombre_completo,
                    :usuario,
                    :password_hash,
                    NOW(),
                    :password_expira_en,
                    :rol_id,
                    :organizacion_id,
                    1
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nombre_completo' => $nombreCompleto,
            ':usuario' => $usuario,
            ':password_hash' => $passwordHash,
            ':password_expira_en' => $passwordExpiraEn,
            ':rol_id' => $rolId,
            ':organizacion_id' => $organizacionId
        ]);

        return (int) $this->pdo->lastInsertId();
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
                  AND (
                        (password_expira_en IS NOT NULL AND password_expira_en <= NOW())
                     OR (password_expira_en IS NULL AND password_actualizada_en IS NOT NULL
                        AND DATE_ADD(password_actualizada_en, INTERVAL " . (int) PASSWORD_EXPIRY_DAYS . " DAY) <= NOW())
                  )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Desactiva ADMIN temporal vencido a 5 dias.
     *
     * Regla:
     * - rol ADMIN,
     * - usuario de tenant (organizacion_id no null),
     * - password_expira_en vencido,
     * - ventana temporal <= 5 dias desde creacion.
     *
     * @return int Cantidad de filas afectadas.
     */
    public function deactivateExpiredAdminTemporales(): int
    {
        $sql = "UPDATE usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                SET u.activo = 0
                WHERE u.activo = 1
                  AND r.nombre = 'ADMIN'
                  AND u.organizacion_id IS NOT NULL
                  AND u.password_expira_en IS NOT NULL
                  AND u.password_expira_en <= NOW()
                  AND u.password_expira_en <= DATE_ADD(u.creado_en, INTERVAL " . self::ADMIN_TEMPORAL_DIAS . " DAY)";

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
        try {
            if ($usuario->passwordExpiraEn !== '') {
                $limite = new DateTimeImmutable($usuario->passwordExpiraEn);
            } else {
                $fechaBase = $usuario->passwordActualizadaEn !== ''
                    ? $usuario->passwordActualizadaEn
                    : $usuario->creadoEn;

                if ($fechaBase === '') {
                    return true;
                }

                $base = new DateTimeImmutable($fechaBase);
                $limite = $base->modify('+' . PASSWORD_EXPIRY_DAYS . ' days');
            }

            $ahora = new DateTimeImmutable('now');
            return $ahora >= $limite;
        } catch (\Throwable $e) {
            return true;
        }
    }
}
