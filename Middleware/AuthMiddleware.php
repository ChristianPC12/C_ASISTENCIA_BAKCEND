<?php
declare(strict_types=1);

/**
 * Clase AuthMiddleware
 *
 * Valida el Bearer token del header Authorization.
 * Si es valido, almacena el usuario autenticado en AuthContext.
 */
final class AuthMiddleware
{
    /**
     * Verifica que exista un token Bearer valido y no expirado.
     * Almacena usuario_id y rol en AuthContext.
     *
     * @return void
     * @throws RuntimeException Si el token es invalido o no existe.
     */
    public static function handle(): void
    {
        // Intentar obtener el header Authorization de multiples fuentes
        $header = $_SERVER['HTTP_AUTHORIZATION']
                ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                ?? '';

        // Fallback: apache_request_headers() si esta disponible
        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header  = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            JsonResponse::send(401, false, 'Token de autenticacion requerido.');
        }

        $tokenPlano = $matches[1];
        $tokenHash  = hash('sha256', $tokenPlano);

        // Aplicar politicas de seguridad antes de validar el token actual
        $usuarioDAO = new UsuarioDAO();
        $tokenDAO = new TokenDAO();
        $usuarioDAO->deactivateExpiredPasswords();
        $tokenDAO->deleteByInvalidUsers();
        $tokenDAO->deleteExpiredSessions();

        $pdo = Conexion::getConexion();

        $sql = "SELECT ut.usuario_id, u.nombre_completo, u.usuario, r.nombre AS rol
                FROM user_tokens ut
                INNER JOIN usuarios u ON u.id = ut.usuario_id
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE ut.token_hash = :token_hash
                  AND u.activo = 1
                  AND ut.expira_en > NOW()
                  AND ut.ultimo_uso_en > DATE_SUB(NOW(), INTERVAL " . (int) SESSION_IDLE_TIMEOUT_MINUTES . " MINUTE)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        if ($row === false) {
            $tokenDAO->deleteByHash($tokenHash);
            JsonResponse::send(401, false, 'Token invalido o expirado.');
        }

        // Renovar actividad para timeout por inactividad
        $tokenDAO->touchLastUseByHash($tokenHash);

        AuthContext::set(
            (int) $row['usuario_id'],
            $row['rol'],
            $row['nombre_completo']
        );
    }
}
