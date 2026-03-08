<?php
declare(strict_types=1);

/**
 * Validador del modulo de presentaciones.
 */
final class PresentacionValidator
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateGenerar(array $data): array
    {
        if (!isset($data['filtros']) || !is_array($data['filtros'])) {
            throw new InvalidArgumentException('El objeto "filtros" es obligatorio.');
        }

        $filtros = $data['filtros'];
        $anio = isset($filtros['anio']) ? (int) $filtros['anio'] : 0;
        $mes = isset($filtros['mes']) ? (int) $filtros['mes'] : 0;

        if ($anio < 2000 || $anio > 2100) {
            throw new InvalidArgumentException('El filtro "anio" es obligatorio y debe estar entre 2000 y 2100.');
        }

        if ($mes < 1 || $mes > 12) {
            throw new InvalidArgumentException('El filtro "mes" es obligatorio y debe estar entre 1 y 12.');
        }

        $culto = null;
        if (isset($filtros['culto']) && trim((string) $filtros['culto']) !== '') {
            $culto = strtoupper(trim((string) $filtros['culto']));
            if (preg_match('/^[A-Z_]{3,20}$/', $culto) !== 1) {
                throw new InvalidArgumentException('El filtro "culto" tiene un formato invalido.');
            }
        }

        return [
            'anio' => $anio,
            'mes' => $mes,
            'culto' => $culto,
            'trimestre' => isset($filtros['trimestre']) ? (int) $filtros['trimestre'] : null,
            'fecha_exacta' => isset($filtros['fecha_exacta'])
                ? trim((string) $filtros['fecha_exacta'])
                : null
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public static function validateList(array $query): array
    {
        $anio = isset($query['anio']) && $query['anio'] !== '' ? (int) $query['anio'] : null;
        $mes = isset($query['mes']) && $query['mes'] !== '' ? (int) $query['mes'] : null;
        $culto = isset($query['culto']) ? strtoupper(trim((string) $query['culto'])) : null;
        $usuarioId = isset($query['usuario_id']) && $query['usuario_id'] !== '' ? (int) $query['usuario_id'] : null;

        if ($anio !== null && ($anio < 2000 || $anio > 2100)) {
            throw new InvalidArgumentException('El filtro "anio" no es valido.');
        }

        if ($mes !== null && ($mes < 1 || $mes > 12)) {
            throw new InvalidArgumentException('El filtro "mes" no es valido.');
        }

        if ($culto !== null && $culto !== '' && preg_match('/^[A-Z_]{3,20}$/', $culto) !== 1) {
            throw new InvalidArgumentException('El filtro "culto" no es valido.');
        }

        if ($usuarioId !== null && $usuarioId <= 0) {
            throw new InvalidArgumentException('El filtro "usuario_id" no es valido.');
        }

        $page = isset($query['page']) ? (int) $query['page'] : 1;
        $limit = isset($query['limit']) ? (int) $query['limit'] : 20;

        if ($page < 1) {
            $page = 1;
        }

        if ($limit < 1) {
            $limit = 20;
        }

        if ($limit > 100) {
            $limit = 100;
        }

        return [
            'anio' => $anio,
            'mes' => $mes,
            'culto' => $culto !== '' ? $culto : null,
            'usuario_id' => $usuarioId,
            'page' => $page,
            'limit' => $limit
        ];
    }
}
