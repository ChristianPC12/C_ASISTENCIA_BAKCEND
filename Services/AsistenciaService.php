<?php
declare(strict_types=1);

/**
 * Clase AsistenciaService
 *
 * Logica de negocio para registros de asistencia.
 * No toca $_SERVER, $_POST, $_GET.
 */
final class AsistenciaService
{
    /** @var AsistenciaDAO */
    private AsistenciaDAO $asistenciaDAO;

    /** @var CultoDAO */
    private CultoDAO $cultoDAO;

    public function __construct()
    {
        $this->asistenciaDAO = new AsistenciaDAO();
        $this->cultoDAO      = new CultoDAO();
    }

    /**
     * Lista registros de asistencia con filtros opcionales.
     *
     * @param array<string, mixed> $filtros Filtros: culto (codigo), anio, trimestre, mes, fecha_exacta.
     * @return array<int, array<string, mixed>>
     */
    public function listar(array $filtros): array
    {
        $filtrosDAO = [];

        // Convertir codigo de culto a culto_id
        if (!empty($filtros['culto'])) {
            $culto = $this->cultoDAO->findByCodigo(strtoupper((string) $filtros['culto']));
            if ($culto !== null) {
                $filtrosDAO['culto_id'] = $culto->id;
            }
        } elseif (!empty($filtros['culto_id'])) {
            $filtrosDAO['culto_id'] = (int) $filtros['culto_id'];
        }

        if (!empty($filtros['anio'])) {
            $filtrosDAO['anio'] = (int) $filtros['anio'];
        }

        if (!empty($filtros['trimestre'])) {
            $filtrosDAO['trimestre'] = (int) $filtros['trimestre'];
        }

        if (!empty($filtros['mes'])) {
            $filtrosDAO['mes'] = (int) $filtros['mes'];
        }

        if (!empty($filtros['fecha_exacta'])) {
            $filtrosDAO['fecha_exacta'] = $this->normalizarFechaExacta((string) $filtros['fecha_exacta']);
        }

        $registros = $this->asistenciaDAO->findAll($filtrosDAO);
        $resultado = [];

        foreach ($registros as $registro) {
            $resultado[] = AsistenciaMapper::toArray($registro);
        }

        return $resultado;
    }

