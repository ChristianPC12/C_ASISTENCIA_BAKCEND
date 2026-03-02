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
}
