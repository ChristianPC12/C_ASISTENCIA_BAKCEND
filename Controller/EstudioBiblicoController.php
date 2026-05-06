<?php
declare(strict_types=1);

/**
 * Endpoints del modulo de estudios biblicos.
 */
final class EstudioBiblicoController
{
    private EstudioBiblicoService $service;

    public function __construct()
    {
        $this->service = new EstudioBiblicoService();
    }

    public function listar(): void
    {
        try {
            $items = $this->service->listar(EstudioBiblicoValidator::validateFilters($_GET));
            JsonResponse::sendV2Success(200, 'Estudios biblicos obtenidos.', ['items' => $items]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::listar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function dashboard(): void
    {
        try {
            $item = $this->service->dashboard(EstudioBiblicoValidator::validateFilters($_GET));
            JsonResponse::sendV2Success(200, 'Dashboard de estudios biblicos obtenido.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::dashboard] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function obtener(int $id): void
    {
        try {
            $item = $this->service->obtener($id);
            JsonResponse::sendV2Success(200, 'Estudio biblico obtenido.', ['item' => $item]);
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::obtener] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crear(): void
    {
        try {
            $item = $this->service->crear(EstudioBiblicoValidator::validateEstudio(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Estudio biblico creado correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::crear] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function asignarDesdeVisita(): void
    {
        try {
            $item = $this->service->crearDesdeVisita(EstudioBiblicoValidator::validateAsignarDesdeVisita(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Estudio biblico asignado correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::asignarDesdeVisita] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function actualizar(int $id): void
    {
        try {
            $item = $this->service->actualizar($id, EstudioBiblicoValidator::validateEstudio(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Estudio biblico actualizado correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::actualizar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function eliminar(int $id): void
    {
        try {
            $this->service->eliminar($id, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Estudio biblico archivado correctamente.');
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::eliminar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crearSesion(int $id): void
    {
        try {
            $item = $this->service->crearSesion($id, EstudioBiblicoValidator::validateSesion(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Sesion registrada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::crearSesion] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function cambiarEstado(int $id): void
    {
        try {
            $item = $this->service->cambiarEstado($id, EstudioBiblicoValidator::validateEstado(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Estado actualizado correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::cambiarEstado] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crearDecision(int $id): void
    {
        try {
            $item = $this->service->crearDecision($id, EstudioBiblicoValidator::validateDecision(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Decision registrada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::crearDecision] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crearAsignacion(int $id): void
    {
        try {
            $item = $this->service->crearAsignacion($id, EstudioBiblicoValidator::validateAsignacion(Sanitizer::getJsonBody()), AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Asignacion registrada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::crearAsignacion] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function listarInstructores(): void
    {
        try {
            $items = $this->service->listarInstructores($_GET);
            JsonResponse::sendV2Success(200, 'Instructores obtenidos.', ['items' => $items]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::listarInstructores] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crearInstructor(): void
    {
        try {
            $item = $this->service->crearInstructor(EstudioBiblicoValidator::validateInstructor(Sanitizer::getJsonBody()));
            JsonResponse::sendV2Success(201, 'Instructor creado correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::crearInstructor] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function actualizarInstructor(int $id): void
    {
        try {
            $item = $this->service->actualizarInstructor($id, EstudioBiblicoValidator::validateInstructor(Sanitizer::getJsonBody()));
            JsonResponse::sendV2Success(200, 'Instructor actualizado correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::actualizarInstructor] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function eliminarInstructor(int $id): void
    {
        try {
            $this->service->eliminarInstructor($id);
            JsonResponse::sendV2Success(200, 'Instructor desactivado correctamente.');
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[EstudioBiblicoController::eliminarInstructor] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }
}
