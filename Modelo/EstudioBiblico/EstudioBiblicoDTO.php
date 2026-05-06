<?php
declare(strict_types=1);

/**
 * DTO base de estudios biblicos.
 */
final class EstudioBiblicoDTO
{
    public int $id;
    public int $organizacionId;
    public int $contactoId;
    public ?int $visitaAsistenteId;
    public string $contactoNombre;
    public ?string $contactoTelefono;
    public ?string $contactoCorreo;
    public ?string $contactoDireccion;
    public ?string $contactoBarrioComunidad;
    public ?string $origenClave;
    public ?int $campanaOrigenId;
    public ?string $campanaOrigenNombre;
    public ?int $pcOrigenId;
    public ?int $instructorPrincipalContactoId;
    public ?string $instructorPrincipalNombre;
    public ?int $instructorSecundarioContactoId;
    public ?string $instructorSecundarioNombre;
    public ?int $responsableUsuarioId;
    public ?string $responsableUsuarioNombre;
    public ?string $responsableUsuarioCargo;
    public string $modalidad;
    public ?string $materialEstudio;
    public ?string $leccionActual;
    public int $totalLeccionesCompletadas;
    public string $fechaInicio;
    public string $frecuenciaPeriodo;
    public int $frecuenciaCantidad;
    public ?string $fechaUltimaSesion;
    public ?string $proximaSesion;
    public string $estadoGeneral;
    public ?string $observaciones;
    public ?string $motivoCierrePausa;
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
        $this->contactoId = 0;
        $this->visitaAsistenteId = null;
        $this->contactoNombre = '';
        $this->contactoTelefono = null;
        $this->contactoCorreo = null;
        $this->contactoDireccion = null;
        $this->contactoBarrioComunidad = null;
        $this->origenClave = null;
        $this->campanaOrigenId = null;
        $this->campanaOrigenNombre = null;
        $this->pcOrigenId = null;
        $this->instructorPrincipalContactoId = null;
        $this->instructorPrincipalNombre = null;
        $this->instructorSecundarioContactoId = null;
        $this->instructorSecundarioNombre = null;
        $this->responsableUsuarioId = null;
        $this->responsableUsuarioNombre = null;
        $this->responsableUsuarioCargo = null;
        $this->modalidad = 'INDIVIDUAL';
        $this->materialEstudio = null;
        $this->leccionActual = null;
        $this->totalLeccionesCompletadas = 0;
        $this->fechaInicio = '';
        $this->frecuenciaPeriodo = 'SEMANA';
        $this->frecuenciaCantidad = 1;
        $this->fechaUltimaSesion = null;
        $this->proximaSesion = null;
        $this->estadoGeneral = 'NUEVO';
        $this->observaciones = null;
        $this->motivoCierrePausa = null;
        $this->creadoPor = null;
        $this->actualizadoPor = null;
        $this->eliminadoPor = null;
        $this->creadoEn = '';
        $this->actualizadoEn = '';
        $this->eliminadoEn = null;
    }
}
