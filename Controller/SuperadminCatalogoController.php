<?php
declare(strict_types=1);

/**
 * Clase SuperadminCatalogoController
 *
 * Endpoints v2 para gestion de catalogos globales de superadmin.
 */
final class SuperadminCatalogoController
{
    /** @var SuperadminCatalogoService */
    private SuperadminCatalogoService $catalogoService;

    public function __construct()
    {
        $this->catalogoService = new SuperadminCatalogoService();
    }

    /**
     * GET /v2/superadmin/campos
     *
     * @return void
     */
    public function listarCampos(): void
    {
        try {
            $items = $this->catalogoService->listarCampos();
            JsonResponse::sendV2Success(200, 'Catalogo de campos obtenido.', ['items' => $items]);
        } catch (\Throwable $e) {
            error_log('[SuperadminCatalogoController::listarCampos] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * POST /v2/superadmin/campos
     *
     * @return void
     */
    public function crearCampo(): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = SuperadminCatalogoValidator::validateCreateCampo($data);
            $campo = $this->catalogoService->crearCampo($validated);
            JsonResponse::sendV2Success(201, 'Campo creado correctamente.', ['item' => $campo]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminCatalogoController::crearCampo] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * PUT /v2/superadmin/campos/{codigo}
     *
     * @param string $codigo
     * @return void
     */
    public function actualizarCampo(string $codigo): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = SuperadminCatalogoValidator::validateUpdateCampo($data);
            $campo = $this->catalogoService->actualizarCampo($codigo, $validated);
            JsonResponse::sendV2Success(200, 'Campo actualizado correctamente.', ['item' => $campo]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminCatalogoController::actualizarCampo] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * DELETE /v2/superadmin/campos/{codigo}
     *
     * @param string $codigo
     * @return void
     */
    public function eliminarCampo(string $codigo): void
    {
        try {
            $this->catalogoService->eliminarCampo($codigo);
            JsonResponse::sendV2Success(200, 'Campo eliminado correctamente.');
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminCatalogoController::eliminarCampo] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * GET /v2/superadmin/distritos
     *
     * @return void
     */
    public function listarDistritos(): void
    {
        try {
            $items = $this->catalogoService->listarDistritos();
            JsonResponse::sendV2Success(200, 'Catalogo de distritos obtenido.', ['items' => $items]);
        } catch (\Throwable $e) {
            error_log('[SuperadminCatalogoController::listarDistritos] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * POST /v2/superadmin/distritos
     *
     * @return void
     */
    public function crearDistrito(): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = SuperadminCatalogoValidator::validateCreateDistrito($data);
            $distrito = $this->catalogoService->crearDistrito($validated);
            JsonResponse::sendV2Success(201, 'Distrito creado correctamente.', ['item' => $distrito]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminCatalogoController::crearDistrito] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * PUT /v2/superadmin/distritos/{codigo}
     *
     * @param string $codigo
     * @return void
     */
    public function actualizarDistrito(string $codigo): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = SuperadminCatalogoValidator::validateUpdateDistrito($data);
            $distrito = $this->catalogoService->actualizarDistrito($codigo, $validated);
            JsonResponse::sendV2Success(200, 'Distrito actualizado correctamente.', ['item' => $distrito]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminCatalogoController::actualizarDistrito] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * DELETE /v2/superadmin/distritos/{codigo}
     *
     * @param string $codigo
     * @return void
     */
    public function eliminarDistrito(string $codigo): void
    {
        try {
            $this->catalogoService->eliminarDistrito($codigo);
            JsonResponse::sendV2Success(200, 'Distrito eliminado correctamente.');
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminCatalogoController::eliminarDistrito] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }
}
