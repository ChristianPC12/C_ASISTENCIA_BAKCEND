<?php
declare(strict_types=1);

$base = dirname(__DIR__, 3);

require_once $base . '/Config/Global.php';

/**
 * @throws RuntimeException
 */
function must(bool $cond, string $message): void
{
    if (!$cond) {
        throw new RuntimeException($message);
    }
}

/**
 * @throws RuntimeException
 */
function readSql(string $path): string
{
    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException('No se pudo leer SQL: ' . $path);
    }
    return $content;
}

/**
 * Ejecuta SQL de archivo sobre una BD temporal reemplazando USE/schema base.
 *
 * @throws PDOException
 * @throws RuntimeException
 */
function execSqlFile(PDO $pdoRoot, string $path, string $dbName): void
{
    $sql = readSql($path);
    $sql = str_replace('`iglesia_asistencia`', '`' . $dbName . '`', $sql);
    $sql = str_replace('USE iglesia_asistencia;', 'USE `' . $dbName . '`;', $sql);
    $sql = str_replace('USE `iglesia_asistencia`;', 'USE `' . $dbName . '`;', $sql);
    $pdoRoot->exec($sql);
}

/**
 * @throws RuntimeException
 */
function tableExists(PDO $pdoRoot, string $dbName, string $table): bool
{
    $stmt = $pdoRoot->prepare(
        'SELECT COUNT(1)
         FROM information_schema.tables
         WHERE table_schema = :db
           AND table_name = :table'
    );
    $stmt->execute([
        ':db' => $dbName,
        ':table' => $table
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * @throws RuntimeException
 */
function columnExists(PDO $pdoRoot, string $dbName, string $table, string $column): bool
{
    $stmt = $pdoRoot->prepare(
        'SELECT COUNT(1)
         FROM information_schema.columns
         WHERE table_schema = :db
           AND table_name = :table
           AND column_name = :column'
    );
    $stmt->execute([
        ':db' => $dbName,
        ':table' => $table,
        ':column' => $column
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

$pdoRoot = new PDO(
    'mysql:host=' . DB_HOST . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
    ]
);

$dbName = 'iglesia_asistencia_b7_stg_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(2)), 0, 4);

$baseSql = [
    $base . '/iglesia_asistencia.sql'
];

$forward0903 = [
    $base . '/migracion_09032026_multitenant_estructura.sql',
    $base . '/migracion_09032026_multitenant_relaciones.sql',
    $base . '/migracion_09032026_multitenant_seed_base.sql',
    $base . '/migracion_09032026_superadmin_rol_base.sql',
    $base . '/migracion_09032026_asistencia_metricas_dinamicas.sql',
    $base . '/migracion_09032026_multitenant_sanity_checks.sql'
];

$rollback0903 = [
    $base . '/rollback_09032026_asistencia_metricas_dinamicas.sql',
    $base . '/rollback_09032026_superadmin_rol_base.sql',
    $base . '/rollback_09032026_multitenant_seed_base.sql',
    $base . '/rollback_09032026_multitenant_relaciones.sql',
    $base . '/rollback_09032026_multitenant_estructura.sql'
];

$apply0903Ok = false;
$rollback0903Ok = false;
$reapply0903Ok = false;

try {
    $pdoRoot->exec('CREATE DATABASE `' . $dbName . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    foreach ($baseSql as $path) {
        execSqlFile($pdoRoot, $path, $dbName);
    }

    foreach ($forward0903 as $path) {
        execSqlFile($pdoRoot, $path, $dbName);
    }

    $apply0903Ok = true;
    must(tableExists($pdoRoot, $dbName, 'organizaciones'), 'No existe tabla organizaciones tras apply.');
    must(tableExists($pdoRoot, $dbName, 'organizacion_roles_cupos'), 'No existe tabla organizacion_roles_cupos tras apply.');
    must(columnExists($pdoRoot, $dbName, 'usuarios', 'organizacion_id'), 'usuarios.organizacion_id no existe tras apply.');

    $stmtSuperadmin = $pdoRoot->query(
        "SELECT COUNT(1) AS c
         FROM `" . $dbName . "`.`roles`
         WHERE nombre = 'SUPERADMIN'"
    );
    must((int) $stmtSuperadmin->fetchColumn() >= 1, 'No existe rol SUPERADMIN tras apply.');

    foreach ($rollback0903 as $path) {
        execSqlFile($pdoRoot, $path, $dbName);
    }

    $rollback0903Ok = true;
    must(!tableExists($pdoRoot, $dbName, 'organizaciones'), 'Tabla organizaciones sigue existiendo tras rollback.');
    must(!columnExists($pdoRoot, $dbName, 'usuarios', 'organizacion_id'), 'usuarios.organizacion_id sigue existiendo tras rollback.');

    // Reaplicar bloque 0903 para validar repetibilidad.
    foreach ($forward0903 as $path) {
        execSqlFile($pdoRoot, $path, $dbName);
    }

    $reapply0903Ok = true;
    must(tableExists($pdoRoot, $dbName, 'organizaciones'), 'No existe tabla organizaciones tras reapply.');
    must(columnExists($pdoRoot, $dbName, 'usuarios', 'organizacion_id'), 'usuarios.organizacion_id no existe tras reapply.');

    echo 'db_temp=' . $dbName . PHP_EOL;
    echo 'apply_0903_ok=' . ($apply0903Ok ? '1' : '0') . PHP_EOL;
    echo 'rollback_0903_ok=' . ($rollback0903Ok ? '1' : '0') . PHP_EOL;
    echo 'reapply_0903_ok=' . ($reapply0903Ok ? '1' : '0') . PHP_EOL;
} finally {
    // Limpieza de la BD temporal de staging.
    $pdoRoot->exec('DROP DATABASE IF EXISTS `' . $dbName . '`');
}
