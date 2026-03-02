<?php
declare(strict_types=1);

/**
 * Clase TokenMapper
 *
 * Mapea filas de la BD a TokenDTO.
 */
final class TokenMapper
{
    /**
     * Convierte una fila de la BD a TokenDTO.
     *
     * @param array<string, mixed> $row Fila de la consulta.
     * @return TokenDTO
     */
    public static function fromRow(array $row): TokenDTO
    {
        $dto = new TokenDTO();
        $dto->id        = (int) $row['id'];
        $dto->usuarioId = (int) $row['usuario_id'];
        $dto->tokenHash = (string) $row['token_hash'];
        $dto->creadoEn  = (string) $row['creado_en'];

        return $dto;
    }
}
