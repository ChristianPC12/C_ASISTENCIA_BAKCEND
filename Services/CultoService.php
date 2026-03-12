<?php
declare(strict_types=1);

/**
 * Clase CultoService
 *
 * Logica de negocio para la entidad Culto.
 * No toca $_SERVER, $_POST, $_GET.
 */
final class CultoService
{
    /** @var CultoDAO */
    private CultoDAO $cultoDAO;
    /** @var SetupDAO */
    private SetupDAO $setupDAO;

    public function __construct()
    {
        $this->cultoDAO = new CultoDAO();
        $this->setupDAO = new SetupDAO();
    }

    /**
     * Lista todos los cultos disponibles.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        if ($organizacionId > 0) {
            $cultosSetup = $this->setupDAO->getCultos($organizacionId);
            $resultado = [];

            foreach ($cultosSetup as $cultoOrg) {
                $activo = filter_var($cultoOrg['activo'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($activo !== true) {
                    continue;
                }

                $cultoGlobal = $this->cultoDAO->ensureFromOrganizacionCulto($organizacionId, $cultoOrg);

                $resultado[] = [
                    'id' => $cultoGlobal->id,
                    'codigo' => (string) ($cultoOrg['codigo'] ?? $cultoGlobal->codigo),
                    'nombre' => (string) ($cultoOrg['nombre'] ?? $cultoGlobal->nombre),
                    'dia_semana' => (int) ($cultoOrg['dia_semana'] ?? $cultoGlobal->diaSemana),
                    'hora_inicio' => (string) ($cultoOrg['hora_inicio'] ?? $cultoGlobal->horaInicio)
                ];
            }

            if (!empty($resultado)) {
                return $resultado;
            }
        }

        $cultosLegacy = $this->cultoDAO->findAll();
        return array_map(static fn(CultoDTO $culto): array => CultoMapper::toArray($culto), $cultosLegacy);
    }
}
