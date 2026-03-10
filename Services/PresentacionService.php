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

    /** @var array<int, string> */
    private const SECTION_IDS = [
        'resumen_ejecutivo',
        'kpis_clave',
        'tendencias',
        'composicion',
        'puntualidad',
        'procedencia',
        'visitas',
        'conclusiones_acciones'
    ];

    public function __construct()
    {
        $this->presentacionDAO = new PresentacionDAO();
        $this->asistenciaService = new AsistenciaService();
        $this->cultoDAO = new CultoDAO();
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
            $culto = $this->cultoDAO->findByCodigo($cultoCodigo);
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

        $registros = $this->asistenciaService->listar($filtrosConsulta);
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

        return PresentacionMapper::toArray($dto);
    }

    /**
     * @param array<int, array<string, mixed>> $registros
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    private function construirMetricas(array $registros, array $filtros): array
    {
        $totalRegistros = count($registros);
        $totalAsistentes = 0;
        $maximo = 0;
        $minimo = 0;

        $ninos = 0;
        $jovenes = 0;
        $antes = 0;
        $despues = 0;
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

        foreach ($registros as $registro) {
            $asistentes = (int) ($registro['total_asistentes'] ?? 0);
            $totalAsistentes += $asistentes;
            $ninos += (int) ($registro['ninos'] ?? 0);
            $jovenes += (int) ($registro['jovenes'] ?? 0);
            $antes += (int) ($registro['llegaron_antes_hora'] ?? 0);
            $despues += (int) ($registro['llegaron_despues_hora'] ?? 0);
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

            $this->acumularNombres($frecuenciaNombres, (string) ($registro['nombres_visitas_barrio'] ?? ''));
            $this->acumularNombres($frecuenciaNombres, (string) ($registro['nombres_visitas_guayabo'] ?? ''));

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

        $baseComposicion = $ninos + $jovenes;
        $basePuntualidad = $antes + $despues;
        $baseProcedencia = $procBarrio + $procGuayabo;
        $totalVisitas = $visitasBarrio + $visitasGuayabo;

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
                ]
            ],
            'puntualidad' => [
                'antes' => [
                    'cantidad' => $antes,
                    'porcentaje' => $this->porcentaje($antes, $basePuntualidad)
                ],
                'despues' => [
                    'cantidad' => $despues,
                    'porcentaje' => $this->porcentaje($despues, $basePuntualidad)
                ]
            ],
            'procedencia' => [
                'barrio' => [
                    'cantidad' => $procBarrio,
                    'porcentaje' => $this->porcentaje($procBarrio, $baseProcedencia)
                ],
                'guayabo' => [
                    'cantidad' => $procGuayabo,
                    'porcentaje' => $this->porcentaje($procGuayabo, $baseProcedencia)
                ]
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
            '- Texto formal y conciso.',
            'SECCIONES_OBLIGATORIAS_ORDEN:',
            '1) resumen_ejecutivo',
            '2) kpis_clave',
            '3) tendencias',
            '4) composicion',
            '5) puntualidad',
            '6) procedencia',
            '7) visitas',
            '8) conclusiones_acciones',
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
        $procedencia = is_array($metricas['procedencia'] ?? null) ? $metricas['procedencia'] : [];
        $visitas = is_array($metricas['visitas'] ?? null) ? $metricas['visitas'] : [];
        $series = is_array($metricas['series']['asistencia_por_fecha'] ?? null) ? $metricas['series']['asistencia_por_fecha'] : [];
        $metricasDinamicas = is_array($metricas['metricas_dinamicas'] ?? null) ? $metricas['metricas_dinamicas'] : [];

        $totalRegistros = (int) ($resumen['total_registros'] ?? 0);
        $totalAsistentes = (int) ($resumen['total_asistentes'] ?? 0);
        $promedio = (float) ($resumen['promedio_por_culto'] ?? 0);
        $maximo = (int) ($resumen['maximo_asistentes'] ?? 0);
        $minimo = (int) ($resumen['minimo_asistentes'] ?? 0);

        $ninosCant = (int) ($composicion['ninos']['cantidad'] ?? 0);
        $ninosPct = (float) ($composicion['ninos']['porcentaje'] ?? 0);
        $jovenesCant = (int) ($composicion['jovenes']['cantidad'] ?? 0);
        $jovenesPct = (float) ($composicion['jovenes']['porcentaje'] ?? 0);

        $antesCant = (int) ($puntualidad['antes']['cantidad'] ?? 0);
        $antesPct = (float) ($puntualidad['antes']['porcentaje'] ?? 0);
        $despuesCant = (int) ($puntualidad['despues']['cantidad'] ?? 0);
        $despuesPct = (float) ($puntualidad['despues']['porcentaje'] ?? 0);

        $barrioCant = (int) ($procedencia['barrio']['cantidad'] ?? 0);
        $barrioPct = (float) ($procedencia['barrio']['porcentaje'] ?? 0);
        $guayaboCant = (int) ($procedencia['guayabo']['cantidad'] ?? 0);
        $guayaboPct = (float) ($procedencia['guayabo']['porcentaje'] ?? 0);

        $visitasTotal = (int) ($visitas['total_visitas'] ?? 0);
        $visitasBarrio = (int) ($visitas['barrio']['cantidad'] ?? 0);
        $visitasBarrioPct = (float) ($visitas['barrio']['porcentaje'] ?? 0);
        $visitasGuayabo = (int) ($visitas['guayabo']['cantidad'] ?? 0);
        $visitasGuayaboPct = (float) ($visitas['guayabo']['porcentaje'] ?? 0);
        $topNombres = is_array($visitas['top_nombres'] ?? null) ? $visitas['top_nombres'] : [];

        $primer = count($series) > 0 ? (int) ($series[0]['total_asistentes'] ?? 0) : 0;
        $ultimo = count($series) > 0 ? (int) ($series[count($series) - 1]['total_asistentes'] ?? 0) : 0;
        $variacionAbs = $ultimo - $primer;
        $variacionPct = $primer > 0 ? round(($variacionAbs / $primer) * 100, 2) : 0.0;
        $direccionTendencia = $variacionAbs > 0 ? 'crecimiento' : ($variacionAbs < 0 ? 'descenso' : 'estabilidad');

        $topNombresTexto = $this->formatearTopNombres($topNombres);
        $topMetricasDinamicas = $this->formatearTopMetricasDinamicas($metricasDinamicas);
        $acciones = $this->construirAccionesDeterministicas($metricas, $variacionPct);
        $mesEtiqueta = (string) ($periodo['mes_etiqueta'] ?? 'Mes');
        $anio = (int) ($periodo['anio'] ?? 0);
        $cultoCodigo = (string) ($periodo['culto_codigo'] ?? 'TODOS');

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
                    'titulo' => 'Resumen Ejecutivo',
                    'resumen' => sprintf(
                        'En %s %d se registraron %d cultos con %d asistentes en total y un promedio de %s por registro.',
                        $mesEtiqueta,
                        $anio,
                        $totalRegistros,
                        $totalAsistentes,
                        $this->fmtDec($promedio)
                    ),
                    'puntos' => [
                        sprintf('Cobertura del periodo: %d registros procesados.', $totalRegistros),
                        sprintf('Rango de asistencia por culto: minimo %d y maximo %d.', $minimo, $maximo),
                        sprintf('Ambito aplicado: culto %s.', $cultoCodigo)
                    ]
                ],
                [
                    'id' => 'kpis_clave',
                    'titulo' => 'Indicadores Clave',
                    'resumen' => 'Los indicadores principales consolidan asistencia total, promedio operativo, concentracion de visitas y metricas dinamicas del periodo.',
                    'puntos' => [
                        sprintf('Asistencia total mensual: %d personas.', $totalAsistentes),
                        sprintf('Promedio por registro: %s asistentes.', $this->fmtDec($promedio)),
                        sprintf('Visitas registradas: %d personas.', $visitasTotal),
                        sprintf('Metricas dinamicas destacadas: %s.', $topMetricasDinamicas)
                    ]
                ],
                [
                    'id' => 'tendencias',
                    'titulo' => 'Tendencias',
                    'resumen' => sprintf(
                        'La serie de asistencia por fecha muestra %s con variacion acumulada de %s%% entre inicio y cierre del periodo.',
                        $direccionTendencia,
                        $this->fmtDec($variacionPct)
                    ),
                    'puntos' => [
                        sprintf('Primer registro del periodo: %d asistentes.', $primer),
                        sprintf('Ultimo registro del periodo: %d asistentes.', $ultimo),
                        sprintf('Variacion absoluta del periodo: %+d asistentes.', $variacionAbs)
                    ]
                ],
                [
                    'id' => 'composicion',
                    'titulo' => 'Composicion',
                    'resumen' => 'La composicion etaria mensual se distribuye entre ninos y jovenes segun los conteos reportados en cada registro.',
                    'puntos' => [
                        sprintf('Ninos: %d (%s%% del total etario).', $ninosCant, $this->fmtDec($ninosPct)),
                        sprintf('Jovenes: %d (%s%% del total etario).', $jovenesCant, $this->fmtDec($jovenesPct)),
                        sprintf('Diferencia de volumen: %d personas.', abs($ninosCant - $jovenesCant))
                    ]
                ],
                [
                    'id' => 'puntualidad',
                    'titulo' => 'Puntualidad',
                    'resumen' => 'La puntualidad mensual contrasta llegadas antes y despues de la hora para orientar acciones operativas de inicio.',
                    'puntos' => [
                        sprintf('Llegadas antes de hora: %d (%s%%).', $antesCant, $this->fmtDec($antesPct)),
                        sprintf('Llegadas despues de hora: %d (%s%%).', $despuesCant, $this->fmtDec($despuesPct)),
                        sprintf('Brecha de puntualidad: %+d personas.', $antesCant - $despuesCant)
                    ]
                ],
                [
                    'id' => 'procedencia',
                    'titulo' => 'Procedencia',
                    'resumen' => 'La procedencia de asistentes se distribuye entre Barrio y Guayabo con participaciones relativas del periodo.',
                    'puntos' => [
                        sprintf('Barrio: %d (%s%% de procedencia).', $barrioCant, $this->fmtDec($barrioPct)),
                        sprintf('Guayabo: %d (%s%% de procedencia).', $guayaboCant, $this->fmtDec($guayaboPct)),
                        sprintf('Diferencia entre zonas: %+d personas.', $barrioCant - $guayaboCant)
                    ]
                ],
                [
                    'id' => 'visitas',
                    'titulo' => 'Visitas',
                    'resumen' => 'El componente de visitas permite monitorear captacion mensual y su distribucion geografica para seguimiento pastoral.',
                    'puntos' => [
                        sprintf('Total de visitas del periodo: %d.', $visitasTotal),
                        sprintf('Visitas de Barrio: %d (%s%%) y Guayabo: %d (%s%%).', $visitasBarrio, $this->fmtDec($visitasBarrioPct), $visitasGuayabo, $this->fmtDec($visitasGuayaboPct)),
                        sprintf('Nombres mas frecuentes: %s.', $topNombresTexto)
                    ]
                ],
                [
                    'id' => 'conclusiones_acciones',
                    'titulo' => 'Conclusiones y Acciones',
                    'resumen' => 'Las acciones sugeridas se generan por reglas fijas de negocio para mantener consistencia entre periodos.',
                    'puntos' => $acciones
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
     * @param array<string, mixed> $metricas
     * @return array<int, string>
     */
    private function construirAccionesDeterministicas(array $metricas, float $variacionPct): array
    {
        $puntualidad = is_array($metricas['puntualidad'] ?? null) ? $metricas['puntualidad'] : [];
        $visitas = is_array($metricas['visitas'] ?? null) ? $metricas['visitas'] : [];

        $antes = (int) ($puntualidad['antes']['cantidad'] ?? 0);
        $despues = (int) ($puntualidad['despues']['cantidad'] ?? 0);
        $visitasTotales = (int) ($visitas['total_visitas'] ?? 0);

        $accionTendencia = $variacionPct < -5
            ? 'Implementar un plan de retencion semanal para recuperar la asistencia del siguiente mes.'
            : ($variacionPct > 5
                ? 'Consolidar practicas actuales de convocatoria para sostener el crecimiento observado.'
                : 'Mantener monitoreo quincenal para detectar cambios tempranos en la asistencia.');

        $accionPuntualidad = $despues > $antes
            ? 'Reforzar recordatorios previos al culto y ajustar tiempos de inicio para mejorar puntualidad.'
            : 'Mantener protocolo de recepcion temprana y seguimiento a grupos con menor puntualidad.';

        $accionVisitas = $visitasTotales > 0
            ? 'Programar seguimiento pastoral a visitas registradas en un plazo maximo de siete dias.'
            : 'Fortalecer estrategias de invitacion comunitaria para incrementar visitas en el proximo periodo.';

        return [$accionTendencia, $accionPuntualidad, $accionVisitas];
    }

    /**
     * @param array<int, array<string, mixed>> $topNombres
     */
    private function formatearTopNombres(array $topNombres): string
    {
        if ($topNombres === []) {
            return 'sin registros relevantes';
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
            return 'sin registros relevantes';
        }

        return implode(', ', $segmentos);
    }

    /**
     * @param array<int, array<string, mixed>> $metricasDinamicas
     */
    private function formatearTopMetricasDinamicas(array $metricasDinamicas): string
    {
        if ($metricasDinamicas === []) {
            return 'sin metricas numericas disponibles';
        }

        usort($metricasDinamicas, static function (array $a, array $b): int {
            return (float) ($b['suma'] ?? 0) <=> (float) ($a['suma'] ?? 0);
        });

        $segmentos = [];
        foreach (array_slice($metricasDinamicas, 0, 3) as $metrica) {
            $clave = trim((string) ($metrica['clave'] ?? ''));
            $suma = (float) ($metrica['suma'] ?? 0);
            if ($clave === '') {
                continue;
            }

            $segmentos[] = sprintf('%s=%s', $clave, $this->fmtDec($suma));
        }

        if ($segmentos === []) {
            return 'sin metricas numericas disponibles';
        }

        return implode(', ', $segmentos);
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

    private function porcentaje(int $cantidad, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($cantidad / $total) * 100, 2);
    }

    private function fmtDec(float $valor): string
    {
        return number_format($valor, 2, '.', '');
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
