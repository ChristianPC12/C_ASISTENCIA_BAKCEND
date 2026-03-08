<?php
declare(strict_types=1);

/**
 * Mapper para la entidad Presentacion.
 */
final class PresentacionMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): PresentacionDTO
    {
        $dto = new PresentacionDTO();
        $dto->id = (int) $row['id'];
        $dto->usuarioId = (int) $row['usuario_id'];
        $dto->usuarioNombre = (string) ($row['usuario_nombre'] ?? '');
        $dto->usuarioLogin = (string) ($row['usuario_login'] ?? '');
        $dto->anio = (int) $row['anio'];
        $dto->mes = (int) $row['mes'];
        $dto->cultoCodigo = isset($row['culto_codigo']) && $row['culto_codigo'] !== ''
            ? (string) $row['culto_codigo']
            : null;
        $dto->filtros = self::decodeJsonArray((string) ($row['filtros_json'] ?? '{}'));
        $dto->metricas = self::decodeJsonArray((string) ($row['metricas_json'] ?? '{}'));
        $dto->promptVersion = (string) ($row['prompt_version'] ?? 'v1');
        $dto->promptBloqueado = (string) ($row['prompt_bloqueado'] ?? '');
        $dto->modelo = (string) ($row['modelo'] ?? '');
        $dto->presentacion = self::decodeJsonArray((string) ($row['presentacion_json'] ?? '{}'));
        $dto->creadoEn = (string) ($row['creado_en'] ?? '');

        return $dto;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(PresentacionDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'usuario_id' => $dto->usuarioId,
            'usuario_nombre' => $dto->usuarioNombre,
            'usuario_login' => $dto->usuarioLogin,
            'anio' => $dto->anio,
            'mes' => $dto->mes,
            'culto_codigo' => $dto->cultoCodigo,
            'filtros' => $dto->filtros,
            'metricas' => $dto->metricas,
            'prompt_version' => $dto->promptVersion,
            'prompt_bloqueado' => $dto->promptBloqueado,
            'modelo' => $dto->modelo,
            'presentacion' => $dto->presentacion,
            'creado_en' => $dto->creadoEn
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toSummaryArray(PresentacionDTO $dto): array
    {
        $resumen = '';
        $secciones = $dto->presentacion['secciones'] ?? [];
        if (is_array($secciones) && isset($secciones[0]) && is_array($secciones[0])) {
            $resumen = (string) ($secciones[0]['resumen'] ?? '');
        }

        return [
            'id' => $dto->id,
            'usuario_id' => $dto->usuarioId,
            'usuario_nombre' => $dto->usuarioNombre,
            'usuario_login' => $dto->usuarioLogin,
            'anio' => $dto->anio,
            'mes' => $dto->mes,
            'culto_codigo' => $dto->cultoCodigo,
            'prompt_version' => $dto->promptVersion,
            'modelo' => $dto->modelo,
            'resumen' => $resumen,
            'creado_en' => $dto->creadoEn
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeJsonArray(string $json): array
    {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
