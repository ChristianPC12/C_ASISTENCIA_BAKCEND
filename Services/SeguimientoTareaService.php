<?php
declare(strict_types=1);

/**
 * Servicio interno para crear tareas de seguimiento desde modulos misioneros.
 */
final class SeguimientoTareaService
{
    private SeguimientoTareaDAO $seguimientoDAO;
    private AuditoriaService $auditoriaService;

    public function __construct()
    {
        $this->seguimientoDAO = new SeguimientoTareaDAO();
        $this->auditoriaService = new AuditoriaService();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crear(array $data, int $organizacionId, int $usuarioId, string $actorNombre): array
    {
        $payload = [
            'organizacion_id' => $organizacionId,
            'modulo' => strtoupper(trim((string) ($data['modulo'] ?? 'SEGUIMIENTO'))),
            'entidad_tipo' => strtoupper(trim((string) ($data['entidad_tipo'] ?? 'TAREA'))),
            'entidad_id' => (int) ($data['entidad_id'] ?? 0),
            'contacto_id' => isset($data['contacto_id']) && $data['contacto_id'] !== null ? (int) $data['contacto_id'] : null,
            'titulo' => trim((string) ($data['titulo'] ?? 'Seguimiento pendiente')),
            'descripcion' => isset($data['descripcion']) && $data['descripcion'] !== '' ? trim((string) $data['descripcion']) : null,
            'responsable_usuario_id' => isset($data['responsable_usuario_id']) && $data['responsable_usuario_id'] !== null ? (int) $data['responsable_usuario_id'] : null,
            'fecha_programada' => $data['fecha_programada'] ?? null,
            'fecha_limite' => $data['fecha_limite'] ?? null,
            'prioridad' => strtoupper(trim((string) ($data['prioridad'] ?? 'MEDIA'))),
            'estado' => 'PENDIENTE',
            'resultado' => null,
            'completado_en' => null,
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ];

        $id = $this->seguimientoDAO->insert($payload);
        $item = ['id' => $id] + $payload;

        $this->auditoriaService->registrar(
            'SEGUIMIENTO',
            'TAREA',
            $id,
            'CREAR',
            'Tarea de seguimiento creada.',
            $organizacionId,
            $usuarioId,
            $actorNombre,
            null,
            $item,
            ['modulo_origen' => $payload['modulo'], 'entidad_tipo' => $payload['entidad_tipo']]
        );

        return $item;
    }
}
