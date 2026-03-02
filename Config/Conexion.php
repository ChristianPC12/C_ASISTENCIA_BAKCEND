<?php
declare(strict_types=1);

/**
 * Clase Conexion
 *
 * Singleton para obtener la conexion PDO a la base de datos.
 * Usa prepared statements reales (EMULATE_PREPARES = false).
 */
final class Conexion
{
    /** @var PDO|null */
    private static ?PDO $instancia = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Obtiene la instancia unica de PDO.
     *
     * @return PDO
     * @throws RuntimeException Si no se puede conectar a la BD.
     */
    public static function getConexion(): PDO
    {
        if (self::$instancia === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                self::$instancia = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'"
                ]);
            } catch (PDOException $e) {
                throw new RuntimeException('Error de conexion a la base de datos.');
            }
        }

        return self::$instancia;
    }
}
