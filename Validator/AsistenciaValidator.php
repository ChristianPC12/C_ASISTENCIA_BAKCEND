<?php
declare(strict_types=1);

/**
 * Clase AsistenciaValidator
 *
 * Valida los datos de entrada para registros de asistencia.
 * Solo valida/sanitiza; no toca BD ni logica de negocio.
 */
final class AsistenciaValidator
{
    /**
     * Valida los datos para crear o actualizar un registro de asistencia.
     *
     * @param array<string, mixed> $data Datos del body JSON.
     * @return array<string, mixed> Datos validados.
     * @throws InvalidArgumentException Si algun campo es invalido.
     */
    public static function validate(array $data): array
    {
        $validated = [];

        // -- culto_id --
        if (!isset($data['culto_id']) || !is_numeric($data['culto_id'])) {
            throw new InvalidArgumentException('El campo "culto_id" es obligatorio y debe ser numerico.');
        }
        $validated['culto_id'] = (int) $data['culto_id'];

        // -- fecha --
        if (empty($data['fecha']) || !is_string($data['fecha'])) {
            throw new InvalidArgumentException('El campo "fecha" es obligatorio (formato: YYYY-MM-DD).');
        }
        $fecha = \DateTime::createFromFormat('Y-m-d', $data['fecha']);
        if ($fecha === false || $fecha->format('Y-m-d') !== $data['fecha']) {
            throw new InvalidArgumentException('El campo "fecha" debe tener formato YYYY-MM-DD valido.');
        }
        $validated['fecha'] = $data['fecha'];

        // -- Contadores (todos deben ser enteros >= 0) --
        $contadores = [
            'llegaron_antes_hora',
            'llegaron_despues_hora',
            'ninos',
            'jovenes',
            'total_asistentes',
            'proc_barrio',
            'proc_guayabo',
            'visitas_barrio',
            'visitas_guayabo',
            'retiros_antes_terminar',
            'se_quedaron_todo'
        ];

        foreach ($contadores as $campo) {
            if (!isset($data[$campo]) || !is_numeric($data[$campo])) {
                throw new InvalidArgumentException("El campo \"{$campo}\" es obligatorio y debe ser numerico.");
            }

            $valor = (int) $data[$campo];
            if ($valor < 0) {
                throw new InvalidArgumentException("El campo \"{$campo}\" no puede ser negativo.");
            }

            $validated[$campo] = $valor;
        }

        // -- Reglas de consistencia --
        if ($validated['total_asistentes'] < ($validated['ninos'] + $validated['jovenes'])) {
            throw new InvalidArgumentException(
                'El total de asistentes no puede ser menor que la suma de ninos y jovenes.'
            );
        }

        $sumaPermanencia = $validated['retiros_antes_terminar'] + $validated['se_quedaron_todo'];
        if ($sumaPermanencia > $validated['total_asistentes']) {
            throw new InvalidArgumentException(
                'La suma de retiros y quienes se quedaron no puede superar el total de asistentes.'
            );
        }

        // -- nombres de visitas (opcionales, texto libre) --
        $validated['nombres_visitas_barrio'] = null;
        if (!empty($data['nombres_visitas_barrio']) && is_string($data['nombres_visitas_barrio'])) {
            $validated['nombres_visitas_barrio'] = Sanitizer::cleanString($data['nombres_visitas_barrio']);
        }

        $validated['nombres_visitas_guayabo'] = null;
        if (!empty($data['nombres_visitas_guayabo']) && is_string($data['nombres_visitas_guayabo'])) {
            $validated['nombres_visitas_guayabo'] = Sanitizer::cleanString($data['nombres_visitas_guayabo']);
        }

        // -- observaciones (opcional) --
        $validated['observaciones'] = null;
        if (!empty($data['observaciones']) && is_string($data['observaciones'])) {
            $validated['observaciones'] = Sanitizer::cleanString($data['observaciones']);
        }

        return $validated;
    }
}
