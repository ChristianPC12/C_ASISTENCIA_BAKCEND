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

    /** @var string */
    public string $passwordActualizadaEn;

    /** @var string */
    public string $passwordExpiraEn;

    /** @var int */
    public int $rolId;

    /** @var string */
    public string $rolNombre;

    /** @var bool */
    public bool $activo;

    /** @var int */
    public int $organizacionId;

    /** @var string */
    public string $codigoInstancia;

    /** @var string */
    public string $tipoOrganizacion;

    /** @var string */
    public string $nombreOrganizacion;

    /** @var bool */
    public bool $organizacionActiva;

    /** @var string */
    public string $campoCodigo;

    /** @var string */
    public string $campoNombre;

    /** @var string */
    public string $creadoEn;

    /** @var string */
    public string $actualizadoEn;
}
