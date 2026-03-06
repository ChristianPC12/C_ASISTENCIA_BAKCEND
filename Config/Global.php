<?php
declare(strict_types=1);

/**
 * Configuracion global del sistema.
 * Constantes de conexion, rutas y parametros generales.
 */

// --- Base de datos ---
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'iglesia_asistencia');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- Ruta base del proyecto (ajustar segun XAMPP) ---
define('BASE_PATH', '/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND');

// --- CORS ---
define('CORS_ORIGIN', '*');
define('CORS_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_HEADERS', 'Content-Type, Authorization');

// --- Seguridad ---
define('PASSWORD_EXPIRY_DAYS', 30);
define('SESSION_IDLE_TIMEOUT_MINUTES', 15);
define('SESSION_MAX_DURATION_HOURS', 8);
define('LOGIN_MAX_FAILED_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW_MINUTES', 15);
define('LOGIN_BLOCK_MINUTES', 15);
