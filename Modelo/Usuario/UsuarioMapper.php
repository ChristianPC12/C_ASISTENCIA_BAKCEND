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
        $dto->passwordActualizadaEn = (string) ($row['password_actualizada_en'] ?? '');
        $dto->passwordExpiraEn = (string) ($row['password_expira_en'] ?? '');
        $dto->rolId           = (int) $row['rol_id'];
        $dto->rolNombre       = (string) ($row['rol_nombre'] ?? '');
        $dto->activo          = (bool) ($row['activo'] ?? true);
        $dto->organizacionId  = (int) ($row['organizacion_id'] ?? 0);
        $dto->codigoInstancia = (string) ($row['codigo_instancia'] ?? '');
        $dto->tipoOrganizacion = (string) ($row['tipo_organizacion'] ?? '');
        $dto->nombreOrganizacion = (string) ($row['nombre_organizacion'] ?? '');
        $dto->organizacionActiva = (bool) ($row['organizacion_activa'] ?? false);
        $dto->campoCodigo     = (string) ($row['campo_codigo'] ?? '');
        $dto->campoNombre     = (string) ($row['campo_nombre'] ?? '');
        $dto->distritoCodigo  = (string) ($row['distrito_codigo'] ?? '');
        $dto->distritoNombre  = (string) ($row['distrito_nombre'] ?? '');
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
            'organizacion_id'  => $dto->organizacionId > 0 ? $dto->organizacionId : null,
            'codigo_instancia' => $dto->codigoInstancia !== '' ? $dto->codigoInstancia : null,
            'tipo_organizacion' => $dto->tipoOrganizacion !== '' ? $dto->tipoOrganizacion : null,
            'nombre_organizacion' => $dto->nombreOrganizacion !== '' ? $dto->nombreOrganizacion : null,
            'campo'            => $dto->campoCodigo !== '' ? $dto->campoCodigo : null,
            'campo_nombre'     => $dto->campoNombre !== '' ? $dto->campoNombre : null,
            'distrito'         => $dto->distritoCodigo !== '' ? $dto->distritoCodigo : null,
            'distrito_nombre'  => $dto->distritoNombre !== '' ? $dto->distritoNombre : null,
            'organizacion_activa' => $dto->organizacionActiva,
            'password_actualizada_en' => $dto->passwordActualizadaEn,
            'password_expira_en' => $dto->passwordExpiraEn !== ''
                ? $dto->passwordExpiraEn
                : self::calcularExpiracionPassword($dto->passwordActualizadaEn),
            'creado_en'        => $dto->creadoEn,
            'actualizado_en'   => $dto->actualizadoEn,
            'tenant'           => self::tenantToArray($dto)
        ];
    }

    /**
     * Convierte el bloque de tenant para respuestas de auth.
     *
     * @param UsuarioDTO $dto
     * @return array<string, mixed>
     */
    public static function tenantToArray(UsuarioDTO $dto): array
    {
        return [
            'organizacion_id'  => $dto->organizacionId > 0 ? $dto->organizacionId : null,
            'codigo_instancia' => $dto->codigoInstancia !== '' ? $dto->codigoInstancia : null,
            'tipo_organizacion' => $dto->tipoOrganizacion !== '' ? $dto->tipoOrganizacion : null,
            'nombre_organizacion' => $dto->nombreOrganizacion !== '' ? $dto->nombreOrganizacion : null,
            'campo'            => $dto->campoCodigo !== '' ? $dto->campoCodigo : null,
            'campo_nombre'     => $dto->campoNombre !== '' ? $dto->campoNombre : null,
            'distrito'         => $dto->distritoCodigo !== '' ? $dto->distritoCodigo : null,
            'distrito_nombre'  => $dto->distritoNombre !== '' ? $dto->distritoNombre : null,
            'activa'           => $dto->organizacionActiva
        ];
    }

    private static function calcularExpiracionPassword(string $passwordActualizadaEn): string
    {
        if ($passwordActualizadaEn === '') {
            return '';
        }

        try {
            $fecha = new DateTimeImmutable($passwordActualizadaEn);
            return $fecha->modify('+' . PASSWORD_EXPIRY_DAYS . ' days')->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return '';
        }
    }
}
