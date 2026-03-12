<?php
declare(strict_types=1);

/**
 * Clase SetupService
 *
 * Logica de negocio del setup inicial por tenant.
 */
final class SetupService
{
    private const CLAVE_PUNTUALIDAD_ANTES = 'llegaron_antes_hora';
    private const CLAVE_PUNTUALIDAD_DESPUES = 'llegaron_despues_hora';
    private const CLAVE_TOTAL_ASISTENTES = 'total_asistentes';
    private const CATEGORIAS_VALIDAS = [
        'informacion_culto',
        'composicion_asistentes',
        'procedencia',
        'visitas',
        'permanencia',
        'total_asistentes',
        'observaciones',
        'adicionales'
    ];

    /** @var SetupDAO */
    private SetupDAO $setupDAO;

    /** @var OrganizacionDAO */
    private OrganizacionDAO $organizacionDAO;

    public function __construct()
    {
        $this->setupDAO = new SetupDAO();
        $this->organizacionDAO = new OrganizacionDAO();
    }

    /**
     * Obtiene estado completo del setup inicial.
     *
     * @param int $organizacionId
     * @return array<string, mixed>
     */
    public function obtenerEstado(int $organizacionId): array
    {
        $this->obtenerOrganizacionActiva($organizacionId);
        $estado = $this->obtenerEstadoFila($organizacionId);

        $cultos = $this->setupDAO->getCultos($organizacionId);
        $procedencias = $this->setupDAO->getProcedencias($organizacionId);
        $metricas = $this->setupDAO->getMetricas($organizacionId);
        $inconsistenciasMetricas = $this->obtenerInconsistenciasMetricas($metricas);
        $faltantes = $this->calcularFaltantes($organizacionId);

        return [
            'estado_setup' => (string) $estado['estado_setup'],
            'bloqueada_operacion' => (int) $estado['bloqueada_operacion'] === 1,
            'setup_completado_en' => $estado['setup_completado_en'] ?? null,
            'ultima_revision_en' => $estado['ultima_revision_en'] ?? null,
            'faltantes' => $faltantes,
            'resumen' => [
                'cultos_activos' => $this->setupDAO->countCultosActivos($organizacionId),
                'procedencias_activas' => $this->setupDAO->countProcedenciasActivas($organizacionId),
                'metricas_habilitadas' => $this->setupDAO->countMetricasHabilitadas($organizacionId),
                'dependencias_invalidas' => count($inconsistenciasMetricas)
            ],
            'configuracion' => [
                'cultos' => $cultos,
                'procedencias' => $procedencias,
                'metricas' => $metricas
            ]
        ];
    }

    /**
     * Guarda configuracion de cultos.
     *
     * @param int                  $organizacionId
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function guardarCultos(int $organizacionId, array $data): array
    {
        $this->obtenerOrganizacionActiva($organizacionId);
        $this->obtenerEstadoFila($organizacionId);

        $this->setupDAO->replaceCultos($organizacionId, $data['cultos']);

        return $this->obtenerEstado($organizacionId);
    }

    /**
     * Guarda configuracion de procedencias.
     *
     * @param int                  $organizacionId
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function guardarProcedencias(int $organizacionId, array $data): array
    {
        $this->obtenerOrganizacionActiva($organizacionId);
        $this->obtenerEstadoFila($organizacionId);

        $this->setupDAO->replaceProcedencias($organizacionId, $data['procedencias']);
        $this->sincronizarMetricasDerivadasDeProcedencias($organizacionId);

        return $this->obtenerEstado($organizacionId);
    }

    /**
     * Guarda configuracion de metricas.
     *
     * @param int                  $organizacionId
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function guardarMetricas(int $organizacionId, array $data): array
    {
        $this->obtenerOrganizacionActiva($organizacionId);
        $this->obtenerEstadoFila($organizacionId);

        $this->assertMetricasConsistentes($data['metricas']);
        $this->setupDAO->replaceMetricas($organizacionId, $data['metricas']);

        return $this->obtenerEstado($organizacionId);
    }

    /**
     * Finaliza setup inicial si la configuracion es coherente.
     *
     * @param int $organizacionId
     * @return array<string, mixed>
     */
    public function finalizar(int $organizacionId): array
    {
        $this->obtenerOrganizacionActiva($organizacionId);
        $estado = $this->obtenerEstadoFila($organizacionId);
        $this->assertSetupEditable($estado);

        $faltantes = $this->calcularFaltantes($organizacionId);
        if (!empty($faltantes)) {
            throw new RuntimeException('SETUP_INCONSISTENT: Configuracion incompleta: ' . implode(', ', $faltantes) . '.');
        }

        $this->setupDAO->markCompleto($organizacionId);

        return $this->obtenerEstado($organizacionId);
    }

