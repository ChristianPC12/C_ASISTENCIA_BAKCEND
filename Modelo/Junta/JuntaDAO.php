<?php
declare(strict_types=1);

/**
 * Acceso a datos de juntas de iglesia.
 */
final class JuntaDAO
{
    private PDO $pdo;

    private const COLUMNS = 'j.id, j.organizacion_id, j.fecha, j.hora_inicio, j.hora_fin, j.tipo, j.moderador,
        j.secretario, j.estado, j.observaciones_generales, j.resumen_general, j.quorum_texto,
        j.junta_anterior_id, ja.fecha AS junta_anterior_fecha, j.creado_por, j.actualizado_por, j.eliminado_por,
        j.creado_en, j.actualizado_en, j.eliminado_en';

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
                    (
                        SELECT COUNT(*)
                        FROM junta_puntos_agenda p
                        WHERE p.junta_id = j.id
                          AND p.organizacion_id = j.organizacion_id
                          AND p.eliminado_en IS NULL
                    ) AS total_puntos,
                    (
                        SELECT COUNT(*)
                        FROM junta_puntos_agenda p
                        WHERE p.junta_id = j.id
                          AND p.organizacion_id = j.organizacion_id
                          AND p.eliminado_en IS NULL
                          AND p.estado IN ("PENDIENTE","DISCUTIDO","VOTADO","POSPUESTO","TRASLADADO")
                    ) AS total_pendientes,
                    (
                        SELECT COUNT(*)
                        FROM junta_puntos_agenda p
                        WHERE p.junta_id = j.id
                          AND p.organizacion_id = j.organizacion_id
                          AND p.eliminado_en IS NULL
                          AND p.estado = "APROBADO"
                    ) AS total_aprobados,
                    (
                        SELECT COUNT(*)
                        FROM junta_puntos_agenda p
                        WHERE p.junta_id = j.id
                          AND p.organizacion_id = j.organizacion_id
                          AND p.eliminado_en IS NULL
                          AND p.estado = "RECHAZADO"
                    ) AS total_rechazados,
                    (
                        SELECT COUNT(*)
                        FROM junta_puntos_agenda p
                        WHERE p.junta_id = j.id
                          AND p.organizacion_id = j.organizacion_id
                          AND p.eliminado_en IS NULL
                          AND p.estado = "RESUELTO_WHATSAPP"
                    ) AS total_resueltos_whatsapp,
                    (
                        SELECT COUNT(*)
                        FROM junta_puntos_agenda p
                        WHERE p.junta_id = j.id
                          AND p.organizacion_id = j.organizacion_id
                          AND p.eliminado_en IS NULL
                          AND p.estado = "TRASLADADO"
                    ) AS total_trasladados,
                    (
                        SELECT COUNT(*)
                        FROM junta_puntos_agenda p
                        WHERE p.junta_id = j.id
                          AND p.organizacion_id = j.organizacion_id
                          AND p.eliminado_en IS NULL
                          AND p.fecha_limite IS NOT NULL
                          AND p.fecha_limite < CURDATE()
                          AND p.estado IN ("PENDIENTE","DISCUTIDO","VOTADO","POSPUESTO","TRASLADADO")
                    ) AS total_vencidos
                FROM juntas_iglesia j
                LEFT JOIN juntas_iglesia ja ON ja.id = j.junta_anterior_id
                WHERE j.organizacion_id = :organizacion_id
                  AND j.eliminado_en IS NULL';

