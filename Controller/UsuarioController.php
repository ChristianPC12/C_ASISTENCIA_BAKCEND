<?php
declare(strict_types=1);

/**
 * Clase UsuarioController
 *
 * Orquesta las operaciones CRUD de usuarios.
 * Solo accesible por ADMIN.
 */
final class UsuarioController
{
    /** @var UsuarioService */
    private UsuarioService $usuarioService;

    public function __construct()
    {
        $this->usuarioService = new UsuarioService();
    }

    /**
     * GET /usuarios
     *
     * @return void
     */
    public function listar(): void
    {
        try {
            $this->requireAdmin();
            $resultado = $this->usuarioService->listar();

            JsonResponse::send(200, true, 'Lista de usuarios obtenida.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(400, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[UsuarioController::listar] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * GET /usuarios/{id}
     *
     * @param int $id ID del usuario.
     * @return void
     */
    public function obtener(int $id): void
    {
        try {
            $this->requireAdmin();
            $resultado = $this->usuarioService->obtenerPorId($id);

            JsonResponse::send(200, true, 'Usuario obtenido.', $resultado);
        } catch (RuntimeException $e) {
            JsonResponse::send(404, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[UsuarioController::obtener] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * POST /usuarios
     *
     * @return void
     */
    public function crear(): void
    {
        try {
            $this->requireAdmin();
            $data      = Sanitizer::getJsonBody();
            $validated = UsuarioValidator::validateCreate($data);
            $resultado = $this->usuarioService->crear($validated);

            JsonResponse::send(201, true, 'Usuario creado correctamente.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(400, false, $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::send(409, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[UsuarioController::crear] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * PUT /usuarios/{id}
     *
     * @param int $id ID del usuario.
     * @return void
     */
    public function actualizar(int $id): void
    {
        try {
            $this->requireAdmin();
            $data      = Sanitizer::getJsonBody();
            $validated = UsuarioValidator::validateUpdate($data);
            $resultado = $this->usuarioService->actualizar($id, $validated);

            JsonResponse::send(200, true, 'Usuario actualizado correctamente.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(400, false, $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::send(409, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[UsuarioController::actualizar] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * DELETE /usuarios/{id}
     *
     * @param int $id ID del usuario.
     * @return void
     */
    public function eliminar(int $id): void
    {
        try {
            $this->requireAdmin();
            $this->usuarioService->eliminar($id);

            JsonResponse::send(200, true, 'Usuario desactivado correctamente.');
        } catch (RuntimeException $e) {
            JsonResponse::send(404, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[UsuarioController::eliminar] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * Verifica que el usuario autenticado sea ADMIN.
     *
     * @return void
     */
    private function requireAdmin(): void
    {
        if (!AuthContext::esAdmin()) {
            JsonResponse::send(403, false, 'Acceso denegado. Se requiere rol ADMIN.');
        }
    }
}
