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
        $sql = "SELECT codigo, nombre, dia_semana, hora_inicio, activo, orden
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
        $sql = "SELECT nombre, activo, orden
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
        $sql = "SELECT clave, etiqueta, categoria, habilitado, obligatorio
                FROM organizacion_metricas_config
                WHERE organizacion_id = :organizacion_id
                ORDER BY id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':organizacion_id' => $organizacionId]);

        return $stmt->fetchAll() ?: [];
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

            $deleteSql = "DELETE FROM organizacion_metricas_config WHERE organizacion_id = :organizacion_id";
            $stmtDelete = $this->pdo->prepare($deleteSql);
            $stmtDelete->execute([':organizacion_id' => $organizacionId]);

            $insertSql = "INSERT INTO organizacion_metricas_config (
                            organizacion_id,
                            clave,
                            etiqueta,
                            categoria,
                            habilitado,
                            obligatorio
                          ) VALUES (
                            :organizacion_id,
                            :clave,
                            :etiqueta,
                            :categoria,
                            :habilitado,
                            :obligatorio
                          )";
            $stmtInsert = $this->pdo->prepare($insertSql);

            foreach ($metricas as $item) {
                $stmtInsert->execute([
                    ':organizacion_id' => $organizacionId,
                    ':clave' => (string) $item['clave'],
                    ':etiqueta' => (string) $item['etiqueta'],
                    ':categoria' => (string) ($item['categoria'] ?? 'adicionales'),
                    ':habilitado' => !empty($item['habilitado']) ? 1 : 0,
                    ':obligatorio' => !empty($item['obligatorio']) ? 1 : 0
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
}
