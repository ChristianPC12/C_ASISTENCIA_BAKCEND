<?php
declare(strict_types=1);

/**
 * DTO base del modulo de campanas.
 */
final class CampanaDTO
{
    public int $id;
    public int $organizacionId;
    public string $nombre;
    public ?string $lema;
    public string $tipo;
    public string $fechaInicio;
    public string $fechaFin;
    public ?string $lugar;
    public ?string $predicador;
    public ?int $responsableUsuarioId;
    public ?string $responsableUsuarioNombre;
    public ?string $descripcion;
    public string $estado;
    public ?string $observaciones;
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
        $this->nombre = '';
        $this->lema = null;
        $this->tipo = 'SEMANA_EVANGELISTICA';
        $this->fechaInicio = '';
        $this->fechaFin = '';
        $this->lugar = null;
        $this->predicador = null;
        $this->responsableUsuarioId = null;
        $this->responsableUsuarioNombre = null;
        $this->descripcion = null;
        $this->estado = 'BORRADOR';
        $this->observaciones = null;
        $this->creadoPor = null;
        $this->actualizadoPor = null;
        $this->eliminadoPor = null;
        $this->creadoEn = '';
        $this->actualizadoEn = '';
        $this->eliminadoEn = null;
    }
}