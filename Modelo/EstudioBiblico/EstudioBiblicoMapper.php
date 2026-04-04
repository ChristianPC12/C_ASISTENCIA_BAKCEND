<?php
declare(strict_types=1);

/**
 * Mapper de estudios biblicos.
 */
final class EstudioBiblicoMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): EstudioBiblicoDTO
    {
        $dto = new EstudioBiblicoDTO();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->organizacionId = (int) ($row['organizacion_id'] ?? 0);
        $dto->contactoId = (int) ($row['contacto_id'] ?? 0);
        $dto->contactoNombre = (string) ($row['contacto_nombre'] ?? $row['nombre_completo'] ?? '');
        $dto->contactoTelefono = isset($row['contacto_telefono']) && $row['contacto_telefono'] !== null ? (string) $row['contacto_telefono'] : null;
        $dto->origenClave = isset($row['origen_clave']) && $row['origen_clave'] !== null ? (string) $row['origen_clave'] : null;
        $dto->campanaOrigenId = isset($row['campana_origen_id']) && $row['campana_origen_id'] !== null ? (int) $row['campana_origen_id'] : null;
        $dto->campanaOrigenNombre = isset($row['campana_origen_nombre']) && $row['campana_origen_nombre'] !== null ? (string) $row['campana_origen_nombre'] : null;
        $dto->pcOrigenId = isset($row['pc_origen_id']) && $row['pc_origen_id'] !== null ? (int) $row['pc_origen_id'] : null;
        $dto->instructorPrincipalContactoId = isset($row['instructor_principal_contacto_id']) && $row['instructor_principal_contacto_id'] !== null ? (int) $row['instructor_principal_contacto_id'] : null;
        $dto->instructorPrincipalNombre = isset($row['instructor_principal_nombre']) && $row['instructor_principal_nombre'] !== null ? (string) $row['instructor_principal_nombre'] : null;
        $dto->instructorSecundarioContactoId = isset($row['instructor_secundario_contacto_id']) && $row['instructor_secundario_contacto_id'] !== null ? (int) $row['instructor_secundario_contacto_id'] : null;
        $dto->instructorSecundarioNombre = isset($row['instructor_secundario_nombre']) && $row['instructor_secundario_nombre'] !== null ? (string) $row['instructor_secundario_nombre'] : null;
        $dto->responsableUsuarioId = isset($row['responsable_usuario_id']) && $row['responsable_usuario_id'] !== null ? (int) $row['responsable_usuario_id'] : null;
        $dto->responsableUsuarioNombre = isset($row['responsable_usuario_nombre']) && $row['responsable_usuario_nombre'] !== null ? (string) $row['responsable_usuario_nombre'] : null;
        $dto->modalidad = (string) ($row['modalidad'] ?? 'INDIVIDUAL');
        $dto->materialEstudio = isset($row['material_estudio']) && $row['material_estudio'] !== null ? (string) $row['material_estudio'] : null;
        $dto->leccionActual = isset($row['leccion_actual']) && $row['leccion_actual'] !== null ? (string) $row['leccion_actual'] : null;
        $dto->totalLeccionesCompletadas = (int) ($row['total_lecciones_completadas'] ?? 0);
        $dto->fechaInicio = (string) ($row['fecha_inicio'] ?? '');
        $dto->fechaUltimaSesion = isset($row['fecha_ultima_sesion']) && $row['fecha_ultima_sesion'] !== null ? (string) $row['fecha_ultima_sesion'] : null;
        $dto->proximaSesion = isset($row['proxima_sesion']) && $row['proxima_sesion'] !== null ? (string) $row['proxima_sesion'] : null;
        $dto->estadoGeneral = (string) ($row['estado_general'] ?? 'NUEVO');
        $dto->observaciones = isset($row['observaciones']) && $row['observaciones'] !== null ? (string) $row['observaciones'] : null;
        $dto->motivoCierrePausa = isset($row['motivo_cierre_pausa']) && $row['motivo_cierre_pausa'] !== null ? (string) $row['motivo_cierre_pausa'] : null;
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
    public static function toArray(EstudioBiblicoDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'organizacion_id' => $dto->organizacionId,
            'contacto_id' => $dto->contactoId,
            'contacto_nombre' => $dto->contactoNombre,
            'contacto_telefono' => $dto->contactoTelefono,
            'origen_clave' => $dto->origenClave,
            'campana_origen_id' => $dto->campanaOrigenId,
            'campana_origen_nombre' => $dto->campanaOrigenNombre,
            'pc_origen_id' => $dto->pcOrigenId,
            'instructor_principal_contacto_id' => $dto->instructorPrincipalContactoId,
            'instructor_principal_nombre' => $dto->instructorPrincipalNombre,
            'instructor_secundario_contacto_id' => $dto->instructorSecundarioContactoId,
            'instructor_secundario_nombre' => $dto->instructorSecundarioNombre,
            'responsable_usuario_id' => $dto->responsableUsuarioId,
            'responsable_usuario_nombre' => $dto->responsableUsuarioNombre,
            'modalidad' => $dto->modalidad,
            'material_estudio' => $dto->materialEstudio,
            'leccion_actual' => $dto->leccionActual,
            'total_lecciones_completadas' => $dto->totalLeccionesCompletadas,
            'fecha_inicio' => $dto->fechaInicio,
            'fecha_ultima_sesion' => $dto->fechaUltimaSesion,
            'proxima_sesion' => $dto->proximaSesion,
            'estado_general' => $dto->estadoGeneral,
            'observaciones' => $dto->observaciones,
            'motivo_cierre_pausa' => $dto->motivoCierrePausa,
            'creado_por' => $dto->creadoPor,
            'actualizado_por' => $dto->actualizadoPor,
            'eliminado_por' => $dto->eliminadoPor,
            'creado_en' => $dto->creadoEn,
            'actualizado_en' => $dto->actualizadoEn,
            'eliminado_en' => $dto->eliminadoEn
        ];
    }
}
