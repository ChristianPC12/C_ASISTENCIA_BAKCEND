<?php
declare(strict_types=1);

/**
 * Convierte filas SQL y DTOs de contacto misionero.
 */
final class ContactoMisioneroMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): ContactoMisioneroDTO
    {
        $dto = new ContactoMisioneroDTO();
        $dto->id = (int) ($row['id'] ?? 0);
        $dto->organizacionId = (int) ($row['organizacion_id'] ?? 0);
        $dto->nombreCompleto = (string) ($row['nombre_completo'] ?? '');
        $dto->telefono = isset($row['telefono']) && $row['telefono'] !== null ? (string) $row['telefono'] : null;
        $dto->telefonoNormalizado = (string) ($row['telefono_normalizado'] ?? '');
        $dto->correo = isset($row['correo']) && $row['correo'] !== null ? (string) $row['correo'] : null;
        $dto->direccion = isset($row['direccion']) && $row['direccion'] !== null ? (string) $row['direccion'] : null;
        $dto->barrioComunidad = isset($row['barrio_comunidad']) && $row['barrio_comunidad'] !== null
            ? (string) $row['barrio_comunidad']
            : null;
        $dto->clasificacionPrincipal = (string) ($row['clasificacion_principal'] ?? 'INTERESADO');
        $dto->esMiembro = (int) ($row['es_miembro'] ?? 0) === 1;
        $dto->estadoContacto = (string) ($row['estado_contacto'] ?? 'ACTIVO');
        $dto->fechaPrimerContacto = isset($row['fecha_primer_contacto']) && $row['fecha_primer_contacto'] !== null
            ? (string) $row['fecha_primer_contacto']
            : null;
        $dto->fechaUltimoContacto = isset($row['fecha_ultimo_contacto']) && $row['fecha_ultimo_contacto'] !== null
            ? (string) $row['fecha_ultimo_contacto']
            : null;
        $dto->origenPrincipalClave = isset($row['origen_principal_clave']) && $row['origen_principal_clave'] !== null
            ? (string) $row['origen_principal_clave']
            : null;
        $dto->moduloOrigen = isset($row['modulo_origen']) && $row['modulo_origen'] !== null
            ? (string) $row['modulo_origen']
            : null;
        $dto->referenciaOrigenId = isset($row['referencia_origen_id']) && $row['referencia_origen_id'] !== null
            ? (int) $row['referencia_origen_id']
            : null;
        $dto->observacionesGenerales = isset($row['observaciones_generales']) && $row['observaciones_generales'] !== null
            ? (string) $row['observaciones_generales']
            : null;
        $dto->creadoPor = isset($row['creado_por']) && $row['creado_por'] !== null ? (int) $row['creado_por'] : null;
        $dto->actualizadoPor = isset($row['actualizado_por']) && $row['actualizado_por'] !== null
            ? (int) $row['actualizado_por']
            : null;
        $dto->eliminadoPor = isset($row['eliminado_por']) && $row['eliminado_por'] !== null
            ? (int) $row['eliminado_por']
            : null;
        $dto->creadoEn = (string) ($row['creado_en'] ?? '');
        $dto->actualizadoEn = (string) ($row['actualizado_en'] ?? '');
        $dto->eliminadoEn = isset($row['eliminado_en']) && $row['eliminado_en'] !== null ? (string) $row['eliminado_en'] : null;

        return $dto;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(ContactoMisioneroDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'organizacion_id' => $dto->organizacionId,
            'nombre_completo' => $dto->nombreCompleto,
            'telefono' => $dto->telefono,
            'telefono_normalizado' => $dto->telefonoNormalizado,
            'correo' => $dto->correo,
            'direccion' => $dto->direccion,
            'barrio_comunidad' => $dto->barrioComunidad,
            'clasificacion_principal' => $dto->clasificacionPrincipal,
            'es_miembro' => $dto->esMiembro,
            'estado_contacto' => $dto->estadoContacto,
            'fecha_primer_contacto' => $dto->fechaPrimerContacto,
            'fecha_ultimo_contacto' => $dto->fechaUltimoContacto,
            'origen_principal_clave' => $dto->origenPrincipalClave,
            'modulo_origen' => $dto->moduloOrigen,
            'referencia_origen_id' => $dto->referenciaOrigenId,
            'observaciones_generales' => $dto->observacionesGenerales,
            'creado_por' => $dto->creadoPor,
            'actualizado_por' => $dto->actualizadoPor,
            'eliminado_por' => $dto->eliminadoPor,
            'creado_en' => $dto->creadoEn,
            'actualizado_en' => $dto->actualizadoEn,
            'eliminado_en' => $dto->eliminadoEn
        ];
    }
}
