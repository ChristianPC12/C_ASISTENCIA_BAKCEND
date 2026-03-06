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

        // Aplicar politicas de seguridad previas.
        $usuarioDAO = new UsuarioDAO();
        $tokenDAO = new TokenDAO();
        $usuarioDAO->deactivateExpiredPasswords();
        $tokenDAO->deleteByInvalidUsers();

        $pdo = Conexion::getConexion();

        // Importante: evaluamos expiraciones usando NOW() de MySQL para evitar
        // desfases de zona horaria entre PHP y la sesion de BD.
        $sql = "SELECT ut.usuario_id,
                       u.nombre_completo,
                       r.nombre AS rol,
                       u.activo,
                       (
                         (u.password_expira_en IS NOT NULL AND u.password_expira_en <= NOW())
                         OR
                         (u.password_expira_en IS NULL
                          AND u.password_actualizada_en IS NOT NULL
                          AND DATE_ADD(u.password_actualizada_en, INTERVAL " . (int) PASSWORD_EXPIRY_DAYS . " DAY) <= NOW())
                       ) AS password_expirada,
                       (ut.expira_en <= NOW()) AS sesion_expirada,
                       (ut.ultimo_uso_en <= DATE_SUB(NOW(), INTERVAL " . (int) SESSION_IDLE_TIMEOUT_MINUTES . " MINUTE)) AS inactividad_expirada
                FROM user_tokens ut
                INNER JOIN usuarios u ON u.id = ut.usuario_id
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE ut.token_hash = :token_hash
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        if ($row === false) {
            JsonResponse::send(401, false, 'Token invalido o revocado.');
        }

        if ((int) $row['activo'] !== 1) {
            $tokenDAO->deleteByHash($tokenHash);
            JsonResponse::send(401, false, 'La cuenta esta desactivada. Inicie sesion con una cuenta activa.');
        }

        if ((int) $row['password_expirada'] === 1) {
            $tokenDAO->deleteByUsuarioId((int) $row['usuario_id']);
            JsonResponse::send(401, false, 'Tu sesion expiro porque la contrasena vencio. Contacta al administrador.');
        }

        if ((int) $row['sesion_expirada'] === 1) {
            $tokenDAO->deleteByHash($tokenHash);
            JsonResponse::send(401, false, 'Tu sesion expiro. Inicia sesion de nuevo.');
        }

        if ((int) $row['inactividad_expirada'] === 1) {
            $tokenDAO->deleteByHash($tokenHash);
            JsonResponse::send(
                401,
                false,
                'Tu sesion expiro por inactividad de ' . (int) SESSION_IDLE_TIMEOUT_MINUTES . ' minutos. Inicia sesion nuevamente.'
            );
        }

        // Limpieza de sesiones expiradas de otros usuarios.
        $tokenDAO->deleteExpiredSessions();

        // Renovar actividad para timeout por inactividad
        $tokenDAO->touchLastUseByHash($tokenHash);

        AuthContext::set(
            (int) $row['usuario_id'],
            (string) $row['rol'],
            (string) $row['nombre_completo']
        );
    }
}
