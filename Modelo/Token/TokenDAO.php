<?php
declare(strict_types=1);

/**
 * Clase TokenDAO
 *
 * Acceso a datos para tokens de autenticacion (user_tokens).
 */
final class TokenDAO
{
    /** @var PDO */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    /**
     * Inserta un nuevo token.
     *
     * @param int    $usuarioId ID del usuario.
     * @param int|null $organizacionId ID de organizacion del usuario.
     * @param string $tokenHash SHA-256 del token plano.
     * @return int ID del token insertado.
     */
    public function insert(int $usuarioId, ?int $organizacionId, string $tokenHash, string $expiraEn): int
    {
        $sql = "INSERT INTO user_tokens (usuario_id, organizacion_id, token_hash, ultimo_uso_en, expira_en)
                VALUES (:usuario_id, :organizacion_id, :token_hash, NOW(), :expira_en)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':organizacion_id' => $organizacionId,
            ':token_hash' => $tokenHash,
            ':expira_en'  => $expiraEn
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Elimina un token por su hash (logout/revocacion).
     *
     * @param string $tokenHash SHA-256 del token plano.
     * @return bool
     */
    public function deleteByHash(string $tokenHash): bool
    {
        $sql = "DELETE FROM user_tokens WHERE token_hash = :token_hash";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':token_hash' => $tokenHash]);
    }

    /**
     * Elimina un token por hash y tenant.
     *
     * @param string $tokenHash
     * @param int    $organizacionId
     * @return bool
     */
    public function deleteByHashAndOrganizacion(string $tokenHash, int $organizacionId): bool
    {
        $sql = "DELETE FROM user_tokens
                WHERE token_hash = :token_hash
                  AND organizacion_id = :organizacion_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':token_hash' => $tokenHash,
            ':organizacion_id' => $organizacionId
        ]);
    }

    /**
     * Elimina todos los tokens de un usuario (forzar logout completo).
     *
     * @param int $usuarioId ID del usuario.
     * @return bool
     */
    public function deleteByUsuarioId(int $usuarioId): bool
    {
        $sql = "DELETE FROM user_tokens WHERE usuario_id = :usuario_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':usuario_id' => $usuarioId]);
    }

    /**
     * Elimina tokens de usuarios inactivos o con password vencido.
     *
     * @return int Cantidad de filas eliminadas.
     */
    public function deleteByInvalidUsers(): int
    {
        $sql = "DELETE ut
                FROM user_tokens ut
                INNER JOIN usuarios u ON u.id = ut.usuario_id
                WHERE u.activo = 0
                   OR DATE_ADD(u.password_actualizada_en, INTERVAL " . (int) PASSWORD_EXPIRY_DAYS . " DAY) <= NOW()";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Elimina tokens vencidos por inactividad o por duracion maxima.
     *
     * @return int Cantidad de filas eliminadas.
     */
    public function deleteExpiredSessions(): int
    {
        $sql = "DELETE FROM user_tokens
                WHERE expira_en <= NOW()
                   OR ultimo_uso_en <= DATE_SUB(NOW(), INTERVAL " . (int) SESSION_IDLE_TIMEOUT_MINUTES . " MINUTE)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Refresca el ultimo uso de un token valido.
     *
     * @param string $tokenHash
     * @return bool
     */
    public function touchLastUseByHash(string $tokenHash): bool
    {
        $sql = "UPDATE user_tokens
                SET ultimo_uso_en = NOW()
                WHERE token_hash = :token_hash";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':token_hash' => $tokenHash]);
    }

    /**
     * Sincroniza el tenant del token con el tenant del usuario autenticado.
     *
     * @param string $tokenHash
     * @param int    $organizacionId
     * @return bool
     */
    public function syncOrganizacionByHash(string $tokenHash, int $organizacionId): bool
    {
        $sql = "UPDATE user_tokens
                SET organizacion_id = :organizacion_id
                WHERE token_hash = :token_hash";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':organizacion_id' => $organizacionId,
            ':token_hash'      => $tokenHash
        ]);
    }

}
