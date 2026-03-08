<?php
declare(strict_types=1);

/**
 * DAO para persistencia de presentaciones.
 */
final class PresentacionDAO
{
    /** @var PDO */
    private PDO $pdo;

    /** @var string */
    private const BASE_COLUMNS = "p.id, p.usuario_id, p.anio, p.mes, p.culto_codigo,
        p.filtros_json, p.metricas_json, p.prompt_version, p.prompt_bloqueado,
        p.modelo, p.presentacion_json, p.creado_en,
        u.nombre_completo AS usuario_nombre, u.usuario AS usuario_login";

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $sql = "INSERT INTO presentaciones
            (usuario_id, anio, mes, culto_codigo, filtros_json, metricas_json,
             prompt_version, prompt_bloqueado, modelo, ia_response_id, presentacion_json)
            VALUES
            (:usuario_id, :anio, :mes, :culto_codigo, :filtros_json, :metricas_json,
             :prompt_version, :prompt_bloqueado, :modelo, :ia_response_id, :presentacion_json)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => (int) $data['usuario_id'],
            ':anio' => (int) $data['anio'],
            ':mes' => (int) $data['mes'],
            ':culto_codigo' => $data['culto_codigo'],
            ':filtros_json' => (string) $data['filtros_json'],
            ':metricas_json' => (string) $data['metricas_json'],
            ':prompt_version' => (string) $data['prompt_version'],
            ':prompt_bloqueado' => (string) $data['prompt_bloqueado'],
            ':modelo' => (string) $data['modelo'],
            ':ia_response_id' => (string) $data['ia_response_id'],
            ':presentacion_json' => (string) $data['presentacion_json']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?PresentacionDTO
    {
        $sql = "SELECT " . self::BASE_COLUMNS . "
            FROM presentaciones p
            INNER JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.id = :id
            LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return PresentacionMapper::fromRow($row);
    }

    public function existsByPeriodoCulto(int $anio, int $mes, ?string $cultoCodigo): bool
    {
        $sql = "SELECT COUNT(id)
            FROM presentaciones
            WHERE anio = :anio
              AND mes = :mes
              AND (culto_codigo <=> :culto_codigo)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->bindValue(':mes', $mes, PDO::PARAM_INT);
        if ($cultoCodigo === null) {
            $stmt->bindValue(':culto_codigo', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':culto_codigo', $cultoCodigo, PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array<int, PresentacionDTO>
     */
    public function findAll(array $filtros, int $usuarioId, bool $esAdmin, int $limit, int $offset): array
    {
        $where = [];
        $params = [];

        if (!$esAdmin) {
            $where[] = 'p.usuario_id = :usuario_id_scope';
            $params[':usuario_id_scope'] = $usuarioId;
        }

        if (!empty($filtros['anio'])) {
            $where[] = 'p.anio = :anio';
            $params[':anio'] = (int) $filtros['anio'];
        }

        if (!empty($filtros['mes'])) {
            $where[] = 'p.mes = :mes';
            $params[':mes'] = (int) $filtros['mes'];
        }

        if (!empty($filtros['culto'])) {
            $where[] = 'p.culto_codigo = :culto';
            $params[':culto'] = (string) $filtros['culto'];
        }

        if ($esAdmin && !empty($filtros['usuario_id'])) {
            $where[] = 'p.usuario_id = :usuario_id';
            $params[':usuario_id'] = (int) $filtros['usuario_id'];
        }

        $sql = "SELECT " . self::BASE_COLUMNS . "
            FROM presentaciones p
            INNER JOIN usuarios u ON u.id = p.usuario_id";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY p.creado_en DESC, p.id DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = PresentacionMapper::fromRow($row);
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $filtros
     */
    public function countAll(array $filtros, int $usuarioId, bool $esAdmin): int
    {
        $where = [];
        $params = [];

        if (!$esAdmin) {
            $where[] = 'usuario_id = :usuario_id_scope';
            $params[':usuario_id_scope'] = $usuarioId;
        }

        if (!empty($filtros['anio'])) {
            $where[] = 'anio = :anio';
            $params[':anio'] = (int) $filtros['anio'];
        }

        if (!empty($filtros['mes'])) {
            $where[] = 'mes = :mes';
            $params[':mes'] = (int) $filtros['mes'];
        }

        if (!empty($filtros['culto'])) {
            $where[] = 'culto_codigo = :culto';
            $params[':culto'] = (string) $filtros['culto'];
        }

        if ($esAdmin && !empty($filtros['usuario_id'])) {
            $where[] = 'usuario_id = :usuario_id';
            $params[':usuario_id'] = (int) $filtros['usuario_id'];
        }

        $sql = 'SELECT COUNT(id) FROM presentaciones';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }
}
