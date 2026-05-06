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

    /** @var int|null */
    private static ?int $organizacionId = null;

    /** @var string|null */
    private static ?string $codigoInstancia = null;

    /** @var string|null */
    private static ?string $tipoOrganizacion = null;

    /** @var string|null */
    private static ?string $nombreOrganizacion = null;

    /** @var string|null */
    private static ?string $rol = null;

    /** @var string|null */
    private static ?string $nombre = null;

    /**
     * Establece los datos del usuario autenticado.
     *
     * @param int         $usuarioId           ID del usuario.
     * @param int|null    $organizacionId      ID de la organizacion (tenant).
     * @param string|null $codigoInstancia     Codigo de instancia del tenant.
     * @param string|null $tipoOrganizacion    Tipo del tenant (IGLESIA/GRUPO).
     * @param string|null $nombreOrganizacion  Nombre del tenant.
     * @param string      $rol                 Nombre del rol (ADMIN, SECRETARIO).
     * @param string      $nombre              Nombre completo del usuario.
     * @return void
     */
    public static function set(
        int $usuarioId,
        ?int $organizacionId,
        ?string $codigoInstancia,
        ?string $tipoOrganizacion,
        ?string $nombreOrganizacion,
        string $rol,
        string $nombre
    ): void
    {
        self::$usuarioId          = $usuarioId;
        self::$organizacionId     = $organizacionId;
        self::$codigoInstancia    = $codigoInstancia;
        self::$tipoOrganizacion   = $tipoOrganizacion;
        self::$nombreOrganizacion = $nombreOrganizacion;
        self::$rol                = $rol;
        self::$nombre             = $nombre;
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
     * Obtiene el ID de la organizacion (tenant) del usuario autenticado.
     *
     * @return int
     * @throws RuntimeException Si no hay tenant asociado.
     */
    public static function getOrganizacionId(): int
    {
        if (self::$organizacionId === null) {
            throw new RuntimeException('No hay organizacion asociada al usuario autenticado.');
        }

        return self::$organizacionId;
    }

    /**
     * Obtiene el codigo de instancia del tenant autenticado.
     *
     * @return string
     * @throws RuntimeException Si no hay tenant asociado.
     */
    public static function getCodigoInstancia(): string
    {
        if (self::$codigoInstancia === null || self::$codigoInstancia === '') {
            throw new RuntimeException('No hay codigo de instancia asociado al usuario autenticado.');
        }

        return self::$codigoInstancia;
    }

    /**
     * Obtiene el tipo de organizacion del tenant autenticado.
     *
     * @return string
     * @throws RuntimeException Si no hay tenant asociado.
     */
    public static function getTipoOrganizacion(): string
    {
        if (self::$tipoOrganizacion === null || self::$tipoOrganizacion === '') {
            throw new RuntimeException('No hay tipo de organizacion asociado al usuario autenticado.');
        }

        return self::$tipoOrganizacion;
    }

    /**
     * Obtiene el nombre de la organizacion del tenant autenticado.
     *
     * @return string
     * @throws RuntimeException Si no hay tenant asociado.
     */
    public static function getNombreOrganizacion(): string
    {
        if (self::$nombreOrganizacion === null || self::$nombreOrganizacion === '') {
            throw new RuntimeException('No hay nombre de organizacion asociado al usuario autenticado.');
        }

        return self::$nombreOrganizacion;
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
     * Verifica si el usuario autenticado tiene rol de Ministerios Personales.
     *
     * @return bool
     */
    public static function esMinisterioPersonal(): bool
    {
        return self::$rol === 'MINISTERIO_PERSONAL';
    }

    /**
     * Verifica si el usuario autenticado tiene rol de instructor biblico.
     *
     * @return bool
     */
    public static function esInstructorBiblico(): bool
    {
        return self::$rol === 'INSTRUCTOR_BIBLICO';
    }

    /**
     * Verifica si el usuario autenticado tiene rol SUPERADMIN.
     *
     * @return bool
     */
    public static function esSuperadmin(): bool
    {
        return self::$rol === 'SUPERADMIN';
    }

    /**
     * Limpia el contexto (util para tests).
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$usuarioId          = null;
        self::$organizacionId     = null;
        self::$codigoInstancia    = null;
        self::$tipoOrganizacion   = null;
        self::$nombreOrganizacion = null;
        self::$rol                = null;
        self::$nombre             = null;
    }
}
