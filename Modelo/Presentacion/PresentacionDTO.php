<?php
declare(strict_types=1);

/**
 * DTO de presentacion generada por IA.
 */
final class PresentacionDTO
{
    /** @var int */
    public int $id;

    /** @var int */
    public int $usuarioId;

    /** @var string */
    public string $usuarioNombre;

    /** @var string */
    public string $usuarioLogin;

    /** @var int */
    public int $anio;

    /** @var int */
    public int $mes;

    /** @var string|null */
    public ?string $cultoCodigo;

    /** @var array<string, mixed> */
    public array $filtros;

    /** @var array<string, mixed> */
    public array $metricas;

    /** @var string */
    public string $promptVersion;

    /** @var string */
    public string $promptBloqueado;

    /** @var string */
    public string $modelo;

    /** @var array<string, mixed> */
    public array $presentacion;

    /** @var string */
    public string $creadoEn;
}
