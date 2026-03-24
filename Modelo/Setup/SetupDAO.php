<?php
declare(strict_types=1);

/**
 * Clase SetupDAO
 *
 * Persistencia del setup inicial por organizacion.
 */
final class SetupDAO
{
    /** @var PDO */
    private PDO $pdo;
    /** @var array<string, bool>|null */
    private ?array $metricasConfigColumns = null;

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    /**
     * Asegura fila base de estado setup para la organizacion.
     *
     * @param int $organizacionId
     * @return void
     */
    public function ensureEstadoRow(int $organizacionId): void
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
     * Obtiene estado setup por organizacion.
     *
     * @param int $organizacionId
     * @return array<string, mixed>|null
     */
    public function getEstadoRow(int $organizacionId): ?array
    {
        $sql = "SELECT organizacion_id,
                       estado_setup,
                       bloqueada_operacion,
                       setup_completado_en,
                       ultima_revision_en,
                       actualizado_en
                FROM organizacion_config_estado
                WHERE organizacion_id = :organizacion_id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Indica si los modulos operativos deben permanecer bloqueados.
     *
     * @param int $organizacionId
     * @return bool
     */
    public function isOperativeBlocked(int $organizacionId): bool
    {
        $this->ensureEstadoRow($organizacionId);
        $estado = $this->getEstadoRow($organizacionId);

        if ($estado === null) {
            return true;
        }

        $bloqueada = (int) ($estado['bloqueada_operacion'] ?? 1) === 1;
        $estadoSetup = strtoupper((string) ($estado['estado_setup'] ?? 'PENDIENTE'));

        return $bloqueada || $estadoSetup !== 'COMPLETO';
    }

    /**
     * Obtiene cultos de setup por organizacion.
     *
     * @param int $organizacionId
     * @return array<int, array<string, mixed>>
     */
    public function getCultos(int $organizacionId): array
    {
        $sql = "SELECT id, codigo, nombre, dia_semana, hora_inicio, activo, orden
                FROM organizacion_cultos
                WHERE organizacion_id = :organizacion_id
                ORDER BY orden ASC, id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Reemplaza cultos de setup por organizacion.
     *
     * @param int                                $organizacionId
     * @param array<int, array<string, mixed>>   $cultos
     * @return void
     */
    public function replaceCultos(int $organizacionId, array $cultos): void
    {
        try {
            $this->pdo->beginTransaction();

            $deleteSql = "DELETE FROM organizacion_cultos WHERE organizacion_id = :organizacion_id";
            $stmtDelete = $this->pdo->prepare($deleteSql);
            $stmtDelete->execute([':organizacion_id' => $organizacionId]);

            $insertSql = "INSERT INTO organizacion_cultos (
                            organizacion_id, codigo, nombre, dia_semana, hora_inicio, activo, orden
                          ) VALUES (
                            :organizacion_id, :codigo, :nombre, :dia_semana, :hora_inicio, :activo, :orden
                          )";
            $stmtInsert = $this->pdo->prepare($insertSql);

            foreach ($cultos as $culto) {
                $stmtInsert->execute([
                    ':organizacion_id' => $organizacionId,
                    ':codigo' => (string) $culto['codigo'],
                    ':nombre' => (string) $culto['nombre'],
                    ':dia_semana' => (int) $culto['dia_semana'],
                    ':hora_inicio' => (string) $culto['hora_inicio'],
                    ':activo' => !empty($culto['activo']) ? 1 : 0,
                    ':orden' => (int) $culto['orden']
                ]);
            }

            $this->markPendienteInternal($organizacionId);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene procedencias de setup por organizacion.
     *
     * @param int $organizacionId
     * @return array<int, array<string, mixed>>
     */
    public function getProcedencias(int $organizacionId): array
    {
        $sql = "SELECT id, nombre, activo, orden
                FROM organizacion_procedencias
                WHERE organizacion_id = :organizacion_id
                ORDER BY orden ASC, id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Reemplaza procedencias de setup por organizacion.
     *
     * @param int                                $organizacionId
     * @param array<int, array<string, mixed>>   $procedencias
     * @return void
     */
    public function replaceProcedencias(int $organizacionId, array $procedencias): void
    {
        try {
            $this->pdo->beginTransaction();

            $deleteSql = "DELETE FROM organizacion_procedencias WHERE organizacion_id = :organizacion_id";
            $stmtDelete = $this->pdo->prepare($deleteSql);
            $stmtDelete->execute([':organizacion_id' => $organizacionId]);

            $insertSql = "INSERT INTO organizacion_procedencias (
                            organizacion_id, nombre, activo, orden
                          ) VALUES (
                            :organizacion_id, :nombre, :activo, :orden
                          )";
            $stmtInsert = $this->pdo->prepare($insertSql);

            foreach ($procedencias as $item) {
                $stmtInsert->execute([
                    ':organizacion_id' => $organizacionId,
                    ':nombre' => (string) $item['nombre'],
                    ':activo' => !empty($item['activo']) ? 1 : 0,
                    ':orden' => (int) $item['orden']
                ]);
            }

            $this->markPendienteInternal($organizacionId);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene metricas de setup por organizacion.
     *
     * @param int $organizacionId
     * @return array<int, array<string, mixed>>
     */
    public function getMetricas(int $organizacionId): array
    {
        $columnas = $this->getMetricasConfigColumns();
        $ordenSql = $columnas['orden'] ? 'ORDER BY orden ASC, id ASC' : 'ORDER BY id ASC';

        if ($columnas['categoria']) {
            $sql = "SELECT clave, etiqueta, categoria, habilitado, 0 AS obligatorio
                    FROM organizacion_metricas_config
                    WHERE organizacion_id = :organizacion_id
                    {$ordenSql}";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':organizacion_id' => $organizacionId]);

            return $stmt->fetchAll() ?: [];
        }

        $selectLegacyCategoria = $columnas['regla_dependencia'] ? ', regla_dependencia' : '';
        $sql = "SELECT clave, etiqueta, habilitado, 0 AS obligatorio{$selectLegacyCategoria}
                FROM organizacion_metricas_config
                WHERE organizacion_id = :organizacion_id
                {$ordenSql}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);

        $rows = $stmt->fetchAll() ?: [];
        return array_map(function (array $item) use ($columnas): array {
            $clave = strtolower(trim((string) ($item['clave'] ?? '')));
            $categoria = null;
            if ($columnas['regla_dependencia']) {
                $categoria = $this->parseCategoriaLegacy((string) ($item['regla_dependencia'] ?? ''));
            }
            $item['categoria'] = $categoria !== null
                ? $categoria
                : $this->inferirCategoriaPorClave($clave);
            if (array_key_exists('regla_dependencia', $item)) {
                unset($item['regla_dependencia']);
            }
            return $item;
        }, $rows);
    }

    /**
     * Reemplaza metricas de setup por organizacion.
     *
     * @param int                                $organizacionId
     * @param array<int, array<string, mixed>>   $metricas
     * @return void
     */
    public function replaceMetricas(int $organizacionId, array $metricas): void
    {
        try {
            $this->pdo->beginTransaction();
            $columnas = $this->getMetricasConfigColumns();

            $deleteSql = "DELETE FROM organizacion_metricas_config WHERE organizacion_id = :organizacion_id";
            $stmtDelete = $this->pdo->prepare($deleteSql);
            $stmtDelete->execute([':organizacion_id' => $organizacionId]);

            $insertColumns = [
                'organizacion_id',
                'clave',
                'etiqueta',
                'habilitado',
                'obligatorio'
            ];
            if ($columnas['categoria']) {
                $insertColumns[] = 'categoria';
            }
            if ($columnas['depende_de_clave']) {
                $insertColumns[] = 'depende_de_clave';
            }
            if ($columnas['regla_dependencia']) {
                $insertColumns[] = 'regla_dependencia';
            }
            if ($columnas['orden']) {
                $insertColumns[] = 'orden';
            }

            $insertSql = "INSERT INTO organizacion_metricas_config (
                            " . implode(",\n                            ", $insertColumns) . "
                          ) VALUES (
                            :" . implode(",\n                            :", $insertColumns) . "
                          )";
            $stmtInsert = $this->pdo->prepare($insertSql);

            foreach ($metricas as $idx => $item) {
                $clave = strtolower(trim((string) ($item['clave'] ?? '')));
                $categoria = strtolower(trim((string) ($item['categoria'] ?? '')));
                if ($categoria === '') {
                    $categoria = $this->inferirCategoriaPorClave($clave);
                }

                $params = [
                    ':organizacion_id' => $organizacionId,
                    ':clave' => (string) $item['clave'],
                    ':etiqueta' => (string) $item['etiqueta'],
                    ':habilitado' => !empty($item['habilitado']) ? 1 : 0,
                    ':obligatorio' => 0
                ];
                if ($columnas['categoria']) {
                    $params[':categoria'] = $categoria !== '' ? $categoria : 'adicionales';
                }
                if ($columnas['depende_de_clave']) {
                    $params[':depende_de_clave'] = null;
                }
                if ($columnas['regla_dependencia']) {
                    $params[':regla_dependencia'] = $this->serializarCategoriaLegacy($categoria);
                }
                if ($columnas['orden']) {
                    $params[':orden'] = $idx + 1;
                }

                $stmtInsert->execute($params);
            }

            $this->markPendienteInternal($organizacionId);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cuenta cultos activos.
     *
     * @param int $organizacionId
     * @return int
     */
    public function countCultosActivos(int $organizacionId): int
    {
        $sql = "SELECT COUNT(id)
                FROM organizacion_cultos
                WHERE organizacion_id = :organizacion_id
                  AND activo = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta procedencias activas.
     *
     * @param int $organizacionId
     * @return int
     */
    public function countProcedenciasActivas(int $organizacionId): int
    {
        $sql = "SELECT COUNT(id)
                FROM organizacion_procedencias
                WHERE organizacion_id = :organizacion_id
                  AND activo = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta metricas habilitadas.
     *
     * @param int $organizacionId
     * @return int
     */
    public function countMetricasHabilitadas(int $organizacionId): int
    {
        $sql = "SELECT COUNT(id)
                FROM organizacion_metricas_config
                WHERE organizacion_id = :organizacion_id
                  AND habilitado = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta administradores definitivos activos (excluye ADMIN temporal de 5 dias).
     *
     * @param int $organizacionId
     * @return int
     */
    public function countAdminsDefinitivosActivos(int $organizacionId): int
    {
        $sql = "SELECT COUNT(u.id)
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.organizacion_id = :organizacion_id
                  AND r.nombre = 'ADMIN'
                  AND u.activo = 1
                  AND (
                        u.password_expira_en IS NULL
                        OR TIMESTAMPDIFF(DAY, u.creado_en, u.password_expira_en) NOT BETWEEN 1 AND 5
                  )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca setup como completo.
     *
     * @param int $organizacionId
     * @return void
     */
    public function markCompleto(int $organizacionId): void
    {
        $sql = "UPDATE organizacion_config_estado
                SET estado_setup = 'COMPLETO',
                    bloqueada_operacion = 0,
                    setup_completado_en = NOW(),
                    ultima_revision_en = NOW()
                WHERE organizacion_id = :organizacion_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);
    }

    /**
     * Marca setup como pendiente.
     *
     * @param int $organizacionId
     * @return void
     */
    public function markPendiente(int $organizacionId): void
    {
        $this->markPendienteInternal($organizacionId);
    }

    /**
     * Marca setup como pendiente (uso interno con/ sin transaccion).
     *
     * @param int $organizacionId
     * @return void
     */
    private function markPendienteInternal(int $organizacionId): void
    {
        $sql = "UPDATE organizacion_config_estado
                SET estado_setup = CASE
                        WHEN estado_setup = 'COMPLETO' THEN 'COMPLETO'
                        ELSE 'PENDIENTE'
                    END,
                    bloqueada_operacion = CASE
                        WHEN estado_setup = 'COMPLETO' THEN 0
                        ELSE 1
                    END,
                    setup_completado_en = CASE
                        WHEN estado_setup = 'COMPLETO' THEN setup_completado_en
                        ELSE NULL
                    END,
                    ultima_revision_en = NOW()
                WHERE organizacion_id = :organizacion_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);
    }

    /**
     * Obtiene columnas disponibles en organizacion_metricas_config.
     *
     * @return array<string, bool>
     */
    private function getMetricasConfigColumns(): array
    {
        if ($this->metricasConfigColumns !== null) {
            return $this->metricasConfigColumns;
        }

        $sql = "SELECT LOWER(COLUMN_NAME) AS column_name
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'organizacion_metricas_config'";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $set = [];
        foreach ($rows as $row) {
            $col = strtolower((string) ($row['column_name'] ?? ''));
            if ($col !== '') {
                $set[$col] = true;
            }
        }

        $this->metricasConfigColumns = [
            'categoria' => isset($set['categoria']),
            'depende_de_clave' => isset($set['depende_de_clave']),
            'regla_dependencia' => isset($set['regla_dependencia']),
            'orden' => isset($set['orden'])
        ];

        return $this->metricasConfigColumns;
    }

    /**
     * Infiere categoria a partir de clave para compatibilidad de esquema.
     *
     * @param string $clave
     * @return string
     */
    private function inferirCategoriaPorClave(string $clave): string
    {
        if ($clave === 'llegaron_antes_hora' || $clave === 'llegaron_despues_hora') {
            return 'informacion_culto';
        }
        if ($clave === 'ninos' || $clave === 'jovenes') {
            return 'composicion_asistentes';
        }
        if ($clave === 'total_asistentes') {
            return 'total_asistentes';
        }
        if ($clave === 'retiros_antes_terminar' || $clave === 'se_quedaron_todo') {
            return 'permanencia';
        }
        if ($clave === 'observaciones') {
            return 'observaciones';
        }
        if (str_starts_with($clave, 'proc_')) {
            return 'procedencia';
        }
        if (str_starts_with($clave, 'visitas_') || str_starts_with($clave, 'nombres_visitas_')) {
            return 'visitas';
        }

        return 'adicionales';
    }

    /**
     * Serializa categoria para esquemas legacy sin columna `categoria`.
     *
     * @param string $categoria
     * @return string
     */
    private function serializarCategoriaLegacy(string $categoria): string
    {
        $valor = strtolower(trim($categoria));
        if ($valor === '') {
            $valor = 'adicionales';
        }
        return 'CAT:' . $valor;
    }

    /**
     * Extrae categoria desde `regla_dependencia` legacy con formato CAT:<categoria>.
     *
     * @param string $legacy
     * @return string|null
     */
    private function parseCategoriaLegacy(string $legacy): ?string
    {
        $valor = strtolower(trim($legacy));
        if ($valor === '' || !str_starts_with($valor, 'cat:')) {
            return null;
        }

        $categoria = trim(substr($valor, 4));
        if ($categoria === '') {
            return null;
        }

        return $categoria;
    }
}