    /**
     * Asegura que la organizacion existe y esta activa.
     *
     * @param int $organizacionId
     * @return OrganizacionDTO
     */
    private function obtenerOrganizacionActiva(int $organizacionId): OrganizacionDTO
    {
        $organizacion = $this->organizacionDAO->findById($organizacionId);
        if ($organizacion === null) {
            throw new OutOfBoundsException('Organizacion no encontrada.');
        }

        if ($organizacion->activa === false) {
            throw new RuntimeException('SETUP_INCONSISTENT: La organizacion se encuentra inactiva.');
        }

        return $organizacion;
    }

    /**
     * Obtiene estado setup (crea fila base si falta).
     *
     * @param int $organizacionId
     * @return array<string, mixed>
     */
    private function obtenerEstadoFila(int $organizacionId): array
    {
        $this->setupDAO->ensureEstadoRow($organizacionId);
        $estado = $this->setupDAO->getEstadoRow($organizacionId);
        if ($estado === null) {
            throw new RuntimeException('SETUP_INCONSISTENT: No se pudo recuperar estado de setup.');
        }

        return $estado;
    }

    /**
     * Rechaza cambios cuando setup ya esta completo.
     *
     * @param array<string, mixed> $estado
     * @return void
     */
    private function assertSetupEditable(array $estado): void
    {
        if (((string) ($estado['estado_setup'] ?? '')) === 'COMPLETO') {
            throw new DomainException('El setup inicial ya fue completado para esta organizacion.');
        }
    }

    /**
     * Calcula faltantes de setup.
     *
     * @param int $organizacionId
     * @return array<int, string>
     */
    private function calcularFaltantes(int $organizacionId): array
    {
        $faltantes = [];

        $cultosActivos = $this->setupDAO->countCultosActivos($organizacionId);
        if ($cultosActivos < 1) {
            $faltantes[] = 'cultos';
        }

        $procedenciasActivas = $this->setupDAO->countProcedenciasActivas($organizacionId);
        if ($procedenciasActivas < 1) {
            $faltantes[] = 'procedencias_minimas';
        } elseif ($procedenciasActivas > 10) {
            $faltantes[] = 'procedencias_maximas';
        }

        $metricas = $this->setupDAO->getMetricas($organizacionId);
        $metricasHabilitadas = 0;
        foreach ($metricas as $metrica) {
            if ($this->toBool($metrica['habilitado'] ?? false)) {
                $metricasHabilitadas++;
            }
        }
        if ($metricasHabilitadas < 1) {
            $faltantes[] = 'metricas';
        }

        return $faltantes;
    }

    /**
     * Lanza error de validacion cuando la configuracion de metricas es inconsistente.
     *
     * @param array<int, array<string, mixed>> $metricas
     * @return void
     */
    private function assertMetricasConsistentes(array $metricas): void
    {
        $inconsistencias = $this->obtenerInconsistenciasMetricas($metricas);
        if (!empty($inconsistencias)) {
            throw new InvalidArgumentException(
                'Configuracion de metricas invalida: ' . implode(', ', $inconsistencias) . '.'
            );
        }
    }

