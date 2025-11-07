<?php
/**
 * ClubSphere - Configuration File (Stable + Warning-Free)
 * Fully environment-aware and PHP 8+ compatible
 *
 * @version 1.2 (Stable, warning-free)
 */

// ============================
// DETECT ENVIRONMENT
// ============================
$env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development';
$isDevelopment = $env === 'development';
$isProduction  = $env === 'production';
$isTesting     = $env === 'testing';

// ============================
// MAIN CONFIGURATION ARRAY
// ============================
$config = [
    'app' => [
        'name'      => $_ENV['APP_NAME'] ?? 'ClubSphere',
        'version'   => $_ENV['APP_VERSION'] ?? '1.0.0',
        'env'       => $env,
        'debug'     => $isDevelopment,
        'url'       => $_ENV['APP_URL'] ?? 'http://localhost',
        'timezone'  => $_ENV['APP_TIMEZONE'] ?? 'UTC',
        'locale'    => $_ENV['APP_LOCALE'] ?? 'en',
    ],

    'database' => [
        'host'      => $_ENV['DB_HOST'] ?? 'localhost',
        'port'      => $_ENV['DB_PORT'] ?? 3307,
        'name'      => $_ENV['DB_NAME'] ?? 'clubsphere',
        'username'  => $_ENV['DB_USER'] ?? 'root',
        'password'  => $_ENV['DB_PASS'] ?? 'root',
        'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
        'prefix'    => $_ENV['DB_PREFIX'] ?? '',
        'strict'    => true,
        'engine'    => 'InnoDB',
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => !$isDevelopment,
        ],
    ],

    'security' => [
        'encryption_key'      => $_ENV['ENCRYPTION_KEY'] ?? 'your-secret-key-here',
        'csrf_protection'     => true,
        'session_lifetime'    => 1800,
        'password_min_length' => 8,
        'max_login_attempts'  => 5,
        'lockout_duration'    => 900,
        'two_factor_auth'     => false,
        'recaptcha_enabled'   => false,
        'recaptcha_site_key'  => $_ENV['RECAPTCHA_SITE_KEY'] ?? '',
        'recaptcha_secret_key'=> $_ENV['RECAPTCHA_SECRET_KEY'] ?? '',
    ],

    'upload' => [
        'max_file_size' => 10 * 1024 * 1024,
        'allowed_extensions' => [
            'image'     => ['jpg','jpeg','png','gif','webp','bmp'],
            'document'  => ['pdf','doc','docx','txt','rtf'],
            'archive'   => ['zip','rar','7z'],
            'video'     => ['mp4','avi','mov','wmv'],
            'audio'     => ['mp3','wav','ogg','flac'],
        ],
        'upload_path' => __DIR__ . '/uploads/',
        'url_path'    => '/uploads/',
        'create_thumbnails' => true,
        'thumbnail_sizes' => [
            'small'  => [150,150],
            'medium' => [300,300],
            'large'  => [600,600],
        ],
    ],

    'mail' => [
        'driver'        => $_ENV['MAIL_DRIVER'] ?? 'smtp',
        'host'          => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port'          => $_ENV['MAIL_PORT'] ?? 587,
        'username'      => $_ENV['MAIL_USERNAME'] ?? '',
        'password'      => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption'    => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from_address'  => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@clubsphere.com',
        'from_name'     => $_ENV['MAIL_FROM_NAME'] ?? 'ClubSphere',
        'sendmail_path' => $_ENV['MAIL_SENDMAIL_PATH'] ?? '/usr/sbin/sendmail -bs',
    ],

    'cache' => [
        'driver'    => $_ENV['CACHE_DRIVER'] ?? 'file',
        'lifetime'  => 3600,
        'path'      => __DIR__ . '/cache/',
    ],

    'session' => [
        'driver'          => $_ENV['SESSION_DRIVER'] ?? 'file',
        'lifetime'        => 120,
        'expire_on_close' => false,
        'encrypt'         => false,
        'files'           => __DIR__ . '/sessions/',
        'cookie'          => 'clubsphere_session',
        'path'            => '/',
        'domain'          => $_ENV['SESSION_DOMAIN'] ?? '',
        'secure'          => $isProduction,
        'http_only'       => true,
        'same_site'       => 'lax',
    ],

    'logging' => [
        'enabled'  => true,
        'level'    => $isDevelopment ? 'debug' : 'info',
        'channels' => [
            'daily'  => ['path' => __DIR__ . '/logs/', 'days' => 30],
            'single' => ['path' => __DIR__ . '/logs/clubsphere.log'],
            'error'  => ['path' => __DIR__ . '/logs/error.log', 'level' => 'error'],
        ],
    ],
];

