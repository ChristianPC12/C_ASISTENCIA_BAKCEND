<?php
declare(strict_types=1);

/**
 * Clase OrganizacionDAO
 *
 * Acceso a datos para organizaciones (tenant) y campos.
 */
final class OrganizacionDAO
{
    /** @var PDO */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    /**
     * Busca un campo por codigo.
     *
     * @param string $codigo Codigo del campo (AN/ACS/MC).
     * @return array<string, mixed>|null
     */
    public function findCampoByCodigo(string $codigo): ?array
    {
        $sql = "SELECT id, codigo, nombre, activo
                FROM campos
                WHERE codigo = :codigo
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Obtiene una organizacion por ID.
     *
     * @param int $id
     * @return OrganizacionDTO|null
     */
    public function findById(int $id): ?OrganizacionDTO
    {
        $sql = "SELECT o.id,
                       o.campo_id,
                       c.codigo AS campo_codigo,
                       c.nombre AS campo_nombre,
                       o.codigo_instancia,
                       o.tipo_organizacion,
                       o.nombre_organizacion,
                       o.correo_contacto,
                       o.activa,
                       EXISTS(
                           SELECT 1
                           FROM usuarios ua
                           INNER JOIN roles ra ON ra.id = ua.rol_id
                           WHERE ua.organizacion_id = o.id
                             AND ra.nombre = 'ADMIN'
                             AND ua.activo = 1
                       ) AS tiene_admin_activo,
                       (
                           SELECT ua.usuario
                           FROM usuarios ua
                           INNER JOIN roles ra ON ra.id = ua.rol_id
                           WHERE ua.organizacion_id = o.id
                             AND ra.nombre = 'ADMIN'
                             AND ua.activo = 1
                           ORDER BY ua.id DESC
                           LIMIT 1
                       ) AS admin_usuario_activo,
                       (
                           SELECT ua.password_expira_en
                           FROM usuarios ua
                           INNER JOIN roles ra ON ra.id = ua.rol_id
                           WHERE ua.organizacion_id = o.id
                             AND ra.nombre = 'ADMIN'
                             AND ua.activo = 1
                           ORDER BY ua.id DESC
                           LIMIT 1
                       ) AS admin_password_expira_en,
                       (
                           SELECT CASE
                               WHEN ua.password_expira_en IS NOT NULL
                                    AND TIMESTAMPDIFF(DAY, ua.creado_en, ua.password_expira_en) BETWEEN 1 AND 5
                               THEN 1
                               ELSE 0
                           END
                           FROM usuarios ua
                           INNER JOIN roles ra ON ra.id = ua.rol_id
                           WHERE ua.organizacion_id = o.id
                             AND ra.nombre = 'ADMIN'
                             AND ua.activo = 1
                           ORDER BY ua.id DESC
                           LIMIT 1
                       ) AS admin_temporal_activo,
                       o.creado_en,
                       o.actualizado_en
                FROM organizaciones o
                INNER JOIN campos c ON c.id = o.campo_id
                WHERE o.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return OrganizacionMapper::fromRow($row);
    }

    /**
     * Lista organizaciones con filtros y paginacion.
     *
     * @param array<string, mixed> $filtros
     * @param int                  $page
     * @param int                  $limit
     * @return array{total:int,items:array<int, OrganizacionDTO>}
     */
    public function list(array $filtros, int $page, int $limit): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['campo'])) {
            $where[] = 'c.codigo = :campo';
            $params[':campo'] = $filtros['campo'];
        }

        if (!empty($filtros['tipo'])) {
            $where[] = 'o.tipo_organizacion = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }

        if (isset($filtros['estado']) && $filtros['estado'] !== 'TODAS') {
            $where[] = 'o.activa = :activa';
            $params[':activa'] = $filtros['estado'] === 'ACTIVA' ? 1 : 0;
        }

        if (!empty($filtros['q'])) {
            $where[] = '(o.codigo_instancia LIKE :q
                         OR o.nombre_organizacion LIKE :q
                         OR c.codigo LIKE :q
                         OR c.nombre LIKE :q)';
            $params[':q'] = '%' . $filtros['q'] . '%';
        }

        $whereSql = implode(' AND ', $where);

        $sqlCount = "SELECT COUNT(o.id)
                     FROM organizaciones o
                     INNER JOIN campos c ON c.id = o.campo_id
                     WHERE {$whereSql}";

        $stmtCount = $this->pdo->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        $offset = ($page - 1) * $limit;

        $sqlData = "SELECT o.id,
                           o.campo_id,
                           c.codigo AS campo_codigo,
                           c.nombre AS campo_nombre,
                           o.codigo_instancia,
                           o.tipo_organizacion,
                           o.nombre_organizacion,
                           o.correo_contacto,
                           o.activa,
                           EXISTS(
                               SELECT 1
                               FROM usuarios ua
                               INNER JOIN roles ra ON ra.id = ua.rol_id
                               WHERE ua.organizacion_id = o.id
                                 AND ra.nombre = 'ADMIN'
                                 AND ua.activo = 1
                           ) AS tiene_admin_activo,
                           (
                               SELECT ua.usuario
                               FROM usuarios ua
                               INNER JOIN roles ra ON ra.id = ua.rol_id
                               WHERE ua.organizacion_id = o.id
                                 AND ra.nombre = 'ADMIN'
                                 AND ua.activo = 1
                               ORDER BY ua.id DESC
                               LIMIT 1
                           ) AS admin_usuario_activo,
                           (
                               SELECT ua.password_expira_en
                               FROM usuarios ua
                               INNER JOIN roles ra ON ra.id = ua.rol_id
                               WHERE ua.organizacion_id = o.id
                                 AND ra.nombre = 'ADMIN'
                                 AND ua.activo = 1
                               ORDER BY ua.id DESC
                               LIMIT 1
                           ) AS admin_password_expira_en,
                           (
                               SELECT CASE
                                   WHEN ua.password_expira_en IS NOT NULL
                                        AND TIMESTAMPDIFF(DAY, ua.creado_en, ua.password_expira_en) BETWEEN 1 AND 5
                                   THEN 1
                                   ELSE 0
                               END
                               FROM usuarios ua
                               INNER JOIN roles ra ON ra.id = ua.rol_id
                               WHERE ua.organizacion_id = o.id
                                 AND ra.nombre = 'ADMIN'
                                 AND ua.activo = 1
                               ORDER BY ua.id DESC
                               LIMIT 1
                           ) AS admin_temporal_activo,
                           o.creado_en,
                           o.actualizado_en
                    FROM organizaciones o
                    INNER JOIN campos c ON c.id = o.campo_id
                    WHERE {$whereSql}
                    ORDER BY o.id DESC
                    LIMIT :offset, :limit";

        $stmtData = $this->pdo->prepare($sqlData);
        $this->bindAssocParams($stmtData, $params);
        $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmtData->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmtData->execute();

        $items = [];
        while ($row = $stmtData->fetch()) {
            $items[] = OrganizacionMapper::fromRow($row);
        }

        return [
            'total' => $total,
            'items' => $items
        ];
    }

    /**
     * Verifica existencia de codigo_instancia.
     *
     * @param string $codigoInstancia
     * @return bool
     */
    public function existsByCodigoInstancia(string $codigoInstancia): bool
    {
        $sql = "SELECT COUNT(id)
                FROM organizaciones
                WHERE codigo_instancia = :codigo";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':codigo' => $codigoInstancia]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Verifica duplicado de nombre por campo y tipo.
     *
     * @param int      $campoId
     * @param string   $tipoOrganizacion
     * @param string   $nombreOrganizacion
     * @param int|null $excludeId
     * @return bool
     */
    public function existsByNombreEnCampoTipo(
        int $campoId,
        string $tipoOrganizacion,
        string $nombreOrganizacion,
        ?int $excludeId = null
    ): bool {
        $sql = "SELECT COUNT(id)
                FROM organizaciones
                WHERE campo_id = :campo_id
                  AND tipo_organizacion = :tipo
                  AND UPPER(TRIM(nombre_organizacion)) = UPPER(TRIM(:nombre))";

        $params = [
            ':campo_id' => $campoId,
            ':tipo' => $tipoOrganizacion,
            ':nombre' => $nombreOrganizacion
        ];

        if ($excludeId !== null) {
            $sql .= " AND id <> :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Inserta una organizacion.
     *
     * @param int         $campoId
     * @param string      $codigoInstancia
     * @param string      $tipoOrganizacion
     * @param string      $nombreOrganizacion
     * @param string|null $correoContacto
     * @return int
     */
    public function insert(
        int $campoId,
        string $codigoInstancia,
        string $tipoOrganizacion,
        string $nombreOrganizacion,
        ?string $correoContacto
    ): int {
        $sql = "INSERT INTO organizaciones (
                    campo_id,
                    codigo_instancia,
                    tipo_organizacion,
                    nombre_organizacion,
                    correo_contacto,
                    activa
                ) VALUES (
                    :campo_id,
                    :codigo_instancia,
                    :tipo_organizacion,
                    :nombre_organizacion,
                    :correo_contacto,
                    1
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':campo_id' => $campoId,
            ':codigo_instancia' => $codigoInstancia,
            ':tipo_organizacion' => $tipoOrganizacion,
            ':nombre_organizacion' => $nombreOrganizacion,
            ':correo_contacto' => $correoContacto
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza campos editables de organizacion.
     *
     * @param int         $id
     * @param string      $tipoOrganizacion
     * @param string      $nombreOrganizacion
     * @param string|null $correoContacto
     * @param bool        $activa
     * @return bool
     */
    public function update(
        int $id,
        string $tipoOrganizacion,
        string $nombreOrganizacion,
        ?string $correoContacto,
        bool $activa
    ): bool {
        $sql = "UPDATE organizaciones
                SET tipo_organizacion = :tipo_organizacion,
                    nombre_organizacion = :nombre_organizacion,
                    correo_contacto = :correo_contacto,
                    activa = :activa
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':tipo_organizacion' => $tipoOrganizacion,
            ':nombre_organizacion' => $nombreOrganizacion,
            ':correo_contacto' => $correoContacto,
            ':activa' => $activa ? 1 : 0,
            ':id' => $id
        ]);
    }

    /**
     * Asegura estado de setup inicial para la organizacion.
     *
     * @param int $organizacionId
     * @return void
     */
    public function ensureConfigEstado(int $organizacionId): void
    {
        $sql = "INSERT INTO organizacion_config_estado (
                    organizacion_id,
                    estado_setup,
                    bloqueada_operacion,
                    setup_completado_en,
                    ultima_revision_en
                )
                SELECT
                    :organizacion_id,
                    'PENDIENTE',
                    1,
                    NULL,
                    NOW()
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM organizacion_config_estado
                    WHERE organizacion_id = :organizacion_id_check
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $organizacionId,
            ':organizacion_id_check' => $organizacionId
        ]);
    }

    /**
     * Expone conexion para transacciones de servicio.
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Asigna parametros nombrados preservando tipo.
     *
     * @param PDOStatement         $stmt
     * @param array<string, mixed> $params
     * @return void
     */
    private function bindAssocParams(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $name => $value) {
            $type = PDO::PARAM_STR;

            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $value = $value ? 1 : 0;
                $type = PDO::PARAM_INT;
            } elseif ($value === null) {
                $type = PDO::PARAM_NULL;
            }

            $stmt->bindValue($name, $value, $type);
        }
    }
}
