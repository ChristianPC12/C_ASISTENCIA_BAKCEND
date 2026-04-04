<?php
declare(strict_types=1);

/**
 * Acceso interno a tareas de seguimiento.
 */
final class SeguimientoTareaDAO
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $sql = 'INSERT INTO seguimiento_tareas (
                    organizacion_id, modulo, entidad_tipo, entidad_id, contacto_id, titulo, descripcion,
                    responsable_usuario_id, fecha_programada, fecha_limite, prioridad, estado,
                    resultado, completado_en, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :modulo, :entidad_tipo, :entidad_id, :contacto_id, :titulo, :descripcion,
                    :responsable_usuario_id, :fecha_programada, :fecha_limite, :prioridad, :estado,
                    :resultado, :completado_en, :creado_por, :actualizado_por
                )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':modulo' => $data['modulo'],
            ':entidad_tipo' => $data['entidad_tipo'],
            ':entidad_id' => $data['entidad_id'],
            ':contacto_id' => $data['contacto_id'],
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'],
            ':responsable_usuario_id' => $data['responsable_usuario_id'],
            ':fecha_programada' => $data['fecha_programada'],
            ':fecha_limite' => $data['fecha_limite'],
            ':prioridad' => $data['prioridad'],
            ':estado' => $data['estado'],
            ':resultado' => $data['resultado'],
            ':completado_en' => $data['completado_en'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
