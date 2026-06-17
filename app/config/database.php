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

// Self-healing database check: ensure missing user columns and profile tables exist
try {
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    
    $missingColumns = [
        'profile_image' => "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL",
        'bio' => "ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL",
        'department' => "ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL",
        'designation' => "ALTER TABLE users ADD COLUMN designation VARCHAR(100) DEFAULT NULL",
        'two_factor_enabled' => "ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0",
        'two_factor_secret' => "ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) DEFAULT NULL",
        'notification_email' => "ALTER TABLE users ADD COLUMN notification_email TINYINT(1) NOT NULL DEFAULT 1",
        'notification_sms' => "ALTER TABLE users ADD COLUMN notification_sms TINYINT(1) NOT NULL DEFAULT 1",
        'theme_preference' => "ALTER TABLE users ADD COLUMN theme_preference VARCHAR(20) NOT NULL DEFAULT 'light'"
    ];
    
    foreach ($missingColumns as $col => $sql) {
        if (!in_array($col, $columns, true)) {
            $pdo->exec($sql);
        }
    }
} catch (Exception $e) {
    error_log("Failed to self-heal users table columns: " . $e->getMessage());
}

try {
    // Create user_permissions table if missing
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_permissions` (
            `user_id` bigint(20) unsigned NOT NULL PRIMARY KEY,
            `permission_level` varchar(50) DEFAULT 'standard',
            `can_manage_users` tinyint(1) NOT NULL DEFAULT 1,
            `can_manage_finance` tinyint(1) NOT NULL DEFAULT 1,
            `can_manage_reports` tinyint(1) NOT NULL DEFAULT 1,
            `can_manage_settings` tinyint(1) NOT NULL DEFAULT 1,
            FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    // Create user_sessions table if missing
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_sessions` (
            `id` bigint(20) unsigned AUTO_INCREMENT PRIMARY KEY,
            `session_id` varchar(255) NOT NULL,
            `user_id` bigint(20) unsigned NOT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `is_current_session` tinyint(1) NOT NULL DEFAULT 0,
            FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    // Create user_activity_log table if missing
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_activity_log` (
            `id` bigint(20) unsigned AUTO_INCREMENT PRIMARY KEY,
            `user_id` bigint(20) unsigned NOT NULL,
            `action_type` varchar(100) NOT NULL,
            `action_description` text DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    // Create tenant_homepages table if missing
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `tenant_homepages` (
            `tenant_id` INT NOT NULL PRIMARY KEY,
            `template_id` VARCHAR(50) NOT NULL DEFAULT 'classic_hero',
            `hero_title` VARCHAR(255) DEFAULT 'Start Your Premium Affiliate Network',
            `hero_subtitle` TEXT DEFAULT 'Track conversions, manage payouts, and grow your affiliate partnerships with zero latency.',
            `hero_cta_text` VARCHAR(50) DEFAULT 'Apply as Partner',
            `hero_cta_url` VARCHAR(255) DEFAULT '/register.php',
            `features_json` TEXT DEFAULT NULL,
            `about_text` TEXT DEFAULT NULL,
            `contact_email` VARCHAR(150) DEFAULT NULL,
            `social_links_json` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Exception $e) {
    error_log("Failed to self-heal profile tables: " . $e->getMessage());
}

// Automatically enforce tenant resolution in tenant contexts
if (php_sapi_name() !== 'cli' && (!defined('SUPER_ADMIN_CONTEXT') || SUPER_ADMIN_CONTEXT !== true)) {
    require_tenant();
}


