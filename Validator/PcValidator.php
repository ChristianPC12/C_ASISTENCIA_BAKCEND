<?php
declare(strict_types=1);

/**
 * Validaciones basicas de pequenas congregaciones.
 */
final class PcValidator
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validatePc(array $data): array
    {
        return [
            'nombre_pc' => $data['nombre_pc'] ?? null,
            'sector' => $data['sector'] ?? null,
            'comunidad' => $data['comunidad'] ?? null,
            'direccion_reunion' => $data['direccion_reunion'] ?? null,
            'anfitrion_nombre' => $data['anfitrion_nombre'] ?? null,
            'anfitrion_telefono' => $data['anfitrion_telefono'] ?? null,
            'lider_principal_nombre' => $data['lider_principal_nombre'] ?? null,
            'lider_principal_telefono' => $data['lider_principal_telefono'] ?? null,
            'lider_auxiliar_nombre' => $data['lider_auxiliar_nombre'] ?? null,
            'lider_auxiliar_telefono' => $data['lider_auxiliar_telefono'] ?? null,
            'fecha_inicio' => $data['fecha_inicio'] ?? null,
            'fecha_fin' => $data['fecha_fin'] ?? null,
            'dia_reunion' => $data['dia_reunion'] ?? null,
            'hora_reunion' => $data['hora_reunion'] ?? null,
            'estado' => $data['estado'] ?? null,
            'pc_madre_id' => $data['pc_madre_id'] ?? null,
            'motivo_cierre' => $data['motivo_cierre'] ?? null,
            'meta_trimestral' => $data['meta_trimestral'] ?? null,
            'observaciones_generales' => $data['observaciones_generales'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateParticipante(array $data): array
    {
        return [
            'nombre' => $data['nombre'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'clasificacion' => $data['clasificacion'] ?? null,
            'rol_pc' => $data['rol_pc'] ?? null,
            'es_miembro' => $data['es_miembro'] ?? null,
            'fecha_ingreso' => $data['fecha_ingreso'] ?? null,
            'fecha_salida' => $data['fecha_salida'] ?? null,
            'motivo_salida' => $data['motivo_salida'] ?? null,
            'estado_participacion' => $data['estado_participacion'] ?? null,
            'observaciones' => $data['observaciones'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateReunion(array $data): array
    {
        return [
            'fecha' => $data['fecha'] ?? null,
            'tema_titulo' => $data['tema_titulo'] ?? null,
            'material_usado' => $data['material_usado'] ?? null,
            'hubo_estudio_biblico' => $data['hubo_estudio_biblico'] ?? null,
            'hubo_visita' => $data['hubo_visita'] ?? null,
            'cantidad_asistentes' => $data['cantidad_asistentes'] ?? null,
            'total_miembros' => $data['total_miembros'] ?? null,
            'total_visitas' => $data['total_visitas'] ?? null,
            'total_ninos' => $data['total_ninos'] ?? null,
            'total_jovenes' => $data['total_jovenes'] ?? null,
            'total_adultos' => $data['total_adultos'] ?? null,
            'observacion_reunion' => $data['observacion_reunion'] ?? null,
            'decisiones_tomadas' => $data['decisiones_tomadas'] ?? null,
            'proximos_pasos' => $data['proximos_pasos'] ?? null,
            'responsable_seguimiento_usuario_id' => $data['responsable_seguimiento_usuario_id'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateAsistenciaReunion(array $data): array
    {
        return [
            'participante_id' => $data['participante_id'] ?? null,
            'asistio' => $data['asistio'] ?? null,
            'clasificacion_dia' => $data['clasificacion_dia'] ?? null,
            'observaciones' => $data['observaciones'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateResultado(array $data): array
    {
        return [
            'fecha' => $data['fecha'] ?? null,
            'tipo_resultado' => $data['tipo_resultado'] ?? null,
            'contacto_nombre' => $data['contacto_nombre'] ?? null,
            'contacto_telefono' => $data['contacto_telefono'] ?? null,
            'estudio_biblico_id' => $data['estudio_biblico_id'] ?? null,
            'cantidad' => $data['cantidad'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'observaciones' => $data['observaciones'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateLiderazgo(array $data): array
    {
        return [
            'nombre' => $data['nombre'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'rol_liderazgo' => $data['rol_liderazgo'] ?? null,
            'fecha_inicio' => $data['fecha_inicio'] ?? null,
            'fecha_fin' => $data['fecha_fin'] ?? null,
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
            'estado' => isset($query['estado']) ? strtoupper(Sanitizer::cleanString((string) $query['estado'])) : '',
            'sector' => isset($query['sector']) ? Sanitizer::cleanString((string) $query['sector']) : '',
            'fecha_desde' => isset($query['fecha_desde']) ? Sanitizer::cleanString((string) $query['fecha_desde']) : '',
            'fecha_hasta' => isset($query['fecha_hasta']) ? Sanitizer::cleanString((string) $query['fecha_hasta']) : '',
            'sin_reunion_dias' => $query['sin_reunion_dias'] ?? null
        ];
    }
}
