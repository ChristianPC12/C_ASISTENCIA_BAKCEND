<?php
declare(strict_types=1);

/**
 * Endpoints del modulo de campanas.
 */
final class CampanaController
{
    private CampanaService $campanaService;

    public function __construct()
    {
        $this->campanaService = new CampanaService();
    }

    public function listar(): void
    {
        try {
            $filtros = CampanaValidator::validateFilters($_GET);
            $items = $this->campanaService->listar($filtros);
            JsonResponse::sendV2Success(200, 'Campanas obtenidas.', ['items' => $items]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::listar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function dashboard(): void
    {
        try {
            $filtros = CampanaValidator::validateFilters($_GET);
            $datos = $this->campanaService->dashboard($filtros);
            JsonResponse::sendV2Success(200, 'Dashboard de campanas obtenido.', ['item' => $datos]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::dashboard] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function obtener(int $id): void
    {
        try {
            $item = $this->campanaService->obtener($id);
            JsonResponse::sendV2Success(200, 'Campana obtenida.', ['item' => $item]);
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::obtener] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crear(): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = CampanaValidator::validateCampana($data);
            $item = $this->campanaService->crear($validated, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Campana creada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::crear] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function actualizar(int $id): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = CampanaValidator::validateCampana($data);
            $item = $this->campanaService->actualizar($id, $validated, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Campana actualizada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::actualizar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function eliminar(int $id): void
    {
        try {
            $this->campanaService->eliminar($id, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Campana archivada correctamente.');
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::eliminar] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crearSesion(int $campanaId): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = CampanaValidator::validateSesion($data);
            $item = $this->campanaService->crearSesion($campanaId, $validated, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Sesion creada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::crearSesion] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function actualizarSesion(int $sesionId): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = CampanaValidator::validateSesion($data);
            $item = $this->campanaService->actualizarSesion($sesionId, $validated, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Sesion actualizada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::actualizarSesion] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function listarVisitas(): void
    {
        try {
            $filtros = [
                'q' => isset($_GET['q']) ? trim((string) $_GET['q']) : '',
                'estado_seguimiento' => isset($_GET['estado_seguimiento']) ? trim((string) $_GET['estado_seguimiento']) : '',
                'clasificacion_etaria' => isset($_GET['clasificacion_etaria']) ? trim((string) $_GET['clasificacion_etaria']) : '',
                'campana_id' => isset($_GET['campana_id']) ? trim((string) $_GET['campana_id']) : ''
            ];
            $items = $this->campanaService->listarVisitas($filtros);
            JsonResponse::sendV2Success(200, 'Listado de visitas obtenido correctamente.', ['items' => $items]);
        } catch (\Throwable $e) {
            error_log('[CampanaController::listarVisitas] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function buscarVisitasSimilares(): void
    {
        try {
            $nombre = isset($_GET['nombre']) ? trim((string) $_GET['nombre']) : '';
            $excluirId = isset($_GET['excluir_id']) && $_GET['excluir_id'] !== '' ? (int) $_GET['excluir_id'] : null;
            if ($nombre === '') {
                JsonResponse::sendV2Success(200, 'Sin consulta.', ['items' => []]);
                return;
            }
            $items = $this->campanaService->buscarVisitasSimilares($nombre, $excluirId);
            JsonResponse::sendV2Success(200, 'B\u00fasqueda de similares completada.', ['items' => $items]);
        } catch (\Throwable $e) {
            error_log('[CampanaController::buscarVisitasSimilares] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crearVisitaSuelta(): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = CampanaValidator::validateAsistente($data);
            $item = $this->campanaService->crearVisitaSuelta($validated, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Visita registrada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::crearVisitaSuelta] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crearAsistente(int $campanaId): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = CampanaValidator::validateAsistente($data);
            $item = $this->campanaService->crearAsistente($campanaId, $validated, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Asistente registrado correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::crearAsistente] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function actualizarAsistente(int $asistenteId): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = CampanaValidator::validateAsistente($data);
            $item = $this->campanaService->actualizarAsistente($asistenteId, $validated, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Asistente actualizado correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::actualizarAsistente] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function eliminarAsistente(int $asistenteId): void
    {
        try {
            $this->campanaService->eliminarAsistente($asistenteId, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Asistente eliminado correctamente.');
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::eliminarAsistente] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function registrarAsistenciaSesion(int $sesionId): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = CampanaValidator::validateAsistenciaSesion($data);
            $item = $this->campanaService->registrarAsistenciaSesion($sesionId, $validated, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Asistencia de sesion registrada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::registrarAsistenciaSesion] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function crearDecision(int $campanaId): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $validated = CampanaValidator::validateDecision($data);
            $item = $this->campanaService->crearDecision($campanaId, $validated, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Decision registrada correctamente.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::crearDecision] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function eliminarDecision(int $decisionId): void
    {
        try {
            $this->campanaService->eliminarDecision($decisionId, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Decision eliminada correctamente.');
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::eliminarDecision] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function entregarPremiosAsistente(int $asistenteId): void
    {
        try {
            $this->campanaService->entregarPremiosAsistente($asistenteId, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(200, 'Premios entregados correctamente.');
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::entregarPremiosAsistente] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }

    public function convertirAsistenteAEstudio(int $asistenteId): void
    {
        try {
            $item = $this->campanaService->convertirAsistenteAEstudio($asistenteId, AuthContext::getUsuarioId(), AuthContext::getNombre());
            JsonResponse::sendV2Success(201, 'Estudio biblico generado correctamente desde la campana.', ['item' => $item]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::sendV2Error(400, 'VALIDATION_ERROR', $e->getMessage());
        } catch (OutOfBoundsException $e) {
            JsonResponse::sendV2Error(404, 'RESOURCE_NOT_FOUND', $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::sendV2Error(409, 'CONFLICT_DUPLICATE', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[CampanaController::convertirAsistenteAEstudio] ' . $e->getMessage());
            JsonResponse::sendV2Error(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
        }
    }
}
