<?php
declare(strict_types=1);

/**
 * Clase AsistenciaService
 *
 * Logica de negocio para registros de asistencia.
 * No toca $_SERVER, $_POST, $_GET.
 */
final class AsistenciaService
{
    /** @var AsistenciaDAO */
    private AsistenciaDAO $asistenciaDAO;

    /** @var CultoDAO */
    private CultoDAO $cultoDAO;

    public function __construct()
    {
        $this->asistenciaDAO = new AsistenciaDAO();
        $this->cultoDAO      = new CultoDAO();
    }

    /**
     * Lista registros de asistencia con filtros opcionales.
     *
     * @param array<string, mixed> $filtros Filtros: culto (codigo), anio, trimestre, mes, fecha_exacta.
     * @return array<int, array<string, mixed>>
     */
    public function listar(array $filtros): array
    {
        $filtrosDAO = [];

        // Convertir codigo de culto a culto_id
        if (!empty($filtros['culto'])) {
            $culto = $this->cultoDAO->findByCodigo(strtoupper((string) $filtros['culto']));
            if ($culto !== null) {
                $filtrosDAO['culto_id'] = $culto->id;
            }
        } elseif (!empty($filtros['culto_id'])) {
            $filtrosDAO['culto_id'] = (int) $filtros['culto_id'];
        }

        if (!empty($filtros['anio'])) {
            $filtrosDAO['anio'] = (int) $filtros['anio'];
        }

        if (!empty($filtros['trimestre'])) {
            $filtrosDAO['trimestre'] = (int) $filtros['trimestre'];
        }

        if (!empty($filtros['mes'])) {
            $filtrosDAO['mes'] = (int) $filtros['mes'];
        }

        if (!empty($filtros['fecha_exacta'])) {
            $filtrosDAO['fecha_exacta'] = trim((string) $filtros['fecha_exacta']);
        }

        $registros = $this->asistenciaDAO->findAll($filtrosDAO);
        $resultado = [];

        foreach ($registros as $registro) {
            $resultado[] = AsistenciaMapper::toArray($registro);
        }

        return $resultado;
    }

    /**
     * Obtiene un registro de asistencia por ID.
     *
     * @param int $id ID del registro.
     * @return array<string, mixed>
     * @throws RuntimeException Si no se encuentra.
     */
    public function obtenerPorId(int $id): array
    {
        $registro = $this->asistenciaDAO->findById($id);

        if ($registro === null) {
            throw new RuntimeException('Registro de asistencia no encontrado.');
        }

        return AsistenciaMapper::toArray($registro);
    }

    /**
     * Crea un nuevo registro de asistencia.
     *
     * @param array<string, mixed> $data         Datos validados.
     * @param int                  $registradoPor ID del usuario autenticado.
     * @return array<string, mixed> Datos del registro creado.
     * @throws RuntimeException Si hay reglas de negocio violadas.
     */
    public function crear(array $data, int $registradoPor): array
    {
        // Verificar que el culto existe
        $culto = $this->cultoDAO->findById($data['culto_id']);
        if ($culto === null) {
            throw new RuntimeException('El culto indicado no existe.');
        }

        // Verificar que la fecha corresponde al dia del culto
        $diaSemanaFecha = (int) date('w', strtotime($data['fecha']));
        // date('w'): 0=Dom, 1=Lun, ..., 6=Sab
        // DAYOFWEEK MySQL: 1=Dom, 2=Lun, ..., 7=Sab
        $diaSemanaFechaMySQL = $diaSemanaFecha === 0 ? 1 : $diaSemanaFecha + 1;

        if ($diaSemanaFechaMySQL !== $culto->diaSemana) {
            throw new RuntimeException(
                "La fecha {$data['fecha']} no corresponde al dia del culto {$culto->nombre}."
            );
        }

        // Verificar duplicado: no puede haber otro registro del mismo culto en la misma fecha
        if ($this->asistenciaDAO->existsByCultoFecha($data['culto_id'], $data['fecha'])) {
            throw new RuntimeException(
                "Ya existe un registro de asistencia para el culto {$culto->nombre} en la fecha {$data['fecha']}."
            );
        }

        $data['registrado_por'] = $registradoPor;
        $id = $this->asistenciaDAO->insert($data);

        return $this->obtenerPorId($id);
    }

    /**
     * Actualiza un registro de asistencia existente.
     *
     * @param int                  $id   ID del registro.
     * @param array<string, mixed> $data Datos validados.
     * @return array<string, mixed> Datos del registro actualizado.
     * @throws RuntimeException Si no se encuentra o hay reglas violadas.
     */
    public function actualizar(int $id, array $data): array
    {
        $registro = $this->asistenciaDAO->findById($id);
        if ($registro === null) {
            throw new RuntimeException('Registro de asistencia no encontrado.');
        }

        // Verificar que el culto existe
        $culto = $this->cultoDAO->findById($data['culto_id']);
        if ($culto === null) {
            throw new RuntimeException('El culto indicado no existe.');
        }

        // Verificar que la fecha corresponde al dia del culto
        $diaSemanaFecha = (int) date('w', strtotime($data['fecha']));
        $diaSemanaFechaMySQL = $diaSemanaFecha === 0 ? 1 : $diaSemanaFecha + 1;

        if ($diaSemanaFechaMySQL !== $culto->diaSemana) {
            throw new RuntimeException(
                "La fecha {$data['fecha']} no corresponde al dia del culto {$culto->nombre}."
            );
        }

        // Verificar duplicado (excluyendo el registro actual)
        if ($this->asistenciaDAO->existsByCultoFecha($data['culto_id'], $data['fecha'], $id)) {
            throw new RuntimeException(
                "Ya existe otro registro de asistencia para el culto {$culto->nombre} en la fecha {$data['fecha']}."
            );
        }

        $this->asistenciaDAO->update($id, $data);

        return $this->obtenerPorId($id);
    }

    /**
     * Elimina un registro de asistencia (hard delete).
     *
     * @param int $id ID del registro.
     * @return void
     * @throws RuntimeException Si no se encuentra.
     */
    public function eliminar(int $id): void
    {
        $registro = $this->asistenciaDAO->findById($id);
        if ($registro === null) {
            throw new RuntimeException('Registro de asistencia no encontrado.');
        }

        $this->asistenciaDAO->delete($id);
    }
}
