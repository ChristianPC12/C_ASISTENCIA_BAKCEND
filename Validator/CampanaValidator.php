<?php
declare(strict_types=1);

/**
 * Validaciones del modulo de campanas.
 */
final class CampanaValidator
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateCampana(array $data): array
    {
        return [
            'nombre' => $data['nombre'] ?? null,
            'lema' => $data['lema'] ?? null,
            'tipo' => $data['tipo'] ?? null,
            'fecha_inicio' => $data['fecha_inicio'] ?? null,
            'fecha_fin' => $data['fecha_fin'] ?? null,
            'lugar' => $data['lugar'] ?? null,
            'hora' => $data['hora'] ?? null,
            'predicador' => $data['predicador'] ?? null,
            'responsable' => $data['responsable'] ?? null,
            'responsable_usuario_id' => $data['responsable_usuario_id'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'estado' => $data['estado'] ?? null,
            'observaciones' => $data['observaciones'] ?? null
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
            'hora_inicio' => $data['hora_inicio'] ?? null,
            'tema_titulo' => $data['tema_titulo'] ?? null,
            'predicador_noche' => $data['predicador_noche'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'estado_sesion' => $data['estado_sesion'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateAsistente(array $data): array
    {
        return [
            'nombre_completo' => $data['nombre_completo'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'telefono_internacional' => $data['telefono_internacional'] ?? null,
            'correo' => $data['correo'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'barrio_comunidad' => $data['barrio_comunidad'] ?? null,
            'procedencia' => $data['procedencia'] ?? null,
            'tipo_asistente' => $data['tipo_asistente'] ?? null,
            'clasificacion_etaria' => $data['clasificacion_etaria'] ?? null,
            'invitado_por_contacto_id' => $data['invitado_por_contacto_id'] ?? null,
            'primera_vez' => $data['primera_vez'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'estado_seguimiento' => $data['estado_seguimiento'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateAsistenciaSesion(array $data): array
    {
        return [
            'campana_asistente_id' => $data['campana_asistente_id'] ?? null,
            'asistio' => $data['asistio'] ?? null,
            'hora_llegada' => $data['hora_llegada'] ?? null,
            'puntual' => $data['puntual'] ?? null,
            'elegible_premio' => $data['elegible_premio'] ?? null,
            'observaciones' => $data['observaciones'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateDecision(array $data): array
    {
        return [
            'campana_asistente_id' => $data['campana_asistente_id'] ?? null,
            'decision_clave' => $data['decision_clave'] ?? null,
            'decision_etiqueta' => $data['decision_etiqueta'] ?? null,
            'fecha_decision' => $data['fecha_decision'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'estudio_biblico_id' => $data['estudio_biblico_id'] ?? null,
            'seguimiento_tarea_id' => $data['seguimiento_tarea_id'] ?? null
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
            'responsable_usuario_id' => $query['responsable_usuario_id'] ?? null,
            'fecha_desde' => isset($query['fecha_desde']) ? Sanitizer::cleanString((string) $query['fecha_desde']) : '',
            'fecha_hasta' => isset($query['fecha_hasta']) ? Sanitizer::cleanString((string) $query['fecha_hasta']) : ''
        ];
    }
}
