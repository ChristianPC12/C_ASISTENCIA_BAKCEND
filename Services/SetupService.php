<?php
declare(strict_types=1);

/**
 * Clase SetupService
 *
 * Logica de negocio del setup inicial por tenant.
 */
final class SetupService
{
    private const REGLA_SI_MAYOR_CERO = 'SI_MAYOR_CERO';
    private const REGLA_AMBOS_O_NINGUNO = 'AMBOS_O_NINGUNO';
    private const CLAVE_PUNTUALIDAD_ANTES = 'llegaron_antes_hora';
    private const CLAVE_PUNTUALIDAD_DESPUES = 'llegaron_despues_hora';

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
        $estado = $this->obtenerEstadoFila($organizacionId);
        $this->assertSetupEditable($estado);

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
        $estado = $this->obtenerEstadoFila($organizacionId);
        $this->assertSetupEditable($estado);

        $this->setupDAO->replaceProcedencias($organizacionId, $data['procedencias']);

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
        $estado = $this->obtenerEstadoFila($organizacionId);
        $this->assertSetupEditable($estado);

        $this->assertMetricasDependenciasValidas($data['metricas']);
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

        $inconsistenciasMetricas = $this->obtenerInconsistenciasMetricas($metricas);
        if (!empty($inconsistenciasMetricas)) {
            $faltantes[] = 'dependencias_metricas';
        }

        return $faltantes;
    }

    /**
     * Lanza error de validacion cuando la configuracion de metricas es inconsistente.
     *
     * @param array<int, array<string, mixed>> $metricas
     * @return void
     */
    private function assertMetricasDependenciasValidas(array $metricas): void
    {
        $inconsistencias = $this->obtenerInconsistenciasMetricas($metricas);
        if (!empty($inconsistencias)) {
            throw new InvalidArgumentException(
                'Dependencias de metricas invalidas: ' . implode(', ', $inconsistencias) . '.'
            );
        }
    }

    /**
     * Retorna lista de inconsistencias semanticas entre metricas y dependencias.
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

            $depende = strtolower(trim((string) ($item['depende_de_clave'] ?? '')));
            $regla = strtoupper(trim((string) ($item['regla_dependencia'] ?? '')));

            $index[$clave] = [
                'clave' => $clave,
                'habilitado' => $this->toBool($item['habilitado'] ?? false),
                'obligatorio' => $this->toBool($item['obligatorio'] ?? false),
                'depende_de_clave' => $depende,
                'regla_dependencia' => $regla
            ];
        }

        $inconsistencias = [];
        $reglasSoportadas = [
            self::REGLA_SI_MAYOR_CERO,
            self::REGLA_AMBOS_O_NINGUNO
        ];

        foreach ($index as $clave => $metrica) {
            if ($metrica['obligatorio'] && !$metrica['habilitado']) {
                $inconsistencias[] = 'obligatorio_sin_habilitar:' . $clave;
            }

            $dependeDe = (string) $metrica['depende_de_clave'];
            if ($dependeDe === '') {
                continue;
            }

            if (!isset($index[$dependeDe])) {
                $inconsistencias[] = 'depende_no_existe:' . $clave . '->' . $dependeDe;
                continue;
            }

            $regla = (string) $metrica['regla_dependencia'];
            if ($regla === '') {
                $inconsistencias[] = 'regla_faltante:' . $clave;
                continue;
            }

            if (!in_array($regla, $reglasSoportadas, true)) {
                $inconsistencias[] = 'regla_no_soportada:' . $clave;
                continue;
            }

            $padre = $index[$dependeDe];
            if ($metrica['habilitado'] && !$padre['habilitado']) {
                $inconsistencias[] = 'depende_inactiva:' . $clave . '->' . $dependeDe;
            }

            if (
                $regla === self::REGLA_AMBOS_O_NINGUNO
                && (
                    $metrica['habilitado'] !== $padre['habilitado']
                    || $metrica['obligatorio'] !== $padre['obligatorio']
                )
            ) {
                $inconsistencias[] = 'ambos_o_ninguno:' . $clave . '<->' . $dependeDe;
            }
        }

        $existeAntes = isset($index[self::CLAVE_PUNTUALIDAD_ANTES]);
        $existeDespues = isset($index[self::CLAVE_PUNTUALIDAD_DESPUES]);

        if ($existeAntes xor $existeDespues) {
            $inconsistencias[] = 'puntualidad_ambos_o_ninguno';
        }

        if ($existeAntes && $existeDespues) {
            $antes = $index[self::CLAVE_PUNTUALIDAD_ANTES];
            $despues = $index[self::CLAVE_PUNTUALIDAD_DESPUES];

            if (
                $antes['habilitado'] !== $despues['habilitado']
                || $antes['obligatorio'] !== $despues['obligatorio']
            ) {
                $inconsistencias[] = 'puntualidad_ambos_o_ninguno';
            }
        }

        if ($this->tieneCicloDependencias($index)) {
            $inconsistencias[] = 'ciclo_dependencias';
        }

        return array_values(array_unique($inconsistencias));
    }

    /**
     * Detecta ciclos en el grafo de dependencias de metricas.
     *
     * @param array<string, array<string, mixed>> $index
     * @return bool
     */
    private function tieneCicloDependencias(array $index): bool
    {
        $estado = [];

        $visitar = function (string $clave) use (&$visitar, &$estado, $index): bool {
            $actual = $estado[$clave] ?? 0;
            if ($actual === 1) {
                return true;
            }
            if ($actual === 2) {
                return false;
            }

            $estado[$clave] = 1;

            $dependeDe = (string) ($index[$clave]['depende_de_clave'] ?? '');
            if ($dependeDe !== '' && isset($index[$dependeDe])) {
                if ($visitar($dependeDe)) {
                    return true;
                }
            }

            $estado[$clave] = 2;
            return false;
        };

        foreach (array_keys($index) as $clave) {
            if ($visitar($clave)) {
                return true;
            }
        }

        return false;
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
}
