<?php
declare(strict_types=1);

/**
 * Clase TokenDTO
 *
 * Data Transfer Object para tokens de autenticacion.
 */
final class TokenDTO
{
    /** @var int */
    public int $id;

    /** @var int */
    public int $usuarioId;

    /** @var string */
    public string $tokenHash;

    /** @var string */
    public string $creadoEn;

    /** @var string */
    public string $ultimoUsoEn;

    /** @var string */
    public string $expiraEn;
}
