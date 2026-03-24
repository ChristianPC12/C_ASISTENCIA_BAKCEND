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

    /** @var SetupDAO */
    private SetupDAO $setupDAO;

    public function __construct()
    {
        $this->asistenciaDAO = new AsistenciaDAO();
        $this->cultoDAO      = new CultoDAO();
        $this->setupDAO      = new SetupDAO();
    }

    /**
     * Lista registros de asistencia con filtros opcionales.
     *
     * @param array<string, mixed> $filtros Filtros: culto (codigo), anio, trimestre, mes, fecha_exacta.
     * @return array<int, array<string, mixed>>
     */
    public function listar(array $filtros): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $filtrosDAO = [];

        // Convertir codigo de culto a culto_id
        if (!empty($filtros['culto'])) {
            $culto = $this->resolverCultoPorCodigo((string) $filtros['culto'], $organizacionId);
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

        $registros = $this->asistenciaDAO->findAll($filtrosDAO, $organizacionId);
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

        $culto = $this->resolverCultoPorCodigo($cultoCodigo, AuthContext::getOrganizacionId());
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
        $metricasSuma = [];
        $metricasMax = [];
        $metricasMin = [];
        $metricasCount = [];

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

            $metricas = $registro['metricas'] ?? null;
            if (is_array($metricas)) {
                foreach ($metricas as $clave => $valor) {
                    if (!is_string($clave)) {
                        continue;
                    }

                    $numero = null;
                    if (is_int($valor) || is_float($valor)) {
                        $numero = (float) $valor;
                    } elseif (is_string($valor) && preg_match('/^-?\d+(\.\d+)?$/', trim($valor)) === 1) {
                        $numero = (float) $valor;
                    }

                    if ($numero === null) {
                        continue;
                    }

                    if (!isset($metricasSuma[$clave])) {
                        $metricasSuma[$clave] = 0.0;
                        $metricasMax[$clave] = $numero;
                        $metricasMin[$clave] = $numero;
                        $metricasCount[$clave] = 0;
                    }

                    $metricasSuma[$clave] += $numero;
                    $metricasCount[$clave]++;
                    if ($numero > $metricasMax[$clave]) {
                        $metricasMax[$clave] = $numero;
                    }
                    if ($numero < $metricasMin[$clave]) {
                        $metricasMin[$clave] = $numero;
                    }
                }
            }
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

        ksort($metricasSuma);
        $metricasDinamicas = [];
        foreach ($metricasSuma as $clave => $suma) {
            $count = (int) ($metricasCount[$clave] ?? 0);
            $promedioMetrica = $count > 0 ? round($suma / $count, 2) : 0.0;
            $metricasDinamicas[] = [
                'clave' => $clave,
                'suma' => round($suma, 2),
                'promedio' => $promedioMetrica,
                'maximo' => round((float) ($metricasMax[$clave] ?? 0), 2),
                'minimo' => round((float) ($metricasMin[$clave] ?? 0), 2),
                'registros' => $count
            ];
        }

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
            'metricas_dinamicas' => $metricasDinamicas,
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
        $organizacionId = AuthContext::getOrganizacionId();
        $registro = $this->asistenciaDAO->findById($id, $organizacionId);

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
        $organizacionId = AuthContext::getOrganizacionId();

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
        if ($this->asistenciaDAO->existsByCultoFecha($organizacionId, $data['culto_id'], $data['fecha'])) {
            throw new RuntimeException(
                "Ya existe un registro de asistencia para el culto {$culto->nombre} en la fecha {$data['fecha']}."
            );
        }

        $metricasConfigActivas = $this->obtenerMetricasConfigActivas($organizacionId);
        $metricas = $this->normalizarMetricasEntrada($data, $metricasConfigActivas);
        $data = $this->mapearMetricasAColumnasLegacy($data, $metricas);

        $metricasJson = json_encode($metricas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($metricasJson)) {
            throw new RuntimeException('No se pudieron serializar las metricas del registro.');
        }

        $data['metricas_json'] = $metricasJson;
        $data['organizacion_id'] = $organizacionId;
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
        $organizacionId = AuthContext::getOrganizacionId();
        $registro = $this->asistenciaDAO->findById($id, $organizacionId);
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
        if ($this->asistenciaDAO->existsByCultoFecha($organizacionId, $data['culto_id'], $data['fecha'], $id)) {
            throw new RuntimeException(
                "Ya existe otro registro de asistencia para el culto {$culto->nombre} en la fecha {$data['fecha']}."
            );
        }

        $metricasConfigActivas = $this->obtenerMetricasConfigActivas($organizacionId);
        $metricas = $this->normalizarMetricasEntrada($data, $metricasConfigActivas);
        $data = $this->mapearMetricasAColumnasLegacy($data, $metricas);

        $metricasJson = json_encode($metricas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($metricasJson)) {
            throw new RuntimeException('No se pudieron serializar las metricas del registro.');
        }

        $data['metricas_json'] = $metricasJson;
        $this->asistenciaDAO->update($id, $data, $organizacionId);

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
        $organizacionId = AuthContext::getOrganizacionId();
        $registro = $this->asistenciaDAO->findById($id, $organizacionId);
        if ($registro === null) {
            throw new RuntimeException('Registro de asistencia no encontrado.');
        }

        $this->asistenciaDAO->delete($id, $organizacionId);
    }

    /**
     * Obtiene metricas activas configuradas para la organizacion.
     *
     * @param int $organizacionId
     * @return array<int, array<string, mixed>>
     */
    private function obtenerMetricasConfigActivas(int $organizacionId): array
    {
        $metricas = $this->setupDAO->getMetricas($organizacionId);
        $activas = [];

        foreach ($metricas as $metrica) {
            $habilitada = filter_var(
                $metrica['habilitado'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
            if ($habilitada === true) {
                $activas[] = $metrica;
            }
        }

        return $activas;
    }

    /**
     * Normaliza metricas de entrada usando configuracion activa del tenant.
     *
     * @param array<string, mixed>             $data
     * @param array<int, array<string, mixed>> $metricasConfigActivas
     * @return array<string, mixed>
     */
    private function normalizarMetricasEntrada(array $data, array $metricasConfigActivas): array
    {
        $entrada = [];
        if (isset($data['metricas']) && is_array($data['metricas'])) {
            foreach ($data['metricas'] as $clave => $valor) {
                if (!is_string($clave)) {
                    continue;
                }
                $claveNorm = strtolower(trim($clave));
                if ($claveNorm !== '') {
                    $entrada[$claveNorm] = $valor;
                }
            }
        } else {
            foreach ($data as $clave => $valor) {
                if (!is_string($clave)) {
                    continue;
                }
                $entrada[strtolower(trim($clave))] = $valor;
            }
        }

        $normalizadas = [];
        $categoriasPorClave = [];
        $esNumericaPorClave = [];
        foreach ($metricasConfigActivas as $config) {
            $clave = strtolower((string) ($config['clave'] ?? ''));
            if ($clave === '') {
                continue;
            }

            $categoria = strtolower(trim((string) ($config['categoria'] ?? '')));
            if ($categoria === '') {
                $categoria = $this->inferirCategoriaMetricaPorClave($clave);
            }
            $categoriasPorClave[$clave] = $categoria;

            $esTexto = $this->esMetricaTexto($clave);
            $esNumericaPorClave[$clave] = !$esTexto;

            $valorEntrada = $entrada[$clave] ?? null;
            if (is_string($valorEntrada)) {
                $valorEntrada = trim($valorEntrada);
            }

            // Cast suave: numericos a int, texto se conserva.
            if (!$esTexto) {
                if (is_numeric($valorEntrada) && !is_string($valorEntrada)) {
                    $valorEntrada = (int) $valorEntrada;
                } elseif (is_string($valorEntrada) && preg_match('/^\d+$/', $valorEntrada) === 1) {
                    $valorEntrada = (int) $valorEntrada;
                }
            }

            $normalizadas[$clave] = $valorEntrada;
        }

        $existeAntes = array_key_exists('llegaron_antes_hora', $normalizadas);
        $existeDespues = array_key_exists('llegaron_despues_hora', $normalizadas);
        $existeRetiros = array_key_exists('retiros_antes_terminar', $normalizadas);
        $existeSeQuedaron = array_key_exists('se_quedaron_todo', $normalizadas);
        $existeTotal = array_key_exists('total_asistentes', $normalizadas);

        if ($existeAntes xor $existeDespues) {
            throw new InvalidArgumentException(
                'Las metricas llegaron_antes_hora y llegaron_despues_hora deben configurarse en par.'
            );
        }

        if ($existeRetiros xor $existeSeQuedaron) {
            throw new InvalidArgumentException(
                'Las metricas retiros_antes_terminar y se_quedaron_todo deben configurarse en par.'
            );
        }

        $clavesInfoCulto = [];
        $clavesPermanencia = [];
        $clavesComposicion = [];
        $clavesProcedencia = [];
        $clavesVisitas = [];
        foreach ($normalizadas as $clave => $valor) {
            if (!($esNumericaPorClave[$clave] ?? false)) {
                continue;
            }
            $categoriaClave = $categoriasPorClave[$clave] ?? $this->inferirCategoriaMetricaPorClave($clave);
            if ($categoriaClave === 'informacion_culto') {
                $clavesInfoCulto[] = $clave;
            } elseif ($categoriaClave === 'permanencia') {
                $clavesPermanencia[] = $clave;
            } elseif ($categoriaClave === 'composicion_asistentes') {
                $clavesComposicion[] = $clave;
            } elseif ($categoriaClave === 'procedencia') {
                $clavesProcedencia[] = $clave;
            } elseif ($categoriaClave === 'visitas') {
                $clavesVisitas[] = $clave;
            }
        }

        if ((
            count($clavesInfoCulto) > 0
            || count($clavesPermanencia) > 0
            || count($clavesComposicion) > 0
            || count($clavesProcedencia) > 0
            || count($clavesVisitas) > 0
        ) && !$existeTotal) {
            throw new InvalidArgumentException(
                'La metrica total_asistentes debe estar habilitada cuando hay metricas que dependen del total.'
            );
        }

        if ($existeTotal) {
            $totalEntrada = $normalizadas['total_asistentes'] ?? null;
            $totalVacio = $this->esMetricaVacia($totalEntrada);
            $totalFueCalculado = false;

            if (count($clavesInfoCulto) > 0) {
                $sumaInfoCulto = 0;
                foreach ($clavesInfoCulto as $claveInfo) {
                    $valorInfo = $this->aEnteroNoNegativo($normalizadas[$claveInfo] ?? 0);
                    $normalizadas[$claveInfo] = $valorInfo;
                    $sumaInfoCulto += $valorInfo;
                }
                $normalizadas['total_asistentes'] = $sumaInfoCulto;
                $totalFueCalculado = true;
            } else {
                if ($totalVacio && count($clavesPermanencia) > 0) {
                    $sumaPermanencia = 0;
                    $faltante = false;
                    foreach ($clavesPermanencia as $clavePermanencia) {
                        $valorPermanencia = $normalizadas[$clavePermanencia] ?? null;
                        if ($this->esMetricaVacia($valorPermanencia)) {
                            $faltante = true;
                            break;
                        }
                        $sumaPermanencia += $this->aEnteroNoNegativo($valorPermanencia);
                    }

                    if (!$faltante) {
                        $normalizadas['total_asistentes'] = $sumaPermanencia;
                        $totalFueCalculado = true;
                    }
                }

                if (!$totalFueCalculado) {
                    $normalizadas['total_asistentes'] = $this->aEnteroNoNegativo($totalEntrada);
                }
            }

            if ($totalVacio && !$totalFueCalculado) {
                $hayValorQueRequiereTotal = false;
                $clavesRequierenTotal = array_merge($clavesPermanencia, $clavesComposicion, $clavesProcedencia, $clavesVisitas);
                foreach ($clavesRequierenTotal as $claveRequiereTotal) {
                    if (!$this->esMetricaVacia($normalizadas[$claveRequiereTotal] ?? null)) {
                        $hayValorQueRequiereTotal = true;
                        break;
                    }
                }

                if ($hayValorQueRequiereTotal) {
                    throw new InvalidArgumentException('Debe indicar total_asistentes para completar este registro.');
                }
            }
        }

        if (count($clavesPermanencia) > 0) {
            $totalAsistentes = $this->aEnteroNoNegativo($normalizadas['total_asistentes'] ?? 0);
            $faltantesPermanencia = [];
            $sumaPermanenciaConocida = 0;

            foreach ($clavesPermanencia as $clavePermanencia) {
                $valorPermanencia = $normalizadas[$clavePermanencia] ?? null;
                if ($this->esMetricaVacia($valorPermanencia)) {
                    $faltantesPermanencia[] = $clavePermanencia;
                    continue;
                }
                $numeroPermanencia = $this->aEnteroNoNegativo($valorPermanencia);
                $normalizadas[$clavePermanencia] = $numeroPermanencia;
                $sumaPermanenciaConocida += $numeroPermanencia;
            }

            if (count($faltantesPermanencia) === 0) {
                if ($sumaPermanenciaConocida !== $totalAsistentes) {
                    throw new InvalidArgumentException(
                        'La suma de metricas de permanencia debe coincidir con total_asistentes.'
                    );
                }
            } elseif (count($faltantesPermanencia) === 1) {
                if ($sumaPermanenciaConocida > $totalAsistentes) {
                    throw new InvalidArgumentException(
                        'La suma de metricas de permanencia no puede superar total_asistentes.'
                    );
                }
                $claveCalculada = $faltantesPermanencia[0];
                $normalizadas[$claveCalculada] = $totalAsistentes - $sumaPermanenciaConocida;
            } else {
                throw new InvalidArgumentException(
                    'Para permanencia debe completar al menos N-1 metricas para calcular la restante.'
                );
            }
        }

        $total = $this->aEnteroNoNegativo($normalizadas['total_asistentes'] ?? 0);

        if (count($clavesComposicion) > 0) {
            $sumaComposicion = 0;
            foreach ($clavesComposicion as $claveComposicion) {
                $valor = $this->aEnteroNoNegativo($normalizadas[$claveComposicion] ?? 0);
                $normalizadas[$claveComposicion] = $valor;
                $sumaComposicion += $valor;
            }
            if ($sumaComposicion > $total) {
                throw new InvalidArgumentException(
                    'La suma de metricas de composicion_asistentes no puede superar total_asistentes.'
                );
            }
        }

        if (count($clavesProcedencia) > 0) {
            $sumaProcedencia = 0;
            foreach ($clavesProcedencia as $claveProcedencia) {
                $valor = $this->aEnteroNoNegativo($normalizadas[$claveProcedencia] ?? 0);
                $normalizadas[$claveProcedencia] = $valor;
                $sumaProcedencia += $valor;
            }
            if ($sumaProcedencia > $total) {
                throw new InvalidArgumentException(
                    'La suma de metricas de procedencia no puede superar total_asistentes.'
                );
            }
        }

        if (count($clavesVisitas) > 0) {
            foreach ($clavesVisitas as $claveVisitas) {
                if (!str_starts_with($claveVisitas, 'visitas_')) {
                    continue;
                }

                $slug = substr($claveVisitas, strlen('visitas_'));
                if ($slug === '') {
                    continue;
                }

                $claveProcedencia = 'proc_' . $slug;
                if (!array_key_exists($claveProcedencia, $normalizadas)) {
                    continue;
                }

                $visitas = $this->aEnteroNoNegativo($normalizadas[$claveVisitas] ?? 0);
                $normalizadas[$claveVisitas] = $visitas;
                $procedencia = $this->aEnteroNoNegativo($normalizadas[$claveProcedencia] ?? 0);
                $normalizadas[$claveProcedencia] = $procedencia;

                if ($visitas > $procedencia) {
                    throw new InvalidArgumentException(
                        'La metrica ' . $claveVisitas . ' no puede superar ' . $claveProcedencia . '.'
                    );
                }
            }
        }

        return $normalizadas;
    }

    /**
     * Mapea metricas dinamicas al esquema legacy para compatibilidad de consultas/reportes actuales.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $metricas
     * @return array<string, mixed>
     */
    private function mapearMetricasAColumnasLegacy(array $data, array $metricas): array
    {
        $data['llegaron_antes_hora'] = $this->aEnteroNoNegativo($metricas['llegaron_antes_hora'] ?? 0);
        $data['llegaron_despues_hora'] = $this->aEnteroNoNegativo($metricas['llegaron_despues_hora'] ?? 0);
        $data['ninos'] = $this->aEnteroNoNegativo($metricas['ninos'] ?? 0);
        $data['jovenes'] = $this->aEnteroNoNegativo($metricas['jovenes'] ?? 0);
        $data['total_asistentes'] = $this->aEnteroNoNegativo($metricas['total_asistentes'] ?? 0);
        $data['proc_barrio'] = $this->aEnteroNoNegativo($metricas['proc_barrio'] ?? 0);
        $data['proc_guayabo'] = $this->aEnteroNoNegativo($metricas['proc_guayabo'] ?? 0);
        $data['visitas_barrio'] = $this->aEnteroNoNegativo($metricas['visitas_barrio'] ?? 0);
        $data['visitas_guayabo'] = $this->aEnteroNoNegativo($metricas['visitas_guayabo'] ?? 0);
        $data['retiros_antes_terminar'] = $this->aEnteroNoNegativo($metricas['retiros_antes_terminar'] ?? 0);
        $data['se_quedaron_todo'] = $this->aEnteroNoNegativo($metricas['se_quedaron_todo'] ?? 0);

        $nombresBarrio = $metricas['nombres_visitas_barrio'] ?? null;
        $data['nombres_visitas_barrio'] = is_string($nombresBarrio) && trim($nombresBarrio) !== ''
            ? trim($nombresBarrio)
            : null;

        $nombresGuayabo = $metricas['nombres_visitas_guayabo'] ?? null;
        $data['nombres_visitas_guayabo'] = is_string($nombresGuayabo) && trim($nombresGuayabo) !== ''
            ? trim($nombresGuayabo)
            : null;

        $observaciones = $metricas['observaciones'] ?? ($data['observaciones'] ?? null);
        $data['observaciones'] = is_string($observaciones) && trim($observaciones) !== ''
            ? trim($observaciones)
            : null;

        return $data;
    }

    /**
     * Infiere categoria por clave cuando no llega en configuracion.
     *
     * @param string $clave
     * @return string
     */
    private function inferirCategoriaMetricaPorClave(string $clave): string
    {
        if ($clave === 'llegaron_antes_hora' || $clave === 'llegaron_despues_hora') {
            return 'informacion_culto';
        }
        if ($clave === 'ninos' || $clave === 'jovenes') {
            return 'composicion_asistentes';
        }
        if ($clave === 'total_asistentes') {
            return 'total_asistentes';
        }
        if ($clave === 'retiros_antes_terminar' || $clave === 'se_quedaron_todo') {
            return 'permanencia';
        }
        if ($clave === 'observaciones') {
            return 'observaciones';
        }
        if (str_starts_with($clave, 'proc_')) {
            return 'procedencia';
        }
        if (str_starts_with($clave, 'visitas_') || str_starts_with($clave, 'nombres_visitas_')) {
            return 'visitas';
        }

        return 'adicionales';
    }

    /**
     * Determina si la clave de metrica se procesa como texto.
     *
     * @param string $clave
     * @return bool
     */
    private function esMetricaTexto(string $clave): bool
    {
        return $clave === 'observaciones' || str_starts_with($clave, 'nombres_');
    }

    /**
     * @param mixed $valor
     * @return bool
     */
    private function esMetricaVacia(mixed $valor): bool
    {
        if ($valor === null) {
            return true;
        }

        if (is_string($valor)) {
            return trim($valor) === '';
        }

        return false;
    }

    /**
     * @param mixed $valor
     * @return int
     */
    private function aEnteroNoNegativo(mixed $valor): int
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        if (!is_numeric($valor)) {
            return 0;
        }

        $entero = (int) $valor;
        return $entero >= 0 ? $entero : 0;
    }

    /**
     * Resuelve culto global por codigo amigable del setup de la organizacion.
     *
     * @param string $codigo
     * @param int $organizacionId
     * @return CultoDTO|null
     */
    private function resolverCultoPorCodigo(string $codigo, int $organizacionId): ?CultoDTO
    {
        $codigoNormalizado = strtoupper(trim($codigo));
        if ($codigoNormalizado === '') {
            return null;
        }

        $cultosSetup = $this->setupDAO->getCultos($organizacionId);
        if (!empty($cultosSetup)) {
            foreach ($cultosSetup as $cultoOrg) {
                $codigoSetup = strtoupper(trim((string) ($cultoOrg['codigo'] ?? '')));
                if ($codigoSetup !== $codigoNormalizado) {
                    continue;
                }

                return $this->cultoDAO->ensureFromOrganizacionCulto($organizacionId, $cultoOrg);
            }

            return null;
        }

        $culto = $this->cultoDAO->findByCodigo($codigoNormalizado);
        if ($culto !== null) {
            return $culto;
        }

        return null;
    }
}
