<?php
declare(strict_types=1);

/**
 * Clase OrganizacionValidator
 *
 * Valida entrada para API superadmin de organizaciones.
 */
final class OrganizacionValidator
{
    private const NOMBRE_MIN = 5;
    private const NOMBRE_MAX = 30;
    private const NOMBRE_ADMIN_MAX = 30;
    private const EMAIL_MAX = 30;

    private const NOMBRE_REGEX = "/^(?!.*\\d)[\\p{L}\\s\\.,\\-\\'\\(\\)]+$/u";
    private const EMAIL_STRICT_REGEX = "/^(?=.{1,30}$)[A-Z0-9.!#$%&'*+\\/=?^_`{|}~-]+@[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])(?:\\.[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9]))+$/i";

    /**
     * Valida filtros de listado.
     *
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public static function validateListFilters(array $query): array
    {
        $validated = [];

        if (array_key_exists('campo', $query)) {
            $validated['campo'] = self::validateCampo($query['campo'], false);
        }

        if (array_key_exists('distrito', $query)) {
            $validated['distrito'] = self::validateDistrito($query['distrito'], false);
        }

        if (array_key_exists('tipo', $query)) {
            $validated['tipo'] = self::validateTipo($query['tipo'], false);
        }

        $validated['estado'] = self::validateEstado($query['estado'] ?? 'TODAS');

        if (array_key_exists('q', $query)) {
            $q = self::toCleanString($query['q']);
            if (strlen($q) > 80) {
                throw new InvalidArgumentException('El filtro q no puede superar 80 caracteres.');
            }
            $validated['q'] = $q !== '' ? $q : null;
        } else {
            $validated['q'] = null;
        }

        $validated['page'] = self::validatePage($query['page'] ?? 1);
        $validated['limit'] = self::validateLimit($query['limit'] ?? 20);

        return $validated;
    }

    /**
     * Valida body para crear organizacion.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateCreate(array $data): array
    {
        return [
            'campo' => self::validateCampo($data['campo'] ?? null, true),
            'distrito' => self::validateDistrito($data['distrito'] ?? null, true),
            'tipo_organizacion' => self::validateTipo($data['tipo_organizacion'] ?? null, true),
            'nombre_organizacion' => self::validateNombre($data['nombre_organizacion'] ?? null),
            'correo_contacto' => self::validateCorreo($data['correo_contacto'] ?? null)
        ];
    }

    /**
     * Valida body para actualizar organizacion.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateUpdate(array $data): array
    {
        return [
            'distrito' => self::validateDistrito($data['distrito'] ?? null, true),
            'tipo_organizacion' => self::validateTipo($data['tipo_organizacion'] ?? null, true),
            'nombre_organizacion' => self::validateNombre($data['nombre_organizacion'] ?? null),
            'correo_contacto' => self::validateCorreo($data['correo_contacto'] ?? null),
            'activa' => self::validateActiva($data['activa'] ?? null, false)
        ];
    }

    /**
     * Valida body para crear ADMIN temporal de una organizacion.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function validateCreateAdminTemporal(array $data): array
    {
        if (!isset($data['nombre_completo']) || !is_string($data['nombre_completo'])) {
            throw new InvalidArgumentException('El campo "nombre_completo" es obligatorio.');
        }

        $nombreCompleto = Sanitizer::cleanString($data['nombre_completo']);
        $nombreLen = strlen($nombreCompleto);
        if ($nombreLen < self::NOMBRE_MIN || $nombreLen > self::NOMBRE_ADMIN_MAX) {
            throw new InvalidArgumentException('El nombre completo debe tener entre 5 y 30 caracteres.');
        }
        if (preg_match(self::NOMBRE_REGEX, $nombreCompleto) !== 1) {
            throw new InvalidArgumentException('El nombre completo no permite numeros ni simbolos no validos.');
        }

        if (!isset($data['usuario']) || !is_string($data['usuario'])) {
            throw new InvalidArgumentException('El campo "usuario" es obligatorio.');
        }

        $usuario = strtolower(Sanitizer::cleanString($data['usuario']));
        $usuarioLen = strlen($usuario);
        if ($usuarioLen < 3 || $usuarioLen > 50) {
            throw new InvalidArgumentException('El usuario debe tener entre 3 y 50 caracteres.');
        }
        if (preg_match('/^[a-z0-9._-]+$/', $usuario) !== 1) {
            throw new InvalidArgumentException('El usuario solo permite letras, numeros, punto, guion y guion bajo.');
        }

        $correoDestino = self::validateCorreoDestino($data['correo_destino'] ?? null);

        $enviarCorreo = false;
        if (array_key_exists('enviar_correo', $data)) {
            if (!is_bool($data['enviar_correo']) && !is_numeric($data['enviar_correo']) && !is_string($data['enviar_correo'])) {
                throw new InvalidArgumentException('El campo "enviar_correo" debe ser booleano.');
            }

            $enviarCorreo = filter_var($data['enviar_correo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($enviarCorreo === null) {
                throw new InvalidArgumentException('El campo "enviar_correo" debe ser booleano.');
            }
        } elseif ($correoDestino !== null) {
            $enviarCorreo = true;
        }

        return [
            'nombre_completo' => $nombreCompleto,
            'usuario' => $usuario,
            'correo_destino' => $correoDestino,
            'enviar_correo' => (bool) $enviarCorreo
        ];
    }

    /**
     * @param mixed $value
     * @param bool  $required
     * @return string|null
     */
    private static function validateCampo(mixed $value, bool $required): ?string
    {
        if ($value === null || $value === '') {
            if ($required) {
                throw new InvalidArgumentException('El campo "campo" es obligatorio.');
            }
            return null;
        }

        $campo = strtoupper(self::toCleanString($value));
        if (preg_match('/^[A-Z0-9]{2,10}$/', $campo) !== 1) {
            throw new InvalidArgumentException('El campo "campo" debe ser alfanumerico (2 a 10 caracteres).');
        }

        return $campo;
    }

