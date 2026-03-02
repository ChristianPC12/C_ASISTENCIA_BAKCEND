<?php
declare(strict_types=1);

/**
 * Clase AsistenciaDTO
 *
 * Data Transfer Object para la entidad Asistencia.
 */
final class AsistenciaDTO
{
    /** @var int */
    public int $id;

    /** @var int */
    public int $cultoId;

    /** @var string */
    public string $cultoCodigo;

    /** @var string */
    public string $cultoNombre;

    /** @var string */
    public string $fecha;

    /** @var int */
    public int $anio;

    /** @var int */
    public int $trimestre;

    /** @var int */
    public int $llegaronAntesHora;

    /** @var int */
    public int $llegaronDespuesHora;

    /** @var int */
    public int $ninos;

    /** @var int */
    public int $jovenes;

    /** @var int */
    public int $totalAsistentes;

    /** @var int */
    public int $procBarrio;

    /** @var int */
    public int $procGuayabo;

    /** @var int */
    public int $visitasBarrio;

    /** @var string|null */
    public ?string $nombresVisitasBarrio;

    /** @var int */
    public int $visitasGuayabo;

    /** @var string|null */
    public ?string $nombresVisitasGuayabo;

    /** @var int */
    public int $retirosAntesTerminar;

    /** @var int */
    public int $seQuedaronTodo;

    /** @var string|null */
    public ?string $observaciones;

    /** @var int */
    public int $registradoPor;

    /** @var string */
    public string $registradoPorNombre;

    /** @var string */
    public string $creadoEn;

    /** @var string */
    public string $actualizadoEn;
}
