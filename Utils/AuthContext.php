<?php
declare(strict_types=1);

/**
 * Clase AuthContext
 *
 * Almacena de forma estatica (stateless por request) los datos
 * del usuario autenticado para que Controllers y Services puedan consultarlos.
 */
final class AuthContext
{
    /** @var int|null */
    private static ?int $usuarioId = null;

    /** @var string|null */
    private static ?string $rol = null;

    /** @var string|null */
    private static ?string $nombre = null;

    /**
     * Establece los datos del usuario autenticado.
     *
     * @param int    $usuarioId ID del usuario.
     * @param string $rol       Nombre del rol (ADMIN, SECRETARIO).
     * @param string $nombre    Nombre completo del usuario.
     * @return void
     */
    public static function set(int $usuarioId, string $rol, string $nombre): void
    {
        self::$usuarioId = $usuarioId;
        self::$rol       = $rol;
        self::$nombre    = $nombre;
    }

    /**
     * Obtiene el ID del usuario autenticado.
     *
     * @return int
     * @throws RuntimeException Si no hay usuario autenticado.
     */
    public static function getUsuarioId(): int
    {
        if (self::$usuarioId === null) {
            throw new RuntimeException('No hay usuario autenticado.');
        }
        return self::$usuarioId;
    }

    /**
     * Obtiene el rol del usuario autenticado.
     *
     * @return string
     * @throws RuntimeException Si no hay usuario autenticado.
     */
    public static function getRol(): string
    {
        if (self::$rol === null) {
            throw new RuntimeException('No hay usuario autenticado.');
        }
        return self::$rol;
    }

    /**
     * Obtiene el nombre del usuario autenticado.
     *
     * @return string
     * @throws RuntimeException Si no hay usuario autenticado.
     */
    public static function getNombre(): string
    {
        if (self::$nombre === null) {
            throw new RuntimeException('No hay usuario autenticado.');
        }
        return self::$nombre;
    }

    /**
     * Verifica si el usuario autenticado tiene rol ADMIN.
     *
     * @return bool
     */
    public static function esAdmin(): bool
    {
        return self::$rol === 'ADMIN';
    }

    /**
     * Limpia el contexto (util para tests).
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$usuarioId = null;
        self::$rol       = null;
        self::$nombre    = null;
    }
}
