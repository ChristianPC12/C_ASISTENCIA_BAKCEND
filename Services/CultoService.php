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

    public function __construct()
    {
        $this->cultoDAO = new CultoDAO();
    }

    /**
     * Lista todos los cultos disponibles.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(): array
    {
        $cultos = $this->cultoDAO->findAll();
        $resultado = [];

        foreach ($cultos as $culto) {
            $resultado[] = CultoMapper::toArray($culto);
        }

        return $resultado;
    }
}
