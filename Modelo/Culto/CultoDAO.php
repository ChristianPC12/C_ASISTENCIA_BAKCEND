<?php
declare(strict_types=1);

/**
 * Clase CultoDAO
 *
 * Acceso a datos para la entidad Culto.
 */
final class CultoDAO
{
    /** @var PDO */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    /**
     * Lista todos los cultos.
     *
     * @return CultoDTO[]
     */
    public function findAll(): array
    {
        $sql = "SELECT id, codigo, nombre, dia_semana, hora_inicio FROM cultos ORDER BY id ASC";

        $stmt = $this->pdo->query($sql);
        $cultos = [];

        while ($row = $stmt->fetch()) {
            $cultos[] = CultoMapper::fromRow($row);
        }

        return $cultos;
    }

    /**
     * Busca un culto por ID.
     *
     * @param int $id ID del culto.
     * @return CultoDTO|null
     */
    public function findById(int $id): ?CultoDTO
    {
        $sql = "SELECT id, codigo, nombre, dia_semana, hora_inicio FROM cultos WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return CultoMapper::fromRow($row);
    }

    /**
     * Busca un culto por codigo (SABADO, DOMINGO, MIERCOLES).
     *
     * @param string $codigo Codigo del culto.
     * @return CultoDTO|null
     */
    public function findByCodigo(string $codigo): ?CultoDTO
    {
        $sql = "SELECT id, codigo, nombre, dia_semana, hora_inicio FROM cultos WHERE codigo = :codigo";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return CultoMapper::fromRow($row);
    }

    /**
     * Garantiza una fila en `cultos` para un culto configurado por organizacion.
     * Retorna el culto global (id) que se usara en asistencia_registro.
     *
     * @param int $organizacionId
     * @param array<string, mixed> $cultoOrg
     * @return CultoDTO
     */
    public function ensureFromOrganizacionCulto(int $organizacionId, array $cultoOrg): CultoDTO
    {
        $cultoOrgId = (int) ($cultoOrg['id'] ?? 0);
        if ($cultoOrgId <= 0) {
            throw new InvalidArgumentException('Culto de organizacion invalido.');
        }

        $codigoGlobal = $this->codigoGlobalDesdeOrganizacion($organizacionId, $cultoOrgId);
        $nombre = trim((string) ($cultoOrg['nombre'] ?? ''));
        $diaSemana = (int) ($cultoOrg['dia_semana'] ?? 0);
        $hora = $this->normalizarHora((string) ($cultoOrg['hora_inicio'] ?? '00:00:00'));

        $actual = $this->findByCodigo($codigoGlobal);
        if ($actual === null) {
            $sqlInsert = "INSERT INTO cultos (codigo, nombre, dia_semana, hora_inicio)
                          VALUES (:codigo, :nombre, :dia_semana, :hora_inicio)";
            $stmtInsert = $this->pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                ':codigo' => $codigoGlobal,
                ':nombre' => $nombre,
                ':dia_semana' => $diaSemana,
                ':hora_inicio' => $hora
            ]);

            $insertId = (int) $this->pdo->lastInsertId();
            $nuevo = $this->findById($insertId);
            if ($nuevo === null) {
                throw new RuntimeException('No se pudo materializar culto global para la organizacion.');
            }

            return $nuevo;
        }

        if (
            $actual->nombre !== $nombre
            || $actual->diaSemana !== $diaSemana
            || $actual->horaInicio !== $hora
        ) {
            $sqlUpdate = "UPDATE cultos
                          SET nombre = :nombre,
                              dia_semana = :dia_semana,
                              hora_inicio = :hora_inicio
                          WHERE id = :id";
            $stmtUpdate = $this->pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':nombre' => $nombre,
                ':dia_semana' => $diaSemana,
                ':hora_inicio' => $hora,
                ':id' => $actual->id
            ]);
        }

        $refrescado = $this->findById($actual->id);
        if ($refrescado === null) {
            throw new RuntimeException('No se pudo refrescar culto global de la organizacion.');
        }

        return $refrescado;
    }

    /**
     * Genera codigo global deterministico (20 chars max) para culto por organizacion.
     *
     * @param int $organizacionId
     * @param int $cultoOrgId
     * @return string
     */
    private function codigoGlobalDesdeOrganizacion(int $organizacionId, int $cultoOrgId): string
    {
        return 'OC' . strtoupper(substr(sha1($organizacionId . ':' . $cultoOrgId), 0, 18));
    }

    /**
     * Normaliza hora para formato TIME HH:MM:SS.
     *
     * @param string $hora
     * @return string
     */
    private function normalizarHora(string $hora): string
    {
        $valor = trim($hora);
        if (preg_match('/^\d{2}:\d{2}$/', $valor) === 1) {
            return $valor . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $valor) === 1) {
            return $valor;
        }
        return '00:00:00';
    }
}
