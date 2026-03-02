<?php
declare(strict_types=1);

/**
 * Clase AsistenciaMapper
 *
 * Mapea filas de la BD a AsistenciaDTO y viceversa.
 */
final class AsistenciaMapper
{
    /**
     * Convierte una fila de la BD a AsistenciaDTO.
     *
     * @param array<string, mixed> $row Fila de la consulta.
     * @return AsistenciaDTO
     */
    public static function fromRow(array $row): AsistenciaDTO
    {
        $dto = new AsistenciaDTO();
        $dto->id                   = (int) $row['id'];
        $dto->cultoId              = (int) $row['culto_id'];
        $dto->cultoCodigo          = (string) ($row['culto_codigo'] ?? '');
        $dto->cultoNombre          = (string) ($row['culto_nombre'] ?? '');
        $dto->fecha                = (string) $row['fecha'];
        $dto->anio                 = (int) $row['anio'];
        $dto->trimestre            = (int) $row['trimestre'];
        $dto->llegaronAntesHora    = (int) $row['llegaron_antes_hora'];
        $dto->llegaronDespuesHora  = (int) $row['llegaron_despues_hora'];
        $dto->ninos                = (int) $row['ninos'];
        $dto->jovenes              = (int) $row['jovenes'];
        $dto->totalAsistentes      = (int) $row['total_asistentes'];
        $dto->procBarrio           = (int) $row['proc_barrio'];
        $dto->procGuayabo          = (int) $row['proc_guayabo'];
        $dto->visitasBarrio        = (int) $row['visitas_barrio'];
        $dto->nombresVisitasBarrio = $row['nombres_visitas_barrio'] !== null ? (string) $row['nombres_visitas_barrio'] : null;
        $dto->visitasGuayabo       = (int) $row['visitas_guayabo'];
        $dto->nombresVisitasGuayabo = $row['nombres_visitas_guayabo'] !== null ? (string) $row['nombres_visitas_guayabo'] : null;
        $dto->retirosAntesTerminar = (int) $row['retiros_antes_terminar'];
        $dto->seQuedaronTodo       = (int) $row['se_quedaron_todo'];
        $dto->observaciones        = $row['observaciones'] !== null ? (string) $row['observaciones'] : null;
        $dto->registradoPor        = (int) $row['registrado_por'];
        $dto->registradoPorNombre  = (string) ($row['registrado_por_nombre'] ?? '');
        $dto->creadoEn             = (string) $row['creado_en'];
        $dto->actualizadoEn        = (string) $row['actualizado_en'];

        return $dto;
    }

    /**
     * Convierte un AsistenciaDTO a array para respuesta JSON.
     *
     * @param AsistenciaDTO $dto DTO a convertir.
     * @return array<string, mixed>
     */
    public static function toArray(AsistenciaDTO $dto): array
    {
        return [
            'id'                     => $dto->id,
            'culto_id'               => $dto->cultoId,
            'culto_codigo'           => $dto->cultoCodigo,
            'culto_nombre'           => $dto->cultoNombre,
            'fecha'                  => $dto->fecha,
            'anio'                   => $dto->anio,
            'trimestre'              => $dto->trimestre,
            'llegaron_antes_hora'    => $dto->llegaronAntesHora,
            'llegaron_despues_hora'  => $dto->llegaronDespuesHora,
            'ninos'                  => $dto->ninos,
            'jovenes'                => $dto->jovenes,
            'total_asistentes'       => $dto->totalAsistentes,
            'proc_barrio'            => $dto->procBarrio,
            'proc_guayabo'           => $dto->procGuayabo,
            'visitas_barrio'         => $dto->visitasBarrio,
            'nombres_visitas_barrio' => $dto->nombresVisitasBarrio,
            'visitas_guayabo'        => $dto->visitasGuayabo,
            'nombres_visitas_guayabo' => $dto->nombresVisitasGuayabo,
            'retiros_antes_terminar' => $dto->retirosAntesTerminar,
            'se_quedaron_todo'       => $dto->seQuedaronTodo,
            'observaciones'          => $dto->observaciones,
            'registrado_por'         => $dto->registradoPor,
            'registrado_por_nombre'  => $dto->registradoPorNombre,
            'creado_en'              => $dto->creadoEn,
            'actualizado_en'         => $dto->actualizadoEn
        ];
    }
}
