<?php
declare(strict_types=1);

/**
 * Configuracion global del sistema.
 * Constantes de conexion, rutas y parametros generales.
 */

/**
 * Lee override local opcional (no versionado).
 * Formato esperado: return ['CLAVE' => 'valor', ...];
 */
$localOverrides = [];
$localConfigPath = __DIR__ . '/Global.local.php';
if (is_file($localConfigPath)) {
    $loaded = require $localConfigPath;
    if (is_array($loaded)) {
        $localOverrides = $loaded;
    }
}

/**
 * Resuelve una configuracion por prioridad:
 * 1) Variable de entorno
 * 2) Override local
 * 3) Valor por defecto
 *
 * @param string               $key
 * @param string|int|bool|null $default
 * @return string|int|bool|null
 */
$cfg = static function (string $key, string|int|bool|null $default = null) use ($localOverrides): string|int|bool|null {
    $env = getenv($key);
    if ($env !== false) {
        return $env;
    }

    if (array_key_exists($key, $localOverrides)) {
        return $localOverrides[$key];
    }

    return $default;
};

// --- Base de datos ---
define('DB_HOST', (string) $cfg('DB_HOST', '127.0.0.1'));
define('DB_NAME', (string) $cfg('DB_NAME', 'iglesia_asistencia'));
define('DB_USER', (string) $cfg('DB_USER', 'root'));
define('DB_PASS', (string) $cfg('DB_PASS', ''));

// --- Ruta base del proyecto (ajustar segun XAMPP) ---
define('BASE_PATH', (string) $cfg('BASE_PATH', '/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND'));

// --- CORS ---
// Configurable por entorno: CORS_ALLOWED_ORIGINS="http://app1.com,http://app2.com"
$corsAllowedOrigins = trim((string) $cfg(
    'CORS_ALLOWED_ORIGINS',
    'http://localhost,http://127.0.0.1,http://localhost:5173,http://127.0.0.1:5173'
));
if ($corsAllowedOrigins === '') {
    $corsAllowedOrigins = 'http://localhost,http://127.0.0.1,http://localhost:5173,http://127.0.0.1:5173';
}
define('CORS_ALLOWED_ORIGINS', $corsAllowedOrigins);
define('CORS_METHODS', (string) $cfg('CORS_METHODS', 'GET, POST, PUT, DELETE, OPTIONS'));
define('CORS_HEADERS', (string) $cfg('CORS_HEADERS', 'Content-Type, Authorization, X-Device-Id'));

// --- Seguridad ---
define('PASSWORD_EXPIRY_DAYS', (int) $cfg('PASSWORD_EXPIRY_DAYS', 30));
define('SESSION_IDLE_TIMEOUT_MINUTES', (int) $cfg('SESSION_IDLE_TIMEOUT_MINUTES', 15));
define('SESSION_MAX_DURATION_HOURS', (int) $cfg('SESSION_MAX_DURATION_HOURS', 8));
define('LOGIN_MAX_FAILED_ATTEMPTS', (int) $cfg('LOGIN_MAX_FAILED_ATTEMPTS', 5));
define('LOGIN_ATTEMPT_WINDOW_MINUTES', (int) $cfg('LOGIN_ATTEMPT_WINDOW_MINUTES', 15));
define('LOGIN_BLOCK_MINUTES', (int) $cfg('LOGIN_BLOCK_MINUTES', 15));

// --- Correo Brevo (superadmin -> admin temporal) ---
define('BREVO_API_KEY', trim((string) $cfg('BREVO_API_KEY', '')));
define('BREVO_SENDER_EMAIL', trim((string) $cfg('BREVO_SENDER_EMAIL', '')));
define('BREVO_SENDER_NAME', trim((string) $cfg('BREVO_SENDER_NAME', 'C_ASISTENCIA')));
define('BREVO_API_URL', trim((string) $cfg('BREVO_API_URL', 'https://api.brevo.com/v3/smtp/email')));
define('BREVO_TIMEOUT_SECONDS', (int) $cfg('BREVO_TIMEOUT_SECONDS', 10));