        $params = [':organizacion_id' => $organizacionId];
        $this->appendMeetingFilters($sql, $params, $filters, 'j');
        $sql .= ' ORDER BY j.fecha DESC, j.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch()) {
            $item = JuntaMapper::toArray(JuntaMapper::fromRow($row));
            $item['total_puntos'] = (int) ($row['total_puntos'] ?? 0);
            $item['total_pendientes'] = (int) ($row['total_pendientes'] ?? 0);
            $item['total_aprobados'] = (int) ($row['total_aprobados'] ?? 0);
            $item['total_rechazados'] = (int) ($row['total_rechazados'] ?? 0);
            $item['total_resueltos_whatsapp'] = (int) ($row['total_resueltos_whatsapp'] ?? 0);
            $item['total_trasladados'] = (int) ($row['total_trasladados'] ?? 0);
            $item['total_vencidos'] = (int) ($row['total_vencidos'] ?? 0);
            $items[] = $item;
        }

        return $items;
    }

    public function findById(int $id, int $organizacionId): ?JuntaDTO
    {
        $sql = 'SELECT ' . self::COLUMNS . '
                FROM juntas_iglesia j
                LEFT JOIN juntas_iglesia ja ON ja.id = j.junta_anterior_id
                WHERE j.id = :id
                  AND j.organizacion_id = :organizacion_id
                  AND j.eliminado_en IS NULL';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId
        ]);
        $row = $stmt->fetch();

        return $row === false ? null : JuntaMapper::fromRow($row);
    }

    /** @param array<string, mixed> $data */
    public function insert(array $data): int
    {
        $sql = 'INSERT INTO juntas_iglesia (
                    organizacion_id, fecha, hora_inicio, hora_fin, tipo, moderador, secretario, estado,
                    observaciones_generales, resumen_general, quorum_texto, junta_anterior_id, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :fecha, :hora_inicio, :hora_fin, :tipo, :moderador, :secretario, :estado,
                    :observaciones_generales, :resumen_general, :quorum_texto, :junta_anterior_id, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':fecha' => $data['fecha'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fin' => $data['hora_fin'],
            ':tipo' => $data['tipo'],
            ':moderador' => $data['moderador'],
            ':secretario' => $data['secretario'],
            ':estado' => $data['estado'],
            ':observaciones_generales' => $data['observaciones_generales'],
            ':resumen_general' => $data['resumen_general'],
            ':quorum_texto' => $data['quorum_texto'],
            ':junta_anterior_id' => $data['junta_anterior_id'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE juntas_iglesia SET
                    fecha = :fecha,
                    hora_inicio = :hora_inicio,
                    hora_fin = :hora_fin,
                    tipo = :tipo,
                    moderador = :moderador,
                    secretario = :secretario,
                    estado = :estado,
                    observaciones_generales = :observaciones_generales,
                    resumen_general = :resumen_general,
                    quorum_texto = :quorum_texto,
                    junta_anterior_id = :junta_anterior_id,
                    actualizado_por = :actualizado_por
                WHERE id = :id
                  AND organizacion_id = :organizacion_id
                  AND eliminado_en IS NULL';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId,
            ':fecha' => $data['fecha'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fin' => $data['hora_fin'],
            ':tipo' => $data['tipo'],
            ':moderador' => $data['moderador'],
            ':secretario' => $data['secretario'],
            ':estado' => $data['estado'],
            ':observaciones_generales' => $data['observaciones_generales'],
            ':resumen_general' => $data['resumen_general'],
            ':quorum_texto' => $data['quorum_texto'],
            ':junta_anterior_id' => $data['junta_anterior_id'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    public function softDelete(int $id, int $organizacionId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE juntas_iglesia
            SET eliminado_en = NOW(), eliminado_por = :usuario_id
            WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL');

        return $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId,
            ':usuario_id' => $usuarioId
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAgendaItems(int $juntaId, int $organizacionId): array
    {
        $sql = 'SELECT p.*, u.nombre_completo AS responsable_nombre, pa.titulo AS punto_anterior_titulo
                FROM junta_puntos_agenda p
                LEFT JOIN usuarios u ON u.id = p.responsable_seguimiento_usuario_id
                LEFT JOIN junta_puntos_agenda pa ON pa.id = p.punto_anterior_id
                WHERE p.junta_id = :junta_id
                  AND p.organizacion_id = :organizacion_id
                  AND p.eliminado_en IS NULL
                ORDER BY p.numero_orden ASC, p.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':junta_id' => $juntaId,
            ':organizacion_id' => $organizacionId
        ]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function listVotesByJunta(int $juntaId, int $organizacionId): array
    {
        $sql = 'SELECT v.*, p.junta_id, p.titulo AS punto_titulo, p.numero_orden
                FROM junta_votaciones v
                INNER JOIN junta_puntos_agenda p ON p.id = v.punto_agenda_id
                WHERE p.junta_id = :junta_id
                  AND v.organizacion_id = :organizacion_id_v
                  AND p.organizacion_id = :organizacion_id_p
                  AND p.eliminado_en IS NULL
                ORDER BY COALESCE(v.fecha_voto, v.creado_en) DESC, v.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':junta_id' => $juntaId,
            ':organizacion_id_v' => $organizacionId,
            ':organizacion_id_p' => $organizacionId
        ]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function listPendingPoints(array $filters, int $organizacionId): array
    {
        $sql = 'SELECT p.*, j.fecha AS junta_fecha, j.tipo AS junta_tipo, u.nombre_completo AS responsable_nombre
                FROM junta_puntos_agenda p
                INNER JOIN juntas_iglesia j ON j.id = p.junta_id
                LEFT JOIN usuarios u ON u.id = p.responsable_seguimiento_usuario_id
                WHERE p.organizacion_id = :organizacion_id
                  AND p.eliminado_en IS NULL
                  AND j.eliminado_en IS NULL
                  AND p.estado IN ("PENDIENTE","DISCUTIDO","VOTADO","POSPUESTO","TRASLADADO")';
        $params = [':organizacion_id' => $organizacionId];

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                p.titulo LIKE :q OR
                p.descripcion_base LIKE :q OR
                p.presentado_por LIKE :q OR
                p.departamento_origen LIKE :q
            )';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['departamento_origen'])) {
            $sql .= ' AND p.departamento_origen LIKE :departamento';
            $params[':departamento'] = '%' . $filters['departamento_origen'] . '%';
        }

        if (!empty($filters['responsable_usuario_id'])) {
            $sql .= ' AND p.responsable_seguimiento_usuario_id = :responsable_usuario_id';
            $params[':responsable_usuario_id'] = (int) $filters['responsable_usuario_id'];
        }

        if (!empty($filters['fecha_desde'])) {
            $sql .= ' AND j.fecha >= :fecha_desde';
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $sql .= ' AND j.fecha <= :fecha_hasta';
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        if (!empty($filters['excluir_junta_id'])) {
            $sql .= ' AND p.junta_id <> :excluir_junta_id';
            $params[':excluir_junta_id'] = (int) $filters['excluir_junta_id'];
        }

        $sql .= ' ORDER BY
                    CASE WHEN p.fecha_limite IS NULL THEN 1 ELSE 0 END,
                    p.fecha_limite ASC,
                    j.fecha DESC,
                    p.numero_orden ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function findPointById(int $id, int $organizacionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM junta_puntos_agenda
            WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL');
        $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId
        ]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function getNextOrder(int $juntaId, int $organizacionId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(numero_orden), 0) + 1
            FROM junta_puntos_agenda
            WHERE junta_id = :junta_id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL');
        $stmt->execute([
            ':junta_id' => $juntaId,
            ':organizacion_id' => $organizacionId
        ]);

        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    public function insertPoint(array $data): int
    {
        $sql = 'INSERT INTO junta_puntos_agenda (
                    organizacion_id, junta_id, numero_orden, titulo, departamento_origen, presentado_por, tipo_punto,
                    descripcion_base, observacion_secretaria, discusion_resumen, decision_final, estado, prioridad,
                    confidencial, responsable_seguimiento_usuario_id, fecha_limite, punto_anterior_id,
                    pasar_proxima_junta, referencia_modulo, referencia_entidad_id, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :junta_id, :numero_orden, :titulo, :departamento_origen, :presentado_por, :tipo_punto,
                    :descripcion_base, :observacion_secretaria, :discusion_resumen, :decision_final, :estado, :prioridad,
                    :confidencial, :responsable_seguimiento_usuario_id, :fecha_limite, :punto_anterior_id,
                    :pasar_proxima_junta, :referencia_modulo, :referencia_entidad_id, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':junta_id' => $data['junta_id'],
            ':numero_orden' => $data['numero_orden'],
            ':titulo' => $data['titulo'],
            ':departamento_origen' => $data['departamento_origen'],
            ':presentado_por' => $data['presentado_por'],
            ':tipo_punto' => $data['tipo_punto'],
            ':descripcion_base' => $data['descripcion_base'],
            ':observacion_secretaria' => $data['observacion_secretaria'],
            ':discusion_resumen' => $data['discusion_resumen'],
            ':decision_final' => $data['decision_final'],
            ':estado' => $data['estado'],
            ':prioridad' => $data['prioridad'],
            ':confidencial' => $data['confidencial'],
            ':responsable_seguimiento_usuario_id' => $data['responsable_seguimiento_usuario_id'],
            ':fecha_limite' => $data['fecha_limite'],
            ':punto_anterior_id' => $data['punto_anterior_id'],
            ':pasar_proxima_junta' => $data['pasar_proxima_junta'],
            ':referencia_modulo' => $data['referencia_modulo'],
            ':referencia_entidad_id' => $data['referencia_entidad_id'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updatePoint(int $id, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE junta_puntos_agenda SET
                    numero_orden = :numero_orden,
                    titulo = :titulo,
                    departamento_origen = :departamento_origen,
                    presentado_por = :presentado_por,
                    tipo_punto = :tipo_punto,
                    descripcion_base = :descripcion_base,
                    observacion_secretaria = :observacion_secretaria,
                    discusion_resumen = :discusion_resumen,
                    decision_final = :decision_final,
                    estado = :estado,
                    prioridad = :prioridad,
                    confidencial = :confidencial,
                    responsable_seguimiento_usuario_id = :responsable_seguimiento_usuario_id,
                    fecha_limite = :fecha_limite,
                    punto_anterior_id = :punto_anterior_id,
                    pasar_proxima_junta = :pasar_proxima_junta,
                    referencia_modulo = :referencia_modulo,
                    referencia_entidad_id = :referencia_entidad_id,
                    actualizado_por = :actualizado_por
                WHERE id = :id
                  AND organizacion_id = :organizacion_id
                  AND eliminado_en IS NULL';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId,
            ':numero_orden' => $data['numero_orden'],
            ':titulo' => $data['titulo'],
            ':departamento_origen' => $data['departamento_origen'],
            ':presentado_por' => $data['presentado_por'],
            ':tipo_punto' => $data['tipo_punto'],
            ':descripcion_base' => $data['descripcion_base'],
            ':observacion_secretaria' => $data['observacion_secretaria'],
            ':discusion_resumen' => $data['discusion_resumen'],
            ':decision_final' => $data['decision_final'],
            ':estado' => $data['estado'],
            ':prioridad' => $data['prioridad'],
            ':confidencial' => $data['confidencial'],
            ':responsable_seguimiento_usuario_id' => $data['responsable_seguimiento_usuario_id'],
            ':fecha_limite' => $data['fecha_limite'],
            ':punto_anterior_id' => $data['punto_anterior_id'],
            ':pasar_proxima_junta' => $data['pasar_proxima_junta'],
            ':referencia_modulo' => $data['referencia_modulo'],
            ':referencia_entidad_id' => $data['referencia_entidad_id'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    public function findVoteById(int $id, int $organizacionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM junta_votaciones
            WHERE id = :id AND organizacion_id = :organizacion_id');
        $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId
        ]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $data */
    public function insertVote(array $data): int
    {
        $sql = 'INSERT INTO junta_votaciones (
                    organizacion_id, punto_agenda_id, requirio_voto, tipo_voto, texto_voto, votos_favor,
                    votos_contra, abstenciones, fecha_voto, observacion, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :punto_agenda_id, :requirio_voto, :tipo_voto, :texto_voto, :votos_favor,
                    :votos_contra, :abstenciones, :fecha_voto, :observacion, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':punto_agenda_id' => $data['punto_agenda_id'],
            ':requirio_voto' => $data['requirio_voto'],
            ':tipo_voto' => $data['tipo_voto'],
            ':texto_voto' => $data['texto_voto'],
            ':votos_favor' => $data['votos_favor'],
            ':votos_contra' => $data['votos_contra'],
            ':abstenciones' => $data['abstenciones'],
            ':fecha_voto' => $data['fecha_voto'],
            ':observacion' => $data['observacion'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateVote(int $id, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE junta_votaciones SET
                    requirio_voto = :requirio_voto,
                    tipo_voto = :tipo_voto,
                    texto_voto = :texto_voto,
                    votos_favor = :votos_favor,
                    votos_contra = :votos_contra,
                    abstenciones = :abstenciones,
                    fecha_voto = :fecha_voto,
                    observacion = :observacion,
                    actualizado_por = :actualizado_por
                WHERE id = :id
                  AND organizacion_id = :organizacion_id';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId,
            ':requirio_voto' => $data['requirio_voto'],
            ':tipo_voto' => $data['tipo_voto'],
            ':texto_voto' => $data['texto_voto'],
            ':votos_favor' => $data['votos_favor'],
            ':votos_contra' => $data['votos_contra'],
            ':abstenciones' => $data['abstenciones'],
            ':fecha_voto' => $data['fecha_voto'],
            ':observacion' => $data['observacion'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    /** @param array<string, mixed> $filters */
    public function getDashboard(array $filters, int $organizacionId): array
    {
        $items = $this->findAllAsArray($filters, $organizacionId);
        $dashboard = [
            'total_juntas' => count($items),
            'total_puntos_tratados' => 0,
            'total_puntos_pendientes' => 0,
            'total_puntos_aprobados' => 0,
            'total_puntos_rechazados' => 0,
            'total_puntos_resueltos_whatsapp' => 0,
            'total_puntos_trasladados' => 0,
            'total_puntos_vencidos' => 0
        ];

        foreach ($items as $item) {
            $dashboard['total_puntos_tratados'] += (int) ($item['total_puntos'] ?? 0);
            $dashboard['total_puntos_pendientes'] += (int) ($item['total_pendientes'] ?? 0);
            $dashboard['total_puntos_aprobados'] += (int) ($item['total_aprobados'] ?? 0);
            $dashboard['total_puntos_rechazados'] += (int) ($item['total_rechazados'] ?? 0);
            $dashboard['total_puntos_resueltos_whatsapp'] += (int) ($item['total_resueltos_whatsapp'] ?? 0);
            $dashboard['total_puntos_trasladados'] += (int) ($item['total_trasladados'] ?? 0);
            $dashboard['total_puntos_vencidos'] += (int) ($item['total_vencidos'] ?? 0);
        }

        return $dashboard;
    }

    /**
     * @param string $alias
     * @param array<string, mixed> $params
     * @param array<string, mixed> $filters
     */
    private function appendMeetingFilters(string &$sql, array &$params, array $filters, string $alias): void
    {
        if (!empty($filters['q'])) {
            $sql .= " AND (
                {$alias}.moderador LIKE :q OR
                {$alias}.secretario LIKE :q OR
                {$alias}.resumen_general LIKE :q OR
                {$alias}.observaciones_generales LIKE :q OR
                EXISTS (
                    SELECT 1
                    FROM junta_puntos_agenda qp
                    WHERE qp.junta_id = {$alias}.id
                      AND qp.organizacion_id = {$alias}.organizacion_id
                      AND qp.eliminado_en IS NULL
                      AND (
                        qp.titulo LIKE :q OR
                        qp.descripcion_base LIKE :q OR
                        qp.presentado_por LIKE :q OR
                        qp.departamento_origen LIKE :q
                      )
                )
            )";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['estado'])) {
            $sql .= " AND {$alias}.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }

        if (!empty($filters['tipo'])) {
            $sql .= " AND {$alias}.tipo = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }

        if (!empty($filters['fecha_desde'])) {
            $sql .= " AND {$alias}.fecha >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $sql .= " AND {$alias}.fecha <= :fecha_hasta";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        if (!empty($filters['departamento_origen'])) {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM junta_puntos_agenda dp
                WHERE dp.junta_id = {$alias}.id
                  AND dp.organizacion_id = {$alias}.organizacion_id
                  AND dp.eliminado_en IS NULL
                  AND dp.departamento_origen LIKE :departamento_origen
            )";
            $params[':departamento_origen'] = '%' . $filters['departamento_origen'] . '%';
        }

        if (!empty($filters['responsable_usuario_id'])) {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM junta_puntos_agenda rp
                WHERE rp.junta_id = {$alias}.id
                  AND rp.organizacion_id = {$alias}.organizacion_id
                  AND rp.eliminado_en IS NULL
                  AND rp.responsable_seguimiento_usuario_id = :responsable_usuario_id
            )";
            $params[':responsable_usuario_id'] = (int) $filters['responsable_usuario_id'];
        }
    }
}
