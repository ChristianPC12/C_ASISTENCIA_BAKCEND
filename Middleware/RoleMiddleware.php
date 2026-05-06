<?php
declare(strict_types=1);

/**
 * Clase RoleMiddleware
 *
 * Guardas de rol reutilizables para rutas/controladores.
 */
final class RoleMiddleware
{
    /**
     * Bloquea a SUPERADMIN en endpoints operativos de tenant.
     *
     * @return void
     */
    public static function denySuperadminInOperative(): void
    {
        if (AuthContext::esSuperadmin()) {
            JsonResponse::send(
                403,
                false,
                'Acceso denegado. SUPERADMIN solo puede operar en el modulo de administracion nacional.'
            );
        }
    }

    /**
     * Exige rol ADMIN.
     *
     * @return void
     */
    public static function requireAdmin(): void
    {
        if (!AuthContext::esAdmin()) {
            JsonResponse::send(403, false, 'Acceso denegado. Se requiere rol ADMIN.');
        }
    }

    /**
     * Exige rol ADMIN para rutas v2.
     *
     * @return void
     */
    public static function requireAdminV2(): void
    {
        if (!AuthContext::esAdmin()) {
            JsonResponse::sendV2Error(403, 'FORBIDDEN_ROLE', 'Acceso denegado. Se requiere rol ADMIN.');
        }
    }

    /**
     * Exige rol operativo para gestionar estudios biblicos.
     *
     * @return void
     */
    public static function requireEstudiosBiblicosGestion(): void
    {
        $rol = AuthContext::getRol();
        if (!in_array($rol, ['ADMIN', 'SECRETARIO', 'MINISTERIO_PERSONAL'], true)) {
            JsonResponse::sendV2Error(403, 'FORBIDDEN_ROLE', 'Acceso denegado para gestionar estudios biblicos.');
        }
    }

    /**
     * Exige setup completo para usar modulos operativos.
     *
     * @return void
     */
    public static function requireSetupCompletedForOperative(): void
    {
        if (AuthContext::esSuperadmin()) {
            return;
        }

        try {
            $organizacionId = AuthContext::getOrganizacionId();
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(
                403,
                'SETUP_REQUIRED',
                'Debes completar la configuracion inicial de tu organizacion para habilitar este modulo.'
            );
        }

        $setupDAO = new SetupDAO();

        if ($setupDAO->isOperativeBlocked($organizacionId)) {
            JsonResponse::sendV2Error(
                403,
                'SETUP_REQUIRED',
                'Debes completar la configuracion inicial de tu organizacion para habilitar este modulo.'
            );
        }
    }

    /**
     * Exige rol SUPERADMIN.
     *
     * @return void
     */
    public static function requireSuperadmin(): void
    {
        if (!AuthContext::esSuperadmin()) {
            JsonResponse::sendV2Error(403, 'FORBIDDEN_ROLE', 'Acceso denegado. Se requiere rol SUPERADMIN.');
        }
    }
}