    /**
     * Obtiene estadisticas agregadas del periodo filtrado.
     * Reglas:
     * - anio obligatorio
     * - culto obligatorio
     * - mes y trimestre no se combinan (mes tiene prioridad)
     *
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    public function obtenerEstadisticas(array $filtros): array
    {
        $anio = (int) ($filtros['anio'] ?? 0);
        if ($anio <= 0) {
            throw new InvalidArgumentException('El filtro anio es obligatorio.');
        }

        $cultoCodigo = strtoupper(trim((string) ($filtros['culto'] ?? '')));
        if ($cultoCodigo === '') {
            throw new InvalidArgumentException('El filtro culto es obligatorio.');
        }

        $culto = $this->cultoDAO->findByCodigo($cultoCodigo);
        if ($culto === null) {
            throw new InvalidArgumentException('El culto indicado no existe.');
        }

        $filtrosConsulta = [
            'anio' => $anio,
            'culto' => $cultoCodigo
        ];

        $mes = !empty($filtros['mes']) ? (int) $filtros['mes'] : null;
        $trimestre = !empty($filtros['trimestre']) ? (int) $filtros['trimestre'] : null;

        if ($mes !== null) {
            if ($mes < 1 || $mes > 12) {
                throw new InvalidArgumentException('El filtro mes debe estar entre 1 y 12.');
            }
            $filtrosConsulta['mes'] = $mes;
        } elseif ($trimestre !== null) {
            if ($trimestre < 1 || $trimestre > 4) {
                throw new InvalidArgumentException('El filtro trimestre debe estar entre 1 y 4.');
            }
            $filtrosConsulta['trimestre'] = $trimestre;
        }

        $registros = $this->listar($filtrosConsulta);
        $totalRegistros = count($registros);

        $totalAsistentes = 0;
        $maximoAsistentes = 0;
        $minimoAsistentes = 0;
        $totalNinos = 0;
        $totalJovenes = 0;
        $totalAntes = 0;
        $totalDespues = 0;
        $totalBarrio = 0;
        $totalGuayabo = 0;
        $totalVisitasBarrio = 0;
        $totalVisitasGuayabo = 0;
        $seriesPorFecha = [];
        $frecuenciaNombres = [];

        foreach ($registros as $registro) {
            $asistentes = (int) ($registro['total_asistentes'] ?? 0);
            $ninos = (int) ($registro['ninos'] ?? 0);
            $jovenes = (int) ($registro['jovenes'] ?? 0);
            $antes = (int) ($registro['llegaron_antes_hora'] ?? 0);
            $despues = (int) ($registro['llegaron_despues_hora'] ?? 0);
            $barrio = (int) ($registro['proc_barrio'] ?? 0);
            $guayabo = (int) ($registro['proc_guayabo'] ?? 0);
            $visitasBarrio = (int) ($registro['visitas_barrio'] ?? 0);
            $visitasGuayabo = (int) ($registro['visitas_guayabo'] ?? 0);

            $totalAsistentes += $asistentes;
            $totalNinos += $ninos;
            $totalJovenes += $jovenes;
            $totalAntes += $antes;
            $totalDespues += $despues;
            $totalBarrio += $barrio;
            $totalGuayabo += $guayabo;
            $totalVisitasBarrio += $visitasBarrio;
            $totalVisitasGuayabo += $visitasGuayabo;

            if ($totalRegistros > 0) {
                if ($maximoAsistentes < $asistentes) {
                    $maximoAsistentes = $asistentes;
                }
                if ($minimoAsistentes === 0 || $asistentes < $minimoAsistentes) {
                    $minimoAsistentes = $asistentes;
                }
            }

            $fecha = (string) ($registro['fecha'] ?? '');
            if ($fecha !== '') {
                if (!isset($seriesPorFecha[$fecha])) {
                    $seriesPorFecha[$fecha] = 0;
                }
                $seriesPorFecha[$fecha] += $asistentes;
            }

            $this->acumularNombres($frecuenciaNombres, (string) ($registro['nombres_visitas_barrio'] ?? ''));
            $this->acumularNombres($frecuenciaNombres, (string) ($registro['nombres_visitas_guayabo'] ?? ''));
        }

        ksort($seriesPorFecha);
        $serieAsistencia = [];
        foreach ($seriesPorFecha as $fecha => $total) {
            $serieAsistencia[] = [
                'fecha' => $fecha,
                'total_asistentes' => $total
            ];
        }

        arsort($frecuenciaNombres);
        $topNombres = [];
        foreach (array_slice($frecuenciaNombres, 0, 10, true) as $nombre => $cantidad) {
            $topNombres[] = [
                'nombre' => $nombre,
                'cantidad' => $cantidad
            ];
        }

        $baseComposicion = $totalNinos + $totalJovenes;
        $basePuntualidad = $totalAntes + $totalDespues;
        $baseProcedencia = $totalBarrio + $totalGuayabo;
        $totalVisitas = $totalVisitasBarrio + $totalVisitasGuayabo;
        $promedio = $totalRegistros > 0 ? round($totalAsistentes / $totalRegistros, 2) : 0;

        $puntualidadDespues = $this->porcentaje($totalDespues, $basePuntualidad);
        $puntualidadAntes = $this->porcentaje($totalAntes, $basePuntualidad);
        $procedenciaBarrio = $this->porcentaje($totalBarrio, $baseProcedencia);
        $procedenciaGuayabo = $this->porcentaje($totalGuayabo, $baseProcedencia);
        $periodo = $this->etiquetaPeriodo($filtrosConsulta, $this->normalizarNombreCulto((string) $culto->nombre));

        $resumenCondensado = $totalRegistros === 0
            ? $periodo . ' -> sin registros para mostrar estadisticas.'
            : sprintf(
                '%s -> %.2f%% tarde y %.2f%% temprano. Procedencia: %.2f%% Barrio y %.2f%% Guayabo.',
                $periodo,
                $puntualidadDespues,
                $puntualidadAntes,
                $procedenciaBarrio,
                $procedenciaGuayabo
            );

        return [
            'filtros_aplicados' => [
                'anio' => $anio,
                'trimestre' => $filtrosConsulta['trimestre'] ?? null,
                'mes' => $filtrosConsulta['mes'] ?? null,
                'culto' => $cultoCodigo,
                'culto_nombre' => $this->normalizarNombreCulto((string) $culto->nombre)
            ],
            'resumen_general' => [
                'total_cultos_registrados' => $totalRegistros,
                'total_asistentes' => $totalAsistentes,
                'promedio_por_culto' => $promedio,
                'maximo_asistentes' => $maximoAsistentes,
                'minimo_asistentes' => $minimoAsistentes
            ],
            'composicion_asistentes' => [
                'ninos' => [
                    'cantidad' => $totalNinos,
                    'porcentaje' => $this->porcentaje($totalNinos, $baseComposicion)
                ],
                'jovenes' => [
                    'cantidad' => $totalJovenes,
                    'porcentaje' => $this->porcentaje($totalJovenes, $baseComposicion)
                ]
            ],
            'puntualidad' => [
                'antes' => [
                    'cantidad' => $totalAntes,
                    'porcentaje' => $puntualidadAntes
                ],
                'despues' => [
                    'cantidad' => $totalDespues,
                    'porcentaje' => $puntualidadDespues
                ]
            ],
            'procedencia' => [
                'barrio' => [
                    'cantidad' => $totalBarrio,
                    'porcentaje' => $procedenciaBarrio
                ],
                'guayabo' => [
                    'cantidad' => $totalGuayabo,
                    'porcentaje' => $procedenciaGuayabo
                ]
            ],
            'visitas' => [
                'total_visitas' => $totalVisitas,
                'barrio' => [
                    'cantidad' => $totalVisitasBarrio,
                    'porcentaje' => $this->porcentaje($totalVisitasBarrio, $totalVisitas)
                ],
                'guayabo' => [
                    'cantidad' => $totalVisitasGuayabo,
                    'porcentaje' => $this->porcentaje($totalVisitasGuayabo, $totalVisitas)
                ],
                'top_nombres' => $topNombres
            ],
            'series' => [
                'asistencia_por_fecha' => $serieAsistencia
            ],
            'resumen_condensado' => $resumenCondensado
        ];
    }

    private function normalizarFechaExacta(string $valor): string
    {
        $fecha = trim($valor);
        if ($fecha === '') {
            return '';
        }

        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $fecha) === 1) {
            [$dia, $mes, $anio] = array_map('intval', explode('/', $fecha));
            if (checkdate($mes, $dia, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }
        }

        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $fecha) === 1) {
            [$anio, $mes, $dia] = array_map('intval', explode('-', $fecha));
            if (checkdate($mes, $dia, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }
        }

        return $fecha;
    }

    /**
     * @param array<string, int> $acumulador
     */
    private function acumularNombres(array &$acumulador, string $lista): void
    {
        $texto = trim($lista);
        if ($texto === '') {
            return;
        }

        $texto = str_replace([';', "\n", "\r"], ',', $texto);
        $texto = preg_replace('/\s+y\s+/iu', ',', $texto) ?? $texto;
        $partes = preg_split('/,/', $texto);
        if ($partes === false) {
            return;
        }

        foreach ($partes as $parte) {
            $nombre = trim((string) $parte);
            if ($nombre === '') {
                continue;
            }

            $nombreNormalizado = preg_replace('/\s+/', ' ', $nombre);
            if ($nombreNormalizado === null || $nombreNormalizado === '') {
                continue;
            }

            if (!isset($acumulador[$nombreNormalizado])) {
                $acumulador[$nombreNormalizado] = 0;
            }
            $acumulador[$nombreNormalizado]++;
        }
    }

