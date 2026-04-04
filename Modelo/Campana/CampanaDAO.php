<?php
declare(strict_types=1);

/**
 * Acceso a datos del modulo de campanas.
 */
final class CampanaDAO
{
    private PDO $pdo;

    private const COLUMNS = 'c.id, c.organizacion_id, c.nombre, c.lema, c.tipo, c.fecha_inicio, c.fecha_fin,
        c.lugar, c.predicador, c.responsable_usuario_id, u.nombre_completo AS responsable_usuario_nombre,
        c.descripcion, c.estado, c.observaciones, c.creado_por, c.actualizado_por, c.eliminado_por,
        c.creado_en, c.actualizado_en, c.eliminado_en';

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    /**
     * @param array<string, mixed> $filters
     * @return CampanaDTO[]
     */
    public function findAll(array $filters, int $organizacionId): array
    {
        $rows = $this->findAllAsArray($filters, $organizacionId);
        $items = [];
        foreach ($rows as $row) {
            $items[] = CampanaMapper::fromRow($row);
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function findAllAsArray(array $filters, int $organizacionId): array
    {
        $sql = 'SELECT ' . self::COLUMNS . ',
                    COUNT(DISTINCT cs.id) AS total_sesiones,
                    COUNT(DISTINCT ca.id) AS total_asistentes,
                    COUNT(DISTINCT cd.id) AS total_decisiones
                FROM campanas c
                LEFT JOIN usuarios u ON u.id = c.responsable_usuario_id
                LEFT JOIN campana_sesiones cs ON cs.campana_id = c.id
                LEFT JOIN campana_asistentes ca ON ca.campana_id = c.id AND ca.eliminado_en IS NULL
                LEFT JOIN campana_decisiones cd ON cd.campana_id = c.id
                WHERE c.organizacion_id = :organizacion_id
                  AND c.eliminado_en IS NULL';

        $params = [':organizacion_id' => $organizacionId];

        if (!empty($filters['q'])) {
            $sql .= ' AND (c.nombre LIKE :q OR c.lema LIKE :q OR c.predicador LIKE :q OR c.lugar LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['estado'])) {
            $sql .= ' AND c.estado = :estado';
            $params[':estado'] = $filters['estado'];
        }

        if (!empty($filters['responsable_usuario_id'])) {
            $sql .= ' AND c.responsable_usuario_id = :responsable_usuario_id';
            $params[':responsable_usuario_id'] = (int) $filters['responsable_usuario_id'];
        }

        if (!empty($filters['fecha_desde'])) {
            $sql .= ' AND c.fecha_fin >= :fecha_desde';
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $sql .= ' AND c.fecha_inicio <= :fecha_hasta';
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        $sql .= ' GROUP BY c.id ORDER BY c.fecha_inicio DESC, c.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch()) {
            $dto = CampanaMapper::fromRow($row);
            $item = CampanaMapper::toArray($dto);
            $item['total_sesiones'] = (int) ($row['total_sesiones'] ?? 0);
            $item['total_asistentes'] = (int) ($row['total_asistentes'] ?? 0);
            $item['total_decisiones'] = (int) ($row['total_decisiones'] ?? 0);
            $items[] = $item;
        }

        return $items;
    }

    public function findById(int $id, int $organizacionId): ?CampanaDTO
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM campanas c
                LEFT JOIN usuarios u ON u.id = c.responsable_usuario_id
                WHERE c.id = :id
                  AND c.organizacion_id = :organizacion_id
                  AND c.eliminado_en IS NULL';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id, ':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();
        return $row === false ? null : CampanaMapper::fromRow($row);
    }

    /** @param array<string, mixed> $data */
    public function insert(array $data): int
    {
        $sql = 'INSERT INTO campanas (
                    organizacion_id, nombre, lema, tipo, fecha_inicio, fecha_fin, lugar, predicador,
                    responsable_usuario_id, descripcion, estado, observaciones, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :nombre, :lema, :tipo, :fecha_inicio, :fecha_fin, :lugar, :predicador,
                    :responsable_usuario_id, :descripcion, :estado, :observaciones, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'], ':nombre' => $data['nombre'], ':lema' => $data['lema'],
            ':tipo' => $data['tipo'], ':fecha_inicio' => $data['fecha_inicio'], ':fecha_fin' => $data['fecha_fin'],
            ':lugar' => $data['lugar'], ':predicador' => $data['predicador'],
            ':responsable_usuario_id' => $data['responsable_usuario_id'], ':descripcion' => $data['descripcion'],
            ':estado' => $data['estado'], ':observaciones' => $data['observaciones'],
            ':creado_por' => $data['creado_por'], ':actualizado_por' => $data['actualizado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE campanas SET
                    nombre = :nombre,
                    lema = :lema,
                    tipo = :tipo,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    lugar = :lugar,
                    predicador = :predicador,
                    responsable_usuario_id = :responsable_usuario_id,
                    descripcion = :descripcion,
                    estado = :estado,
                    observaciones = :observaciones,
                    actualizado_por = :actualizado_por
                WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id, ':organizacion_id' => $organizacionId, ':nombre' => $data['nombre'], ':lema' => $data['lema'],
            ':tipo' => $data['tipo'], ':fecha_inicio' => $data['fecha_inicio'], ':fecha_fin' => $data['fecha_fin'],
            ':lugar' => $data['lugar'], ':predicador' => $data['predicador'],
            ':responsable_usuario_id' => $data['responsable_usuario_id'], ':descripcion' => $data['descripcion'],
            ':estado' => $data['estado'], ':observaciones' => $data['observaciones'], ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    public function softDelete(int $id, int $organizacionId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE campanas SET eliminado_en = CURRENT_TIMESTAMP, eliminado_por = :eliminado_por WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL');
        return $stmt->execute([':id' => $id, ':organizacion_id' => $organizacionId, ':eliminado_por' => $usuarioId]);
    }

    /** @return array<string, mixed> */
    public function getDashboard(array $filters, int $organizacionId): array
    {
        $where = ['c.organizacion_id = :organizacion_id', 'c.eliminado_en IS NULL'];
        $params = [':organizacion_id' => $organizacionId];
        if (!empty($filters['fecha_desde'])) { $where[] = 'c.fecha_fin >= :fecha_desde'; $params[':fecha_desde'] = $filters['fecha_desde']; }
        if (!empty($filters['fecha_hasta'])) { $where[] = 'c.fecha_inicio <= :fecha_hasta'; $params[':fecha_hasta'] = $filters['fecha_hasta']; }
        if (!empty($filters['estado'])) { $where[] = 'c.estado = :estado'; $params[':estado'] = $filters['estado']; }
        if (!empty($filters['responsable_usuario_id'])) { $where[] = 'c.responsable_usuario_id = :responsable_usuario_id'; $params[':responsable_usuario_id'] = (int) $filters['responsable_usuario_id']; }

        $sql = "SELECT
                    COUNT(DISTINCT c.id) AS total_campanas,
                    SUM(CASE WHEN c.estado = 'ACTIVA' THEN 1 ELSE 0 END) AS total_activas,
                    SUM(CASE WHEN c.estado = 'FINALIZADA' THEN 1 ELSE 0 END) AS total_finalizadas,
                    COUNT(DISTINCT ca.id) AS total_asistentes_unicos,
                    COUNT(DISTINCT CASE WHEN ca.tipo_asistente IN ('VISITA', 'INTERESADO') THEN ca.id END) AS total_visitas_unicas,
                    COUNT(DISTINCT cd.id) AS total_decisiones,
                    COUNT(DISTINCT cd.estudio_biblico_id) AS total_estudios_derivados,
                    COUNT(DISTINCT CASE WHEN cd.decision_clave = 'BAUTIZADO' THEN cd.id END) AS total_bautismos_relacionados
                FROM campanas c
                LEFT JOIN campana_asistentes ca ON ca.campana_id = c.id AND ca.eliminado_en IS NULL
                LEFT JOIN campana_decisiones cd ON cd.campana_id = c.id
                WHERE " . implode(' AND ', $where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'total_campanas' => (int) ($row['total_campanas'] ?? 0),
            'total_activas' => (int) ($row['total_activas'] ?? 0),
            'total_finalizadas' => (int) ($row['total_finalizadas'] ?? 0),
            'total_asistentes_unicos' => (int) ($row['total_asistentes_unicos'] ?? 0),
            'total_visitas_unicas' => (int) ($row['total_visitas_unicas'] ?? 0),
            'total_decisiones' => (int) ($row['total_decisiones'] ?? 0),
            'total_estudios_derivados' => (int) ($row['total_estudios_derivados'] ?? 0),
            'total_bautismos_relacionados' => (int) ($row['total_bautismos_relacionados'] ?? 0)
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function listSessions(int $campanaId, int $organizacionId): array
    {
        $sql = 'SELECT cs.*, COUNT(cas.id) AS total_registros,
                    SUM(CASE WHEN cas.puntual = 1 THEN 1 ELSE 0 END) AS total_puntuales
                FROM campana_sesiones cs
                LEFT JOIN campana_asistencia_sesiones cas ON cas.campana_sesion_id = cs.id
                WHERE cs.campana_id = :campana_id
                  AND cs.organizacion_id = :organizacion_id
                GROUP BY cs.id
                ORDER BY cs.fecha ASC, cs.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':campana_id' => $campanaId, ':organizacion_id' => $organizacionId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @param array<string, mixed> $data */
    public function insertSession(array $data): int
    {
        $sql = 'INSERT INTO campana_sesiones (organizacion_id, campana_id, fecha, hora_inicio, tema_titulo, predicador_noche, observaciones, estado_sesion, creado_por, actualizado_por)
                VALUES (:organizacion_id, :campana_id, :fecha, :hora_inicio, :tema_titulo, :predicador_noche, :observaciones, :estado_sesion, :creado_por, :actualizado_por)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'], ':campana_id' => $data['campana_id'], ':fecha' => $data['fecha'],
            ':hora_inicio' => $data['hora_inicio'], ':tema_titulo' => $data['tema_titulo'], ':predicador_noche' => $data['predicador_noche'],
            ':observaciones' => $data['observaciones'], ':estado_sesion' => $data['estado_sesion'],
            ':creado_por' => $data['creado_por'], ':actualizado_por' => $data['actualizado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findSessionById(int $sesionId, int $organizacionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM campana_sesiones WHERE id = :id AND organizacion_id = :organizacion_id');
        $stmt->execute([':id' => $sesionId, ':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $data */
    public function updateSession(int $sesionId, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE campana_sesiones SET fecha = :fecha, hora_inicio = :hora_inicio, tema_titulo = :tema_titulo,
                    predicador_noche = :predicador_noche, observaciones = :observaciones, estado_sesion = :estado_sesion,
                    actualizado_por = :actualizado_por
                WHERE id = :id AND organizacion_id = :organizacion_id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $sesionId, ':organizacion_id' => $organizacionId, ':fecha' => $data['fecha'], ':hora_inicio' => $data['hora_inicio'],
            ':tema_titulo' => $data['tema_titulo'], ':predicador_noche' => $data['predicador_noche'], ':observaciones' => $data['observaciones'],
            ':estado_sesion' => $data['estado_sesion'], ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listAttendees(int $campanaId, int $organizacionId): array
    {
        $sql = 'SELECT ca.*, cm.nombre_completo AS contacto_nombre, cm.telefono AS contacto_telefono,
                    COUNT(DISTINCT cas.id) AS total_noches,
                    SUM(CASE WHEN cas.puntual = 1 THEN 1 ELSE 0 END) AS total_puntuales
                FROM campana_asistentes ca
                LEFT JOIN contactos_misioneros cm ON cm.id = ca.contacto_id
                LEFT JOIN campana_asistencia_sesiones cas ON cas.campana_asistente_id = ca.id
                WHERE ca.campana_id = :campana_id
                  AND ca.organizacion_id = :organizacion_id
                  AND ca.eliminado_en IS NULL
                GROUP BY ca.id
                ORDER BY ca.nombre_snapshot ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':campana_id' => $campanaId, ':organizacion_id' => $organizacionId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed>|null */
    public function findAttendeeById(int $asistenteId, int $organizacionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM campana_asistentes WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL');
        $stmt->execute([':id' => $asistenteId, ':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $data */
    public function insertAttendee(array $data): int
    {
        $sql = 'INSERT INTO campana_asistentes (
                    organizacion_id, campana_id, contacto_id, nombre_snapshot, telefono_snapshot, procedencia,
                    tipo_asistente, clasificacion_etaria, invitado_por_contacto_id, primera_vez, observaciones,
                    estado_seguimiento, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :campana_id, :contacto_id, :nombre_snapshot, :telefono_snapshot, :procedencia,
                    :tipo_asistente, :clasificacion_etaria, :invitado_por_contacto_id, :primera_vez, :observaciones,
                    :estado_seguimiento, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'], ':campana_id' => $data['campana_id'], ':contacto_id' => $data['contacto_id'],
            ':nombre_snapshot' => $data['nombre_snapshot'], ':telefono_snapshot' => $data['telefono_snapshot'], ':procedencia' => $data['procedencia'],
            ':tipo_asistente' => $data['tipo_asistente'], ':clasificacion_etaria' => $data['clasificacion_etaria'],
            ':invitado_por_contacto_id' => $data['invitado_por_contacto_id'], ':primera_vez' => $data['primera_vez'],
            ':observaciones' => $data['observaciones'], ':estado_seguimiento' => $data['estado_seguimiento'],
            ':creado_por' => $data['creado_por'], ':actualizado_por' => $data['actualizado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateAttendee(int $asistenteId, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE campana_asistentes SET contacto_id = :contacto_id, nombre_snapshot = :nombre_snapshot,
                    telefono_snapshot = :telefono_snapshot, procedencia = :procedencia, tipo_asistente = :tipo_asistente,
                    clasificacion_etaria = :clasificacion_etaria, invitado_por_contacto_id = :invitado_por_contacto_id,
                    primera_vez = :primera_vez, observaciones = :observaciones, estado_seguimiento = :estado_seguimiento,
                    actualizado_por = :actualizado_por
                WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $asistenteId, ':organizacion_id' => $organizacionId, ':contacto_id' => $data['contacto_id'],
            ':nombre_snapshot' => $data['nombre_snapshot'], ':telefono_snapshot' => $data['telefono_snapshot'],
            ':procedencia' => $data['procedencia'], ':tipo_asistente' => $data['tipo_asistente'],
            ':clasificacion_etaria' => $data['clasificacion_etaria'], ':invitado_por_contacto_id' => $data['invitado_por_contacto_id'],
            ':primera_vez' => $data['primera_vez'], ':observaciones' => $data['observaciones'],
            ':estado_seguimiento' => $data['estado_seguimiento'], ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    public function updateAttendeeFollowupStatus(int $asistenteId, int $organizacionId, string $estadoSeguimiento, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE campana_asistentes
            SET estado_seguimiento = :estado_seguimiento, actualizado_por = :actualizado_por
            WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL');
        return $stmt->execute([
            ':id' => $asistenteId,
            ':organizacion_id' => $organizacionId,
            ':estado_seguimiento' => $estadoSeguimiento,
            ':actualizado_por' => $usuarioId
        ]);
    }

    /** @param array<string, mixed> $data @return array<string, mixed>|null */
    public function upsertSessionAttendance(int $sesionId, int $organizacionId, array $data): ?array
    {
        $sql = 'INSERT INTO campana_asistencia_sesiones (
                    organizacion_id, campana_id, campana_sesion_id, campana_asistente_id, asistio,
                    hora_llegada, puntual, elegible_premio, observaciones, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :campana_id, :campana_sesion_id, :campana_asistente_id, :asistio,
                    :hora_llegada, :puntual, :elegible_premio, :observaciones, :creado_por, :actualizado_por
                ) ON DUPLICATE KEY UPDATE
                    asistio = VALUES(asistio),
                    hora_llegada = VALUES(hora_llegada),
                    puntual = VALUES(puntual),
                    elegible_premio = VALUES(elegible_premio),
                    observaciones = VALUES(observaciones),
                    actualizado_por = VALUES(actualizado_por),
                    actualizado_en = CURRENT_TIMESTAMP';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $organizacionId, ':campana_id' => $data['campana_id'], ':campana_sesion_id' => $sesionId,
            ':campana_asistente_id' => $data['campana_asistente_id'], ':asistio' => $data['asistio'], ':hora_llegada' => $data['hora_llegada'],
            ':puntual' => $data['puntual'], ':elegible_premio' => $data['elegible_premio'], ':observaciones' => $data['observaciones'],
            ':creado_por' => $data['creado_por'], ':actualizado_por' => $data['actualizado_por']
        ]);

        $stmt = $this->pdo->prepare('SELECT * FROM campana_asistencia_sesiones WHERE campana_sesion_id = :campana_sesion_id AND campana_asistente_id = :campana_asistente_id AND organizacion_id = :organizacion_id');
        $stmt->execute([':campana_sesion_id' => $sesionId, ':campana_asistente_id' => $data['campana_asistente_id'], ':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public function listDecisions(int $campanaId, int $organizacionId): array
    {
        $sql = 'SELECT cd.*, ca.nombre_snapshot FROM campana_decisiones cd
                INNER JOIN campana_asistentes ca ON ca.id = cd.campana_asistente_id
                WHERE cd.campana_id = :campana_id AND cd.organizacion_id = :organizacion_id
                ORDER BY cd.fecha_decision DESC, cd.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':campana_id' => $campanaId, ':organizacion_id' => $organizacionId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @param array<string, mixed> $data */
    public function insertDecision(array $data): int
    {
        $sql = 'INSERT INTO campana_decisiones (
                    organizacion_id, campana_id, campana_asistente_id, decision_clave, decision_etiqueta,
                    fecha_decision, observaciones, estudio_biblico_id, seguimiento_tarea_id, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :campana_id, :campana_asistente_id, :decision_clave, :decision_etiqueta,
                    :fecha_decision, :observaciones, :estudio_biblico_id, :seguimiento_tarea_id, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'], ':campana_id' => $data['campana_id'], ':campana_asistente_id' => $data['campana_asistente_id'],
            ':decision_clave' => $data['decision_clave'], ':decision_etiqueta' => $data['decision_etiqueta'],
            ':fecha_decision' => $data['fecha_decision'], ':observaciones' => $data['observaciones'],
            ':estudio_biblico_id' => $data['estudio_biblico_id'], ':seguimiento_tarea_id' => $data['seguimiento_tarea_id'],
            ':creado_por' => $data['creado_por'], ':actualizado_por' => $data['actualizado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
