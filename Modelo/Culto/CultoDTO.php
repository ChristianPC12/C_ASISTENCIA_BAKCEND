<?php
declare(strict_types=1);

/**
 * Clase CultoDTO
 *
 * Data Transfer Object para la entidad Culto.
 */
final class CultoDTO
{
    /** @var int */
    public int $id;

    /** @var string */
    public string $codigo;

    /** @var string */
    public string $nombre;

    /** @var int */
    public int $diaSemana;

    /** @var string */
    public string $horaInicio;
}
