<?php
declare(strict_types=1);

/**
 * Validaciones basicas de estudios biblicos.
 */
final class EstudioBiblicoValidator
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateEstudio(array $data): array
    {
        return [
            'persona_nombre' => $data['persona_nombre'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'correo' => $data['correo'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'barrio_comunidad' => $data['barrio_comunidad'] ?? null,
            'origen_clave' => $data['origen_clave'] ?? null,
            'campana_origen_id' => $data['campana_origen_id'] ?? null,
            'pc_origen_id' => $data['pc_origen_id'] ?? null,
            'instructor_principal_nombre' => $data['instructor_principal_nombre'] ?? null,
            'instructor_principal_telefono' => $data['instructor_principal_telefono'] ?? null,
            'instructor_secundario_nombre' => $data['instructor_secundario_nombre'] ?? null,
            'instructor_secundario_telefono' => $data['instructor_secundario_telefono'] ?? null,
            'responsable_usuario_id' => $data['responsable_usuario_id'] ?? null,
            'modalidad' => $data['modalidad'] ?? null,
            'material_estudio' => $data['material_estudio'] ?? null,
            'leccion_actual' => $data['leccion_actual'] ?? null,
            'total_lecciones_completadas' => $data['total_lecciones_completadas'] ?? null,
            'fecha_inicio' => $data['fecha_inicio'] ?? null,
            'fecha_ultima_sesion' => $data['fecha_ultima_sesion'] ?? null,
            'proxima_sesion' => $data['proxima_sesion'] ?? null,
            'estado_general' => $data['estado_general'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'motivo_cierre_pausa' => $data['motivo_cierre_pausa'] ?? null,
            'motivo_reasignacion' => $data['motivo_reasignacion'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateSesion(array $data): array
    {
        return [
            'fecha' => $data['fecha'] ?? null,
            'tema_leccion' => $data['tema_leccion'] ?? null,
            'resumen_breve' => $data['resumen_breve'] ?? null,
            'dudas_surgidas' => $data['dudas_surgidas'] ?? null,
            'asistencia' => $data['asistencia'] ?? null,
            'percepcion_avance' => $data['percepcion_avance'] ?? null,
            'proxima_accion' => $data['proxima_accion'] ?? null,
            'proxima_fecha_sugerida' => $data['proxima_fecha_sugerida'] ?? null,
            'responsable_usuario_id' => $data['responsable_usuario_id'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateDecision(array $data): array
    {
        return [
            'decision_clave' => $data['decision_clave'] ?? null,
            'decision_etiqueta' => $data['decision_etiqueta'] ?? null,
            'fecha_decision' => $data['fecha_decision'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'requiere_seguimiento' => $data['requiere_seguimiento'] ?? null,
            'seguimiento_fecha_limite' => $data['seguimiento_fecha_limite'] ?? null,
            'seguimiento_titulo' => $data['seguimiento_titulo'] ?? null,
            'seguimiento_descripcion' => $data['seguimiento_descripcion'] ?? null,
            'seguimiento_responsable_usuario_id' => $data['seguimiento_responsable_usuario_id'] ?? null,
            'prioridad' => $data['prioridad'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateAsignacion(array $data): array
    {
        return [
            'instructor_principal_nombre' => $data['instructor_principal_nombre'] ?? null,
            'instructor_principal_telefono' => $data['instructor_principal_telefono'] ?? null,
            'instructor_secundario_nombre' => $data['instructor_secundario_nombre'] ?? null,
            'instructor_secundario_telefono' => $data['instructor_secundario_telefono'] ?? null,
            'responsable_usuario_id' => $data['responsable_usuario_id'] ?? null,
            'motivo_cambio' => $data['motivo_cambio'] ?? null,
            'observaciones' => $data['observaciones'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public static function validateFilters(array $query): array
    {
        return [
            'q' => isset($query['q']) ? Sanitizer::cleanString((string) $query['q']) : '',
            'estado_general' => isset($query['estado_general']) ? strtoupper(Sanitizer::cleanString((string) $query['estado_general'])) : '',
            'origen_clave' => isset($query['origen_clave']) ? strtoupper(Sanitizer::cleanString((string) $query['origen_clave'])) : '',
            'responsable_usuario_id' => $query['responsable_usuario_id'] ?? null,
            'fecha_desde' => isset($query['fecha_desde']) ? Sanitizer::cleanString((string) $query['fecha_desde']) : '',
            'fecha_hasta' => isset($query['fecha_hasta']) ? Sanitizer::cleanString((string) $query['fecha_hasta']) : ''
        ];
    }
}
