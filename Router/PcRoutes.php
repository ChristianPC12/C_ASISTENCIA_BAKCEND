<?php
declare(strict_types=1);

/**
 * Rutas del modulo de pequenas congregaciones.
 */
final class PcRoutes
{
    public static function resolve(string $method, string $uri): bool
    {
        $itemPattern = '#^/pequenas-congregaciones/(\d+)$#';
        $dashboardPattern = '#^/pequenas-congregaciones/dashboard$#';
        $participantePattern = '#^/pequenas-congregaciones/(\d+)/participantes$#';
        $participanteItemPattern = '#^/pequenas-congregaciones/participantes/(\d+)$#';
        $participanteConvertirPattern = '#^/pequenas-congregaciones/participantes/(\d+)/convertir-estudio$#';
        $reunionPattern = '#^/pequenas-congregaciones/(\d+)/reuniones$#';
        $reunionItemPattern = '#^/pequenas-congregaciones/reuniones/(\d+)$#';
        $reunionAsistenciaPattern = '#^/pequenas-congregaciones/reuniones/(\d+)/asistencia$#';
        $resultadoPattern = '#^/pequenas-congregaciones/(\d+)/resultados$#';
        $resultadoItemPattern = '#^/pequenas-congregaciones/resultados/(\d+)$#';
        $liderazgoPattern = '#^/pequenas-congregaciones/(\d+)/liderazgo$#';
        $liderazgoItemPattern = '#^/pequenas-congregaciones/liderazgo/(\d+)$#';

        if ($method === 'GET' && $uri === '/pequenas-congregaciones') {
            return self::resolverOperativo(fn() => (new PcController())->listar());
        }
        if ($method === 'GET' && preg_match($dashboardPattern, $uri)) {
            return self::resolverOperativo(fn() => (new PcController())->dashboard());
        }
        if ($method === 'GET' && preg_match($itemPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->obtener((int) $matches[1]));
        }
        if ($method === 'POST' && $uri === '/pequenas-congregaciones') {
            return self::resolverOperativo(fn() => (new PcController())->crear());
        }
        if ($method === 'PUT' && preg_match($itemPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->actualizar((int) $matches[1]));
        }
        if ($method === 'DELETE' && preg_match($itemPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->eliminar((int) $matches[1]));
        }
        if ($method === 'POST' && preg_match($participantePattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->crearParticipante((int) $matches[1]));
        }
        if ($method === 'PUT' && preg_match($participanteItemPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->actualizarParticipante((int) $matches[1]));
        }
        if ($method === 'POST' && preg_match($participanteConvertirPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->convertirParticipanteAEstudio((int) $matches[1]));
        }
        if ($method === 'POST' && preg_match($reunionPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->crearReunion((int) $matches[1]));
        }
        if ($method === 'PUT' && preg_match($reunionItemPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->actualizarReunion((int) $matches[1]));
        }
        if ($method === 'POST' && preg_match($reunionAsistenciaPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->registrarAsistenciaReunion((int) $matches[1]));
        }
        if ($method === 'POST' && preg_match($resultadoPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->crearResultado((int) $matches[1]));
        }
        if ($method === 'PUT' && preg_match($resultadoItemPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->actualizarResultado((int) $matches[1]));
        }
        if ($method === 'POST' && preg_match($liderazgoPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->crearLiderazgo((int) $matches[1]));
        }
        if ($method === 'PUT' && preg_match($liderazgoItemPattern, $uri, $matches)) {
            return self::resolverOperativo(fn() => (new PcController())->actualizarLiderazgo((int) $matches[1]));
        }

        return false;
    }

    private static function resolverOperativo(callable $resolver): bool
    {
        AuthMiddleware::handle();
        RoleMiddleware::denySuperadminInOperative();
        RoleMiddleware::requireSetupCompletedForOperative();
        $resolver();
        return true;
    }
}
