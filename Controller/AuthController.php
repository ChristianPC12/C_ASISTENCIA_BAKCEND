<?php
declare(strict_types=1);

/**
 * Clase AuthController
 *
 * Orquesta las operaciones de autenticacion.
 * try/catch obligatorio: InvalidArgumentException = 400, Throwable = 500.
 */
final class AuthController
{
    /** @var AuthService */
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * POST /auth/login
     *
     * @return void
     */
    public function login(): void
    {
        try {
            $data      = Sanitizer::getJsonBody();
            $validated = AuthValidator::validateLogin($data);
            $resultado = $this->authService->login($validated['usuario'], $validated['password']);

            JsonResponse::send(200, true, 'Inicio de sesion exitoso.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(400, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AuthController::login] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * POST /auth/logout
     *
     * @return void
     */
    public function logout(): void
    {
        try {
            $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
                JsonResponse::send(400, false, 'Token no proporcionado.');
            }

            $this->authService->logout($matches[1]);

            JsonResponse::send(200, true, 'Sesion cerrada correctamente.');
        } catch (\Throwable $e) {
            error_log('[AuthController::logout] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * GET /auth/me
     *
     * @return void
     */
    public function me(): void
    {
        try {
            $usuarioId = AuthContext::getUsuarioId();
            $resultado = $this->authService->me($usuarioId);

            JsonResponse::send(200, true, 'Usuario autenticado.', $resultado);
        } catch (\Throwable $e) {
            error_log('[AuthController::me] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }
}