    private function porcentaje(int $cantidad, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($cantidad / $total) * 100, 2);
    }

    /**
     * @param array<string, mixed> $filtros
     */
    private function etiquetaPeriodo(array $filtros, string $cultoNombre): string
    {
        $anio = (int) ($filtros['anio'] ?? date('Y'));
        $mes = !empty($filtros['mes']) ? (int) $filtros['mes'] : null;
        $trimestre = !empty($filtros['trimestre']) ? (int) $filtros['trimestre'] : null;

        if ($mes !== null) {
            $meses = [
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre'
            ];
            $etiquetaMes = $meses[$mes] ?? ('Mes ' . $mes);
            return $anio . ', ' . $etiquetaMes . ', ' . $cultoNombre;
        }

        if ($trimestre !== null) {
            return $anio . ', Trimestre ' . $trimestre . ', ' . $cultoNombre;
        }

        return $anio . ', ' . $cultoNombre;
    }

    private function normalizarNombreCulto(string $nombre): string
    {
        return str_replace(
            ['Sabado', 'Miercoles'],
            ['Sábado', 'Miércoles'],
            $nombre
        );
    }

    /**
     * Obtiene un registro de asistencia por ID.
     *
     * @param int $id ID del registro.
     * @return array<string, mixed>
     * @throws RuntimeException Si no se encuentra.
     */
    public function obtenerPorId(int $id): array
    {
        $registro = $this->asistenciaDAO->findById($id);

        if ($registro === null) {
            throw new RuntimeException('Registro de asistencia no encontrado.');
        }

        return AsistenciaMapper::toArray($registro);
    }

    /**
     * Crea un nuevo registro de asistencia.
     *
     * @param array<string, mixed> $data         Datos validados.
     * @param int                  $registradoPor ID del usuario autenticado.
     * @return array<string, mixed> Datos del registro creado.
     * @throws RuntimeException Si hay reglas de negocio violadas.
     */
    public function crear(array $data, int $registradoPor): array
    {
        // Verificar que el culto existe
        $culto = $this->cultoDAO->findById($data['culto_id']);
        if ($culto === null) {
            throw new RuntimeException('El culto indicado no existe.');
        }

        // Verificar que la fecha corresponde al dia del culto
        $diaSemanaFecha = (int) date('w', strtotime($data['fecha']));
        // date('w'): 0=Dom, 1=Lun, ..., 6=Sab
        // DAYOFWEEK MySQL: 1=Dom, 2=Lun, ..., 7=Sab
        $diaSemanaFechaMySQL = $diaSemanaFecha === 0 ? 1 : $diaSemanaFecha + 1;

        if ($diaSemanaFechaMySQL !== $culto->diaSemana) {
            throw new RuntimeException(
                "La fecha {$data['fecha']} no corresponde al dia del culto {$culto->nombre}."
            );
        }

        // Verificar duplicado: no puede haber otro registro del mismo culto en la misma fecha
        if ($this->asistenciaDAO->existsByCultoFecha($data['culto_id'], $data['fecha'])) {
            throw new RuntimeException(
                "Ya existe un registro de asistencia para el culto {$culto->nombre} en la fecha {$data['fecha']}."
            );
        }

        $data['registrado_por'] = $registradoPor;
        $id = $this->asistenciaDAO->insert($data);

        return $this->obtenerPorId($id);
    }

    /**
     * Actualiza un registro de asistencia existente.
     *
     * @param int                  $id   ID del registro.
     * @param array<string, mixed> $data Datos validados.
     * @return array<string, mixed> Datos del registro actualizado.
     * @throws RuntimeException Si no se encuentra o hay reglas violadas.
     */
    public function actualizar(int $id, array $data): array
    {
        $registro = $this->asistenciaDAO->findById($id);
        if ($registro === null) {
            throw new RuntimeException('Registro de asistencia no encontrado.');
        }

        // Verificar que el culto existe
        $culto = $this->cultoDAO->findById($data['culto_id']);
        if ($culto === null) {
            throw new RuntimeException('El culto indicado no existe.');
        }

        // Verificar que la fecha corresponde al dia del culto
        $diaSemanaFecha = (int) date('w', strtotime($data['fecha']));
        $diaSemanaFechaMySQL = $diaSemanaFecha === 0 ? 1 : $diaSemanaFecha + 1;

        if ($diaSemanaFechaMySQL !== $culto->diaSemana) {
            throw new RuntimeException(
                "La fecha {$data['fecha']} no corresponde al dia del culto {$culto->nombre}."
            );
        }

        // Verificar duplicado (excluyendo el registro actual)
        if ($this->asistenciaDAO->existsByCultoFecha($data['culto_id'], $data['fecha'], $id)) {
            throw new RuntimeException(
                "Ya existe otro registro de asistencia para el culto {$culto->nombre} en la fecha {$data['fecha']}."
            );
        }

        $this->asistenciaDAO->update($id, $data);

        return $this->obtenerPorId($id);
    }

    /**
     * Elimina un registro de asistencia (hard delete).
     *
     * @param int $id ID del registro.
     * @return void
     * @throws RuntimeException Si no se encuentra.
     */
    public function eliminar(int $id): void
    {
        $registro = $this->asistenciaDAO->findById($id);
        if ($registro === null) {
            throw new RuntimeException('Registro de asistencia no encontrado.');
        }

        $this->asistenciaDAO->delete($id);
    }
}