    /**
     * Retorna lista de inconsistencias semanticas de metricas.
     *
     * @param array<int, array<string, mixed>> $metricas
     * @return array<int, string>
     */
    private function obtenerInconsistenciasMetricas(array $metricas): array
    {
        $index = [];

        foreach ($metricas as $item) {
            $clave = strtolower(trim((string) ($item['clave'] ?? '')));
            if ($clave === '') {
                continue;
            }

            $categoria = strtolower(trim((string) ($item['categoria'] ?? 'adicionales')));

            $index[$clave] = [
                'clave' => $clave,
                'habilitado' => $this->toBool($item['habilitado'] ?? false),
                'obligatorio' => $this->toBool($item['obligatorio'] ?? false),
                'categoria' => $categoria
            ];
        }

        $inconsistencias = [];

        foreach ($index as $clave => $metrica) {
            if ($metrica['obligatorio'] && !$metrica['habilitado']) {
                $inconsistencias[] = 'obligatorio_sin_habilitar:' . $clave;
            }

            $categoria = (string) ($metrica['categoria'] ?? '');
            if (!in_array($categoria, self::CATEGORIAS_VALIDAS, true)) {
                $inconsistencias[] = 'categoria_no_valida:' . $clave . '->' . $categoria;
                continue;
            }

            $categoriaEsperada = $this->categoriaEsperadaPorClave($clave);
            if ($categoriaEsperada !== null && $categoria !== $categoriaEsperada) {
                $inconsistencias[] = 'categoria_invalida:' . $clave . '->' . $categoria;
            }
        }

        $existeAntes = isset($index[self::CLAVE_PUNTUALIDAD_ANTES]);
        $existeDespues = isset($index[self::CLAVE_PUNTUALIDAD_DESPUES]);
        $existeTotal = isset($index[self::CLAVE_TOTAL_ASISTENTES]);

        if ($existeAntes xor $existeDespues) {
            $inconsistencias[] = 'puntualidad_incompleta';
        }
        if (!$existeAntes) {
            $inconsistencias[] = 'metrica_base_faltante:' . self::CLAVE_PUNTUALIDAD_ANTES;
        }
        if (!$existeDespues) {
            $inconsistencias[] = 'metrica_base_faltante:' . self::CLAVE_PUNTUALIDAD_DESPUES;
        }
        if (!$existeTotal) {
            $inconsistencias[] = 'metrica_base_faltante:' . self::CLAVE_TOTAL_ASISTENTES;
        }

        if ($existeAntes && $existeDespues && $existeTotal) {
            $antes = $index[self::CLAVE_PUNTUALIDAD_ANTES];
            $despues = $index[self::CLAVE_PUNTUALIDAD_DESPUES];
            $total = $index[self::CLAVE_TOTAL_ASISTENTES];

            if (
                $antes['habilitado'] !== $despues['habilitado']
                || $antes['obligatorio'] !== $despues['obligatorio']
            ) {
                $inconsistencias[] = 'puntualidad_ambos_o_ninguno';
            }

            if ($total['habilitado'] && (!$antes['habilitado'] || !$despues['habilitado'])) {
                $inconsistencias[] = 'total_sin_puntualidad_habilitada';
            }

            if (($antes['habilitado'] || $despues['habilitado']) && !$total['habilitado']) {
                $inconsistencias[] = 'puntualidad_sin_total_habilitada';
            }
        }

        return array_values(array_unique($inconsistencias));
    }

