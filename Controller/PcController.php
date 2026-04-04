<?php
declare(strict_types=1);

/**
 * Endpoints del modulo de pequenas congregaciones.
 */
final class PcController
{
    private PcService $pcService;

    public function __construct()
    {
        $this->pcService = new PcService();
    }

    public function listar(): void
    {
        try {
            $filtros = PcValidator::validateFilters($_GET);
            JsonResponse::sendV2Success(200, 'PC obtenidas.', ['items' => $this->pcService->listar($filtros)]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[PcController::listar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function dashboard(): void
    {
        try {
            $filtros = PcValidator::validateFilters($_GET);
            JsonResponse::sendV2Success(200, 'Dashboard de PC obtenido.', ['item' => $this->pcService->dashboard($filtros)]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[PcController::dashboard] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function obtener(int $id): void
    {
        try {
            JsonResponse::sendV2Success(200, 'PC obtenida.', ['item' => $this->pcService->obtener($id)]);
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[PcController::obtener] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crear(): void
    {
        try {
            $validated = PcValidator::validatePc(Sanitizer::getJsonBody());
            $item = $this->pcService->crear($validated, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'PC creada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[PcController::crear] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function actualizar(int $id): void
    {
        try {
            $validated = PcValidator::validatePc(Sanitizer::getJsonBody());
            $item = $this->pcService->actualizar($id, $validated, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'PC actualizada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[PcController::actualizar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function eliminar(int $id): void
    {
        try {
            $this->pcService->eliminar($id, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'PC archivada correctamente.');
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[PcController::eliminar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crearParticipante(int $pcId): void
    {
        $this->resolverMutacion(fn(array $data) => $this->pcService->crearParticipante($pcId, $data, AuthContext::getUsuarioId(), AuthContext::getNombre()), [PcValidator::class, 'validateParticipante'], 'Participante registrado correctamente.');
    }

    public function actualizarParticipante(int $participanteId): void
    {
        $this->resolverMutacion(fn(array $data) => $this->pcService->actualizarParticipante($participanteId, $data, AuthContext::getUsuarioId(), AuthContext::getNombre()), [PcValidator::class, 'validateParticipante'], 'Participante actualizado correctamente.');
    }

    public function crearReunion(int $pcId): void
    {
        $this->resolverMutacion(fn(array $data) => $this->pcService->crearReunion($pcId, $data, AuthContext::getUsuarioId(), AuthContext::getNombre()), [PcValidator::class, 'validateReunion'], 'Reunion registrada correctamente.');
    }

    public function actualizarReunion(int $reunionId): void
    {
        $this->resolverMutacion(fn(array $data) => $this->pcService->actualizarReunion($reunionId, $data, AuthContext::getUsuarioId(), AuthContext::getNombre()), [PcValidator::class, 'validateReunion'], 'Reunion actualizada correctamente.');
    }

    public function registrarAsistenciaReunion(int $reunionId): void
    {
        $this->resolverMutacion(fn(array $data) => $this->pcService->registrarAsistenciaReunion($reunionId, $data, AuthContext::getUsuarioId(), AuthContext::getNombre()), [PcValidator::class, 'validateAsistenciaReunion'], 'Asistencia registrada correctamente.');
    }

    public function crearResultado(int $pcId): void
    {
        $this->resolverMutacion(fn(array $data) => $this->pcService->crearResultado($pcId, $data, AuthContext::getUsuarioId(), AuthContext::getNombre()), [PcValidator::class, 'validateResultado'], 'Resultado registrado correctamente.');
    }

    public function actualizarResultado(int $resultadoId): void
    {
        $this->resolverMutacion(fn(array $data) => $this->pcService->actualizarResultado($resultadoId, $data, AuthContext::getUsuarioId(), AuthContext::getNombre()), [PcValidator::class, 'validateResultado'], 'Resultado actualizado correctamente.');
    }

    public function crearLiderazgo(int $pcId): void
    {
        $this->resolverMutacion(fn(array $data) => $this->pcService->crearLiderazgo($pcId, $data, AuthContext::getUsuarioId(), AuthContext::getNombre()), [PcValidator::class, 'validateLiderazgo'], 'Movimiento de liderazgo registrado correctamente.');
    }

    public function actualizarLiderazgo(int $liderazgoId): void
    {
        $this->resolverMutacion(fn(array $data) => $this->pcService->actualizarLiderazgo($liderazgoId, $data, AuthContext::getUsuarioId(), AuthContext::getNombre()), [PcValidator::class, 'validateLiderazgo'], 'Movimiento de liderazgo actualizado correctamente.');
    }

    public function convertirParticipanteAEstudio(int $participanteId): void
    {
        try {
            $item = $this->pcService->convertirParticipanteAEstudio($participanteId, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Estudio biblico generado correctamente desde la PC.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[PcController::convertirParticipanteAEstudio] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $handler
     * @param callable(array<string, mixed>): array<string, mixed> $validator
     */
    private function resolverMutacion(callable $handler, callable $validator, string $mensaje): void
    {
        try {
            $validated = $validator(Sanitizer::getJsonBody());
            JsonResponse::sendV2Success(200, $mensaje, ['item' => $handler($validated)]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[PcController::resolverMutacion] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }
}
