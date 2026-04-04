<?php
declare(strict_types=1);

/**
 * DTO base para contactos misioneros reutilizables.
 */
final class ContactoMisioneroDTO
{
    public int $id;
    public int $organizacionId;
    public string $nombreCompleto;
    public ?string $telefono;
    public string $telefonoNormalizado;
    public ?string $correo;
    public ?string $direccion;
    public ?string $barrioComunidad;
    public string $clasificacionPrincipal;
    public bool $esMiembro;
    public string $estadoContacto;
    public ?string $fechaPrimerContacto;
    public ?string $fechaUltimoContacto;
    public ?string $origenPrincipalClave;
    public ?string $moduloOrigen;
    public ?int $referenciaOrigenId;
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
        $this->nombreCompleto = '';
        $this->telefono = null;
        $this->telefonoNormalizado = '';
        $this->correo = null;
        $this->direccion = null;
        $this->barrioComunidad = null;
        $this->clasificacionPrincipal = 'INTERESADO';
        $this->esMiembro = false;
        $this->estadoContacto = 'ACTIVO';
        $this->fechaPrimerContacto = null;
        $this->fechaUltimoContacto = null;
        $this->origenPrincipalClave = null;
        $this->moduloOrigen = null;
        $this->referenciaOrigenId = null;
        $this->observacionesGenerales = null;
        $this->creadoPor = null;
        $this->actualizadoPor = null;
        $this->eliminadoPor = null;
        $this->creadoEn = '';
        $this->actualizadoEn = '';
        $this->eliminadoEn = null;
    }
}
