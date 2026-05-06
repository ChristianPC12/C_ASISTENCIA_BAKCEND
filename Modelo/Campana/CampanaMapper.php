<?php
declare(strict_types=1);

/**
 * Mapper para campanas.
 */
final class CampanaMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): CampanaDTO
    {
        $dto = new CampanaDTO();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->organizacionId = (int) ($row['organizacion_id'] ?? 0);
        $dto->nombre = (string) ($row['nombre'] ?? '');
        $dto->lema = isset($row['lema']) && $row['lema'] !== null ? (string) $row['lema'] : null;
        $dto->tipo = (string) ($row['tipo'] ?? 'SEMANA_EVANGELISTICA');
        $dto->fechaInicio = (string) ($row['fecha_inicio'] ?? '');
        $dto->fechaFin = (string) ($row['fecha_fin'] ?? '');
        $dto->lugar = isset($row['lugar']) && $row['lugar'] !== null ? (string) $row['lugar'] : null;
        $dto->hora = isset($row['hora']) && $row['hora'] !== null ? (string) $row['hora'] : null;
        $dto->predicador = isset($row['predicador']) && $row['predicador'] !== null ? (string) $row['predicador'] : null;
        $dto->responsable = isset($row['responsable']) && $row['responsable'] !== null ? (string) $row['responsable'] : null;
        $dto->responsableUsuarioId = isset($row['responsable_usuario_id']) && $row['responsable_usuario_id'] !== null
            ? (int) $row['responsable_usuario_id']
            : null;
        $dto->responsableUsuarioNombre = isset($row['responsable_usuario_nombre']) && $row['responsable_usuario_nombre'] !== null
            ? (string) $row['responsable_usuario_nombre']
            : null;
        $dto->descripcion = isset($row['descripcion']) && $row['descripcion'] !== null ? (string) $row['descripcion'] : null;
        $dto->estado = (string) ($row['estado'] ?? 'BORRADOR');
        $dto->observaciones = isset($row['observaciones']) && $row['observaciones'] !== null
            ? (string) $row['observaciones']
            : null;
        $dto->creadoPor = isset($row['creado_por']) && $row['creado_por'] !== null ? (int) $row['creado_por'] : null;
        $dto->actualizadoPor = isset($row['actualizado_por']) && $row['actualizado_por'] !== null
            ? (int) $row['actualizado_por']
            : null;
        $dto->eliminadoPor = isset($row['eliminado_por']) && $row['eliminado_por'] !== null
            ? (int) $row['eliminado_por']
            : null;
        $dto->creadoEn = (string) ($row['creado_en'] ?? '');
        $dto->actualizadoEn = (string) ($row['actualizado_en'] ?? '');
        $dto->eliminadoEn = isset($row['eliminado_en']) && $row['eliminado_en'] !== null ? (string) $row['eliminado_en'] : null;

        return $dto;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(CampanaDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'organizacion_id' => $dto->organizacionId,
            'nombre' => $dto->nombre,
            'lema' => $dto->lema,
            'tipo' => $dto->tipo,
            'fecha_inicio' => $dto->fechaInicio,
            'fecha_fin' => $dto->fechaFin,
            'lugar' => $dto->lugar,
            'hora' => $dto->hora,
            'predicador' => $dto->predicador,
            'responsable' => $dto->responsable,
            'responsable_usuario_id' => $dto->responsableUsuarioId,
            'responsable_usuario_nombre' => $dto->responsableUsuarioNombre,
            'descripcion' => $dto->descripcion,
            'estado' => $dto->estado,
            'observaciones' => $dto->observaciones,
            'creado_por' => $dto->creadoPor,
            'actualizado_por' => $dto->actualizadoPor,
            'eliminado_por' => $dto->eliminadoPor,
            'creado_en' => $dto->creadoEn,
            'actualizado_en' => $dto->actualizadoEn,
            'eliminado_en' => $dto->eliminadoEn
        ];
    }
}