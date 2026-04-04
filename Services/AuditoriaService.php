<?php
declare(strict_types=1);

/**
 * Servicio compartido para registrar eventos relevantes.
 */
final class AuditoriaService
{
    private AuditoriaEventoDAO $auditoriaDAO;

    public function __construct()
    {
        $this->auditoriaDAO = new AuditoriaEventoDAO();
    }

    /**
     * @param array<string, mixed>|null $antes
     * @param array<string, mixed>|null $despues
     * @param array<string, mixed>      $metadata
     */
    public function registrar(
        string $modulo,
        string $entidadTipo,
        ?int $entidadId,
        string $accion,
        string $resumen,
        ?int $organizacionId = null,
        ?int $actorUsuarioId = null,
        ?string $actorNombre = null,
        ?array $antes = null,
        ?array $despues = null,
        array $metadata = []
    ): void {
        $this->auditoriaDAO->insert([
            'organizacion_id' => $organizacionId,
            'modulo' => $modulo,
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'accion' => $accion,
            'resumen' => $resumen,
            'antes_json' => $antes !== null ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
            'despues_json' => $despues !== null ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null,
            'metadata_json' => $metadata !== [] ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            'actor_usuario_id' => $actorUsuarioId,
            'actor_nombre' => $actorNombre
        ]);
    }
}
