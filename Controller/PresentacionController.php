<?php
declare(strict_types=1);

/**
 * Controlador del modulo de presentaciones.
 */
final class PresentacionController
{
    /** @var PresentacionService */
    private PresentacionService $presentacionService;

    public function __construct()
    {
        $this->presentacionService = new PresentacionService();
    }

    /**
     * GET /presentaciones
     */
    public function listar(): void
    {
        try {
            $filtros = PresentacionValidator::validateList($_GET);
            $usuarioId = AuthContext::getUsuarioId();
            $esAdmin = AuthContext::esAdmin();

            $resultado = $this->presentacionService->listar($filtros, $usuarioId, $esAdmin);
            JsonResponse::send(200, true, 'Lista de presentaciones obtenida.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(400, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[PresentacionController::listar] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * POST /presentaciones/generar
     */
    public function generar(): void
    {
        try {
            $data = Sanitizer::getJsonBody();
            $filtros = PresentacionValidator::validateGenerar($data);
            $usuarioId = AuthContext::getUsuarioId();

            $resultado = $this->presentacionService->generar($filtros, $usuarioId);
            JsonResponse::send(201, true, 'Presentacion generada y almacenada correctamente.', $resultado);
        } catch (InvalidArgumentException $e) {
            JsonResponse::send(400, false, $e->getMessage());
        } catch (DomainException $e) {
            JsonResponse::send(403, false, $e->getMessage());
        } catch (RuntimeException $e) {
            $code = (int) $e->getCode();
            $status = ($code >= 400 && $code <= 599) ? $code : 500;
            JsonResponse::send($status, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[PresentacionController::generar] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }

    /**
     * GET /presentaciones/{id}
     */
    public function obtener(int $id): void
    {
        try {
            $usuarioId = AuthContext::getUsuarioId();
            $esAdmin = AuthContext::esAdmin();

            $resultado = $this->presentacionService->obtenerPorId($id, $usuarioId, $esAdmin);
            JsonResponse::send(200, true, 'Presentacion obtenida correctamente.', $resultado);
        } catch (DomainException $e) {
            JsonResponse::send(403, false, $e->getMessage());
        } catch (RuntimeException $e) {
            $code = (int) $e->getCode();
            $status = ($code >= 400 && $code <= 599) ? $code : 500;
            JsonResponse::send($status, false, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[PresentacionController::obtener] ' . $e->getMessage());
            JsonResponse::send(500, false, 'Error interno del servidor.');
        }
    }
}