// ============================
// CONFIG HELPER
// ============================
function config($key, $default = null) {
    global $config;
    $keys = explode('.', $key);
    $value = $config;
    foreach ($keys as $k) {
        if (!isset($value[$k])) return $default;
        $value = $value[$k];
    }
    return $value;
}

// ============================
// ENVIRONMENT-SPECIFIC SETTINGS
// ============================
if ($isDevelopment) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    $config['cache']['lifetime'] = 0;
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

// ============================
// PHP RUNTIME CONFIG (SAFE)
// ============================

// Only apply session ini settings before session starts
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string)(config('session.lifetime') * 60));
    ini_set('session.cookie_lifetime', (string)(config('session.lifetime') * 60));
}

// Always safe to set these
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '20M');

// ============================
// ERROR HANDLERS
// ============================
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile:$errline");
    return in_array($errno, [E_NOTICE, E_USER_NOTICE, E_DEPRECATED]);
});

set_exception_handler(function ($ex) {
    error_log("Exception: {$ex->getMessage()} in {$ex->getFile()}:{$ex->getLine()}");
    http_response_code(500);
    if (config('app.debug')) {
        echo "<pre style='background:#111;color:#0f0;padding:10px;font-size:13px;border-radius:8px;'>{$ex}</pre>";
    } else {
        echo "<div style='background:#f8f9fa;padding:20px;text-align:center;font-family:Arial'>
                <h1 style='color:#dc3545;'>Server Error</h1>
                <p>Please try again later.</p>
              </div>";
    }
});

// ============================
// SHUTDOWN HANDLER
// ============================
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal Error: {$error['message']} in {$error['file']}:{$error['line']}");
    }

    if (class_exists('Database') && method_exists('Database', 'getInstance')) {
        try {
            $db = Database::getInstance();
            if (method_exists($db, 'getStats')) {
                $stats = $db->getStats();
                error_log("DB Queries: {$stats['query_count']} (ConnTime: {$stats['connection_time']}s)");
            }
        } catch (Throwable $t) {
            error_log("DB cleanup failed: " . $t->getMessage());
        }
    }
});

// ============================
// DEFINE PATH CONSTANTS
// ============================
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__ . DS);
define('STORAGE_PATH', ROOT_PATH . 'storage' . DS);
define('CACHE_PATH', STORAGE_PATH . 'cache' . DS);
define('LOGS_PATH', STORAGE_PATH . 'logs' . DS);
define('SESSIONS_PATH', STORAGE_PATH . 'sessions' . DS);

// ============================
// ENSURE DIRECTORIES EXIST
// ============================
foreach ([STORAGE_PATH, CACHE_PATH, LOGS_PATH, SESSIONS_PATH, config('upload.upload_path')] as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

// ============================
// LOAD ENVIRONMENT-SPECIFIC CONFIG
// ============================
$envFile = __DIR__ . "/config.$env.php";
if (file_exists($envFile)) {
    require_once $envFile;
}

// ============================
// FINAL RUNTIME SETTINGS
// ============================
date_default_timezone_set(config('app.timezone'));
setlocale(LC_ALL, config('app.locale') . '.UTF-8');
