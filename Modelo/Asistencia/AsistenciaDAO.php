<?php
declare(strict_types=1);

/**
 * Clase AsistenciaDAO
 *
 * Acceso a datos para la entidad Asistencia (tabla asistencia_registro).
 */
final class AsistenciaDAO
{
    /** @var PDO */
    private PDO $pdo;

    /** @var string Columnas base para SELECT (sin SELECT *) */
    private const COLUMNS = "ar.id, ar.culto_id, c.codigo AS culto_codigo, c.nombre AS culto_nombre,
                              ar.fecha, ar.anio, ar.trimestre,
                              ar.llegaron_antes_hora, ar.llegaron_despues_hora,
                              ar.ninos, ar.jovenes, ar.total_asistentes,
                              ar.proc_barrio, ar.proc_guayabo,
                              ar.visitas_barrio, ar.nombres_visitas_barrio,
                              ar.visitas_guayabo, ar.nombres_visitas_guayabo,
                              ar.retiros_antes_terminar, ar.se_quedaron_todo,
                              ar.observaciones, ar.metricas_json, ar.registrado_por,
                              u.nombre_completo AS registrado_por_nombre,
                              ar.creado_en, ar.actualizado_en";

    /** @var string JOINs comunes */
    private const JOINS = "FROM asistencia_registro ar
                           INNER JOIN cultos c ON c.id = ar.culto_id
                           INNER JOIN usuarios u ON u.id = ar.registrado_por";

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    /**
     * Busca un registro de asistencia por ID dentro de un tenant.
     *
     * @param int $id
     * @param int $organizacionId
     * @return AsistenciaDTO|null
     */
    public function findById(int $id, int $organizacionId): ?AsistenciaDTO
    {
        $sql = "SELECT " . self::COLUMNS . " " . self::JOINS . "
                WHERE ar.id = :id
                  AND ar.organizacion_id = :organizacion_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId
        ]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return AsistenciaMapper::fromRow($row);
    }

    /**
     * Lista registros de asistencia con filtros opcionales (tenant-aware).
     *
     * @param array<string, mixed> $filtros
     * @param int                  $organizacionId
     * @return AsistenciaDTO[]
     */
    public function findAll(array $filtros = [], int $organizacionId = 0): array
    {
        $sql    = "SELECT " . self::COLUMNS . " " . self::JOINS;
        $where  = ["ar.organizacion_id = :organizacion_id"];
        $params = [':organizacion_id' => $organizacionId];

        if (!empty($filtros['culto_id'])) {
            $where[]             = "ar.culto_id = :culto_id";
            $params[':culto_id'] = (int) $filtros['culto_id'];
        }

        if (!empty($filtros['anio'])) {
            $where[]         = "ar.anio = :anio";
            $params[':anio'] = (int) $filtros['anio'];
        }

        if (!empty($filtros['trimestre'])) {
            $where[]              = "ar.trimestre = :trimestre";
            $params[':trimestre'] = (int) $filtros['trimestre'];
        }

        if (!empty($filtros['mes'])) {
            $where[]        = "MONTH(ar.fecha) = :mes";
            $params[':mes'] = (int) $filtros['mes'];
        }

        if (!empty($filtros['fecha_exacta'])) {
            $where[]                 = "ar.fecha = :fecha_exacta";
            $params[':fecha_exacta'] = $filtros['fecha_exacta'];
        }

        $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY ar.fecha DESC, ar.culto_id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $registros = [];
        while ($row = $stmt->fetch()) {
            $registros[] = AsistenciaMapper::fromRow($row);
        }

        return $registros;
    }

    /**
     * Inserta un nuevo registro de asistencia.
     *
     * @param array<string, mixed> $data
     * @return int
     */
    public function insert(array $data): int
    {
        $sql = "INSERT INTO asistencia_registro
                    (organizacion_id, culto_id, fecha, llegaron_antes_hora, llegaron_despues_hora,
                     ninos, jovenes, total_asistentes,
                     proc_barrio, proc_guayabo,
                     visitas_barrio, nombres_visitas_barrio,
                     visitas_guayabo, nombres_visitas_guayabo,
                     retiros_antes_terminar, se_quedaron_todo,
                     observaciones, metricas_json, registrado_por)
                VALUES
                    (:organizacion_id, :culto_id, :fecha, :llegaron_antes_hora, :llegaron_despues_hora,
                     :ninos, :jovenes, :total_asistentes,
                     :proc_barrio, :proc_guayabo,
                     :visitas_barrio, :nombres_visitas_barrio,
                     :visitas_guayabo, :nombres_visitas_guayabo,
                     :retiros_antes_terminar, :se_quedaron_todo,
                     :observaciones, :metricas_json, :registrado_por)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':culto_id' => $data['culto_id'],
            ':fecha' => $data['fecha'],
            ':llegaron_antes_hora' => $data['llegaron_antes_hora'],
            ':llegaron_despues_hora' => $data['llegaron_despues_hora'],
            ':ninos' => $data['ninos'],
            ':jovenes' => $data['jovenes'],
            ':total_asistentes' => $data['total_asistentes'],
            ':proc_barrio' => $data['proc_barrio'],
            ':proc_guayabo' => $data['proc_guayabo'],
            ':visitas_barrio' => $data['visitas_barrio'],
            ':nombres_visitas_barrio' => $data['nombres_visitas_barrio'],
            ':visitas_guayabo' => $data['visitas_guayabo'],
            ':nombres_visitas_guayabo' => $data['nombres_visitas_guayabo'],
            ':retiros_antes_terminar' => $data['retiros_antes_terminar'],
            ':se_quedaron_todo' => $data['se_quedaron_todo'],
            ':observaciones' => $data['observaciones'],
            ':metricas_json' => $data['metricas_json'],
            ':registrado_por' => $data['registrado_por']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un registro de asistencia existente.
     *
     * @param int                  $id
     * @param array<string, mixed> $data
     * @param int                  $organizacionId
     * @return bool
     */
    public function update(int $id, array $data, int $organizacionId): bool
    {
        $sql = "UPDATE asistencia_registro SET
                    culto_id = :culto_id,
                    fecha = :fecha,
                    llegaron_antes_hora = :llegaron_antes_hora,
                    llegaron_despues_hora = :llegaron_despues_hora,
                    ninos = :ninos,
                    jovenes = :jovenes,
                    total_asistentes = :total_asistentes,
                    proc_barrio = :proc_barrio,
                    proc_guayabo = :proc_guayabo,
                    visitas_barrio = :visitas_barrio,
                    nombres_visitas_barrio = :nombres_visitas_barrio,
                    visitas_guayabo = :visitas_guayabo,
                    nombres_visitas_guayabo = :nombres_visitas_guayabo,
                    retiros_antes_terminar = :retiros_antes_terminar,
                    se_quedaron_todo = :se_quedaron_todo,
                    observaciones = :observaciones,
                    metricas_json = :metricas_json
                WHERE id = :id
                  AND organizacion_id = :organizacion_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':culto_id' => $data['culto_id'],
            ':fecha' => $data['fecha'],
            ':llegaron_antes_hora' => $data['llegaron_antes_hora'],
            ':llegaron_despues_hora' => $data['llegaron_despues_hora'],
            ':ninos' => $data['ninos'],
            ':jovenes' => $data['jovenes'],
            ':total_asistentes' => $data['total_asistentes'],
            ':proc_barrio' => $data['proc_barrio'],
            ':proc_guayabo' => $data['proc_guayabo'],
            ':visitas_barrio' => $data['visitas_barrio'],
            ':nombres_visitas_barrio' => $data['nombres_visitas_barrio'],
            ':visitas_guayabo' => $data['visitas_guayabo'],
            ':nombres_visitas_guayabo' => $data['nombres_visitas_guayabo'],
            ':retiros_antes_terminar' => $data['retiros_antes_terminar'],
            ':se_quedaron_todo' => $data['se_quedaron_todo'],
            ':observaciones' => $data['observaciones'],
            ':metricas_json' => $data['metricas_json'],
            ':id' => $id,
            ':organizacion_id' => $organizacionId
        ]);
    }

    /**
     * Elimina un registro de asistencia (hard delete).
     *
     * @param int $id
     * @param int $organizacionId
     * @return bool
     */
    public function delete(int $id, int $organizacionId): bool
    {
        $sql = "DELETE FROM asistencia_registro
                WHERE id = :id
                  AND organizacion_id = :organizacion_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId
        ]);
    }

    /**
     * Verifica si existe un registro para el mismo culto y fecha (duplicado) por tenant.
     *
     * @param int      $organizacionId
     * @param int      $cultoId
     * @param string   $fecha
     * @param int|null $excludeId
     * @return bool
     */
    public function existsByCultoFecha(
        int $organizacionId,
        int $cultoId,
        string $fecha,
        ?int $excludeId = null
    ): bool {
        $sql = "SELECT COUNT(id)
                FROM asistencia_registro
                WHERE organizacion_id = :organizacion_id
                  AND culto_id = :culto_id
                  AND fecha = :fecha";
        $params = [
            ':organizacion_id' => $organizacionId,
            ':culto_id' => $cultoId,
            ':fecha' => $fecha
        ];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }
}
