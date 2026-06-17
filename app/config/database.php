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

// Self-healing database check: ensure default roles exist
try {
    $roles_in_db = $pdo->query("SELECT role_id, role_name FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);
    $defaultRoles = [
        1 => 'admin',
        2 => 'manager',
        3 => 'affiliate',
        4 => 'advertiser'
    ];
    $missing = false;
    foreach ($defaultRoles as $id => $name) {
        if (!isset($roles_in_db[$id]) || $roles_in_db[$id] !== $name) {
            $missing = true;
            break;
        }
    }
    if ($missing) {
        foreach ($defaultRoles as $id => $name) {
            $otherId = array_search($name, $roles_in_db);
            if ($otherId !== false) {
                // Update the ID to match standard ID
                $upd = $pdo->prepare("UPDATE roles SET role_id = ? WHERE role_id = ?");
                $upd->execute([$id, $otherId]);
            } elseif (!isset($roles_in_db[$id])) {
                // Insert the missing role
                $ins = $pdo->prepare("INSERT INTO roles (role_id, role_name) VALUES (?, ?)");
                $ins->execute([$id, $name]);
            } else {
                // ID exists but has a different name, rename it to the expected role name
                $upd = $pdo->prepare("UPDATE roles SET role_name = ? WHERE role_id = ?");
                $upd->execute([$name, $id]);
            }
        }
    }
} catch (Exception $e) {
    // Ignore errors if roles table doesn't exist yet (e.g. during initial schema import)
}

// Automatically enforce tenant resolution in tenant contexts
if (php_sapi_name() !== 'cli' && (!defined('SUPER_ADMIN_CONTEXT') || SUPER_ADMIN_CONTEXT !== true)) {
    require_tenant();
}


