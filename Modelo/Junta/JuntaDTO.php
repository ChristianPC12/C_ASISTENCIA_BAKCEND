<?php
declare(strict_types=1);

/**
 * DTO principal de juntas de iglesia.
 */
final class JuntaDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $organizacionId,
        public readonly string $fecha,
        public readonly ?string $horaInicio,
        public readonly ?string $horaFin,
        public readonly string $tipo,
        public readonly ?string $moderador,
        public readonly ?string $secretario,
        public readonly string $estado,
        public readonly ?string $observacionesGenerales,
        public readonly ?string $resumenGeneral,
        public readonly ?string $quorumTexto,
        public readonly ?int $juntaAnteriorId,
        public readonly ?string $juntaAnteriorFecha,
        public readonly ?int $creadoPor,
        public readonly ?int $actualizadoPor,
        public readonly ?int $eliminadoPor,
        public readonly ?string $creadoEn,
        public readonly ?string $actualizadoEn,
        public readonly ?string $eliminadoEn
    ) {
    }
}
