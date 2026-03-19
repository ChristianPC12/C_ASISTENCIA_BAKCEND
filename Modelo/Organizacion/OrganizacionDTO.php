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
    public ?int $distritoId;
    public ?string $distritoCodigo;
    public ?string $distritoNombre;
    public string $codigoInstancia;
    public string $tipoOrganizacion;
    public string $nombreOrganizacion;
    public ?string $correoContacto;
    public bool $activa;
    public bool $tieneAdminActivo;
    public bool $tieneAdminTemporalRegistrado;
    public bool $adminTemporalActivo;
    public ?string $adminUsuarioActivo;
    public ?string $adminPasswordExpiraEn;
    public ?string $adminUsuarioTemporal;
    public ?string $adminTemporalExpiraEn;
    public string $creadoEn;
    public string $actualizadoEn;

    public function __construct()
    {
        $this->id = 0;
        $this->campoId = 0;
        $this->campoCodigo = '';
        $this->campoNombre = '';
        $this->distritoId = null;
        $this->distritoCodigo = null;
        $this->distritoNombre = null;
        $this->codigoInstancia = '';
        $this->tipoOrganizacion = '';
        $this->nombreOrganizacion = '';
        $this->correoContacto = null;
        $this->activa = true;
        $this->tieneAdminActivo = false;
        $this->tieneAdminTemporalRegistrado = false;
        $this->adminTemporalActivo = false;
        $this->adminUsuarioActivo = null;
        $this->adminPasswordExpiraEn = null;
        $this->adminUsuarioTemporal = null;
        $this->adminTemporalExpiraEn = null;
        $this->creadoEn = '';
        $this->actualizadoEn = '';
    }
}
