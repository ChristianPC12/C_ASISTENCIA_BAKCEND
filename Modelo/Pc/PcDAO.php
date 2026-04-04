<?php
declare(strict_types=1);

/**
 * Acceso a datos de pequenas congregaciones.
 */
final class PcDAO
{
    private PDO $pdo;

    private const COLUMNS = 'p.id, p.organizacion_id, p.nombre_pc, p.sector, p.comunidad, p.direccion_reunion,
        p.anfitrion_contacto_id, anfit.nombre_completo AS anfitrion_nombre,
        p.lider_principal_contacto_id, lp.nombre_completo AS lider_principal_nombre,
        p.lider_auxiliar_contacto_id, laux.nombre_completo AS lider_auxiliar_nombre,
        p.fecha_inicio, p.fecha_fin, p.dia_reunion, p.hora_reunion, p.estado,
        p.pc_madre_id, pm.nombre_pc AS pc_madre_nombre,
        p.motivo_cierre, p.meta_trimestral, p.observaciones_generales,
        p.creado_por, p.actualizado_por, p.eliminado_por, p.creado_en, p.actualizado_en, p.eliminado_en';

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
                        FROM pc_participantes pp
                        WHERE pp.pc_id = p.id
                          AND pp.organizacion_id = p.organizacion_id
                          AND pp.eliminado_en IS NULL
                          AND pp.estado_participacion = "ACTIVO"
                    ) AS total_participantes_activos,
                    (
                        SELECT COUNT(*)
                        FROM pc_reuniones pr
                        WHERE pr.pc_id = p.id
                          AND pr.organizacion_id = p.organizacion_id
                    ) AS total_reuniones,
                    (
                        SELECT MAX(pr.fecha)
                        FROM pc_reuniones pr
                        WHERE pr.pc_id = p.id
                          AND pr.organizacion_id = p.organizacion_id
                    ) AS ultima_reunion,
                    (
                        SELECT COALESCE(SUM(CASE WHEN pres.tipo_resultado = "ESTUDIO_BIBLICO_GENERADO" THEN pres.cantidad ELSE 0 END), 0)
                        FROM pc_resultados pres
                        WHERE pres.pc_id = p.id
                          AND pres.organizacion_id = p.organizacion_id
                    ) AS total_estudios_originados,
                    (
                        SELECT COALESCE(SUM(CASE WHEN pres.tipo_resultado = "DECISION_ESPIRITUAL" THEN pres.cantidad ELSE 0 END), 0)
                        FROM pc_resultados pres
                        WHERE pres.pc_id = p.id
                          AND pres.organizacion_id = p.organizacion_id
                    ) AS total_decisiones,
                    (
                        SELECT COALESCE(SUM(CASE WHEN pres.tipo_resultado = "BAUTISMO_RELACIONADO" THEN pres.cantidad ELSE 0 END), 0)
                        FROM pc_resultados pres
                        WHERE pres.pc_id = p.id
                          AND pres.organizacion_id = p.organizacion_id
                    ) AS total_bautismos
                FROM pc_grupos p
                LEFT JOIN contactos_misioneros anfit ON anfit.id = p.anfitrion_contacto_id
                LEFT JOIN contactos_misioneros lp ON lp.id = p.lider_principal_contacto_id
                LEFT JOIN contactos_misioneros laux ON laux.id = p.lider_auxiliar_contacto_id
                LEFT JOIN pc_grupos pm ON pm.id = p.pc_madre_id
                WHERE p.organizacion_id = :organizacion_id
                  AND p.eliminado_en IS NULL';

        $params = [':organizacion_id' => $organizacionId];

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                p.nombre_pc LIKE :q OR
                p.sector LIKE :q OR
                p.comunidad LIKE :q OR
                anfit.nombre_completo LIKE :q OR
                lp.nombre_completo LIKE :q OR
                laux.nombre_completo LIKE :q
            )';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['estado'])) {
            $sql .= ' AND p.estado = :estado';
            $params[':estado'] = $filters['estado'];
        }

        if (!empty($filters['sector'])) {
            $sql .= ' AND (p.sector LIKE :sector OR p.comunidad LIKE :sector)';
            $params[':sector'] = '%' . $filters['sector'] . '%';
        }

        if (!empty($filters['fecha_desde'])) {
            $sql .= ' AND COALESCE(p.fecha_fin, p.fecha_inicio) >= :fecha_desde';
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $sql .= ' AND p.fecha_inicio <= :fecha_hasta';
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        if (!empty($filters['sin_reunion_dias'])) {
            $sql .= ' AND COALESCE(DATEDIFF(CURDATE(), (
                SELECT MAX(pr2.fecha)
                FROM pc_reuniones pr2
                WHERE pr2.pc_id = p.id
                  AND pr2.organizacion_id = p.organizacion_id
            )), 99999) >= :sin_reunion_dias';
            $params[':sin_reunion_dias'] = (int) $filters['sin_reunion_dias'];
        }

        $sql .= ' ORDER BY p.fecha_inicio DESC, p.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch()) {
            $item = PcMapper::toArray(PcMapper::fromRow($row));
            $item['total_participantes_activos'] = (int) ($row['total_participantes_activos'] ?? 0);
            $item['total_reuniones'] = (int) ($row['total_reuniones'] ?? 0);
            $item['ultima_reunion'] = $row['ultima_reunion'] ?? null;
            $item['total_estudios_originados'] = (int) ($row['total_estudios_originados'] ?? 0);
            $item['total_decisiones'] = (int) ($row['total_decisiones'] ?? 0);
            $item['total_bautismos'] = (int) ($row['total_bautismos'] ?? 0);
            $items[] = $item;
        }

        return $items;
    }

    public function findById(int $id, int $organizacionId): ?PcDTO
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM pc_grupos p
                LEFT JOIN contactos_misioneros anfit ON anfit.id = p.anfitrion_contacto_id
                LEFT JOIN contactos_misioneros lp ON lp.id = p.lider_principal_contacto_id
                LEFT JOIN contactos_misioneros laux ON laux.id = p.lider_auxiliar_contacto_id
                LEFT JOIN pc_grupos pm ON pm.id = p.pc_madre_id
                WHERE p.id = :id
                  AND p.organizacion_id = :organizacion_id
                  AND p.eliminado_en IS NULL';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id, ':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();
        return $row === false ? null : PcMapper::fromRow($row);
    }

    /** @param array<string, mixed> $data */
    public function insert(array $data): int
    {
        $sql = 'INSERT INTO pc_grupos (
                    organizacion_id, nombre_pc, sector, comunidad, direccion_reunion, anfitrion_contacto_id,
                    lider_principal_contacto_id, lider_auxiliar_contacto_id, fecha_inicio, fecha_fin,
                    dia_reunion, hora_reunion, estado, pc_madre_id, motivo_cierre, meta_trimestral,
                    observaciones_generales, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :nombre_pc, :sector, :comunidad, :direccion_reunion, :anfitrion_contacto_id,
                    :lider_principal_contacto_id, :lider_auxiliar_contacto_id, :fecha_inicio, :fecha_fin,
                    :dia_reunion, :hora_reunion, :estado, :pc_madre_id, :motivo_cierre, :meta_trimestral,
                    :observaciones_generales, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':nombre_pc' => $data['nombre_pc'],
            ':sector' => $data['sector'],
            ':comunidad' => $data['comunidad'],
            ':direccion_reunion' => $data['direccion_reunion'],
            ':anfitrion_contacto_id' => $data['anfitrion_contacto_id'],
            ':lider_principal_contacto_id' => $data['lider_principal_contacto_id'],
            ':lider_auxiliar_contacto_id' => $data['lider_auxiliar_contacto_id'],
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin' => $data['fecha_fin'],
            ':dia_reunion' => $data['dia_reunion'],
            ':hora_reunion' => $data['hora_reunion'],
            ':estado' => $data['estado'],
            ':pc_madre_id' => $data['pc_madre_id'],
            ':motivo_cierre' => $data['motivo_cierre'],
            ':meta_trimestral' => $data['meta_trimestral'],
            ':observaciones_generales' => $data['observaciones_generales'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE pc_grupos SET
                    nombre_pc = :nombre_pc,
                    sector = :sector,
                    comunidad = :comunidad,
                    direccion_reunion = :direccion_reunion,
                    anfitrion_contacto_id = :anfitrion_contacto_id,
                    lider_principal_contacto_id = :lider_principal_contacto_id,
                    lider_auxiliar_contacto_id = :lider_auxiliar_contacto_id,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    dia_reunion = :dia_reunion,
                    hora_reunion = :hora_reunion,
                    estado = :estado,
                    pc_madre_id = :pc_madre_id,
                    motivo_cierre = :motivo_cierre,
                    meta_trimestral = :meta_trimestral,
                    observaciones_generales = :observaciones_generales,
                    actualizado_por = :actualizado_por
                WHERE id = :id
                  AND organizacion_id = :organizacion_id
                  AND eliminado_en IS NULL';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId,
            ':nombre_pc' => $data['nombre_pc'],
            ':sector' => $data['sector'],
            ':comunidad' => $data['comunidad'],
            ':direccion_reunion' => $data['direccion_reunion'],
            ':anfitrion_contacto_id' => $data['anfitrion_contacto_id'],
            ':lider_principal_contacto_id' => $data['lider_principal_contacto_id'],
            ':lider_auxiliar_contacto_id' => $data['lider_auxiliar_contacto_id'],
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin' => $data['fecha_fin'],
            ':dia_reunion' => $data['dia_reunion'],
            ':hora_reunion' => $data['hora_reunion'],
            ':estado' => $data['estado'],
            ':pc_madre_id' => $data['pc_madre_id'],
            ':motivo_cierre' => $data['motivo_cierre'],
            ':meta_trimestral' => $data['meta_trimestral'],
            ':observaciones_generales' => $data['observaciones_generales'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    public function softDelete(int $id, int $organizacionId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE pc_grupos SET eliminado_en = CURRENT_TIMESTAMP, eliminado_por = :eliminado_por WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL');
        return $stmt->execute([':id' => $id, ':organizacion_id' => $organizacionId, ':eliminado_por' => $usuarioId]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getDashboard(array $filters, int $organizacionId): array
    {
        $items = $this->findAllAsArray($filters, $organizacionId);
        $totalActivas = 0;
        $totalInactivas = 0;
        $totalParticipantes = 0;
        $totalEstudios = 0;
        $totalDecisiones = 0;
        $totalBautismos = 0;
        $totalMultiplicadas = 0;
        $sinMovimiento = 0;

        foreach ($items as $item) {
            if (($item['estado'] ?? '') === 'ACTIVA') {
                $totalActivas++;
            }
            if (in_array((string) ($item['estado'] ?? ''), ['INACTIVA', 'PAUSADA', 'CERRADA'], true)) {
                $totalInactivas++;
            }
            if (($item['estado'] ?? '') === 'MULTIPLICADA') {
                $totalMultiplicadas++;
            }
            $totalParticipantes += (int) ($item['total_participantes_activos'] ?? 0);
            $totalEstudios += (int) ($item['total_estudios_originados'] ?? 0);
            $totalDecisiones += (int) ($item['total_decisiones'] ?? 0);
            $totalBautismos += (int) ($item['total_bautismos'] ?? 0);
            $ultimaReunion = isset($item['ultima_reunion']) && $item['ultima_reunion'] !== null ? strtotime((string) $item['ultima_reunion']) : false;
            if ($ultimaReunion === false || ((time() - $ultimaReunion) / 86400) >= 21) {
                $sinMovimiento++;
            }
        }

        return [
            'total_pc_activas' => $totalActivas,
            'total_pc_inactivas' => $totalInactivas,
            'total_participantes_activos' => $totalParticipantes,
            'total_reuniones' => $this->countMeetingsByFilters($filters, $organizacionId),
            'total_visitas_periodo' => $this->sumVisitsByFilters($filters, $organizacionId),
            'total_estudios_originados' => $totalEstudios,
            'total_decisiones' => $totalDecisiones,
            'total_bautismos' => $totalBautismos,
            'total_pc_multiplicadas' => $totalMultiplicadas,
            'pc_sin_movimiento' => $sinMovimiento
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function listParticipants(int $pcId, int $organizacionId): array
    {
        $sql = 'SELECT pp.*, cm.nombre_completo AS contacto_nombre, cm.telefono AS contacto_telefono
                FROM pc_participantes pp
                INNER JOIN contactos_misioneros cm ON cm.id = pp.contacto_id
                WHERE pp.pc_id = :pc_id
                  AND pp.organizacion_id = :organizacion_id
                  AND pp.eliminado_en IS NULL
                ORDER BY pp.estado_participacion ASC, cm.nombre_completo ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pc_id' => $pcId, ':organizacion_id' => $organizacionId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed>|null */
    public function findParticipantById(int $participanteId, int $organizacionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pc_participantes WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL');
        $stmt->execute([':id' => $participanteId, ':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $data */
    public function insertParticipant(array $data): int
    {
        $sql = 'INSERT INTO pc_participantes (
                    organizacion_id, pc_id, contacto_id, clasificacion, rol_pc, es_miembro,
                    fecha_ingreso, fecha_salida, motivo_salida, estado_participacion, observaciones,
                    creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :pc_id, :contacto_id, :clasificacion, :rol_pc, :es_miembro,
                    :fecha_ingreso, :fecha_salida, :motivo_salida, :estado_participacion, :observaciones,
                    :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':pc_id' => $data['pc_id'],
            ':contacto_id' => $data['contacto_id'],
            ':clasificacion' => $data['clasificacion'],
            ':rol_pc' => $data['rol_pc'],
            ':es_miembro' => $data['es_miembro'],
            ':fecha_ingreso' => $data['fecha_ingreso'],
            ':fecha_salida' => $data['fecha_salida'],
            ':motivo_salida' => $data['motivo_salida'],
            ':estado_participacion' => $data['estado_participacion'],
            ':observaciones' => $data['observaciones'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateParticipant(int $participanteId, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE pc_participantes SET
                    contacto_id = :contacto_id,
                    clasificacion = :clasificacion,
                    rol_pc = :rol_pc,
                    es_miembro = :es_miembro,
                    fecha_ingreso = :fecha_ingreso,
                    fecha_salida = :fecha_salida,
                    motivo_salida = :motivo_salida,
                    estado_participacion = :estado_participacion,
                    observaciones = :observaciones,
                    actualizado_por = :actualizado_por
                WHERE id = :id
                  AND organizacion_id = :organizacion_id
                  AND eliminado_en IS NULL';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $participanteId,
            ':organizacion_id' => $organizacionId,
            ':contacto_id' => $data['contacto_id'],
            ':clasificacion' => $data['clasificacion'],
            ':rol_pc' => $data['rol_pc'],
            ':es_miembro' => $data['es_miembro'],
            ':fecha_ingreso' => $data['fecha_ingreso'],
            ':fecha_salida' => $data['fecha_salida'],
            ':motivo_salida' => $data['motivo_salida'],
            ':estado_participacion' => $data['estado_participacion'],
            ':observaciones' => $data['observaciones'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listMeetings(int $pcId, int $organizacionId): array
    {
        $sql = 'SELECT pr.*, u.nombre_completo AS responsable_usuario_nombre,
                    COUNT(DISTINCT prp.id) AS total_registros_individuales
                FROM pc_reuniones pr
                LEFT JOIN usuarios u ON u.id = pr.responsable_seguimiento_usuario_id
                LEFT JOIN pc_reunion_participantes prp ON prp.reunion_id = pr.id
                WHERE pr.pc_id = :pc_id
                  AND pr.organizacion_id = :organizacion_id
                GROUP BY pr.id
                ORDER BY pr.fecha DESC, pr.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pc_id' => $pcId, ':organizacion_id' => $organizacionId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed>|null */
    public function findMeetingById(int $meetingId, int $organizacionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pc_reuniones WHERE id = :id AND organizacion_id = :organizacion_id');
        $stmt->execute([':id' => $meetingId, ':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $data */
    public function insertMeeting(array $data): int
    {
        $sql = 'INSERT INTO pc_reuniones (
                    organizacion_id, pc_id, fecha, tema_titulo, material_usado, hubo_estudio_biblico,
                    hubo_visita, cantidad_asistentes, total_miembros, total_visitas, total_ninos,
                    total_jovenes, total_adultos, observacion_reunion, decisiones_tomadas,
                    proximos_pasos, responsable_seguimiento_usuario_id, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :pc_id, :fecha, :tema_titulo, :material_usado, :hubo_estudio_biblico,
                    :hubo_visita, :cantidad_asistentes, :total_miembros, :total_visitas, :total_ninos,
                    :total_jovenes, :total_adultos, :observacion_reunion, :decisiones_tomadas,
                    :proximos_pasos, :responsable_seguimiento_usuario_id, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':pc_id' => $data['pc_id'],
            ':fecha' => $data['fecha'],
            ':tema_titulo' => $data['tema_titulo'],
            ':material_usado' => $data['material_usado'],
            ':hubo_estudio_biblico' => $data['hubo_estudio_biblico'],
            ':hubo_visita' => $data['hubo_visita'],
            ':cantidad_asistentes' => $data['cantidad_asistentes'],
            ':total_miembros' => $data['total_miembros'],
            ':total_visitas' => $data['total_visitas'],
            ':total_ninos' => $data['total_ninos'],
            ':total_jovenes' => $data['total_jovenes'],
            ':total_adultos' => $data['total_adultos'],
            ':observacion_reunion' => $data['observacion_reunion'],
            ':decisiones_tomadas' => $data['decisiones_tomadas'],
            ':proximos_pasos' => $data['proximos_pasos'],
            ':responsable_seguimiento_usuario_id' => $data['responsable_seguimiento_usuario_id'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateMeeting(int $meetingId, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE pc_reuniones SET
                    fecha = :fecha,
                    tema_titulo = :tema_titulo,
                    material_usado = :material_usado,
                    hubo_estudio_biblico = :hubo_estudio_biblico,
                    hubo_visita = :hubo_visita,
                    cantidad_asistentes = :cantidad_asistentes,
                    total_miembros = :total_miembros,
                    total_visitas = :total_visitas,
                    total_ninos = :total_ninos,
                    total_jovenes = :total_jovenes,
                    total_adultos = :total_adultos,
                    observacion_reunion = :observacion_reunion,
                    decisiones_tomadas = :decisiones_tomadas,
                    proximos_pasos = :proximos_pasos,
                    responsable_seguimiento_usuario_id = :responsable_seguimiento_usuario_id,
                    actualizado_por = :actualizado_por
                WHERE id = :id
                  AND organizacion_id = :organizacion_id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $meetingId,
            ':organizacion_id' => $organizacionId,
            ':fecha' => $data['fecha'],
            ':tema_titulo' => $data['tema_titulo'],
            ':material_usado' => $data['material_usado'],
            ':hubo_estudio_biblico' => $data['hubo_estudio_biblico'],
            ':hubo_visita' => $data['hubo_visita'],
            ':cantidad_asistentes' => $data['cantidad_asistentes'],
            ':total_miembros' => $data['total_miembros'],
            ':total_visitas' => $data['total_visitas'],
            ':total_ninos' => $data['total_ninos'],
            ':total_jovenes' => $data['total_jovenes'],
            ':total_adultos' => $data['total_adultos'],
            ':observacion_reunion' => $data['observacion_reunion'],
            ':decisiones_tomadas' => $data['decisiones_tomadas'],
            ':proximos_pasos' => $data['proximos_pasos'],
            ':responsable_seguimiento_usuario_id' => $data['responsable_seguimiento_usuario_id'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    /** @param array<string, mixed> $data @return array<string, mixed>|null */
    public function upsertMeetingAttendance(int $meetingId, int $organizacionId, array $data): ?array
    {
        $sql = 'INSERT INTO pc_reunion_participantes (
                    organizacion_id, reunion_id, contacto_id, asistio, clasificacion_dia, observaciones,
                    creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :reunion_id, :contacto_id, :asistio, :clasificacion_dia, :observaciones,
                    :creado_por, :actualizado_por
                ) ON DUPLICATE KEY UPDATE
                    asistio = VALUES(asistio),
                    clasificacion_dia = VALUES(clasificacion_dia),
                    observaciones = VALUES(observaciones),
                    actualizado_por = VALUES(actualizado_por),
                    actualizado_en = CURRENT_TIMESTAMP';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $organizacionId,
            ':reunion_id' => $meetingId,
            ':contacto_id' => $data['contacto_id'],
            ':asistio' => $data['asistio'],
            ':clasificacion_dia' => $data['clasificacion_dia'],
            ':observaciones' => $data['observaciones'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);

        $stmt = $this->pdo->prepare('SELECT prp.*, cm.nombre_completo AS contacto_nombre
            FROM pc_reunion_participantes prp
            INNER JOIN contactos_misioneros cm ON cm.id = prp.contacto_id
            WHERE prp.reunion_id = :reunion_id
              AND prp.contacto_id = :contacto_id
              AND prp.organizacion_id = :organizacion_id');
        $stmt->execute([
            ':reunion_id' => $meetingId,
            ':contacto_id' => $data['contacto_id'],
            ':organizacion_id' => $organizacionId
        ]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public function listMeetingAttendance(int $meetingId, int $organizacionId): array
    {
        $sql = 'SELECT prp.*, cm.nombre_completo AS contacto_nombre, cm.telefono AS contacto_telefono
                FROM pc_reunion_participantes prp
                INNER JOIN contactos_misioneros cm ON cm.id = prp.contacto_id
                WHERE prp.reunion_id = :reunion_id
                  AND prp.organizacion_id = :organizacion_id
                ORDER BY cm.nombre_completo ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':reunion_id' => $meetingId, ':organizacion_id' => $organizacionId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function listOutcomes(int $pcId, int $organizacionId): array
    {
        $sql = 'SELECT pr.*, cm.nombre_completo AS contacto_nombre, eb.material_estudio, eb.estado_general AS estudio_estado
                FROM pc_resultados pr
                LEFT JOIN contactos_misioneros cm ON cm.id = pr.contacto_id
                LEFT JOIN estudios_biblicos eb ON eb.id = pr.estudio_biblico_id
                WHERE pr.pc_id = :pc_id
                  AND pr.organizacion_id = :organizacion_id
                ORDER BY pr.fecha DESC, pr.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pc_id' => $pcId, ':organizacion_id' => $organizacionId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed>|null */
    public function findOutcomeById(int $outcomeId, int $organizacionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pc_resultados WHERE id = :id AND organizacion_id = :organizacion_id');
        $stmt->execute([':id' => $outcomeId, ':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $data */
    public function insertOutcome(array $data): int
    {
        $sql = 'INSERT INTO pc_resultados (
                    organizacion_id, pc_id, fecha, tipo_resultado, contacto_id, estudio_biblico_id,
                    cantidad, descripcion, observaciones, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :pc_id, :fecha, :tipo_resultado, :contacto_id, :estudio_biblico_id,
                    :cantidad, :descripcion, :observaciones, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':pc_id' => $data['pc_id'],
            ':fecha' => $data['fecha'],
            ':tipo_resultado' => $data['tipo_resultado'],
            ':contacto_id' => $data['contacto_id'],
            ':estudio_biblico_id' => $data['estudio_biblico_id'],
            ':cantidad' => $data['cantidad'],
            ':descripcion' => $data['descripcion'],
            ':observaciones' => $data['observaciones'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateOutcome(int $outcomeId, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE pc_resultados SET
                    fecha = :fecha,
                    tipo_resultado = :tipo_resultado,
                    contacto_id = :contacto_id,
                    estudio_biblico_id = :estudio_biblico_id,
                    cantidad = :cantidad,
                    descripcion = :descripcion,
                    observaciones = :observaciones,
                    actualizado_por = :actualizado_por
                WHERE id = :id
                  AND organizacion_id = :organizacion_id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $outcomeId,
            ':organizacion_id' => $organizacionId,
            ':fecha' => $data['fecha'],
            ':tipo_resultado' => $data['tipo_resultado'],
            ':contacto_id' => $data['contacto_id'],
            ':estudio_biblico_id' => $data['estudio_biblico_id'],
            ':cantidad' => $data['cantidad'],
            ':descripcion' => $data['descripcion'],
            ':observaciones' => $data['observaciones'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listLeadershipHistory(int $pcId, int $organizacionId): array
    {
        $sql = 'SELECT plh.*, cm.nombre_completo AS contacto_nombre, cm.telefono AS contacto_telefono
                FROM pc_lideres_historial plh
                INNER JOIN contactos_misioneros cm ON cm.id = plh.contacto_id
                WHERE plh.pc_id = :pc_id
                  AND plh.organizacion_id = :organizacion_id
                ORDER BY plh.fecha_inicio DESC, plh.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pc_id' => $pcId, ':organizacion_id' => $organizacionId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed>|null */
    public function findLeadershipById(int $leadershipId, int $organizacionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pc_lideres_historial WHERE id = :id AND organizacion_id = :organizacion_id');
        $stmt->execute([':id' => $leadershipId, ':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $data */
    public function insertLeadership(array $data): int
    {
        $sql = 'INSERT INTO pc_lideres_historial (
                    organizacion_id, pc_id, contacto_id, rol_liderazgo, fecha_inicio, fecha_fin,
                    motivo_cambio, observaciones, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :pc_id, :contacto_id, :rol_liderazgo, :fecha_inicio, :fecha_fin,
                    :motivo_cambio, :observaciones, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':pc_id' => $data['pc_id'],
            ':contacto_id' => $data['contacto_id'],
            ':rol_liderazgo' => $data['rol_liderazgo'],
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin' => $data['fecha_fin'],
            ':motivo_cambio' => $data['motivo_cambio'],
            ':observaciones' => $data['observaciones'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateLeadership(int $leadershipId, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE pc_lideres_historial SET
                    contacto_id = :contacto_id,
                    rol_liderazgo = :rol_liderazgo,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    motivo_cambio = :motivo_cambio,
                    observaciones = :observaciones,
                    actualizado_por = :actualizado_por
                WHERE id = :id
                  AND organizacion_id = :organizacion_id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $leadershipId,
            ':organizacion_id' => $organizacionId,
            ':contacto_id' => $data['contacto_id'],
            ':rol_liderazgo' => $data['rol_liderazgo'],
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin' => $data['fecha_fin'],
            ':motivo_cambio' => $data['motivo_cambio'],
            ':observaciones' => $data['observaciones'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    public function closeLeadershipByRole(int $pcId, int $organizacionId, string $rol, int $usuarioId, ?string $motivo = null): bool
    {
        $sql = 'UPDATE pc_lideres_historial
                SET fecha_fin = COALESCE(fecha_fin, CURRENT_DATE),
                    motivo_cambio = COALESCE(:motivo_cambio, motivo_cambio),
                    actualizado_por = :actualizado_por
                WHERE pc_id = :pc_id
                  AND organizacion_id = :organizacion_id
                  AND rol_liderazgo = :rol_liderazgo
                  AND fecha_fin IS NULL';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':pc_id' => $pcId,
            ':organizacion_id' => $organizacionId,
            ':rol_liderazgo' => $rol,
            ':motivo_cambio' => $motivo,
            ':actualizado_por' => $usuarioId
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function countMeetingsByFilters(array $filters, int $organizacionId): int
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM pc_reuniones pr
                INNER JOIN pc_grupos p ON p.id = pr.pc_id
                WHERE p.organizacion_id = :organizacion_id
                  AND p.eliminado_en IS NULL';
        $params = [':organizacion_id' => $organizacionId];
        if (!empty($filters['estado'])) { $sql .= ' AND p.estado = :estado'; $params[':estado'] = $filters['estado']; }
        if (!empty($filters['sector'])) { $sql .= ' AND (p.sector LIKE :sector OR p.comunidad LIKE :sector)'; $params[':sector'] = '%' . $filters['sector'] . '%'; }
        if (!empty($filters['fecha_desde'])) { $sql .= ' AND pr.fecha >= :fecha_desde'; $params[':fecha_desde'] = $filters['fecha_desde']; }
        if (!empty($filters['fecha_hasta'])) { $sql .= ' AND pr.fecha <= :fecha_hasta'; $params[':fecha_hasta'] = $filters['fecha_hasta']; }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];
        return (int) ($row['total'] ?? 0);
    }

    /** @param array<string, mixed> $filters */
    private function sumVisitsByFilters(array $filters, int $organizacionId): int
    {
        $sql = 'SELECT COALESCE(SUM(pr.total_visitas), 0) AS total
                FROM pc_reuniones pr
                INNER JOIN pc_grupos p ON p.id = pr.pc_id
                WHERE p.organizacion_id = :organizacion_id
                  AND p.eliminado_en IS NULL';
        $params = [':organizacion_id' => $organizacionId];
        if (!empty($filters['estado'])) { $sql .= ' AND p.estado = :estado'; $params[':estado'] = $filters['estado']; }
        if (!empty($filters['sector'])) { $sql .= ' AND (p.sector LIKE :sector OR p.comunidad LIKE :sector)'; $params[':sector'] = '%' . $filters['sector'] . '%'; }
        if (!empty($filters['fecha_desde'])) { $sql .= ' AND pr.fecha >= :fecha_desde'; $params[':fecha_desde'] = $filters['fecha_desde']; }
        if (!empty($filters['fecha_hasta'])) { $sql .= ' AND pr.fecha <= :fecha_hasta'; $params[':fecha_hasta'] = $filters['fecha_hasta']; }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];
        return (int) ($row['total'] ?? 0);
    }
}
