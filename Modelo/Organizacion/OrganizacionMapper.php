<?php
declare(strict_types=1);

/**
 * Clase OrganizacionMapper
 *
 * Convierte entre filas SQL, DTO y arreglo de salida.
 */
final class OrganizacionMapper
{
    /**
     * Convierte una fila de BD en OrganizacionDTO.
     *
     * @param array<string, mixed> $row
     * @return OrganizacionDTO
     */
    public static function fromRow(array $row): OrganizacionDTO
    {
        $dto = new OrganizacionDTO();

        $dto->id = (int) ($row['id'] ?? 0);
        $dto->campoId = (int) ($row['campo_id'] ?? 0);
        $dto->campoCodigo = (string) ($row['campo_codigo'] ?? '');
        $dto->campoNombre = (string) ($row['campo_nombre'] ?? '');
        $dto->codigoInstancia = (string) ($row['codigo_instancia'] ?? '');
        $dto->tipoOrganizacion = (string) ($row['tipo_organizacion'] ?? '');
        $dto->nombreOrganizacion = (string) ($row['nombre_organizacion'] ?? '');

        $correo = $row['correo_contacto'] ?? null;
        $dto->correoContacto = $correo !== null ? (string) $correo : null;

        $dto->activa = (int) ($row['activa'] ?? 0) === 1;
        $dto->creadoEn = (string) ($row['creado_en'] ?? '');
        $dto->actualizadoEn = (string) ($row['actualizado_en'] ?? '');

        return $dto;
    }

    /**
     * Convierte OrganizacionDTO en arreglo para respuesta.
     *
     * @param OrganizacionDTO $dto
     * @return array<string, mixed>
     */
    public static function toArray(OrganizacionDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'campo' => $dto->campoCodigo,
            'campo_nombre' => $dto->campoNombre,
            'codigo_instancia' => $dto->codigoInstancia,
            'tipo_organizacion' => $dto->tipoOrganizacion,
            'nombre_organizacion' => $dto->nombreOrganizacion,
            'correo_contacto' => $dto->correoContacto,
            'activa' => $dto->activa,
            'creado_en' => $dto->creadoEn,
            'actualizado_en' => $dto->actualizadoEn
        ];
    }
}

