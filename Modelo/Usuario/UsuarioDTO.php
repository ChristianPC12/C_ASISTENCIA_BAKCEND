<?php
declare(strict_types=1);

/**
 * Clase UsuarioDTO
 *
 * Data Transfer Object para la entidad Usuario.
 * Contiene los datos de un usuario sin logica de negocio.
 */
final class UsuarioDTO
{
    /** @var int */
    public int $id;

    /** @var string */
    public string $nombreCompleto;

    /** @var string */
    public string $usuario;

    /** @var string */
    public string $passwordHash;

    /** @var int */
    public int $rolId;

    /** @var string */
    public string $rolNombre;

    /** @var bool */
    public bool $activo;

    /** @var string */
    public string $creadoEn;

    /** @var string */
    public string $actualizadoEn;
}