    /**
     * @param mixed $value
     * @param bool  $required
     * @return string|null
     */
    private static function validateDistrito(mixed $value, bool $required): ?string
    {
        if ($value === null || $value === '') {
            if ($required) {
                throw new InvalidArgumentException('El campo "distrito" es obligatorio.');
            }
            return null;
        }

        $distrito = strtoupper(self::toCleanString($value));
        if (preg_match('/^[A-Z0-9_]{2,24}$/', $distrito) !== 1) {
            throw new InvalidArgumentException(
                'El campo "distrito" debe ser alfanumerico con guion bajo (2 a 24 caracteres).'
            );
        }

        return $distrito;
    }

    /**
     * @param mixed $value
     * @param bool  $required
     * @return string|null
     */
    private static function validateTipo(mixed $value, bool $required): ?string
    {
        if ($value === null || $value === '') {
            if ($required) {
                throw new InvalidArgumentException('El campo "tipo_organizacion" es obligatorio.');
            }
            return null;
        }

        $tipo = strtoupper(self::toCleanString($value));
        if (!in_array($tipo, ['IGLESIA', 'GRUPO'], true)) {
            throw new InvalidArgumentException('El campo "tipo_organizacion" debe ser IGLESIA o GRUPO.');
        }

        return $tipo;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function validateEstado(mixed $value): string
    {
        $estado = strtoupper(self::toCleanString($value));
        if ($estado === '') {
            $estado = 'TODAS';
        }

        if (!in_array($estado, ['TODAS', 'ACTIVA', 'INACTIVA'], true)) {
            throw new InvalidArgumentException('El filtro estado debe ser TODAS, ACTIVA o INACTIVA.');
        }

        return $estado;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function validateNombre(mixed $value): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('El campo "nombre_organizacion" es obligatorio.');
        }

        $nombre = Sanitizer::cleanString($value);
        $len = strlen($nombre);
        if ($len < self::NOMBRE_MIN || $len > self::NOMBRE_MAX) {
            throw new InvalidArgumentException('El nombre de organizacion debe tener entre 5 y 30 caracteres.');
        }
        if (preg_match(self::NOMBRE_REGEX, $nombre) !== 1) {
            throw new InvalidArgumentException('El nombre de organizacion no permite numeros ni simbolos no validos.');
        }

        return $nombre;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function validateCorreo(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException('El campo "correo_contacto" debe ser texto.');
        }

        $correo = strtolower(Sanitizer::cleanString($value));
        if (strlen($correo) > self::EMAIL_MAX) {
            throw new InvalidArgumentException('El correo de contacto no puede superar 30 caracteres.');
        }

        if (
            strpos($correo, '..') !== false
            || preg_match(self::EMAIL_STRICT_REGEX, $correo) !== 1
            || filter_var($correo, FILTER_VALIDATE_EMAIL) === false
        ) {
            throw new InvalidArgumentException('El correo de contacto no tiene un formato valido.');
        }

        return $correo;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function validateCorreoDestino(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException('El campo "correo_destino" debe ser texto.');
        }

        $correo = strtolower(Sanitizer::cleanString($value));
        if (strlen($correo) > self::EMAIL_MAX) {
            throw new InvalidArgumentException('El correo destino no puede superar 30 caracteres.');
        }

        if (
            strpos($correo, '..') !== false
            || preg_match(self::EMAIL_STRICT_REGEX, $correo) !== 1
            || filter_var($correo, FILTER_VALIDATE_EMAIL) === false
        ) {
            throw new InvalidArgumentException('El correo destino no tiene un formato valido.');
        }

        return $correo;
    }

    /**
     * @param mixed $value
     * @param bool  $required
     * @return bool|null
     */
    private static function validateActiva(mixed $value, bool $required): ?bool
    {
        if ($value === null || $value === '') {
            if ($required) {
                throw new InvalidArgumentException('El campo "activa" es obligatorio.');
            }
            return null;
        }

        if (!is_bool($value) && !is_numeric($value) && !is_string($value)) {
            throw new InvalidArgumentException('El campo "activa" debe ser booleano.');
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            throw new InvalidArgumentException('El campo "activa" debe ser booleano.');
        }

        return (bool) $parsed;
    }

    /**
     * @param mixed $value
     * @return int
     */
    private static function validatePage(mixed $value): int
    {
        if (!is_scalar($value) || !is_numeric((string) $value)) {
            throw new InvalidArgumentException('El filtro page debe ser numerico.');
        }

        $page = (int) $value;
        if ($page < 1) {
            throw new InvalidArgumentException('El filtro page debe ser mayor o igual a 1.');
        }

        return $page;
    }

    /**
     * @param mixed $value
     * @return int
     */
    private static function validateLimit(mixed $value): int
    {
        if (!is_scalar($value) || !is_numeric((string) $value)) {
            throw new InvalidArgumentException('El filtro limit debe ser numerico.');
        }

        $limit = (int) $value;
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('El filtro limit debe estar entre 1 y 100.');
        }

        return $limit;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function toCleanString(mixed $value): string
    {
        if (!is_scalar($value)) {
            throw new InvalidArgumentException('Se esperaba un valor de texto valido.');
        }

        return Sanitizer::cleanString((string) $value);
    }
}
