<?php
declare(strict_types=1);

/**
 * Clase OrganizacionDTO
 *
 * DTO de la entidad organizacion (tenant).
 */
final class OrganizacionDTO
{
    public int $id;
    public int $campoId;
    public string $campoCodigo;
    public string $campoNombre;
    public string $codigoInstancia;
    public string $tipoOrganizacion;
    public string $nombreOrganizacion;
    public ?string $correoContacto;
    public bool $activa;
    public string $creadoEn;
    public string $actualizadoEn;

    public function __construct()
    {
        $this->id = 0;
        $this->campoId = 0;
        $this->campoCodigo = '';
        $this->campoNombre = '';
        $this->codigoInstancia = '';
        $this->tipoOrganizacion = '';
        $this->nombreOrganizacion = '';
        $this->correoContacto = null;
        $this->activa = true;
        $this->creadoEn = '';
        $this->actualizadoEn = '';
    }
}

