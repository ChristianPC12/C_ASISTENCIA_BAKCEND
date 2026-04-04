<?php
declare(strict_types=1);

/**
 * DTO base de pequenas congregaciones.
 */
final class PcDTO
{
    public int $id;
    public int $organizacionId;
    public string $nombrePc;
    public ?string $sector;
    public ?string $comunidad;
    public ?string $direccionReunion;
    public ?int $anfitrionContactoId;
    public ?string $anfitrionNombre;
    public ?int $liderPrincipalContactoId;
    public ?string $liderPrincipalNombre;
    public ?int $liderAuxiliarContactoId;
    public ?string $liderAuxiliarNombre;
    public string $fechaInicio;
    public ?string $fechaFin;
    public ?int $diaReunion;
    public ?string $horaReunion;
    public string $estado;
    public ?int $pcMadreId;
    public ?string $pcMadreNombre;
    public ?string $motivoCierre;
    public ?string $metaTrimestral;
    public ?string $observacionesGenerales;
    public ?int $creadoPor;
    public ?int $actualizadoPor;
    public ?int $eliminadoPor;
    public string $creadoEn;
    public string $actualizadoEn;
    public ?string $eliminadoEn;

    public function __construct()
    {
        $this->id = 0;
        $this->organizacionId = 0;
        $this->nombrePc = '';
        $this->sector = null;
        $this->comunidad = null;
        $this->direccionReunion = null;
        $this->anfitrionContactoId = null;
        $this->anfitrionNombre = null;
        $this->liderPrincipalContactoId = null;
        $this->liderPrincipalNombre = null;
        $this->liderAuxiliarContactoId = null;
        $this->liderAuxiliarNombre = null;
        $this->fechaInicio = '';
        $this->fechaFin = null;
        $this->diaReunion = null;
        $this->horaReunion = null;
        $this->estado = 'ACTIVA';
        $this->pcMadreId = null;
        $this->pcMadreNombre = null;
        $this->motivoCierre = null;
        $this->metaTrimestral = null;
        $this->observacionesGenerales = null;
        $this->creadoPor = null;
        $this->actualizadoPor = null;
        $this->eliminadoPor = null;
        $this->creadoEn = '';
        $this->actualizadoEn = '';
        $this->eliminadoEn = null;
    }
}
