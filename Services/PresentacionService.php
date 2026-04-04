<?php
declare(strict_types=1);

/**
 * Logica de negocio del modulo de presentaciones.
 */
final class PresentacionService
{
    /** @var PresentacionDAO */
    private PresentacionDAO $presentacionDAO;

    /** @var AsistenciaService */
    private AsistenciaService $asistenciaService;

    /** @var CultoDAO */
    private CultoDAO $cultoDAO;
    /** @var SetupDAO */
    private SetupDAO $setupDAO;

    /** @var array<int, string> */
    private const SECTION_IDS = [
        'resumen_ejecutivo',
        'datos_clave',
        'tendencias',
        'composicion',
        'puntualidad',
        'permanencia',
        'procedencia',
        'visitas'
    ];

    public function __construct()
    {
        $this->presentacionDAO = new PresentacionDAO();
        $this->asistenciaService = new AsistenciaService();
        $this->cultoDAO = new CultoDAO();
        $this->setupDAO = new SetupDAO();
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    public function generar(array $filtros, int $usuarioId): array
    {
        $organizacionId = AuthContext::getOrganizacionId();

        $filtrosConsulta = [
            'anio' => (int) $filtros['anio'],
            'mes' => str_pad((string) ((int) $filtros['mes']), 2, '0', STR_PAD_LEFT)
        ];

        $cultoCodigo = null;
        if (!empty($filtros['culto'])) {
            $cultoCodigo = (string) $filtros['culto'];
            $culto = $this->resolverCultoPorCodigo($cultoCodigo, $organizacionId);
            if ($culto === null) {
                throw new InvalidArgumentException('El culto indicado no existe.');
            }
            $filtrosConsulta['culto'] = $cultoCodigo;
        }

        $anio = (int) $filtrosConsulta['anio'];
        $mes = (int) $filtrosConsulta['mes'];
        if ($this->presentacionDAO->existsByPeriodoCulto($organizacionId, $anio, $mes, $cultoCodigo)) {
            throw new RuntimeException(
                'Ya existe una presentacion para ese periodo y culto dentro de esta organizacion. Use un culto diferente o cambie el mes.',
                409
            );
        }

        $registros = $this->listarRegistrosPresentacion($filtrosConsulta);
        if (count($registros) === 0) {
            throw new RuntimeException('No hay registros en el mes seleccionado. No se puede generar la presentacion.', 422);
        }

        $metricas = $this->construirMetricas($registros, $filtrosConsulta);
        $promptBloqueado = $this->construirPromptBloqueado($metricas);
        $presentacion = $this->construirPresentacionDeterministica($metricas);
        $this->validarPresentacion($presentacion);

        $filtrosJson = json_encode($filtrosConsulta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $metricasJson = json_encode($metricas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $presentacionJson = json_encode($presentacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($filtrosJson === false || $metricasJson === false || $presentacionJson === false) {
            throw new RuntimeException('No se pudo serializar la presentacion para guardarla.', 500);
        }

        $id = $this->presentacionDAO->insert([
            'organizacion_id' => $organizacionId,
            'usuario_id' => $usuarioId,
            'anio' => (int) $filtrosConsulta['anio'],
            'mes' => (int) $filtrosConsulta['mes'],
            'culto_codigo' => $cultoCodigo,
            'filtros_json' => $filtrosJson,
            'metricas_json' => $metricasJson,
            'prompt_version' => 'v1',
            'prompt_bloqueado' => $promptBloqueado,
            'modelo' => 'motor_reglas_v1',
            'ia_response_id' => '',
            'presentacion_json' => $presentacionJson
        ]);

        $dto = $this->presentacionDAO->findById($id, $organizacionId);
        if ($dto === null) {
            throw new RuntimeException('No se pudo leer la presentacion generada.', 500);
        }

        return PresentacionMapper::toArray($dto);
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    public function listar(array $filtros, int $usuarioId, bool $esAdmin): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $page = (int) ($filtros['page'] ?? 1);
        $limit = (int) ($filtros['limit'] ?? 20);
        $offset = ($page - 1) * $limit;

        $total = $this->presentacionDAO->countAll($filtros, $usuarioId, $esAdmin, $organizacionId);
        $items = $this->presentacionDAO->findAll($filtros, $usuarioId, $esAdmin, $organizacionId, $limit, $offset);

        $itemsResumen = [];
        foreach ($items as $item) {
            $item = $this->normalizarPresentacionDTO($item);
            $itemsResumen[] = PresentacionMapper::toSummaryArray($item);
        }

        return [
            'items' => $itemsResumen,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => max(1, $limit > 0 ? (int) ceil($total / $limit) : 1)
            ]
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function obtenerPorId(int $id, int $usuarioId, bool $esAdmin): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $dto = $this->presentacionDAO->findById($id, $organizacionId);
        if ($dto === null) {
            throw new RuntimeException('Presentacion no encontrada.', 404);
        }

        if (!$esAdmin && $dto->usuarioId !== $usuarioId) {
            throw new DomainException('No tiene permisos para ver esta presentacion.');
        }

        $dto = $this->normalizarPresentacionDTO($dto);
        return PresentacionMapper::toArray($dto);
    }

    /**
     * @param array<int, array<string, mixed>> $registros
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    private function construirMetricas(array $registros, array $filtros): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        [$etiquetasPorClave, $categoriasPorClave] = $this->obtenerContextoMetricas($organizacionId);

        $totalRegistros = count($registros);
        $totalAsistentes = 0;
        $maximo = 0;
        $minimo = 0;

        $ninos = 0;
        $jovenes = 0;
        $antes = 0;
        $despues = 0;
        $retiros = 0;
        $seQuedaron = 0;
        $procBarrio = 0;
        $procGuayabo = 0;
        $visitasBarrio = 0;
        $visitasGuayabo = 0;

        $seriesPorFecha = [];
        $frecuenciaNombres = [];
        $metricasSuma = [];
        $metricasMax = [];
        $metricasMin = [];
        $metricasCount = [];
        $categoriasAcumuladas = [
            'composicion_asistentes' => [],
            'informacion_culto' => [],
            'permanencia' => [],
            'procedencia' => [],
            'visitas' => []
        ];

        foreach ($registros as $registro) {
            $asistentes = (int) ($registro['total_asistentes'] ?? 0);
            $totalAsistentes += $asistentes;
            $ninos += (int) ($registro['ninos'] ?? 0);
            $jovenes += (int) ($registro['jovenes'] ?? 0);
            $antes += (int) ($registro['llegaron_antes_hora'] ?? 0);
            $despues += (int) ($registro['llegaron_despues_hora'] ?? 0);
            $retiros += (int) ($registro['retiros_antes_terminar'] ?? 0);
            $seQuedaron += (int) ($registro['se_quedaron_todo'] ?? 0);
            $procBarrio += (int) ($registro['proc_barrio'] ?? 0);
            $procGuayabo += (int) ($registro['proc_guayabo'] ?? 0);
            $visitasBarrio += (int) ($registro['visitas_barrio'] ?? 0);
            $visitasGuayabo += (int) ($registro['visitas_guayabo'] ?? 0);

            if ($totalRegistros > 0) {
                if ($asistentes > $maximo) {
                    $maximo = $asistentes;
                }
                if ($minimo === 0 || $asistentes < $minimo) {
                    $minimo = $asistentes;
                }
            }

            $fecha = (string) ($registro['fecha'] ?? '');
            if ($fecha !== '') {
                if (!isset($seriesPorFecha[$fecha])) {
                    $seriesPorFecha[$fecha] = 0;
                }
                $seriesPorFecha[$fecha] += $asistentes;
            }

            $metricas = $registro['metricas'] ?? null;
            if (is_array($metricas)) {
                foreach ($metricas as $clave => $valor) {
                    if (!is_string($clave)) {
                        continue;
                    }

                    $clave = strtolower(trim($clave));
                    if ($clave === '') {
                        continue;
                    }

                    if (str_starts_with($clave, 'nombres_visitas_') && is_string($valor)) {
                        $this->acumularNombres($frecuenciaNombres, $valor);
                    }

                    $numero = $this->resolverNumeroMetrica($valor);
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

                    $categoria = $categoriasPorClave[$clave] ?? $this->inferirCategoriaPresentacion($clave);
                    if (!isset($categoriasAcumuladas[$categoria])) {
                        continue;
                    }

                    $etiqueta = $etiquetasPorClave[$clave] ?? $this->humanizarClavePresentacion($clave);
                    $this->acumularTotalCategoria($categoriasAcumuladas[$categoria], $etiqueta, $numero);
                }
            }
        }

        ksort($seriesPorFecha);

        $series = [];
        foreach ($seriesPorFecha as $fecha => $total) {
            $series[] = [
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

        $baseComposicion = (int) round(array_sum($categoriasAcumuladas['composicion_asistentes']));
        $basePuntualidad = (int) round(array_sum($categoriasAcumuladas['informacion_culto']));
        $basePermanencia = (int) round(array_sum($categoriasAcumuladas['permanencia']));
        $baseProcedencia = (int) round(array_sum($categoriasAcumuladas['procedencia']));
        $totalVisitas = (int) round(array_sum($categoriasAcumuladas['visitas']));

        ksort($metricasSuma);
        $metricasDinamicas = [];
        foreach ($metricasSuma as $clave => $suma) {
            if (abs($suma) < 0.00001) {
                continue;
            }

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

        $composicionItems = $this->mapearItemsCategoria($categoriasAcumuladas['composicion_asistentes'], $baseComposicion);
        $puntualidadItems = $this->mapearItemsCategoria($categoriasAcumuladas['informacion_culto'], $basePuntualidad);
        $permanenciaItems = $this->mapearItemsCategoria($categoriasAcumuladas['permanencia'], $basePermanencia);
        $procedenciaItems = $this->mapearItemsCategoria($categoriasAcumuladas['procedencia'], $baseProcedencia);
        $visitasItems = $this->mapearItemsCategoria($categoriasAcumuladas['visitas'], $totalVisitas);

        return [
            'periodo' => [
                'anio' => (int) ($filtros['anio'] ?? date('Y')),
                'mes' => (int) ($filtros['mes'] ?? 0),
                'mes_etiqueta' => $this->nombreMes((int) ($filtros['mes'] ?? 0)),
                'culto_codigo' => isset($filtros['culto']) ? (string) $filtros['culto'] : 'TODOS'
            ],
            'resumen' => [
                'total_registros' => $totalRegistros,
                'total_asistentes' => $totalAsistentes,
                'promedio_por_culto' => $totalRegistros > 0 ? round($totalAsistentes / $totalRegistros, 2) : 0,
                'maximo_asistentes' => $maximo,
                'minimo_asistentes' => $minimo
            ],
            'composicion' => [
                'ninos' => [
                    'cantidad' => $ninos,
                    'porcentaje' => $this->porcentaje($ninos, $baseComposicion)
                ],
                'jovenes' => [
                    'cantidad' => $jovenes,
                    'porcentaje' => $this->porcentaje($jovenes, $baseComposicion)
                ],
                'items' => $composicionItems
            ],
            'puntualidad' => [
                'antes' => [
                    'cantidad' => $antes,
                    'porcentaje' => $this->porcentaje($antes, $basePuntualidad)
                ],
                'despues' => [
                    'cantidad' => $despues,
                    'porcentaje' => $this->porcentaje($despues, $basePuntualidad)
                ],
                'items' => $puntualidadItems
            ],
            'permanencia' => [
                'retiros_antes_terminar' => [
                    'cantidad' => $retiros,
                    'porcentaje' => $this->porcentaje($retiros, $basePermanencia)
                ],
                'se_quedaron_todo' => [
                    'cantidad' => $seQuedaron,
                    'porcentaje' => $this->porcentaje($seQuedaron, $basePermanencia)
                ],
                'items' => $permanenciaItems,
                'total' => $basePermanencia
            ],
            'procedencia' => [
                'barrio' => [
                    'cantidad' => $procBarrio,
                    'porcentaje' => $this->porcentaje($procBarrio, $baseProcedencia)
                ],
                'guayabo' => [
                    'cantidad' => $procGuayabo,
                    'porcentaje' => $this->porcentaje($procGuayabo, $baseProcedencia)
                ],
                'items' => $procedenciaItems
            ],
            'visitas' => [
                'total_visitas' => $totalVisitas,
                'barrio' => [
                    'cantidad' => $visitasBarrio,
                    'porcentaje' => $this->porcentaje($visitasBarrio, $totalVisitas)
                ],
                'guayabo' => [
                    'cantidad' => $visitasGuayabo,
                    'porcentaje' => $this->porcentaje($visitasGuayabo, $totalVisitas)
                ],
                'items' => $visitasItems,
                'top_nombres' => $topNombres
            ],
            'series' => [
                'asistencia_por_fecha' => $series
            ],
            'metricas_dinamicas' => $metricasDinamicas
        ];
    }

    /**
     * @param array<string, mixed> $metricas
     */
    private function construirPromptBloqueado(array $metricas): string
    {
        $metricasJson = json_encode($metricas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($metricasJson === false) {
            $metricasJson = '{}';
        }

        return implode("\n", [
            'PLANTILLA_BLOQUEADA: presentacion_mensual_iasd_v1',
            'INSTRUCCION: generar una presentacion estadistica mensual con motor deterministico interno.',
            'REGLAS:',
            '- Usa solo METRICAS_CANONICAS.',
            '- No inventar datos ni porcentajes.',
            '- Manten el orden exacto de secciones.',
            '- Texto claro, breve y entendible para usuarios no tecnicos.',
            'SECCIONES_OBLIGATORIAS_ORDEN:',
            '1) resumen_ejecutivo',
            '2) datos_clave',
            '3) tendencias',
            '4) composicion',
            '5) puntualidad',
            '6) permanencia',
            '7) procedencia',
            '8) visitas',
            'METRICAS_CANONICAS:',
            $metricasJson
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function construirPresentacionDeterministica(array $metricas): array
    {
        $periodo = is_array($metricas['periodo'] ?? null) ? $metricas['periodo'] : [];
        $resumen = is_array($metricas['resumen'] ?? null) ? $metricas['resumen'] : [];
        $composicion = is_array($metricas['composicion'] ?? null) ? $metricas['composicion'] : [];
        $puntualidad = is_array($metricas['puntualidad'] ?? null) ? $metricas['puntualidad'] : [];
        $permanencia = is_array($metricas['permanencia'] ?? null) ? $metricas['permanencia'] : [];
        $procedencia = is_array($metricas['procedencia'] ?? null) ? $metricas['procedencia'] : [];
        $visitas = is_array($metricas['visitas'] ?? null) ? $metricas['visitas'] : [];
        $series = is_array($metricas['series']['asistencia_por_fecha'] ?? null)
            ? $metricas['series']['asistencia_por_fecha']
            : [];

        $totalRegistros = (int) ($resumen['total_registros'] ?? 0);
        $totalAsistentes = (int) ($resumen['total_asistentes'] ?? 0);
        $promedio = (float) ($resumen['promedio_por_culto'] ?? 0);
        $maximo = (int) ($resumen['maximo_asistentes'] ?? 0);
        $minimo = (int) ($resumen['minimo_asistentes'] ?? 0);
        $visitasTotal = (int) ($visitas['total_visitas'] ?? 0);

        $topNombres = is_array($visitas['top_nombres'] ?? null) ? $visitas['top_nombres'] : [];
        $composicionItems = is_array($composicion['items'] ?? null) ? $composicion['items'] : [];
        $puntualidadItems = is_array($puntualidad['items'] ?? null) ? $puntualidad['items'] : [];
        $permanenciaItems = is_array($permanencia['items'] ?? null) ? $permanencia['items'] : [];
        $procedenciaItems = is_array($procedencia['items'] ?? null) ? $procedencia['items'] : [];
        $visitasItems = is_array($visitas['items'] ?? null) ? $visitas['items'] : [];

        $primer = count($series) > 0 ? (int) ($series[0]['total_asistentes'] ?? 0) : 0;
        $ultimo = count($series) > 0 ? (int) ($series[count($series) - 1]['total_asistentes'] ?? 0) : 0;
        $variacionAbs = $ultimo - $primer;
        $variacionPct = $primer > 0 ? round(($variacionAbs / $primer) * 100, 2) : 0.0;
        $direccionTendencia = $variacionAbs > 0 ? 'crecimiento' : ($variacionAbs < 0 ? 'descenso' : 'estabilidad');
        $hayComparacion = count($series) > 1;

        $topNombresTexto = $this->formatearTopNombres($topNombres);
        $mesEtiqueta = (string) ($periodo['mes_etiqueta'] ?? 'Mes');
        $anio = (int) ($periodo['anio'] ?? 0);
        $cultoCodigo = (string) ($periodo['culto_codigo'] ?? 'TODOS');
        $ambitoCulto = $cultoCodigo === 'TODOS' ? '' : (' del culto ' . $cultoCodigo);

        $resumenTopComposicion = $this->resumenCortoCategoria($composicionItems);
        $resumenTopPuntualidad = $this->resumenCortoCategoria($puntualidadItems);
        $resumenTopPermanencia = $this->resumenCortoCategoria($permanenciaItems);
        $resumenTopProcedencia = $this->resumenCortoCategoria($procedenciaItems);
        $resumenTopVisitas = $this->resumenCortoCategoria($visitasItems);

        $puntosComposicion = $this->construirPuntosCategoria(
            $composicionItems,
            'Total con composición registrada: ' . $this->sumaItems($composicionItems) . '.'
        );
        $puntosPuntualidad = $this->construirPuntosCategoria(
            $puntualidadItems,
            $this->fraseMayorCategoria($puntualidadItems, 'La mayoría de las llegadas se registró en %s.')
        );
        $puntosPermanencia = $this->construirPuntosCategoria(
            $permanenciaItems,
            'Total con permanencia registrada: ' . $this->sumaItems($permanenciaItems) . '.'
        );
        $puntosProcedencia = $this->construirPuntosCategoria(
            $procedenciaItems,
            $this->fraseMayorCategoria($procedenciaItems, 'La procedencia con más asistentes fue %s.')
        );
        $puntosVisitas = $this->construirPuntosVisitas($visitasTotal, $visitasItems, $topNombresTexto);

        return [
            'version' => 'v1',
            'plantilla' => 'presentacion_mensual_iasd',
            'periodo' => [
                'anio' => $anio,
                'mes' => (int) ($periodo['mes'] ?? 0),
                'culto_codigo' => $cultoCodigo,
                'total_registros' => $totalRegistros,
                'total_asistentes' => $totalAsistentes
            ],
            'secciones' => [
                [
                    'id' => 'resumen_ejecutivo',
                    'titulo' => 'Resumen ejecutivo',
                    'resumen' => sprintf(
                        'En %s %d se registraron %d cultos%s con %d asistentes en total.',
                        $mesEtiqueta,
                        $anio,
                        $totalRegistros,
                        $ambitoCulto,
                        $totalAsistentes
                    ),
                    'puntos' => [
                        sprintf('Promedio por registro: %s asistentes.', $this->fmtDec($promedio)),
                        sprintf('Registro más alto: %d asistentes.', $maximo),
                        sprintf('Registro más bajo: %d asistentes.', $minimo)
                    ]
                ],
                [
                    'id' => 'datos_clave',
                    'titulo' => 'Datos clave',
                    'resumen' => 'Estos son los datos principales del período para una revisión rápida.',
                    'puntos' => [
                        sprintf('Total de registros: %d.', $totalRegistros),
                        sprintf('Total de asistentes: %d.', $totalAsistentes),
                        sprintf('Promedio por registro: %s asistentes.', $this->fmtDec($promedio)),
                        $visitasTotal > 0
                            ? sprintf('Total de visitas registradas: %d.', $visitasTotal)
                            : 'No se registraron visitas en este período.'
                    ]
                ],
                [
                    'id' => 'tendencias',
                    'titulo' => 'Tendencias',
                    'resumen' => $hayComparacion
                        ? sprintf(
                            'La asistencia mostró %s entre el primer y el último registro del período.',
                            $direccionTendencia === 'crecimiento'
                                ? 'un aumento'
                                : ($direccionTendencia === 'descenso' ? 'una disminución' : 'estabilidad')
                        )
                        : 'Solo hay un registro en el período, por lo que no hay una tendencia para comparar.',
                    'puntos' => [
                        sprintf('Primer registro: %d asistentes.', $primer),
                        sprintf('Último registro: %d asistentes.', $ultimo),
                        $hayComparacion
                            ? sprintf('Cambio del período: %+d asistentes (%s%%).', $variacionAbs, $this->fmtDec($variacionPct))
                            : 'Se necesita más de un registro para calcular una variación.'
                    ]
                ],
                [
                    'id' => 'composicion',
                    'titulo' => 'Composición',
                    'resumen' => $resumenTopComposicion !== ''
                        ? sprintf('La composición registrada se concentró principalmente en %s.', $resumenTopComposicion)
                        : 'No hubo datos de composición registrados en este período.',
                    'puntos' => $puntosComposicion
                ],
                [
                    'id' => 'puntualidad',
                    'titulo' => 'Puntualidad',
                    'resumen' => $resumenTopPuntualidad !== ''
                        ? sprintf('Así se distribuyeron las llegadas durante el período: %s.', $resumenTopPuntualidad)
                        : 'No hubo datos de puntualidad registrados en este período.',
                    'puntos' => $puntosPuntualidad
                ],
                [
                    'id' => 'permanencia',
                    'titulo' => 'Permanencia',
                    'resumen' => $resumenTopPermanencia !== ''
                        ? sprintf('La permanencia registrada se distribuyó principalmente en %s.', $resumenTopPermanencia)
                        : 'No hubo datos de permanencia registrados en este período.',
                    'puntos' => $puntosPermanencia
                ],
                [
                    'id' => 'procedencia',
                    'titulo' => 'Procedencia',
                    'resumen' => $resumenTopProcedencia !== ''
                        ? sprintf('Las procedencias con más asistentes fueron %s.', $resumenTopProcedencia)
                        : 'No hubo procedencias registradas en este período.',
                    'puntos' => $puntosProcedencia
                ],
                [
                    'id' => 'visitas',
                    'titulo' => 'Visitas',
                    'resumen' => $visitasTotal > 0
                        ? ($resumenTopVisitas !== ''
                            ? sprintf('Las visitas del período se concentraron principalmente en %s.', $resumenTopVisitas)
                            : sprintf('Se registraron %d visitas durante el período.', $visitasTotal))
                        : 'No se registraron visitas en este período.',
                    'puntos' => $puntosVisitas
                ]
            ]
        ];
    }

    /**
     * @param array<string, mixed> $presentacion
     */
    private function validarPresentacion(array $presentacion): void
    {
        if (($presentacion['version'] ?? '') !== 'v1') {
            throw new RuntimeException('La presentacion generada no incluye version v1.', 500);
        }

        if (($presentacion['plantilla'] ?? '') !== 'presentacion_mensual_iasd') {
            throw new RuntimeException('La plantilla de la presentacion es invalida.', 500);
        }

        if (!isset($presentacion['secciones']) || !is_array($presentacion['secciones']) || count($presentacion['secciones']) !== 8) {
            throw new RuntimeException('La presentacion generada no trae 8 secciones.', 500);
        }

        foreach (self::SECTION_IDS as $index => $idEsperado) {
            $seccion = $presentacion['secciones'][$index] ?? null;
            if (!is_array($seccion) || ($seccion['id'] ?? '') !== $idEsperado) {
                throw new RuntimeException('La presentacion generada no respeta el orden de secciones.', 500);
            }

            $titulo = trim((string) ($seccion['titulo'] ?? ''));
            $resumen = trim((string) ($seccion['resumen'] ?? ''));
            $puntos = $seccion['puntos'] ?? null;
            if ($titulo === '' || strlen($titulo) < 3) {
                throw new RuntimeException('Se detecto una seccion sin titulo valido.', 500);
            }
            if ($resumen === '' || strlen($resumen) < 20) {
                throw new RuntimeException('Se detecto una seccion sin resumen valido.', 500);
            }
            if (!is_array($puntos) || count($puntos) < 3) {
                throw new RuntimeException('Se detecto una seccion sin puntos suficientes.', 500);
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $topNombres
     */
    private function formatearTopNombres(array $topNombres): string
    {
        if ($topNombres === []) {
            return 'sin nombres registrados';
        }

        $segmentos = [];
        foreach (array_slice($topNombres, 0, 3) as $item) {
            $nombre = trim((string) ($item['nombre'] ?? ''));
            $cantidad = (int) ($item['cantidad'] ?? 0);
            if ($nombre === '') {
                continue;
            }
            $segmentos[] = sprintf('%s (%d)', $nombre, $cantidad);
        }

        if ($segmentos === []) {
            return 'sin nombres registrados';
        }

        return implode(', ', $segmentos);
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    private function obtenerContextoMetricas(int $organizacionId): array
    {
        $metricas = $this->setupDAO->getMetricas($organizacionId);
        $etiquetas = [];
        $categorias = [];

        foreach ($metricas as $metrica) {
            $clave = strtolower(trim((string) ($metrica['clave'] ?? '')));
            if ($clave === '') {
                continue;
            }

            $etiquetas[$clave] = trim((string) ($metrica['etiqueta'] ?? '')) !== ''
                ? trim((string) $metrica['etiqueta'])
                : $this->humanizarClavePresentacion($clave);

            $categoria = strtolower(trim((string) ($metrica['categoria'] ?? '')));
            $categorias[$clave] = $categoria !== ''
                ? $categoria
                : $this->inferirCategoriaPresentacion($clave);
        }

        return [$etiquetas, $categorias];
    }

    private function inferirCategoriaPresentacion(string $clave): string
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
        if (str_starts_with($clave, 'proc_')) {
            return 'procedencia';
        }
        if (str_starts_with($clave, 'visitas_') || str_starts_with($clave, 'nombres_visitas_')) {
            return 'visitas';
        }
        if ($clave === 'retiros_antes_terminar' || $clave === 'se_quedaron_todo') {
            return 'permanencia';
        }
        if ($clave === 'observaciones') {
            return 'observaciones';
        }

        return 'adicionales';
    }

    private function humanizarClavePresentacion(string $clave): string
    {
        $texto = strtolower(trim($clave));
        if ($texto === '') {
            return '';
        }

        $texto = str_replace(
            ['ninos', 'jovenes', 'despues', 'maximo', 'minimo'],
            ['niños', 'jóvenes', 'después', 'máximo', 'mínimo'],
            $texto
        );
        $texto = str_replace(['_', '-'], ' ', $texto);

        return ucfirst(trim($texto));
    }

    /**
     * @param mixed $valor
     */
    private function resolverNumeroMetrica($valor): ?float
    {
        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        if (is_string($valor) && preg_match('/^-?\d+(\.\d+)?$/', trim($valor)) === 1) {
            return (float) $valor;
        }

        return null;
    }

    /**
     * @param array<string, float> $acumulador
     */
    private function acumularTotalCategoria(array &$acumulador, string $etiqueta, float $valor): void
    {
        $etiquetaNormalizada = trim($etiqueta);
        if ($etiquetaNormalizada === '') {
            return;
        }

        if (!isset($acumulador[$etiquetaNormalizada])) {
            $acumulador[$etiquetaNormalizada] = 0.0;
        }

        $acumulador[$etiquetaNormalizada] += $valor;
    }

    /**
     * @param array<string, float> $acumulados
     * @return array<int, array<string, mixed>>
     */
    private function mapearItemsCategoria(array $acumulados, int $base): array
    {
        if ($acumulados === []) {
            return [];
        }

        arsort($acumulados);
        $items = [];
        foreach ($acumulados as $etiqueta => $cantidad) {
            $cantidadEntera = (int) round($cantidad);
            if ($cantidadEntera <= 0) {
                continue;
            }

            $items[] = [
                'etiqueta' => $etiqueta,
                'cantidad' => $cantidadEntera,
                'porcentaje' => $this->porcentaje($cantidadEntera, $base)
            ];
        }

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function resumenCortoCategoria(array $items, int $limite = 2): string
    {
        if ($items === []) {
            return '';
        }

        $partes = [];
        foreach (array_slice($items, 0, $limite) as $item) {
            $etiqueta = trim((string) ($item['etiqueta'] ?? ''));
            $cantidad = (int) ($item['cantidad'] ?? 0);
            if ($etiqueta === '') {
                continue;
            }
            $partes[] = sprintf('%s (%d)', $etiqueta, $cantidad);
        }

        return implode(', ', $partes);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, string>
     */
    private function construirPuntosCategoria(array $items, string $extra = ''): array
    {
        $puntos = [];

        foreach (array_slice($items, 0, 3) as $item) {
            $etiqueta = trim((string) ($item['etiqueta'] ?? ''));
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $porcentaje = (float) ($item['porcentaje'] ?? 0);
            if ($etiqueta === '') {
                continue;
            }
            $puntos[] = sprintf('%s: %d (%s%%).', $etiqueta, $cantidad, $this->fmtDec($porcentaje));
        }

        if ($extra !== '') {
            $puntos[] = $extra;
        }

        if ($puntos === []) {
            return [
                'No hubo datos registrados en este período.',
                'No se encontraron cantidades para mostrar.',
                'Puede revisar otros filtros para ver más información.'
            ];
        }

        while (count($puntos) < 3) {
            $puntos[] = $puntos[count($puntos) - 1];
        }

        return array_slice($puntos, 0, 4);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function fraseMayorCategoria(array $items, string $plantilla): string
    {
        if ($items === []) {
            return '';
        }

        $etiqueta = trim((string) ($items[0]['etiqueta'] ?? ''));
        if ($etiqueta === '') {
            return '';
        }

        return sprintf($plantilla, $etiqueta);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function sumaItems(array $items): int
    {
        $total = 0;
        foreach ($items as $item) {
            $total += (int) ($item['cantidad'] ?? 0);
        }

        return $total;
    }

    /**
     * @param array<int, array<string, mixed>> $visitasItems
     * @return array<int, string>
     */
    private function construirPuntosVisitas(int $visitasTotal, array $visitasItems, string $topNombresTexto): array
    {
        $puntos = [
            $visitasTotal > 0
                ? sprintf('Total de visitas registradas: %d.', $visitasTotal)
                : 'No se registraron visitas en este período.'
        ];

        if ($visitasItems !== []) {
            foreach (array_slice($visitasItems, 0, 2) as $item) {
                $etiqueta = trim((string) ($item['etiqueta'] ?? ''));
                $cantidad = (int) ($item['cantidad'] ?? 0);
                $porcentaje = (float) ($item['porcentaje'] ?? 0);
                if ($etiqueta === '') {
                    continue;
                }
                $puntos[] = sprintf('%s: %d (%s%% de las visitas).', $etiqueta, $cantidad, $this->fmtDec($porcentaje));
            }
        } else {
            $puntos[] = 'No hubo zonas con visitas registradas.';
        }

        $puntos[] = $topNombresTexto !== 'sin nombres registrados'
            ? sprintf('Nombres más repetidos: %s.', $topNombresTexto)
            : 'No se registraron nombres de visitas.';

        return array_slice($puntos, 0, 4);
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array<int, array<string, mixed>>
     */
    private function listarRegistrosPresentacion(array $filtros, ?string $creadoHasta = null): array
    {
        if ($creadoHasta !== null && trim($creadoHasta) !== '') {
            $filtros['creado_hasta'] = trim($creadoHasta);
        }

        return $this->asistenciaService->listar($filtros);
    }

    private function normalizarPresentacionDTO(PresentacionDTO $dto): PresentacionDTO
    {
        if (!$this->esPresentacionLegada($dto->presentacion)) {
            return $dto;
        }

        try {
            $registros = $this->listarRegistrosPresentacion(
                is_array($dto->filtros) ? $dto->filtros : [],
                $dto->creadoEn !== '' ? $dto->creadoEn : null
            );

            if ($registros === []) {
                return $dto;
            }

            $dto->metricas = $this->construirMetricas($registros, $dto->filtros);
            $dto->presentacion = $this->construirPresentacionDeterministica($dto->metricas);
            $this->validarPresentacion($dto->presentacion);
        } catch (Throwable $e) {
            return $dto;
        }

        return $dto;
    }

    /**
     * @param array<string, mixed> $presentacion
     */
    private function esPresentacionLegada(array $presentacion): bool
    {
        $secciones = $presentacion['secciones'] ?? null;
        if (!is_array($secciones) || count($secciones) !== count(self::SECTION_IDS)) {
            return true;
        }

        foreach (self::SECTION_IDS as $index => $idEsperado) {
            $seccion = $secciones[$index] ?? null;
            if (!is_array($seccion) || ($seccion['id'] ?? '') !== $idEsperado) {
                return true;
            }
        }

        $textoPlano = json_encode($presentacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($textoPlano)) {
            return true;
        }

        return str_contains($textoPlano, 'Métricas dinámicas destacadas')
            || str_contains($textoPlano, 'Conclusiones y Acciones')
            || str_contains($textoPlano, 'El componente de visitas permite monitorear');
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

            $normalizado = preg_replace('/\s+/', ' ', $nombre);
            if ($normalizado === null || $normalizado === '') {
                continue;
            }

            if (!isset($acumulador[$normalizado])) {
                $acumulador[$normalizado] = 0;
            }

            $acumulador[$normalizado]++;
        }
    }

    /**
     * Resuelve culto por codigo amigable del setup tenant.
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

    private function porcentaje(int $cantidad, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($cantidad / $total) * 100, 2);
    }

    private function fmtDec(float $valor): string
    {
        $texto = number_format($valor, 2, '.', '');
        $texto = rtrim(rtrim($texto, '0'), '.');
        return $texto === '-0' ? '0' : $texto;
    }

    private function nombreMes(int $mes): string
    {
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

        return $meses[$mes] ?? ('Mes ' . $mes);
    }
}
