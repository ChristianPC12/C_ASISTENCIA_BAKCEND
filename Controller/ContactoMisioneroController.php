<?php
declare(strict_types=1);

/**
 * CRUD tenant-aware de contactos misioneros.
 */
final class ContactoMisioneroController
{
    private ContactoMisioneroService $contactoService;

    public function __construct()
    {
        $this->contactoService = new ContactoMisioneroService();
    }

    public function listar(): void
    {
        try {
            $filtros = ContactoMisioneroValidator::validateListFilters($_GET);
            $items = $this->contactoService->listar($filtros, AuthContext::getOrganizacionId());
            JsonResponse::sendV2Success(200, 'Contactos misioneros obtenidos.', ['items' => $items]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[ContactoMisioneroController::listar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function obtener(int $id): void
    {
        try {
            $item = $this->contactoService->obtener($id, AuthContext::getOrganizacionId());
            JsonResponse::sendV2Success(200, 'Contacto misionero obtenido.', ['item' => $item]);
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[ContactoMisioneroController::obtener] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crear(): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = ContactoMisioneroValidator::validateUpsert($data);
            $item = $this->contactoService->resolverOCrear(
                $validated,
                AuthContext::getOrganizacionId(),
                AuthContext::getUsuarioId(),
                AuthContext::getNombre()
            );
            JsonResponse::sendV2Success(201, 'Contacto misionero registrado.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[ContactoMisioneroController::crear] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function actualizar(int $id): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = ContactoMisioneroValidator::validateUpsert($data);
            $item = $this->contactoService->actualizar(
                $id,
                $validated,
                AuthContext::getOrganizacionId(),
                AuthContext::getUsuarioId(),
                AuthContext::getNombre()
            );
            JsonResponse::sendV2Success(200, 'Contacto misionero actualizado.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[ContactoMisioneroController::actualizar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function eliminar(int $id): void
    {
        try {
            $this->contactoService->eliminar(
                $id,
                AuthContext::getOrganizacionId(),
                AuthContext::getUsuarioId(),
                AuthContext::getNombre()
            );
            JsonResponse::sendV2Success(200, 'Contacto misionero eliminado correctamente.');
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[ContactoMisioneroController::eliminar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }
}
