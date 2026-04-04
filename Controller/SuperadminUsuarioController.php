<?php
declare(strict_types=1);

/**
 * Clase SuperadminUsuarioController
 *
 * Endpoints v2 para mantenimiento de cuentas SUPERADMIN.
 */
final class SuperadminUsuarioController
{
    /** @var SuperadminUsuarioService */
    private SuperadminUsuarioService $usuarioService;

    public function __construct()
    {
        $this->usuarioService = new SuperadminUsuarioService();
    }

    /**
     * GET /v2/superadmin/usuarios-superadmin
     *
     * @return void
     */
    public function listar(): void
    {
        try {
            $items = $this->usuarioService->listar();
            JsonResponse::sendV2Success(200, 'Superadministradores obtenidos correctamente.', ['items' => $items]);
        } catch (\Throwable $e) {
            error_log('[SuperadminUsuarioController::listar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * POST /v2/superadmin/usuarios-superadmin
     *
     * @return void
     */
    public function crear(): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = SuperadminUsuarioValidator::validateCreate($data);
            $usuario = $this->usuarioService->crear($validated);
            JsonResponse::sendV2Success(201, 'Superadministrador creado correctamente.', ['item' => $usuario]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminUsuarioController::crear] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * PUT /v2/superadmin/usuarios-superadmin/{id}
     *
     * @param int $id
     * @return void
     */
    public function actualizar(int $id): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = SuperadminUsuarioValidator::validateUpdate($data);
            $usuario = $this->usuarioService->actualizar($id, $validated, AuthContext::getUsuarioId());
            JsonResponse::sendV2Success(200, 'Superadministrador actualizado correctamente.', ['item' => $usuario]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminUsuarioController::actualizar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * PUT /v2/superadmin/usuarios-superadmin/{id}/password
     *
     * @param int $id
     * @return void
     */
    public function actualizarPassword(int $id): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = SuperadminUsuarioValidator::validatePasswordUpdate($data);
            $usuario = $this->usuarioService->actualizarPassword($id, $validated);
            JsonResponse::sendV2Success(200, 'Contraseña actualizada correctamente.', ['item' => $usuario]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminUsuarioController::actualizarPassword] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }
}
