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
        $dto->distritoId = isset($row['distrito_id']) && $row['distrito_id'] !== null
            ? (int) $row['distrito_id']
            : null;
        $dto->distritoCodigo = isset($row['distrito_codigo']) && $row['distrito_codigo'] !== null
            ? (string) $row['distrito_codigo']
            : null;
        $dto->distritoNombre = isset($row['distrito_nombre']) && $row['distrito_nombre'] !== null
            ? (string) $row['distrito_nombre']
            : null;
        $dto->codigoInstancia = (string) ($row['codigo_instancia'] ?? '');
        $dto->tipoOrganizacion = (string) ($row['tipo_organizacion'] ?? '');
        $dto->nombreOrganizacion = (string) ($row['nombre_organizacion'] ?? '');

        $correo = $row['correo_contacto'] ?? null;
        $dto->correoContacto = $correo !== null ? (string) $correo : null;

        $dto->activa = (int) ($row['activa'] ?? 0) === 1;
        $dto->tieneAdminActivo = (int) ($row['tiene_admin_activo'] ?? 0) === 1;
        $dto->adminTemporalActivo = (int) ($row['admin_temporal_activo'] ?? 0) === 1;
        $dto->adminUsuarioActivo = isset($row['admin_usuario_activo']) && $row['admin_usuario_activo'] !== null
            ? (string) $row['admin_usuario_activo']
            : null;
        $dto->adminPasswordExpiraEn = isset($row['admin_password_expira_en']) && $row['admin_password_expira_en'] !== null
            ? (string) $row['admin_password_expira_en']
            : null;
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
            'distrito' => $dto->distritoCodigo,
            'distrito_codigo' => $dto->distritoCodigo,
            'distrito_nombre' => $dto->distritoNombre,
            'codigo_instancia' => $dto->codigoInstancia,
            'tipo_organizacion' => $dto->tipoOrganizacion,
            'nombre_organizacion' => $dto->nombreOrganizacion,
            'correo_contacto' => $dto->correoContacto,
            'activa' => $dto->activa,
            'tiene_admin_activo' => $dto->tieneAdminActivo,
            'admin_temporal_activo' => $dto->adminTemporalActivo,
            'admin_usuario_activo' => $dto->adminUsuarioActivo,
            'admin_password_expira_en' => $dto->adminPasswordExpiraEn,
            'creado_en' => $dto->creadoEn,
            'actualizado_en' => $dto->actualizadoEn
        ];
    }
}
