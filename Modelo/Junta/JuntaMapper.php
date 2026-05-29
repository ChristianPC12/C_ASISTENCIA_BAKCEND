<?php
declare(strict_types=1);

/**
 * Mapper de juntas de iglesia.
 */
final class JuntaMapper
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): JuntaDTO
    {
        return new JuntaDTO(
            isset($row['id']) ? (int) $row['id'] : null,
            (int) $row['organizacion_id'],
            (string) $row['fecha'],
            isset($row['hora_inicio']) ? (string) $row['hora_inicio'] : null,
            isset($row['hora_fin']) ? (string) $row['hora_fin'] : null,
            (string) ($row['tipo'] ?? 'PRESENCIAL'),
            isset($row['moderador']) ? (string) $row['moderador'] : null,
            isset($row['secretario']) ? (string) $row['secretario'] : null,
            (string) ($row['estado'] ?? 'BORRADOR'),
            isset($row['observaciones_generales']) ? (string) $row['observaciones_generales'] : null,
            isset($row['resumen_general']) ? (string) $row['resumen_general'] : null,
            isset($row['quorum_texto']) ? (string) $row['quorum_texto'] : null,
            isset($row['junta_anterior_id']) ? (int) $row['junta_anterior_id'] : null,
            isset($row['junta_anterior_fecha']) ? (string) $row['junta_anterior_fecha'] : null,
            isset($row['creado_por']) ? (int) $row['creado_por'] : null,
            isset($row['actualizado_por']) ? (int) $row['actualizado_por'] : null,
            isset($row['eliminado_por']) ? (int) $row['eliminado_por'] : null,
            isset($row['creado_en']) ? (string) $row['creado_en'] : null,
            isset($row['actualizado_en']) ? (string) $row['actualizado_en'] : null,
            isset($row['eliminado_en']) ? (string) $row['eliminado_en'] : null
        );
    }

    /** @return array<string, mixed> */
    public static function toArray(JuntaDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'organizacion_id' => $dto->organizacionId,
            'fecha' => $dto->fecha,
            'hora_inicio' => $dto->horaInicio,
            'hora_fin' => $dto->horaFin,
            'tipo' => $dto->tipo,
            'moderador' => $dto->moderador,
            'secretario' => $dto->secretario,
            'estado' => $dto->estado,
            'observaciones_generales' => $dto->observacionesGenerales,
            'resumen_general' => $dto->resumenGeneral,
            'quorum_texto' => $dto->quorumTexto,
            'junta_anterior_id' => $dto->juntaAnteriorId,
            'junta_anterior_fecha' => $dto->juntaAnteriorFecha,
            'creado_por' => $dto->creadoPor,
            'actualizado_por' => $dto->actualizadoPor,
            'eliminado_por' => $dto->eliminadoPor,
            'creado_en' => $dto->creadoEn,
            'actualizado_en' => $dto->actualizadoEn,
            'eliminado_en' => $dto->eliminadoEn
        ];
    }
}
