<?php
declare(strict_types=1);

/**
 * Validaciones basicas del modulo de juntas.
 */
final class JuntaValidator
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public static function validateJunta(array $data): array
    {
        return [
            'fecha' => $data['fecha'] ?? null,
            'hora_inicio' => $data['hora_inicio'] ?? null,
            'hora_fin' => $data['hora_fin'] ?? null,
            'tipo' => $data['tipo'] ?? null,
            'moderador' => $data['moderador'] ?? null,
            'secretario' => $data['secretario'] ?? null,
            'estado' => $data['estado'] ?? null,
            'observaciones_generales' => $data['observaciones_generales'] ?? null,
            'resumen_general' => $data['resumen_general'] ?? null,
            'quorum_texto' => $data['quorum_texto'] ?? null,
            'junta_anterior_id' => $data['junta_anterior_id'] ?? null
        ];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public static function validatePunto(array $data): array
    {
        return [
            'numero_orden' => $data['numero_orden'] ?? null,
            'titulo' => $data['titulo'] ?? null,
            'departamento_origen' => $data['departamento_origen'] ?? null,
            'presentado_por' => $data['presentado_por'] ?? null,
            'tipo_punto' => $data['tipo_punto'] ?? null,
            'descripcion_base' => $data['descripcion_base'] ?? null,
            'observacion_secretaria' => $data['observacion_secretaria'] ?? null,
            'discusion_resumen' => $data['discusion_resumen'] ?? null,
            'decision_final' => $data['decision_final'] ?? null,
            'estado' => $data['estado'] ?? null,
            'prioridad' => $data['prioridad'] ?? null,
            'confidencial' => $data['confidencial'] ?? null,
            'responsable_seguimiento_usuario_id' => $data['responsable_seguimiento_usuario_id'] ?? null,
            'fecha_limite' => $data['fecha_limite'] ?? null,
            'punto_anterior_id' => $data['punto_anterior_id'] ?? null,
            'pasar_proxima_junta' => $data['pasar_proxima_junta'] ?? null,
            'referencia_modulo' => $data['referencia_modulo'] ?? null,
            'referencia_entidad_id' => $data['referencia_entidad_id'] ?? null
        ];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public static function validateVotacion(array $data): array
    {
        return [
            'requirio_voto' => $data['requirio_voto'] ?? null,
            'tipo_voto' => $data['tipo_voto'] ?? null,
            'texto_voto' => $data['texto_voto'] ?? null,
            'votos_favor' => $data['votos_favor'] ?? null,
            'votos_contra' => $data['votos_contra'] ?? null,
            'abstenciones' => $data['abstenciones'] ?? null,
            'fecha_voto' => $data['fecha_voto'] ?? null,
            'observacion' => $data['observacion'] ?? null,
            'estado_resultante' => $data['estado_resultante'] ?? null
        ];
    }

    /** @param array<string, mixed> $query @return array<string, mixed> */
    public static function validateFilters(array $query): array
    {
        return [
            'q' => isset($query['q']) ? Sanitizer::cleanString((string) $query['q']) : '',
            'estado' => isset($query['estado']) ? strtoupper(Sanitizer::cleanString((string) $query['estado'])) : '',
            'tipo' => isset($query['tipo']) ? strtoupper(Sanitizer::cleanString((string) $query['tipo'])) : '',
            'departamento_origen' => isset($query['departamento_origen']) ? Sanitizer::cleanString((string) $query['departamento_origen']) : '',
            'responsable_usuario_id' => $query['responsable_usuario_id'] ?? null,
            'fecha_desde' => isset($query['fecha_desde']) ? Sanitizer::cleanString((string) $query['fecha_desde']) : '',
            'fecha_hasta' => isset($query['fecha_hasta']) ? Sanitizer::cleanString((string) $query['fecha_hasta']) : '',
            'excluir_junta_id' => $query['excluir_junta_id'] ?? null
        ];
    }
}
