<?php
declare(strict_types=1);

/**
 * Acceso a datos del modulo de campanas.
 */
final class CampanaDAO
{
    private PDO $pdo;

    private const COLUMNS = 'c.id, c.organizacion_id, c.nombre, c.lema, c.tipo, c.fecha_inicio, c.fecha_fin,
        c.lugar, c.hora, c.predicador, c.responsable, c.responsable_usuario_id, u.nombre_completo AS responsable_usuario_nombre,
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
            $camposBusqueda = ['c.nombre', 'c.lema', 'c.predicador', 'c.lugar'];
            $condicionesBusqueda = [];
            foreach ($camposBusqueda as $index => $campo) {
                $key = ':q_' . $index;
                $condicionesBusqueda[] = $campo . ' LIKE ' . $key;
                $params[$key] = '%' . $filters['q'] . '%';
            }
            $sql .= ' AND (' . implode(' OR ', $condicionesBusqueda) . ')';
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

        $sql .= ' GROUP BY c.id ORDER BY CASE WHEN c.fecha_inicio >= CURDATE() THEN 0 ELSE 1 END, ABS(DATEDIFF(c.fecha_inicio, CURDATE())) ASC, c.id DESC';
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
                    organizacion_id, nombre, lema, tipo, fecha_inicio, fecha_fin, lugar, hora, predicador, responsable,
                    responsable_usuario_id, descripcion, estado, observaciones, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :nombre, :lema, :tipo, :fecha_inicio, :fecha_fin, :lugar, :hora, :predicador, :responsable,
                    :responsable_usuario_id, :descripcion, :estado, :observaciones, :creado_por, :actualizado_por
                )';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'], ':nombre' => $data['nombre'], ':lema' => $data['lema'],
            ':tipo' => $data['tipo'], ':fecha_inicio' => $data['fecha_inicio'], ':fecha_fin' => $data['fecha_fin'],
            ':lugar' => $data['lugar'], ':hora' => $data['hora'], ':predicador' => $data['predicador'], ':responsable' => $data['responsable'],
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
                    hora = :hora,
                    predicador = :predicador,
                    responsable = :responsable,
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
            ':lugar' => $data['lugar'], ':hora' => $data['hora'], ':predicador' => $data['predicador'], ':responsable' => $data['responsable'],
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
                    COUNT(DISTINCT CASE WHEN cd.decision_clave = 'ACEPTO_ESTUDIO_BIBLICO' THEN cd.id END) AS total_estudios_derivados,
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
                    cm.correo AS contacto_correo, cm.direccion AS contacto_direccion,
                    cm.barrio_comunidad AS contacto_barrio_comunidad,
                    COUNT(DISTINCT cas.id) AS total_noches,
                    SUM(CASE WHEN cas.puntual = 1 THEN 1 ELSE 0 END) AS total_puntuales,
                    SUM(CASE WHEN cas.elegible_premio = 1 THEN 1 ELSE 0 END) AS total_premios,
                    SUM(CASE WHEN cas.elegible_premio = 1 AND cas.premio_entregado = 0 THEN 1 ELSE 0 END) AS total_premios_pendientes
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

    /** @return array<int, array<string, mixed>> */
    public function listAllAttendees(int $organizacionId, array $filtros = []): array
    {
        $where = ['ca.organizacion_id = :organizacion_id', 'ca.eliminado_en IS NULL'];
        $params = [':organizacion_id' => $organizacionId];

        if (!empty($filtros['q'])) {
            $camposBusqueda = [
                'ca.nombre_snapshot',
                'cm.nombre_completo',
                'ca.telefono_snapshot',
                'ca.procedencia',
                'cm.telefono',
                'cm.correo',
                'cm.direccion',
                'cm.barrio_comunidad'
            ];
            $condicionesBusqueda = [];
            foreach ($camposBusqueda as $index => $campo) {
                $key = ':q_' . $index;
                $condicionesBusqueda[] = $campo . ' LIKE ' . $key;
                $params[$key] = '%' . $filtros['q'] . '%';
            }
            $where[] = '(' . implode(' OR ', $condicionesBusqueda) . ')';
        }
        if (!empty($filtros['estado_seguimiento'])) {
            $where[] = 'ca.estado_seguimiento = :estado_seguimiento';
            $params[':estado_seguimiento'] = $filtros['estado_seguimiento'];
        }
        if (!empty($filtros['clasificacion_etaria'])) {
            $where[] = 'ca.clasificacion_etaria = :clasificacion_etaria';
            $params[':clasificacion_etaria'] = $filtros['clasificacion_etaria'];
        }
        if (!empty($filtros['campana_id'])) {
            if ($filtros['campana_id'] === 'SUELTAS') {
                $where[] = 'ca.campana_id IS NULL';
            } else {
                $where[] = 'ca.campana_id = :campana_id';
                $params[':campana_id'] = (int) $filtros['campana_id'];
            }
        }

        $sql = "SELECT ca.*, c.lema AS campana_lema, c.fecha_inicio AS campana_fecha_inicio,
                    c.fecha_fin AS campana_fecha_fin,
                    cm.telefono AS contacto_telefono,
                    cm.correo AS contacto_correo,
                    cm.direccion AS contacto_direccion,
                    cm.barrio_comunidad AS contacto_barrio_comunidad,
                    (SELECT eb.id
                     FROM estudios_biblicos eb
                     WHERE eb.organizacion_id = ca.organizacion_id
                       AND eb.eliminado_en IS NULL
                       AND eb.estado_general NOT IN ('NO_CONTINUA', 'BAUTIZADO', 'CERRADO')
                       AND (
                         eb.visita_asistente_id = ca.id
                         OR (ca.contacto_id IS NOT NULL AND eb.contacto_id = ca.contacto_id)
                         OR EXISTS (
                           SELECT 1
                           FROM estudio_biblico_visitas ev
                           WHERE ev.estudio_id = eb.id
                             AND ev.organizacion_id = eb.organizacion_id
                             AND (
                               ev.visita_asistente_id = ca.id
                               OR (ca.contacto_id IS NOT NULL AND ev.contacto_id = ca.contacto_id)
                             )
                         )
                       )
                     ORDER BY eb.actualizado_en DESC
                     LIMIT 1
                    ) AS estudio_biblico_activo_id,
                    (SELECT GROUP_CONCAT(DISTINCT c2.lema ORDER BY c2.fecha_inicio DESC SEPARATOR '||')
                     FROM campana_asistentes ca2
                     INNER JOIN campanas c2 ON c2.id = ca2.campana_id
                     WHERE ca2.organizacion_id = ca.organizacion_id
                       AND ca2.eliminado_en IS NULL
                       AND ca2.campana_id IS NOT NULL
                       AND (
                         (ca.contacto_id IS NOT NULL AND ca2.contacto_id = ca.contacto_id)
                         OR LOWER(TRIM(ca2.nombre_snapshot)) = LOWER(TRIM(ca.nombre_snapshot))
                       )
                    ) AS campanas_asistidas_raw
                FROM campana_asistentes ca
                LEFT JOIN campanas c ON c.id = ca.campana_id
                LEFT JOIN contactos_misioneros cm ON cm.id = ca.contacto_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY ca.creado_en DESC, ca.nombre_snapshot ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    private function normalizarNombre(string $valor): string
    {
        $valor = mb_strtolower(trim($valor), 'UTF-8');
        $reemplazos = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u'
        ];
        $valor = strtr($valor, $reemplazos);
        $valor = preg_replace('/\s+/', ' ', $valor);
        $prefijos = ['don ', 'dona ', 'sr ', 'sra ', 'srta ', 'hno ', 'hna '];
        foreach ($prefijos as $p) {
            if (strpos($valor, $p) === 0) {
                $valor = substr($valor, strlen($p));
                break;
            }
        }
        return trim($valor);
    }

    /** @return array<int, array<string, mixed>> */
    public function findSimilarAttendees(int $organizacionId, string $nombre, ?int $excluirId = null): array
    {
        $normalizado = $this->normalizarNombre($nombre);
        if (mb_strlen($normalizado) < 3) {
            return [];
        }

        $sql = "SELECT ca.*, c.lema AS campana_lema, c.fecha_inicio AS campana_fecha_inicio,
                    c.fecha_fin AS campana_fecha_fin,
                    cm.telefono AS contacto_telefono,
                    cm.correo AS contacto_correo,
                    cm.direccion AS contacto_direccion,
                    cm.barrio_comunidad AS contacto_barrio_comunidad
                FROM campana_asistentes ca
                LEFT JOIN campanas c ON c.id = ca.campana_id
                LEFT JOIN contactos_misioneros cm ON cm.id = ca.contacto_id
                WHERE ca.organizacion_id = :organizacion_id
                  AND ca.eliminado_en IS NULL";
        $params = [':organizacion_id' => $organizacionId];
        if ($excluirId !== null) {
            $sql .= ' AND ca.id != :excluir';
            $params[':excluir'] = $excluirId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll() ?: [];

        $matches = [];
        foreach ($filas as $fila) {
            $filaNorm = $this->normalizarNombre((string) ($fila['nombre_snapshot'] ?? ''));
            if ($filaNorm === '') continue;

            if ($filaNorm === $normalizado) {
                $similarity = 1.0;
            } elseif (strpos($filaNorm, $normalizado) !== false || strpos($normalizado, $filaNorm) !== false) {
                $similarity = 0.92;
            } else {
                similar_text($normalizado, $filaNorm, $percent);
                $similarity = $percent / 100;
            }

            if ($similarity >= 0.85) {
                $fila['similarity'] = round($similarity, 3);
                $matches[] = $fila;
            }
        }

        usort($matches, static fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        return array_slice($matches, 0, 10);
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

    public function softDeleteAttendee(int $asistenteId, int $organizacionId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE campana_asistentes SET eliminado_en = CURRENT_TIMESTAMP, eliminado_por = :eliminado_por WHERE id = :id AND organizacion_id = :organizacion_id AND eliminado_en IS NULL');
        return $stmt->execute([':id' => $asistenteId, ':organizacion_id' => $organizacionId, ':eliminado_por' => $usuarioId]);
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

    public function entregarPremiosAsistente(int $asistenteId, int $organizacionId, int $usuarioId): int
    {
        $stmt = $this->pdo->prepare('UPDATE campana_asistencia_sesiones SET premio_entregado = 1, actualizado_por = :usuario_id, actualizado_en = CURRENT_TIMESTAMP WHERE campana_asistente_id = :asistente_id AND organizacion_id = :org_id AND elegible_premio = 1 AND premio_entregado = 0');
        $stmt->execute([':usuario_id' => $usuarioId, ':asistente_id' => $asistenteId, ':org_id' => $organizacionId]);
        return $stmt->rowCount();
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

    /** @return array<string, mixed>|null */
    public function findDecisionById(int $decisionId, int $organizacionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT cd.*, ca.nombre_snapshot FROM campana_decisiones cd INNER JOIN campana_asistentes ca ON ca.id = cd.campana_asistente_id WHERE cd.id = :id AND cd.organizacion_id = :organizacion_id');
        $stmt->execute([':id' => $decisionId, ':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function deleteDecision(int $decisionId, int $organizacionId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM campana_decisiones WHERE id = :id AND organizacion_id = :organizacion_id');
        return $stmt->execute([':id' => $decisionId, ':organizacion_id' => $organizacionId]);
    }
}
