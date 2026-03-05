<?php
declare(strict_types=1);

/**
 * Clase AsistenciaRoutes
 *
 * Enrutador para endpoints de asistencia.
 * Solo enruta; NO valida, NO BD, NO logica de negocio.
 */
final class AsistenciaRoutes
{
    /**
     * Resuelve la ruta de asistencias.
     *
     * @param string $method Metodo HTTP.
     * @param string $uri    URI normalizada.
     * @return bool true si la ruta fue resuelta, false si no coincide.
     */
    public static function resolve(string $method, string $uri): bool
    {
        // Patron para item: /asistencias/{id}
        $itemPattern = '#^/asistencias/(\d+)$#';
        $exportPattern = '#^/asistencias/(\d+)/exportar/(excel|pdf)$#';
        $reportPattern = '#^/asistencias/reportes/(excel|pdf)$#';

        // GET /asistencias (lista con filtros)
        if ($method === 'GET' && $uri === '/asistencias') {
            AuthMiddleware::handle();
            $controller = new AsistenciaController();
            $controller->listar();
            return true;
        }

        // GET /asistencias/{id}
        if ($method === 'GET' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            $controller = new AsistenciaController();
            $controller->obtener((int) $matches[1]);
            return true;
        }

        // GET /asistencias/{id}/exportar/{excel|pdf}
        if ($method === 'GET' && preg_match($exportPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            $controller = new AsistenciaController();
            if ($matches[2] === 'excel') {
                $controller->exportarExcel((int) $matches[1]);
            } else {
                $controller->exportarPdf((int) $matches[1]);
            }
            return true;
        }

        // GET /asistencias/reportes/{excel|pdf}
        if ($method === 'GET' && preg_match($reportPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            $controller = new AsistenciaController();
            if ($matches[1] === 'excel') {
                $controller->exportarInformeExcel();
            } else {
                $controller->exportarInformePdf();
            }
            return true;
        }

        // POST /asistencias
        if ($method === 'POST' && $uri === '/asistencias') {
            AuthMiddleware::handle();
            $controller = new AsistenciaController();
            $controller->crear();
            return true;
        }

        // PUT /asistencias/{id}
        if ($method === 'PUT' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            $controller = new AsistenciaController();
            $controller->actualizar((int) $matches[1]);
            return true;
        }

        // DELETE /asistencias/{id}
        if ($method === 'DELETE' && preg_match($itemPattern, $uri, $matches)) {
            AuthMiddleware::handle();
            $controller = new AsistenciaController();
            $controller->eliminar((int) $matches[1]);
            return true;
        }

        return false;
    }
}
