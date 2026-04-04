<?php
declare(strict_types=1);

/**
 * Acceso a datos de estudios biblicos.
 */
final class EstudioBiblicoDAO
{
    private PDO $pdo;

    private const COLUMNS = 'e.id, e.organizacion_id, e.contacto_id,
        c.nombre_completo AS contacto_nombre, c.telefono AS contacto_telefono,
        e.origen_clave, e.campana_origen_id, camp.nombre AS campana_origen_nombre, e.pc_origen_id,
        e.instructor_principal_contacto_id, ip.nombre_completo AS instructor_principal_nombre,
        e.instructor_secundario_contacto_id, isec.nombre_completo AS instructor_secundario_nombre,
        e.responsable_usuario_id, u.nombre_completo AS responsable_usuario_nombre,
        e.modalidad, e.material_estudio, e.leccion_actual, e.total_lecciones_completadas,
        e.fecha_inicio, e.fecha_ultima_sesion, e.proxima_sesion, e.estado_general,
        e.observaciones, e.motivo_cierre_pausa, e.creado_por, e.actualizado_por,
        e.eliminado_por, e.creado_en, e.actualizado_en, e.eliminado_en';

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function findAllAsArray(array $filters, int $organizacionId): array
    {
        $sql = 'SELECT ' . self::COLUMNS . ',
                    COUNT(DISTINCT s.id) AS total_sesiones,
                    COUNT(DISTINCT d.id) AS total_decisiones
                FROM estudios_biblicos e
                INNER JOIN contactos_misioneros c ON c.id = e.contacto_id
                LEFT JOIN campanas camp ON camp.id = e.campana_origen_id
                LEFT JOIN contactos_misioneros ip ON ip.id = e.instructor_principal_contacto_id
                LEFT JOIN contactos_misioneros isec ON isec.id = e.instructor_secundario_contacto_id
                LEFT JOIN usuarios u ON u.id = e.responsable_usuario_id
                LEFT JOIN estudio_sesiones s ON s.estudio_id = e.id
                LEFT JOIN estudio_decisiones d ON d.estudio_id = e.id
                WHERE e.organizacion_id = :organizacion_id
                  AND e.eliminado_en IS NULL';

        $params = [':organizacion_id' => $organizacionId];

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                c.nombre_completo LIKE :q OR
                c.telefono LIKE :q OR
                e.material_estudio LIKE :q OR
                e.leccion_actual LIKE :q
            )';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['estado_general'])) {
            $sql .= ' AND e.estado_general = :estado_general';
            $params[':estado_general'] = $filters['estado_general'];
        }

        if (!empty($filters['origen_clave'])) {
            $sql .= ' AND e.origen_clave = :origen_clave';
            $params[':origen_clave'] = $filters['origen_clave'];
        }

        if (!empty($filters['responsable_usuario_id'])) {
            $sql .= ' AND e.responsable_usuario_id = :responsable_usuario_id';
            $params[':responsable_usuario_id'] = (int) $filters['responsable_usuario_id'];
        }

        if (!empty($filters['fecha_desde'])) {
            $sql .= ' AND e.fecha_inicio >= :fecha_desde';
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $sql .= ' AND e.fecha_inicio <= :fecha_hasta';
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        $sql .= ' GROUP BY e.id ORDER BY e.fecha_inicio DESC, e.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch()) {
            $item = EstudioBiblicoMapper::toArray(EstudioBiblicoMapper::fromRow($row));
            $item['total_sesiones'] = (int) ($row['total_sesiones'] ?? 0);
            $item['total_decisiones'] = (int) ($row['total_decisiones'] ?? 0);
            $items[] = $item;
        }

        return $items;
    }

    public function findById(int $id, int $organizacionId): ?EstudioBiblicoDTO
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM estudios_biblicos e
                INNER JOIN contactos_misioneros c ON c.id = e.contacto_id
                LEFT JOIN campanas camp ON camp.id = e.campana_origen_id
                LEFT JOIN contactos_misioneros ip ON ip.id = e.instructor_principal_contacto_id
                LEFT JOIN contactos_misioneros isec ON isec.id = e.instructor_secundario_contacto_id
                LEFT JOIN usuarios u ON u.id = e.responsable_usuario_id
                WHERE e.id = :id
                  AND e.organizacion_id = :organizacion_id
                  AND e.eliminado_en IS NULL';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id, ':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();
        return $row === false ? null : EstudioBiblicoMapper::fromRow($row);
    }

    public function findOpenByContactoId(int $contactoId, int $organizacionId, ?int $excludeId = null): ?EstudioBiblicoDTO
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM estudios_biblicos e
                INNER JOIN contactos_misioneros c ON c.id = e.contacto_id
                LEFT JOIN campanas camp ON camp.id = e.campana_origen_id
                LEFT JOIN contactos_misioneros ip ON ip.id = e.instructor_principal_contacto_id
                LEFT JOIN contactos_misioneros isec ON isec.id = e.instructor_secundario_contacto_id
                LEFT JOIN usuarios u ON u.id = e.responsable_usuario_id
                WHERE e.organizacion_id = :organizacion_id
                  AND e.contacto_id = :contacto_id
                  AND e.eliminado_en IS NULL
                  AND e.estado_general NOT IN (\'NO_CONTINUA\', \'BAUTIZADO\', \'CERRADO\')';

        $params = [':organizacion_id' => $organizacionId, ':contacto_id' => $contactoId];
        if ($excludeId !== null) {
            $sql .= ' AND e.id <> :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }
        $sql .= ' ORDER BY e.actualizado_en DESC LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : EstudioBiblicoMapper::fromRow($row);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $sql = 'INSERT INTO estudios_biblicos (
                    organizacion_id, contacto_id, origen_clave, campana_origen_id, pc_origen_id,
                    instructor_principal_contacto_id, instructor_secundario_contacto_id, responsable_usuario_id,
                    modalidad, material_estudio, leccion_actual, total_lecciones_completadas, fecha_inicio,
                    fecha_ultima_sesion, proxima_sesion, estado_general, observaciones, motivo_cierre_pausa,
                    creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :contacto_id, :origen_clave, :campana_origen_id, :pc_origen_id,
                    :instructor_principal_contacto_id, :instructor_secundario_contacto_id, :responsable_usuario_id,
                    :modalidad, :material_estudio, :leccion_actual, :total_lecciones_completadas, :fecha_inicio,
                    :fecha_ultima_sesion, :proxima_sesion, :estado_general, :observaciones, :motivo_cierre_pausa,
                    :creado_por, :actualizado_por
                )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':contacto_id' => $data['contacto_id'],
            ':origen_clave' => $data['origen_clave'],
            ':campana_origen_id' => $data['campana_origen_id'],
            ':pc_origen_id' => $data['pc_origen_id'],
            ':instructor_principal_contacto_id' => $data['instructor_principal_contacto_id'],
            ':instructor_secundario_contacto_id' => $data['instructor_secundario_contacto_id'],
            ':responsable_usuario_id' => $data['responsable_usuario_id'],
            ':modalidad' => $data['modalidad'],
            ':material_estudio' => $data['material_estudio'],
            ':leccion_actual' => $data['leccion_actual'],
            ':total_lecciones_completadas' => $data['total_lecciones_completadas'],
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_ultima_sesion' => $data['fecha_ultima_sesion'],
            ':proxima_sesion' => $data['proxima_sesion'],
            ':estado_general' => $data['estado_general'],
            ':observaciones' => $data['observaciones'],
            ':motivo_cierre_pausa' => $data['motivo_cierre_pausa'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE estudios_biblicos SET
                    contacto_id = :contacto_id,
                    origen_clave = :origen_clave,
                    campana_origen_id = :campana_origen_id,
                    pc_origen_id = :pc_origen_id,
                    instructor_principal_contacto_id = :instructor_principal_contacto_id,
                    instructor_secundario_contacto_id = :instructor_secundario_contacto_id,
                    responsable_usuario_id = :responsable_usuario_id,
                    modalidad = :modalidad,
                    material_estudio = :material_estudio,
                    leccion_actual = :leccion_actual,
                    total_lecciones_completadas = :total_lecciones_completadas,
                    fecha_inicio = :fecha_inicio,
                    fecha_ultima_sesion = :fecha_ultima_sesion,
                    proxima_sesion = :proxima_sesion,
                    estado_general = :estado_general,
                    observaciones = :observaciones,
                    motivo_cierre_pausa = :motivo_cierre_pausa,
                    actualizado_por = :actualizado_por
                WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId,
            ':contacto_id' => $data['contacto_id'],
            ':origen_clave' => $data['origen_clave'],
            ':campana_origen_id' => $data['campana_origen_id'],
            ':pc_origen_id' => $data['pc_origen_id'],
            ':instructor_principal_contacto_id' => $data['instructor_principal_contacto_id'],
            ':instructor_secundario_contacto_id' => $data['instructor_secundario_contacto_id'],
            ':responsable_usuario_id' => $data['responsable_usuario_id'],
            ':modalidad' => $data['modalidad'],
            ':material_estudio' => $data['material_estudio'],
            ':leccion_actual' => $data['leccion_actual'],
            ':total_lecciones_completadas' => $data['total_lecciones_completadas'],
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_ultima_sesion' => $data['fecha_ultima_sesion'],
            ':proxima_sesion' => $data['proxima_sesion'],
            ':estado_general' => $data['estado_general'],
            ':observaciones' => $data['observaciones'],
            ':motivo_cierre_pausa' => $data['motivo_cierre_pausa'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    public function softDelete(int $id, int $organizacionId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE estudios_biblicos SET eliminado_en = CURRENT_TIMESTAMP, eliminado_por = :eliminado_por WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL');
        return $stmt->execute([':id' => $id, ':organizacion_id' => $organizacionId, ':eliminado_por' => $usuarioId]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboard(array $filters, int $organizacionId): array
    {
        $where = ['e.organizacion_id = :organizacion_id', 'e.eliminado_en IS NULL'];
        $params = [':organizacion_id' => $organizacionId];

        if (!empty($filters['estado_general'])) {
            $where[] = 'e.estado_general = :estado_general';
            $params[':estado_general'] = $filters['estado_general'];
        }
        if (!empty($filters['origen_clave'])) {
            $where[] = 'e.origen_clave = :origen_clave';
            $params[':origen_clave'] = $filters['origen_clave'];
        }
        if (!empty($filters['responsable_usuario_id'])) {
            $where[] = 'e.responsable_usuario_id = :responsable_usuario_id';
            $params[':responsable_usuario_id'] = (int) $filters['responsable_usuario_id'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where[] = 'e.fecha_inicio >= :fecha_desde';
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where[] = 'e.fecha_inicio <= :fecha_hasta';
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        $sql = "SELECT
                    COUNT(DISTINCT e.id) AS total_estudios,
                    COUNT(DISTINCT CASE WHEN e.estado_general IN ('NUEVO','ASIGNADO','CONTACTADO','EN_PROCESO','LISTO_DECISION','CANDIDATO_BAUTISMAL') THEN e.id END) AS total_activos,
                    COUNT(DISTINCT CASE WHEN e.estado_general = 'PAUSADO' THEN e.id END) AS total_pausados,
                    COUNT(DISTINCT CASE WHEN e.estado_general = 'BAUTIZADO' THEN e.id END) AS total_bautizados,
                    COUNT(DISTINCT CASE WHEN e.estado_general = 'CANDIDATO_BAUTISMAL' THEN e.id END) AS total_candidatos,
                    COUNT(DISTINCT CASE WHEN e.estado_general = 'LISTO_DECISION' THEN e.id END) AS total_listos_decision,
                    COUNT(DISTINCT CASE WHEN e.estado_general = 'NO_CONTINUA' THEN e.id END) AS total_no_continua,
                    COUNT(DISTINCT d.id) AS total_decisiones
                FROM estudios_biblicos e
                LEFT JOIN estudio_decisiones d ON d.estudio_id = e.id
                WHERE " . implode(' AND ', $where);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'total_estudios' => (int) ($row['total_estudios'] ?? 0),
            'total_activos' => (int) ($row['total_activos'] ?? 0),
            'total_pausados' => (int) ($row['total_pausados'] ?? 0),
            'total_bautizados' => (int) ($row['total_bautizados'] ?? 0),
            'total_candidatos' => (int) ($row['total_candidatos'] ?? 0),
            'total_listos_decision' => (int) ($row['total_listos_decision'] ?? 0),
            'total_no_continua' => (int) ($row['total_no_continua'] ?? 0),
            'total_decisiones' => (int) ($row['total_decisiones'] ?? 0)
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSessions(int $estudioId, int $organizacionId): array
    {
        $stmt = $this->pdo->prepare('SELECT s.*, u.nombre_completo AS responsable_usuario_nombre
            FROM estudio_sesiones s
            LEFT JOIN usuarios u ON u.id = s.responsable_usuario_id
            WHERE s.estudio_id = :estudio_id AND s.organizacion_id = :organizacion_id
            ORDER BY s.fecha DESC, s.id DESC');
        $stmt->execute([':estudio_id' => $estudioId, ':organizacion_id' => $organizacionId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insertSession(array $data): int
    {
        $sql = 'INSERT INTO estudio_sesiones (
                    organizacion_id, estudio_id, fecha, tema_leccion, resumen_breve, dudas_surgidas,
                    asistencia, percepcion_avance, proxima_accion, proxima_fecha_sugerida,
                    responsable_usuario_id, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :estudio_id, :fecha, :tema_leccion, :resumen_breve, :dudas_surgidas,
                    :asistencia, :percepcion_avance, :proxima_accion, :proxima_fecha_sugerida,
                    :responsable_usuario_id, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':estudio_id' => $data['estudio_id'],
            ':fecha' => $data['fecha'],
            ':tema_leccion' => $data['tema_leccion'],
            ':resumen_breve' => $data['resumen_breve'],
            ':dudas_surgidas' => $data['dudas_surgidas'],
            ':asistencia' => $data['asistencia'],
            ':percepcion_avance' => $data['percepcion_avance'],
            ':proxima_accion' => $data['proxima_accion'],
            ':proxima_fecha_sugerida' => $data['proxima_fecha_sugerida'],
            ':responsable_usuario_id' => $data['responsable_usuario_id'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDecisions(int $estudioId, int $organizacionId): array
    {
        $stmt = $this->pdo->prepare('SELECT d.* FROM estudio_decisiones d
            WHERE d.estudio_id = :estudio_id AND d.organizacion_id = :organizacion_id
            ORDER BY d.fecha_decision DESC, d.id DESC');
        $stmt->execute([':estudio_id' => $estudioId, ':organizacion_id' => $organizacionId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insertDecision(array $data): int
    {
        $sql = 'INSERT INTO estudio_decisiones (
                    organizacion_id, estudio_id, decision_clave, decision_etiqueta, fecha_decision,
                    observaciones, requiere_seguimiento, seguimiento_tarea_id, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :estudio_id, :decision_clave, :decision_etiqueta, :fecha_decision,
                    :observaciones, :requiere_seguimiento, :seguimiento_tarea_id, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':estudio_id' => $data['estudio_id'],
            ':decision_clave' => $data['decision_clave'],
            ':decision_etiqueta' => $data['decision_etiqueta'],
            ':fecha_decision' => $data['fecha_decision'],
            ':observaciones' => $data['observaciones'],
            ':requiere_seguimiento' => $data['requiere_seguimiento'],
            ':seguimiento_tarea_id' => $data['seguimiento_tarea_id'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAssignments(int $estudioId, int $organizacionId): array
    {
        $sql = 'SELECT a.*,
                    ip.nombre_completo AS instructor_principal_nombre,
                    isec.nombre_completo AS instructor_secundario_nombre,
                    u.nombre_completo AS responsable_usuario_nombre
                FROM estudio_asignaciones a
                LEFT JOIN contactos_misioneros ip ON ip.id = a.instructor_principal_contacto_id
                LEFT JOIN contactos_misioneros isec ON isec.id = a.instructor_secundario_contacto_id
                LEFT JOIN usuarios u ON u.id = a.responsable_usuario_id
                WHERE a.estudio_id = :estudio_id AND a.organizacion_id = :organizacion_id
                ORDER BY a.fecha_asignacion DESC, a.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':estudio_id' => $estudioId, ':organizacion_id' => $organizacionId]);
        return $stmt->fetchAll() ?: [];
    }

    public function closeAssignments(int $estudioId, int $organizacionId, int $usuarioId, ?string $motivo): bool
    {
        $sql = 'UPDATE estudio_asignaciones
                SET vigente = 0,
                    fecha_fin = CURRENT_TIMESTAMP,
                    motivo_cambio = COALESCE(:motivo_cambio, motivo_cambio),
                    actualizado_por = :actualizado_por
                WHERE estudio_id = :estudio_id
                  AND organizacion_id = :organizacion_id
                  AND vigente = 1';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':motivo_cambio' => $motivo,
            ':actualizado_por' => $usuarioId,
            ':estudio_id' => $estudioId,
            ':organizacion_id' => $organizacionId
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insertAssignment(array $data): int
    {
        $sql = 'INSERT INTO estudio_asignaciones (
                    organizacion_id, estudio_id, instructor_principal_contacto_id, instructor_secundario_contacto_id,
                    responsable_usuario_id, fecha_asignacion, fecha_fin, motivo_cambio, vigente,
                    observaciones, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :estudio_id, :instructor_principal_contacto_id, :instructor_secundario_contacto_id,
                    :responsable_usuario_id, :fecha_asignacion, :fecha_fin, :motivo_cambio, :vigente,
                    :observaciones, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':estudio_id' => $data['estudio_id'],
            ':instructor_principal_contacto_id' => $data['instructor_principal_contacto_id'],
            ':instructor_secundario_contacto_id' => $data['instructor_secundario_contacto_id'],
            ':responsable_usuario_id' => $data['responsable_usuario_id'],
            ':fecha_asignacion' => $data['fecha_asignacion'],
            ':fecha_fin' => $data['fecha_fin'],
            ':motivo_cambio' => $data['motivo_cambio'],
            ':vigente' => $data['vigente'],
            ':observaciones' => $data['observaciones'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateStudyDates(int $id, int $organizacionId, ?string $fechaUltimaSesion, ?string $proximaSesion, ?string $estadoGeneral, int $usuarioId): bool
    {
        $sql = 'UPDATE estudios_biblicos
                SET fecha_ultima_sesion = :fecha_ultima_sesion,
                    proxima_sesion = :proxima_sesion,
                    estado_general = COALESCE(:estado_general, estado_general),
                    actualizado_por = :actualizado_por
                WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':fecha_ultima_sesion' => $fechaUltimaSesion,
            ':proxima_sesion' => $proximaSesion,
            ':estado_general' => $estadoGeneral,
            ':actualizado_por' => $usuarioId,
            ':id' => $id,
            ':organizacion_id' => $organizacionId
        ]);
    }
}
