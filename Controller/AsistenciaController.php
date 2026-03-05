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
    /** @var AsistenciaExportService */
    private AsistenciaExportService $asistenciaExportService;

    public function __construct()
    {
        $this->asistenciaService = new AsistenciaService();
        $this->asistenciaExportService = new AsistenciaExportService();
    }

    /**
     * GET /asistencias
     * Soporta filtros por query string: culto, anio, trimestre, mes.
     *
     * @return void
     */
    public function listar(): void
    {
        try {
            $filtros = $this->obtenerFiltrosDesdeQuery();

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

    /**
     * GET /asistencias/{id}/exportar/excel
     *
     * @param int $id ID del registro.
     * @return void
     */
    public function exportarExcel(int $id): void
    {
        try {
            $registro = $this->asistenciaService->obtenerPorId($id);
            $contenido = $this->asistenciaExportService->generarExcel($registro);
            $filename = $this->nombreArchivo((string) $registro['fecha'], 'xls');

            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            echo $contenido;
        } catch (RuntimeException $e) {
            JsonResponse::send(404, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AsistenciaController::exportarExcel] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }


    /**
     * GET /asistencias/reportes/excel
     *
     * @return void
     */
    public function exportarInformeExcel(): void
    {
        try {
            $filtros = $this->obtenerFiltrosDesdeQuery();
            $registros = $this->asistenciaService->listar($filtros);
            $contenido = $this->asistenciaExportService->generarInformeExcel($registros, $filtros);
            $filename = $this->nombreArchivoInforme($filtros, 'xls');

            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            echo $contenido;
        } catch (\Throwable $e) {
            error_log('[AsistenciaController::exportarInformeExcel] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }


    private function nombreArchivo(string $fecha, string $ext): string
    {
        $fechaSegura = preg_replace('/[^0-9\-]/', '', $fecha) ?: 'sin-fecha';
        return "asistencia_{$fechaSegura}.{$ext}";
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerFiltrosDesdeQuery(): array
    {
        return [
            'culto'        => $_GET['culto'] ?? null,
            'culto_id'     => $_GET['culto_id'] ?? null,
            'anio'         => $_GET['anio'] ?? null,
            'trimestre'    => $_GET['trimestre'] ?? null,
            'mes'          => $_GET['mes'] ?? null,
            'fecha_exacta' => $_GET['fecha_exacta'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $filtros
     */
    private function nombreArchivoInforme(array $filtros, string $ext): string
    {
        $anio = !empty($filtros['anio']) ? preg_replace('/[^0-9]/', '', (string) $filtros['anio']) : date('Y');
        $periodo = 'todos';
        if (!empty($filtros['mes'])) {
            $periodo = 'mes-' . str_pad((string) preg_replace('/[^0-9]/', '', (string) $filtros['mes']), 2, '0', STR_PAD_LEFT);
        } elseif (!empty($filtros['trimestre'])) {
            $periodo = 't' . preg_replace('/[^0-9]/', '', (string) $filtros['trimestre']);
        }

        return "informe_asistencia_{$anio}_{$periodo}.{$ext}";
    }
}
