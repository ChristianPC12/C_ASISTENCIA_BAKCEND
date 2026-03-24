<?php
declare(strict_types=1);

/**
 * Clase SetupValidator
 *
 * Valida payloads de setup inicial por tenant.
 */
final class SetupValidator
{
    /**
     * Valida payload de cultos.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateCultos(array $data): array
    {
        if (!isset($data['cultos']) || !is_array($data['cultos'])) {
            throw new InvalidArgumentException('El campo "cultos" es obligatorio y debe ser una lista.');
        }

        if (count($data['cultos']) < 1) {
            throw new InvalidArgumentException('Debe enviar al menos un culto.');
        }

        $cultos = [];
        $codigos = [];
        $ordenes = [];

        foreach (array_values($data['cultos']) as $index => $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException('Cada culto debe ser un objeto valido.');
            }

            $codigo = strtoupper(self::requireString($item, 'codigo', 2, 30));
            if (preg_match('/^[A-Z0-9_]+$/', $codigo) !== 1) {
                throw new InvalidArgumentException('Cada "codigo" de culto debe ser alfanumerico con guion bajo.');
            }

            if (isset($codigos[$codigo])) {
                throw new InvalidArgumentException('No se permiten codigos de culto duplicados.');
            }
            $codigos[$codigo] = true;

            $nombre = self::requireString($item, 'nombre', 3, 80);

            if (!isset($item['dia_semana']) || !is_numeric($item['dia_semana'])) {
                throw new InvalidArgumentException('Cada culto requiere "dia_semana" numerico.');
            }
            $diaSemana = (int) $item['dia_semana'];
            if ($diaSemana < 1 || $diaSemana > 7) {
                throw new InvalidArgumentException('Cada "dia_semana" debe estar entre 1 y 7.');
            }

            $horaInicio = self::requireString($item, 'hora_inicio', 4, 8);
            if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horaInicio) !== 1) {
                throw new InvalidArgumentException('Cada "hora_inicio" debe tener formato HH:MM o HH:MM:SS.');
            }
            if (strlen($horaInicio) === 5) {
                $horaInicio .= ':00';
            }

            $activo = true;
            if (array_key_exists('activo', $item)) {
                $activo = filter_var($item['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($activo === null) {
                    throw new InvalidArgumentException('Cada "activo" de culto debe ser booleano.');
                }
            }

            $orden = $index + 1;
            if (array_key_exists('orden', $item)) {
                if (!is_numeric($item['orden'])) {
                    throw new InvalidArgumentException('Cada "orden" de culto debe ser numerico.');
                }
                $orden = (int) $item['orden'];
            }
            if ($orden < 1 || $orden > 99) {
                throw new InvalidArgumentException('Cada "orden" de culto debe estar entre 1 y 99.');
            }

            if (isset($ordenes[$orden])) {
                throw new InvalidArgumentException('No se permiten ordenes de culto duplicados.');
            }
            $ordenes[$orden] = true;

            $cultos[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'dia_semana' => $diaSemana,
                'hora_inicio' => $horaInicio,
                'activo' => (bool) $activo,
                'orden' => $orden
            ];
        }

        return ['cultos' => $cultos];
    }

    /**
     * Valida payload de procedencias.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateProcedencias(array $data): array
    {
        if (!isset($data['procedencias']) || !is_array($data['procedencias'])) {
            throw new InvalidArgumentException('El campo "procedencias" es obligatorio y debe ser una lista.');
        }

        $count = count($data['procedencias']);
        if ($count < 1 || $count > 10) {
            throw new InvalidArgumentException('Debe enviar entre 1 y 10 procedencias.');
        }

        $procedencias = [];
        $nombres = [];
        $ordenes = [];

        foreach (array_values($data['procedencias']) as $index => $item) {
            $nombre = '';
            $activo = true;
            $orden = $index + 1;

            if (is_string($item)) {
                $nombre = Sanitizer::cleanString($item);
            } elseif (is_array($item)) {
                $nombre = self::requireString($item, 'nombre', 2, 80);

                if (array_key_exists('activo', $item)) {
                    $activo = filter_var($item['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($activo === null) {
                        throw new InvalidArgumentException('Cada "activo" de procedencia debe ser booleano.');
                    }
                }

                if (array_key_exists('orden', $item)) {
                    if (!is_numeric($item['orden'])) {
                        throw new InvalidArgumentException('Cada "orden" de procedencia debe ser numerico.');
                    }
                    $orden = (int) $item['orden'];
                }
            } else {
                throw new InvalidArgumentException('Cada procedencia debe ser texto u objeto valido.');
            }

            $nombreLen = strlen($nombre);
            if ($nombreLen < 2 || $nombreLen > 80) {
                throw new InvalidArgumentException('Cada procedencia debe tener entre 2 y 80 caracteres.');
            }

            $nombreNorm = strtoupper($nombre);
            if (isset($nombres[$nombreNorm])) {
                throw new InvalidArgumentException('No se permiten procedencias duplicadas.');
            }
            $nombres[$nombreNorm] = true;

            if ($orden < 1 || $orden > 99) {
                throw new InvalidArgumentException('Cada "orden" de procedencia debe estar entre 1 y 99.');
            }
            if (isset($ordenes[$orden])) {
                throw new InvalidArgumentException('No se permiten ordenes de procedencia duplicados.');
            }
            $ordenes[$orden] = true;

            $procedencias[] = [
                'nombre' => $nombre,
                'activo' => (bool) $activo,
                'orden' => $orden
            ];
        }

        return ['procedencias' => $procedencias];
    }

    /**
     * Valida payload de metricas.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateMetricas(array $data): array
    {
        if (!isset($data['metricas']) || !is_array($data['metricas'])) {
            throw new InvalidArgumentException('El campo "metricas" es obligatorio y debe ser una lista.');
        }

        if (count($data['metricas']) < 1) {
            throw new InvalidArgumentException('Debe enviar al menos una metrica.');
        }

        $metricas = [];
        $claves = [];
        $categoriasPermitidas = [
            'informacion_culto' => true,
            'composicion_asistentes' => true,
            'procedencia' => true,
            'visitas' => true,
            'permanencia' => true,
            'total_asistentes' => true,
            'observaciones' => true,
            'adicionales' => true
        ];

        foreach (array_values($data['metricas']) as $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException('Cada metrica debe ser un objeto valido.');
            }

            $clave = strtolower(self::requireString($item, 'clave', 2, 80));
            if (preg_match('/^[a-z0-9_]+$/', $clave) !== 1) {
                throw new InvalidArgumentException('Cada "clave" de metrica debe ser alfanumerica con guion bajo.');
            }

            if (isset($claves[$clave])) {
                throw new InvalidArgumentException('No se permiten claves de metrica duplicadas.');
            }
            $claves[$clave] = true;

            $etiqueta = self::requireString($item, 'etiqueta', 2, 120);

            $habilitado = true;
            if (array_key_exists('habilitado', $item)) {
                $habilitado = filter_var($item['habilitado'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($habilitado === null) {
                    throw new InvalidArgumentException('Cada "habilitado" de metrica debe ser booleano.');
                }
            }

            $categoria = 'adicionales';
            if (array_key_exists('categoria', $item)) {
                if (!is_string($item['categoria'])) {
                    throw new InvalidArgumentException('Cada "categoria" de metrica debe ser texto.');
                }
                $categoria = strtolower(Sanitizer::cleanString($item['categoria']));
            }
            if (!isset($categoriasPermitidas[$categoria])) {
                throw new InvalidArgumentException(
                    'La "categoria" de metrica no es valida. Categorias permitidas: '
                    . implode(', ', array_keys($categoriasPermitidas)) . '.'
                );
            }

            $metricas[] = [
                'clave' => $clave,
                'etiqueta' => $etiqueta,
                'habilitado' => (bool) $habilitado,
                'obligatorio' => false,
                'categoria' => $categoria
            ];
        }

        return ['metricas' => $metricas];
    }

    /**
     * @param array<string, mixed> $data
     * @param string               $field
     * @param int                  $min
     * @param int                  $max
     * @return string
     */
    private static function requireString(array $data, string $field, int $min, int $max): string
    {
        if (!array_key_exists($field, $data) || !is_string($data[$field])) {
            throw new InvalidArgumentException('El campo "' . $field . '" es obligatorio.');
        }

        $value = Sanitizer::cleanString($data[$field]);
        $len = strlen($value);
        if ($len < $min || $len > $max) {
            throw new InvalidArgumentException(
                'El campo "' . $field . '" debe tener entre ' . $min . ' y ' . $max . ' caracteres.'
            );
        }

        return $value;
    }
}