    /**
     * Categoria esperada para metricas base conocidas.
     *
     * @param string $clave
     * @return string|null
     */
    private function categoriaEsperadaPorClave(string $clave): ?string
    {
        if ($clave === self::CLAVE_PUNTUALIDAD_ANTES || $clave === self::CLAVE_PUNTUALIDAD_DESPUES) {
            return 'informacion_culto';
        }
        if ($clave === self::CLAVE_TOTAL_ASISTENTES) {
            return 'total_asistentes';
        }
        if ($clave === 'ninos' || $clave === 'jovenes') {
            return 'composicion_asistentes';
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

        return null;
    }

    /**
     * Normaliza valores mixtos a booleano.
     *
     * @param mixed $value
     * @return bool
     */
    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'true', 'on', 'yes', 'si'], true);
        }

        return false;
    }

    /**
     * Sincroniza metricas derivadas de procedencias para que se reflejen en registro.
     *
     * @param int $organizacionId
     * @return void
     */
    private function sincronizarMetricasDerivadasDeProcedencias(int $organizacionId): void
    {
        $procedencias = $this->setupDAO->getProcedencias($organizacionId);
        $metricasActuales = $this->setupDAO->getMetricas($organizacionId);

        $metricasPorClave = [];
        foreach ($metricasActuales as $item) {
            $clave = strtolower(trim((string) ($item['clave'] ?? '')));
            if ($clave !== '') {
                $metricasPorClave[$clave] = $item;
            }
        }

        $metricasBase = [];
        foreach ($metricasActuales as $item) {
            $clave = strtolower(trim((string) ($item['clave'] ?? '')));
            if ($clave === '') {
                continue;
            }
            if ($this->esMetricaDerivadaProcedencia($clave)) {
                continue;
            }

            $categoria = strtolower(trim((string) ($item['categoria'] ?? '')));
            if ($categoria === '') {
                $categoria = $this->categoriaEsperadaPorClave($clave) ?? 'adicionales';
            }

            $habilitado = $this->toBool($item['habilitado'] ?? false);
            $obligatorio = $habilitado ? $this->toBool($item['obligatorio'] ?? false) : false;

            $metricasBase[] = [
                'clave' => $clave,
                'etiqueta' => (string) ($item['etiqueta'] ?? $clave),
                'categoria' => $categoria,
                'habilitado' => $habilitado,
                'obligatorio' => $obligatorio
            ];
        }

        $metricasDerivadas = [];
        foreach ($procedencias as $procedencia) {
            $activa = $this->toBool($procedencia['activo'] ?? false);
            $nombre = trim((string) ($procedencia['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }

            $slug = $this->slugProcedencia($nombre);
            if ($slug === '') {
                continue;
            }

            $metricasDerivadas[] = $this->construirMetricaProcedencia(
                'proc_' . $slug,
                'Procedencia de ' . $nombre,
                'procedencia',
                $activa,
                $activa,
                $metricasPorClave
            );
            $metricasDerivadas[] = $this->construirMetricaProcedencia(
                'visitas_' . $slug,
                $this->etiquetaVisitasProcedencia($nombre),
                'visitas',
                $activa,
                false,
                $metricasPorClave
            );
            $metricasDerivadas[] = $this->construirMetricaProcedencia(
                'nombres_visitas_' . $slug,
                $this->etiquetaNombresVisitasProcedencia($nombre),
                'visitas',
                $activa,
                false,
                $metricasPorClave
            );
        }

        $final = array_merge($metricasBase, $metricasDerivadas);
        if (!empty($final)) {
            $this->setupDAO->replaceMetricas($organizacionId, $final);
        }
    }

    /**
     * @param string $clave
     * @return bool
     */
    private function esMetricaDerivadaProcedencia(string $clave): bool
    {
        return str_starts_with($clave, 'proc_')
            || str_starts_with($clave, 'visitas_')
            || str_starts_with($clave, 'nombres_visitas_');
    }

    /**
     * @param string $nombre
     * @return string
     */
    private function slugProcedencia(string $nombre): string
    {
        $texto = trim($nombre);
        if ($texto === '') {
            return '';
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if (!is_string($ascii) || $ascii === '') {
            $ascii = $texto;
        }

        $slug = strtolower($ascii);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '';
        $slug = preg_replace('/_+/', '_', $slug) ?? '';
        $slug = trim($slug, '_');

        return substr($slug, 0, 40);
    }

    /**
     * @param string $clave
     * @param string $etiqueta
     * @param string $categoria
     * @param bool $habilitadoDefault
     * @param bool $obligatorioDefault
     * @param array<string, array<string, mixed>> $metricasPorClave
     * @return array<string, mixed>
     */
    private function construirMetricaProcedencia(
        string $clave,
        string $etiqueta,
        string $categoria,
        bool $habilitadoDefault,
        bool $obligatorioDefault,
        array $metricasPorClave
    ): array {
        $actual = $metricasPorClave[$clave] ?? null;
        $habilitado = $actual !== null
            ? $this->toBool($actual['habilitado'] ?? false)
            : $habilitadoDefault;
        $obligatorio = $actual !== null
            ? $this->toBool($actual['obligatorio'] ?? false)
            : $obligatorioDefault;

        if (!$habilitado) {
            $obligatorio = false;
        }

        return [
            'clave' => $clave,
            'etiqueta' => $etiqueta,
            'categoria' => $categoria,
            'habilitado' => $habilitado,
            'obligatorio' => $obligatorio
        ];
    }

    /**
     * Etiqueta natural para metrica de visitas por procedencia.
     *
     * @param string $nombre
     * @return string
     */
    private function etiquetaVisitasProcedencia(string $nombre): string
    {
        $nombreLimpio = trim($nombre);
        $normalizado = strtolower($this->slugProcedencia($nombreLimpio));

        if ($normalizado === 'barrio') {
            return 'Visitas del barrio';
        }

        return 'Visitas de ' . $nombreLimpio;
    }

    /**
     * Etiqueta natural para metrica de nombres de visitas por procedencia.
     *
     * @param string $nombre
     * @return string
     */
    private function etiquetaNombresVisitasProcedencia(string $nombre): string
    {
        $nombreLimpio = trim($nombre);
        $normalizado = strtolower($this->slugProcedencia($nombreLimpio));

        if ($normalizado === 'barrio') {
            return 'Nombres de visitas del barrio';
        }

        return 'Nombres de visitas de ' . $nombreLimpio;
    }
}
