<?php
declare(strict_types=1);

/**
 * Clase SuperadminOrganizacionController
 *
 * Endpoints v2 para gestion de organizaciones por SUPERADMIN.
 */
final class SuperadminOrganizacionController
{
    /** @var OrganizacionService */
    private OrganizacionService $organizacionService;

    public function __construct()
    {
        $this->organizacionService = new OrganizacionService();
    }

    /**
     * GET /v2/superadmin/organizaciones
     *
     * @return void
     */
    public function listar(): void
    {
        try {
            $filtros = OrganizacionValidator::validateListFilters($_GET);
            $resultado = $this->organizacionService->listar($filtros);

            JsonResponse::sendV2Success(200, 'Lista de organizaciones obtenida.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminOrganizacionController::listar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * POST /v2/superadmin/organizaciones
     *
     * @return void
     */
    public function crear(): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = OrganizacionValidator::validateCreate($data);
            $resultado = $this->organizacionService->crear($validated);

            JsonResponse::sendV2Success(201, 'Organizacion creada correctamente.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminOrganizacionController::crear] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * PUT /v2/superadmin/organizaciones/{organizacion_id}
     *
     * @param int $organizacionId
     * @return void
     */
    public function actualizar(int $organizacionId): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = OrganizacionValidator::validateUpdate($data);
            $resultado = $this->organizacionService->actualizar($organizacionId, $validated);

            JsonResponse::sendV2Success(200, 'Organizacion actualizada correctamente.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminOrganizacionController::actualizar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * POST /v2/superadmin/organizaciones/{organizacion_id}/admin-temporal
     *
     * @param int $organizacionId
     * @return void
     */
    public function crearAdminTemporal(int $organizacionId): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = OrganizacionValidator::validateCreateAdminTemporal($data);
            $resultado = $this->organizacionService->crearAdminTemporal($organizacionId, $validated);

            JsonResponse::sendV2Success(201, 'ADMIN temporal creado correctamente.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[SuperadminOrganizacionController::crearAdminTemporal] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }
}
