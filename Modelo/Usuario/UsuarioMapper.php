<?php
declare(strict_types=1);

/**
 * Clase UsuarioMapper
 *
 * Mapea filas de la BD (array asociativo) a UsuarioDTO.
 */
final class UsuarioMapper
{
    /**
     * Convierte una fila de la BD a UsuarioDTO.
     *
     * @param array<string, mixed> $row Fila de la consulta.
     * @return UsuarioDTO
     */
    public static function fromRow(array $row): UsuarioDTO
    {
        $dto = new UsuarioDTO();
        $dto->id              = (int) $row['id'];
        $dto->nombreCompleto  = (string) $row['nombre_completo'];
        $dto->usuario         = (string) $row['usuario'];
        $dto->passwordHash    = (string) ($row['password_hash'] ?? '');
        $dto->rolId           = (int) $row['rol_id'];
        $dto->rolNombre       = (string) ($row['rol_nombre'] ?? '');
        $dto->activo          = (bool) ($row['activo'] ?? true);
        $dto->creadoEn        = (string) ($row['creado_en'] ?? '');
        $dto->actualizadoEn   = (string) ($row['actualizado_en'] ?? '');

        return $dto;
    }

    /**
     * Convierte un UsuarioDTO a array para respuesta JSON (sin password).
     *
     * @param UsuarioDTO $dto DTO a convertir.
     * @return array<string, mixed>
     */
    public static function toArray(UsuarioDTO $dto): array
    {
        return [
            'id'               => $dto->id,
            'nombre_completo'  => $dto->nombreCompleto,
            'usuario'          => $dto->usuario,
            'rol_id'           => $dto->rolId,
            'rol'              => $dto->rolNombre,
            'activo'           => $dto->activo,
            'creado_en'        => $dto->creadoEn,
            'actualizado_en'   => $dto->actualizadoEn
        ];
    }
}
