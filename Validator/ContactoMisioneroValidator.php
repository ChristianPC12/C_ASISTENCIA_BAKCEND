<?php
declare(strict_types=1);

/**
 * Validaciones basicas del modulo de contactos misioneros.
 */
final class ContactoMisioneroValidator
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateUpsert(array $data): array
    {
        return [
            'nombre_completo' => $data['nombre_completo'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'correo' => $data['correo'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'barrio_comunidad' => $data['barrio_comunidad'] ?? null,
            'clasificacion_principal' => $data['clasificacion_principal'] ?? null,
            'es_miembro' => $data['es_miembro'] ?? null,
            'estado_contacto' => $data['estado_contacto'] ?? null,
            'fecha_primer_contacto' => $data['fecha_primer_contacto'] ?? null,
            'fecha_ultimo_contacto' => $data['fecha_ultimo_contacto'] ?? null,
            'origen_principal_clave' => $data['origen_principal_clave'] ?? null,
            'modulo_origen' => $data['modulo_origen'] ?? null,
            'referencia_origen_id' => $data['referencia_origen_id'] ?? null,
            'observaciones_generales' => $data['observaciones_generales'] ?? null
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public static function validateListFilters(array $query): array
    {
        return [
            'q' => isset($query['q']) ? Sanitizer::cleanString((string) $query['q']) : '',
            'estado' => isset($query['estado']) ? strtoupper(Sanitizer::cleanString((string) $query['estado'])) : '',
            'clasificacion' => isset($query['clasificacion'])
                ? strtoupper(Sanitizer::cleanString((string) $query['clasificacion']))
                : '',
            'origen' => isset($query['origen']) ? strtoupper(Sanitizer::cleanString((string) $query['origen'])) : ''
        ];
    }
}
