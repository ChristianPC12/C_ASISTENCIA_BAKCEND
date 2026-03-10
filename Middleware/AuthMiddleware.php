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
     * Verifica que exista un token Bearer valido, no expirado y con tenant valido.
     * Almacena usuario_id, tenant y rol en AuthContext.
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
        $usuarioDAO->deactivateExpiredAdminTemporales();
        $usuarioDAO->deactivateExpiredPasswords();
        $tokenDAO->deleteByInvalidUsers();

        $pdo = Conexion::getConexion();

        // Importante: evaluamos expiraciones usando NOW() de MySQL para evitar
        // desfases de zona horaria entre PHP y la sesion de BD.
        $sql = "SELECT ut.usuario_id,
                       ut.organizacion_id AS token_organizacion_id,
                       u.nombre_completo,
                       u.organizacion_id AS usuario_organizacion_id,
                       r.nombre AS rol,
                       u.activo,
                       o.codigo_instancia,
                       o.tipo_organizacion,
                       o.nombre_organizacion,
                       o.activa AS organizacion_activa,
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
                LEFT JOIN organizaciones o ON o.id = u.organizacion_id
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

        $rol = (string) ($row['rol'] ?? '');
        $esSuperadmin = $rol === 'SUPERADMIN';
        $organizacionId = isset($row['usuario_organizacion_id'])
            ? (int) $row['usuario_organizacion_id']
            : 0;

        if (!$esSuperadmin) {
            if ($organizacionId <= 0) {
                $tokenDAO->deleteByHash($tokenHash);
                JsonResponse::send(
                    403,
                    false,
                    'La cuenta autenticada no tiene una organizacion asignada. Contacta al administrador.'
                );
            }

            if (empty($row['codigo_instancia'])) {
                $tokenDAO->deleteByHash($tokenHash);
                JsonResponse::send(
                    403,
                    false,
                    'La organizacion asociada a la cuenta no existe o no esta disponible.'
                );
            }

            if ((int) ($row['organizacion_activa'] ?? 0) !== 1) {
                $tokenDAO->deleteByHash($tokenHash);
                JsonResponse::send(
                    403,
                    false,
                    'La organizacion de esta cuenta se encuentra inactiva.'
                );
            }

            $tokenOrganizacionId = isset($row['token_organizacion_id'])
                ? (int) $row['token_organizacion_id']
                : 0;

            if ($tokenOrganizacionId !== $organizacionId) {
                // Sincroniza el tenant del token para mantener trazabilidad por sesion.
                $tokenDAO->syncOrganizacionByHash($tokenHash, $organizacionId);
            }
        }

        // Limpieza de sesiones expiradas de otros usuarios.
        $tokenDAO->deleteExpiredSessions();

        // Renovar actividad para timeout por inactividad
        $tokenDAO->touchLastUseByHash($tokenHash);

        AuthContext::set(
            (int) $row['usuario_id'],
            $esSuperadmin ? null : $organizacionId,
            $esSuperadmin ? null : (string) $row['codigo_instancia'],
            $esSuperadmin ? null : (string) $row['tipo_organizacion'],
            $esSuperadmin ? null : (string) $row['nombre_organizacion'],
            $rol,
            (string) $row['nombre_completo']
        );
    }
}
