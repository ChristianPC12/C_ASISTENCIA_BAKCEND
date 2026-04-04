<?php
declare(strict_types=1);

/**
 * Mapper de pequenas congregaciones.
 */
final class PcMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): PcDTO
    {
        $dto = new PcDTO();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->organizacionId = (int) ($row['organizacion_id'] ?? 0);
        $dto->nombrePc = (string) ($row['nombre_pc'] ?? '');
        $dto->sector = isset($row['sector']) && $row['sector'] !== null ? (string) $row['sector'] : null;
        $dto->comunidad = isset($row['comunidad']) && $row['comunidad'] !== null ? (string) $row['comunidad'] : null;
        $dto->direccionReunion = isset($row['direccion_reunion']) && $row['direccion_reunion'] !== null ? (string) $row['direccion_reunion'] : null;
        $dto->anfitrionContactoId = isset($row['anfitrion_contacto_id']) && $row['anfitrion_contacto_id'] !== null ? (int) $row['anfitrion_contacto_id'] : null;
        $dto->anfitrionNombre = isset($row['anfitrion_nombre']) && $row['anfitrion_nombre'] !== null ? (string) $row['anfitrion_nombre'] : null;
        $dto->liderPrincipalContactoId = isset($row['lider_principal_contacto_id']) && $row['lider_principal_contacto_id'] !== null ? (int) $row['lider_principal_contacto_id'] : null;
        $dto->liderPrincipalNombre = isset($row['lider_principal_nombre']) && $row['lider_principal_nombre'] !== null ? (string) $row['lider_principal_nombre'] : null;
        $dto->liderAuxiliarContactoId = isset($row['lider_auxiliar_contacto_id']) && $row['lider_auxiliar_contacto_id'] !== null ? (int) $row['lider_auxiliar_contacto_id'] : null;
        $dto->liderAuxiliarNombre = isset($row['lider_auxiliar_nombre']) && $row['lider_auxiliar_nombre'] !== null ? (string) $row['lider_auxiliar_nombre'] : null;
        $dto->fechaInicio = (string) ($row['fecha_inicio'] ?? '');
        $dto->fechaFin = isset($row['fecha_fin']) && $row['fecha_fin'] !== null ? (string) $row['fecha_fin'] : null;
        $dto->diaReunion = isset($row['dia_reunion']) && $row['dia_reunion'] !== null ? (int) $row['dia_reunion'] : null;
        $dto->horaReunion = isset($row['hora_reunion']) && $row['hora_reunion'] !== null ? (string) $row['hora_reunion'] : null;
        $dto->estado = (string) ($row['estado'] ?? 'ACTIVA');
        $dto->pcMadreId = isset($row['pc_madre_id']) && $row['pc_madre_id'] !== null ? (int) $row['pc_madre_id'] : null;
        $dto->pcMadreNombre = isset($row['pc_madre_nombre']) && $row['pc_madre_nombre'] !== null ? (string) $row['pc_madre_nombre'] : null;
        $dto->motivoCierre = isset($row['motivo_cierre']) && $row['motivo_cierre'] !== null ? (string) $row['motivo_cierre'] : null;
        $dto->metaTrimestral = isset($row['meta_trimestral']) && $row['meta_trimestral'] !== null ? (string) $row['meta_trimestral'] : null;
        $dto->observacionesGenerales = isset($row['observaciones_generales']) && $row['observaciones_generales'] !== null ? (string) $row['observaciones_generales'] : null;
        $dto->creadoPor = isset($row['creado_por']) && $row['creado_por'] !== null ? (int) $row['creado_por'] : null;
        $dto->actualizadoPor = isset($row['actualizado_por']) && $row['actualizado_por'] !== null ? (int) $row['actualizado_por'] : null;
        $dto->eliminadoPor = isset($row['eliminado_por']) && $row['eliminado_por'] !== null ? (int) $row['eliminado_por'] : null;
        $dto->creadoEn = (string) ($row['creado_en'] ?? '');
        $dto->actualizadoEn = (string) ($row['actualizado_en'] ?? '');
        $dto->eliminadoEn = isset($row['eliminado_en']) && $row['eliminado_en'] !== null ? (string) $row['eliminado_en'] : null;

        return $dto;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(PcDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'organizacion_id' => $dto->organizacionId,
            'nombre_pc' => $dto->nombrePc,
            'sector' => $dto->sector,
            'comunidad' => $dto->comunidad,
            'direccion_reunion' => $dto->direccionReunion,
            'anfitrion_contacto_id' => $dto->anfitrionContactoId,
            'anfitrion_nombre' => $dto->anfitrionNombre,
            'lider_principal_contacto_id' => $dto->liderPrincipalContactoId,
            'lider_principal_nombre' => $dto->liderPrincipalNombre,
            'lider_auxiliar_contacto_id' => $dto->liderAuxiliarContactoId,
            'lider_auxiliar_nombre' => $dto->liderAuxiliarNombre,
            'fecha_inicio' => $dto->fechaInicio,
            'fecha_fin' => $dto->fechaFin,
            'dia_reunion' => $dto->diaReunion,
            'hora_reunion' => $dto->horaReunion,
            'estado' => $dto->estado,
            'pc_madre_id' => $dto->pcMadreId,
            'pc_madre_nombre' => $dto->pcMadreNombre,
            'motivo_cierre' => $dto->motivoCierre,
            'meta_trimestral' => $dto->metaTrimestral,
            'observaciones_generales' => $dto->observacionesGenerales,
            'creado_por' => $dto->creadoPor,
            'actualizado_por' => $dto->actualizadoPor,
            'eliminado_por' => $dto->eliminadoPor,
            'creado_en' => $dto->creadoEn,
            'actualizado_en' => $dto->actualizadoEn,
            'eliminado_en' => $dto->eliminadoEn
        ];
    }
}
