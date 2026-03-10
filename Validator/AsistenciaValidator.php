<?php
declare(strict_types=1);

/**
 * Clase AsistenciaValidator
 *
 * Valida los datos de entrada para registros de asistencia.
 * Soporta payload legacy fijo y payload dinamico por metricas.
 */
final class AsistenciaValidator
{
    /**
     * Valida los datos para crear o actualizar un registro de asistencia.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
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

        // -- modo dinamico --
        if (array_key_exists('metricas', $data)) {
            $validated['metricas'] = self::validateMetricas($data['metricas']);

            // observaciones en modo dinamico (opcional)
            $validated['observaciones'] = null;
            if (!empty($data['observaciones']) && is_string($data['observaciones'])) {
                $validated['observaciones'] = Sanitizer::cleanString($data['observaciones']);
            }

            return $validated;
        }

        // -- modo legacy fijo --
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

        $validated['nombres_visitas_barrio'] = null;
        if (!empty($data['nombres_visitas_barrio']) && is_string($data['nombres_visitas_barrio'])) {
            $validated['nombres_visitas_barrio'] = Sanitizer::cleanString($data['nombres_visitas_barrio']);
        }

        $validated['nombres_visitas_guayabo'] = null;
        if (!empty($data['nombres_visitas_guayabo']) && is_string($data['nombres_visitas_guayabo'])) {
            $validated['nombres_visitas_guayabo'] = Sanitizer::cleanString($data['nombres_visitas_guayabo']);
        }

        $validated['observaciones'] = null;
        if (!empty($data['observaciones']) && is_string($data['observaciones'])) {
            $validated['observaciones'] = Sanitizer::cleanString($data['observaciones']);
        }

        return $validated;
    }

    /**
     * Valida objeto dinamico de metricas.
     *
     * @param mixed $metricasRaw
     * @return array<string, mixed>
     */
    private static function validateMetricas(mixed $metricasRaw): array
    {
        if (!is_array($metricasRaw)) {
            throw new InvalidArgumentException('El campo "metricas" debe ser un objeto JSON.');
        }

        if ($metricasRaw === []) {
            throw new InvalidArgumentException('El campo "metricas" no puede venir vacio.');
        }

        $metricas = [];
        foreach ($metricasRaw as $clave => $valor) {
            if (!is_string($clave)) {
                throw new InvalidArgumentException('Cada clave de "metricas" debe ser texto.');
            }

            $claveNorm = strtolower(Sanitizer::cleanString($clave));
            if ($claveNorm === '' || preg_match('/^[a-z0-9_]{2,80}$/', $claveNorm) !== 1) {
                throw new InvalidArgumentException('Cada clave de "metricas" debe ser alfanumerica con guion bajo.');
            }

            if ($valor === null) {
                $metricas[$claveNorm] = null;
                continue;
            }

            if (is_bool($valor)) {
                $metricas[$claveNorm] = $valor ? 1 : 0;
                continue;
            }

            if (is_int($valor) || is_float($valor) || (is_string($valor) && preg_match('/^\d+$/', trim($valor)) === 1)) {
                $numero = (int) $valor;
                if ($numero < 0) {
                    throw new InvalidArgumentException('Los valores numericos de "metricas" no pueden ser negativos.');
                }
                $metricas[$claveNorm] = $numero;
                continue;
            }

            if (is_string($valor)) {
                $texto = Sanitizer::cleanString($valor);
                if (strlen($texto) > 1000) {
                    throw new InvalidArgumentException('Los valores de texto en "metricas" no pueden exceder 1000 caracteres.');
                }
                $metricas[$claveNorm] = $texto;
                continue;
            }

            throw new InvalidArgumentException('Cada valor de "metricas" debe ser numero, texto, booleano o null.');
        }

        return $metricas;
    }
}
