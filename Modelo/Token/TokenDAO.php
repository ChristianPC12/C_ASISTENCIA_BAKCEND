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
     * @param string $tokenHash SHA-256 del token plano.
     * @return int ID del token insertado.
     */
    public function insert(int $usuarioId, string $tokenHash): int
    {
        $sql = "INSERT INTO user_tokens (usuario_id, token_hash)
                VALUES (:usuario_id, :token_hash)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':token_hash' => $tokenHash
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

}
