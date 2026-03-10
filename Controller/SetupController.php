<?php
declare(strict_types=1);

/**
 * Clase SetupController
 *
 * Endpoints v2 del setup inicial por tenant.
 */
final class SetupController
{
    /** @var SetupService */
    private SetupService $setupService;

    public function __construct()
    {
        $this->setupService = new SetupService();
    }

    /**
     * GET /v2/setup/estado
     *
     * @return void
     */
    public function estado(): void
    {
        try {
            $organizacionId = AuthContext::getOrganizacionId();
            $resultado = $this->setupService->obtenerEstado($organizacionId);

            JsonResponse::sendV2Success(200, 'Estado de setup obtenido.', $resultado);
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'SETUP_INCONSISTENT:')) {
                JsonResponse::sendV2Error(409, 'SETUP_INCONSISTENT', $this->limpiarPrefijoSetup($e->getMessage()));
            }

            error_log('[SetupController::estado] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        } catch (\Throwable $e) {
            error_log('[SetupController::estado] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * PUT /v2/setup/cultos
     *
     * @return void
     */
    public function cultos(): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = SetupValidator::validateCultos($data);
            $organizacionId = AuthContext::getOrganizacionId();
            $resultado = $this->setupService->guardarCultos($organizacionId, $validated);

            JsonResponse::sendV2Success(200, 'Cultos de setup guardados.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (DomainException $e) {
            JsonResponse::sendV2Error(403, 'SETUP_ALREADY_COMPLETED', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'SETUP_INCONSISTENT:')) {
                JsonResponse::sendV2Error(409, 'SETUP_INCONSISTENT', $this->limpiarPrefijoSetup($e->getMessage()));
            }

            error_log('[SetupController::cultos] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        } catch (\Throwable $e) {
            error_log('[SetupController::cultos] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * PUT /v2/setup/metricas
     *
     * @return void
     */
    public function metricas(): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = SetupValidator::validateMetricas($data);
            $organizacionId = AuthContext::getOrganizacionId();
            $resultado = $this->setupService->guardarMetricas($organizacionId, $validated);

            JsonResponse::sendV2Success(200, 'Metricas de setup guardadas.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (DomainException $e) {
            JsonResponse::sendV2Error(403, 'SETUP_ALREADY_COMPLETED', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'SETUP_INCONSISTENT:')) {
                JsonResponse::sendV2Error(409, 'SETUP_INCONSISTENT', $this->limpiarPrefijoSetup($e->getMessage()));
            }

            error_log('[SetupController::metricas] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        } catch (\Throwable $e) {
            error_log('[SetupController::metricas] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * PUT /v2/setup/procedencias
     *
     * @return void
     */
    public function procedencias(): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = SetupValidator::validateProcedencias($data);
            $organizacionId = AuthContext::getOrganizacionId();
            $resultado = $this->setupService->guardarProcedencias($organizacionId, $validated);

            JsonResponse::sendV2Success(200, 'Procedencias de setup guardadas.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (DomainException $e) {
            JsonResponse::sendV2Error(403, 'SETUP_ALREADY_COMPLETED', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'SETUP_INCONSISTENT:')) {
                JsonResponse::sendV2Error(409, 'SETUP_INCONSISTENT', $this->limpiarPrefijoSetup($e->getMessage()));
            }

            error_log('[SetupController::procedencias] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        } catch (\Throwable $e) {
            error_log('[SetupController::procedencias] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * POST /v2/setup/finalizar
     *
     * @return void
     */
    public function finalizar(): void
    {
        try {
            $organizacionId = AuthContext::getOrganizacionId();
            $resultado = $this->setupService->finalizar($organizacionId);

            JsonResponse::sendV2Success(200, 'Setup inicial completado.', $resultado);
        } catch (DomainException $e) {
            JsonResponse::sendV2Error(403, 'SETUP_ALREADY_COMPLETED', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'SETUP_INCONSISTENT:')) {
                JsonResponse::sendV2Error(409, 'SETUP_INCONSISTENT', $this->limpiarPrefijoSetup($e->getMessage()));
            }

            error_log('[SetupController::finalizar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        } catch (\Throwable $e) {
            error_log('[SetupController::finalizar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * Limpia prefijo tecnico de errores setup.
     *
     * @param string $mensaje
     * @return string
     */
    private function limpiarPrefijoSetup(string $mensaje): string
    {
        return trim(str_replace('SETUP_INCONSISTENT:', '', $mensaje));
    }
}

