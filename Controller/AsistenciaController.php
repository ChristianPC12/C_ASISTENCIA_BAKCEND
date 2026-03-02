<?php
declare(strict_types=1);

/**
 * Clase AsistenciaController
 *
 * Orquesta las operaciones CRUD de registros de asistencia.
 */
final class AsistenciaController
{
    /** @var AsistenciaService */
    private AsistenciaService $asistenciaService;

    public function __construct()
    {
        $this->asistenciaService = new AsistenciaService();
    }

    /**
     * GET /asistencias
     * Soporta filtros por query string: culto, anio, trimestre.
     *
     * @return void
     */
    public function listar(): void
    {
        try {
            $filtros = [
                'culto'     => $_GET['culto'] ?? null,
                'culto_id'  => $_GET['culto_id'] ?? null,
                'anio'      => $_GET['anio'] ?? null,
                'trimestre' => $_GET['trimestre'] ?? null
            ];

            $resultado = $this->asistenciaService->listar($filtros);

            JsonResponse::send(200, true, 'Lista de asistencias obtenida.', $resultado);
        } catch (\Throwable $e) {
            error_log('[AsistenciaController::listar] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * GET /asistencias/{id}
     *
     * @param int $id ID del registro.
     * @return void
     */
    public function obtener(int $id): void
    {
        try {
            $resultado = $this->asistenciaService->obtenerPorId($id);

            JsonResponse::send(200, true, 'Registro de asistencia obtenido.', $resultado);
        } catch (RuntimeException $e) {
            JsonResponse::send(404, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AsistenciaController::obtener] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * POST /asistencias
     *
     * @return void
     */
    public function crear(): void
    {
        try {
            $data         = Sanitizer::getJsonBody();
            $validated    = AsistenciaValidator::validate($data);
            $registradoPor = AuthContext::getUsuarioId();
            $resultado    = $this->asistenciaService->crear($validated, $registradoPor);

            JsonResponse::send(201, true, 'Registro de asistencia creado correctamente.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(400, false, $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::send(409, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AsistenciaController::crear] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * PUT /asistencias/{id}
     *
     * @param int $id ID del registro.
     * @return void
     */
    public function actualizar(int $id): void
    {
        try {
            $data      = Sanitizer::getJsonBody();
            $validated = AsistenciaValidator::validate($data);
            $resultado = $this->asistenciaService->actualizar($id, $validated);

            JsonResponse::send(200, true, 'Registro de asistencia actualizado correctamente.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(400, false, $e->getMessage());
        } catch (RuntimeException $e) {
            JsonResponse::send(409, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AsistenciaController::actualizar] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * DELETE /asistencias/{id}
     *
     * @param int $id ID del registro.
     * @return void
     */
    public function eliminar(int $id): void
    {
        try {
            $this->asistenciaService->eliminar($id);

            JsonResponse::send(200, true, 'Registro de asistencia eliminado correctamente.');
        } catch (RuntimeException $e) {
            JsonResponse::send(404, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AsistenciaController::eliminar] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }
}
