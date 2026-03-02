<?php
declare(strict_types=1);

/**
 * Clase CultoMapper
 *
 * Mapea filas de la BD a CultoDTO.
 */
final class CultoMapper
{
    /**
     * Convierte una fila de la BD a CultoDTO.
     *
     * @param array<string, mixed> $row Fila de la consulta.
     * @return CultoDTO
     */
    public static function fromRow(array $row): CultoDTO
    {
        $dto = new CultoDTO();
        $dto->id         = (int) $row['id'];
        $dto->codigo     = (string) $row['codigo'];
        $dto->nombre     = (string) $row['nombre'];
        $dto->diaSemana  = (int) $row['dia_semana'];
        $dto->horaInicio = (string) $row['hora_inicio'];

        return $dto;
    }

    /**
     * Convierte un CultoDTO a array para respuesta JSON.
     *
     * @param CultoDTO $dto DTO a convertir.
     * @return array<string, mixed>
     */
    public static function toArray(CultoDTO $dto): array
    {
        return [
            'id'          => $dto->id,
            'codigo'      => $dto->codigo,
            'nombre'      => $dto->nombre,
            'dia_semana'  => $dto->diaSemana,
            'hora_inicio' => $dto->horaInicio
        ];
    }
}
