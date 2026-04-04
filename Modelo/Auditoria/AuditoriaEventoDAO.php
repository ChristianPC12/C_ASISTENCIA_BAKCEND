<?php
declare(strict_types=1);

/**
 * Persistencia basica de auditoria por modulo.
 */
final class AuditoriaEventoDAO
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
        $sql = 'INSERT INTO auditoria_eventos (
                    organizacion_id, modulo, entidad_tipo, entidad_id, accion, resumen,
                    antes_json, despues_json, metadata_json, actor_usuario_id, actor_nombre
                ) VALUES (
                    :organizacion_id, :modulo, :entidad_tipo, :entidad_id, :accion, :resumen,
                    :antes_json, :despues_json, :metadata_json, :actor_usuario_id, :actor_nombre
                )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':modulo' => $data['modulo'],
            ':entidad_tipo' => $data['entidad_tipo'],
            ':entidad_id' => $data['entidad_id'],
            ':accion' => $data['accion'],
            ':resumen' => $data['resumen'],
            ':antes_json' => $data['antes_json'],
            ':despues_json' => $data['despues_json'],
            ':metadata_json' => $data['metadata_json'],
            ':actor_usuario_id' => $data['actor_usuario_id'],
            ':actor_nombre' => $data['actor_nombre']
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
