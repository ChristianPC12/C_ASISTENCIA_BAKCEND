<?php
declare(strict_types=1);

/**
 * Clase CultoController
 *
 * Orquesta las operaciones de consulta de cultos.
 */
final class CultoController
{
    /** @var CultoService */
    private CultoService $cultoService;

    public function __construct()
    {
        $this->cultoService = new CultoService();
    }

    /**
     * GET /cultos
     *
     * @return void
     */
    public function listar(): void
    {
        try {
            $resultado = $this->cultoService->listar();

            JsonResponse::send(200, true, 'Lista de cultos obtenida.', $resultado);
        } catch (\Throwable $e) {
            error_log('[CultoController::listar] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }
}
