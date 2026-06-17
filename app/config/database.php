<?php
/**
 * Database connection (PHP 7.1 compatible)
 */

if (!defined('APP_INIT')) {
    die('Direct access not allowed');
}

// Load config settings
$config_file = __DIR__ . '/config.php';
$config = [];
if (file_exists($config_file)) {
    $config = require $config_file;
}

$DB_HOST = $config['db_host'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = $config['db_name'] ?? getenv('DB_NAME') ?: 'helnovexaa_affiliate';
$DB_USER = $config['db_user'] ?? getenv('DB_USER') ?: 'helnovexaa_affiliateuser';
$DB_PASS = $config['db_pass'] ?? getenv('DB_PASS') ?: 'Soumojit1234@';

// Load tenant helpers and GuardPDO class
require_once __DIR__ . '/../core/tenant.php';

try {
    $pdo = new GuardPDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // NEVER show DB errors in production
    error_log('DB Connection failed: ' . $e->getMessage());
    die('Database connection error');
}

// Automatically enforce tenant resolution in tenant contexts
if (php_sapi_name() !== 'cli' && (!defined('SUPER_ADMIN_CONTEXT') || SUPER_ADMIN_CONTEXT !== true)) {
    require_tenant();
}


