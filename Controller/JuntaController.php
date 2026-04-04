<?php
declare(strict_types=1);

/**
 * Endpoints del modulo de juntas de iglesia.
 */
final class JuntaController
{
    private JuntaService $service;

    public function __construct()
    {
        $this->service = new JuntaService();
    }

    public function listar(): void
    {
        try {
            $items = $this->service->listar(JuntaValidator::validateFilters($_GET));
            JsonResponse::sendV2Success(200, 'Juntas obtenidas.', ['items' => $items]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[JuntaController::listar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function dashboard(): void
    {
        try {
            $item = $this->service->dashboard(JuntaValidator::validateFilters($_GET));
            JsonResponse::sendV2Success(200, 'Dashboard de juntas obtenido.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[JuntaController::dashboard] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function pendientes(): void
    {
        try {
            $items = $this->service->pendientes(JuntaValidator::validateFilters($_GET));
            JsonResponse::sendV2Success(200, 'Pendientes de juntas obtenidos.', ['items' => $items]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[JuntaController::pendientes] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function obtener(int $id): void
    {
        try {
            $item = $this->service->obtener($id);
            JsonResponse::sendV2Success(200, 'Junta obtenida.', ['item' => $item]);
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[JuntaController::obtener] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crear(): void
    {
        try {
            $item = $this->service->crear(JuntaValidator::validateJunta(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Junta creada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[JuntaController::crear] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function actualizar(int $id): void
    {
        try {
            $item = $this->service->actualizar($id, JuntaValidator::validateJunta(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Junta actualizada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[JuntaController::actualizar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function eliminar(int $id): void
    {
        try {
            $this->service->eliminar($id, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Junta archivada correctamente.');
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[JuntaController::eliminar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crearPunto(int $id): void
    {
        try {
            $item = $this->service->crearPunto($id, JuntaValidator::validatePunto(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Punto creado correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[JuntaController::crearPunto] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function actualizarPunto(int $id): void
    {
        try {
            $item = $this->service->actualizarPunto($id, JuntaValidator::validatePunto(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Punto actualizado correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[JuntaController::actualizarPunto] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crearVotacion(int $id): void
    {
        try {
            $item = $this->service->crearVotacion($id, JuntaValidator::validateVotacion(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Votacion registrada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[JuntaController::crearVotacion] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function actualizarVotacion(int $id): void
    {
        try {
            $item = $this->service->actualizarVotacion($id, JuntaValidator::validateVotacion(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Votacion actualizada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[JuntaController::actualizarVotacion] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }
}
