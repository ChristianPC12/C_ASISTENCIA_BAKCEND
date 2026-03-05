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
                              ar.observaciones, ar.registrado_por,
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
     * Busca un registro de asistencia por ID.
     *
     * @param int $id ID del registro.
     * @return AsistenciaDTO|null
     */
    public function findById(int $id): ?AsistenciaDTO
    {
        $sql = "SELECT " . self::COLUMNS . " " . self::JOINS . " WHERE ar.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return AsistenciaMapper::fromRow($row);
    }

    /**
     * Lista registros de asistencia con filtros opcionales.
     *
     * @param array<string, mixed> $filtros Filtros: culto_id, anio, trimestre, mes, fecha_exacta.
     * @return AsistenciaDTO[]
     */
    public function findAll(array $filtros = []): array
    {
        $sql    = "SELECT " . self::COLUMNS . " " . self::JOINS;
        $where  = [];
        $params = [];

        if (!empty($filtros['culto_id'])) {
            $where[]              = "ar.culto_id = :culto_id";
            $params[':culto_id']  = (int) $filtros['culto_id'];
        }

        if (!empty($filtros['anio'])) {
            $where[]          = "ar.anio = :anio";
            $params[':anio']  = (int) $filtros['anio'];
        }

        if (!empty($filtros['trimestre'])) {
            $where[]               = "ar.trimestre = :trimestre";
            $params[':trimestre']  = (int) $filtros['trimestre'];
        }

        if (!empty($filtros['mes'])) {
            $where[]         = "MONTH(ar.fecha) = :mes";
            $params[':mes']  = (int) $filtros['mes'];
        }

        if (!empty($filtros['fecha_exacta'])) {
            $where[] = "ar.fecha = :fecha_exacta";
            $params[':fecha_exacta'] = $filtros['fecha_exacta'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

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
     * @param array<string, mixed> $data Datos del registro.
     * @return int ID del registro insertado.
     */
    public function insert(array $data): int
    {
        $sql = "INSERT INTO asistencia_registro
                    (culto_id, fecha, llegaron_antes_hora, llegaron_despues_hora,
                     ninos, jovenes, total_asistentes,
                     proc_barrio, proc_guayabo,
                     visitas_barrio, nombres_visitas_barrio,
                     visitas_guayabo, nombres_visitas_guayabo,
                     retiros_antes_terminar, se_quedaron_todo,
                     observaciones, registrado_por)
                VALUES
                    (:culto_id, :fecha, :llegaron_antes_hora, :llegaron_despues_hora,
                     :ninos, :jovenes, :total_asistentes,
                     :proc_barrio, :proc_guayabo,
                     :visitas_barrio, :nombres_visitas_barrio,
                     :visitas_guayabo, :nombres_visitas_guayabo,
                     :retiros_antes_terminar, :se_quedaron_todo,
                     :observaciones, :registrado_por)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':culto_id'               => $data['culto_id'],
            ':fecha'                  => $data['fecha'],
            ':llegaron_antes_hora'    => $data['llegaron_antes_hora'],
            ':llegaron_despues_hora'  => $data['llegaron_despues_hora'],
            ':ninos'                  => $data['ninos'],
            ':jovenes'                => $data['jovenes'],
            ':total_asistentes'       => $data['total_asistentes'],
            ':proc_barrio'            => $data['proc_barrio'],
            ':proc_guayabo'           => $data['proc_guayabo'],
            ':visitas_barrio'         => $data['visitas_barrio'],
            ':nombres_visitas_barrio' => $data['nombres_visitas_barrio'],
            ':visitas_guayabo'        => $data['visitas_guayabo'],
            ':nombres_visitas_guayabo' => $data['nombres_visitas_guayabo'],
            ':retiros_antes_terminar' => $data['retiros_antes_terminar'],
            ':se_quedaron_todo'       => $data['se_quedaron_todo'],
            ':observaciones'          => $data['observaciones'],
            ':registrado_por'         => $data['registrado_por']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un registro de asistencia existente.
     *
     * @param int                  $id   ID del registro.
     * @param array<string, mixed> $data Datos a actualizar.
     * @return bool
     */
    public function update(int $id, array $data): bool
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
                    observaciones = :observaciones
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':culto_id'               => $data['culto_id'],
            ':fecha'                  => $data['fecha'],
            ':llegaron_antes_hora'    => $data['llegaron_antes_hora'],
            ':llegaron_despues_hora'  => $data['llegaron_despues_hora'],
            ':ninos'                  => $data['ninos'],
            ':jovenes'                => $data['jovenes'],
            ':total_asistentes'       => $data['total_asistentes'],
            ':proc_barrio'            => $data['proc_barrio'],
            ':proc_guayabo'           => $data['proc_guayabo'],
            ':visitas_barrio'         => $data['visitas_barrio'],
            ':nombres_visitas_barrio' => $data['nombres_visitas_barrio'],
            ':visitas_guayabo'        => $data['visitas_guayabo'],
            ':nombres_visitas_guayabo' => $data['nombres_visitas_guayabo'],
            ':retiros_antes_terminar' => $data['retiros_antes_terminar'],
            ':se_quedaron_todo'       => $data['se_quedaron_todo'],
            ':observaciones'          => $data['observaciones'],
            ':id'                     => $id
        ]);
    }

    /**
     * Elimina un registro de asistencia (hard delete).
     *
     * @param int $id ID del registro.
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM asistencia_registro WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Verifica si existe un registro para el mismo culto y fecha (duplicado).
     *
     * @param int         $cultoId    ID del culto.
     * @param string      $fecha      Fecha del culto (Y-m-d).
     * @param int|null    $excludeId  ID a excluir (para updates).
     * @return bool
     */
    public function existsByCultoFecha(int $cultoId, string $fecha, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(id) FROM asistencia_registro
                WHERE culto_id = :culto_id AND fecha = :fecha";
        $params = [
            ':culto_id' => $cultoId,
            ':fecha'    => $fecha
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
