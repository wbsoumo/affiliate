<?php
define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true);
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/plain');

try {
    echo "=== TENANTS ===\n";
    $tenants = $pdo->query("SELECT id, name, slug, owner_email, status FROM tenants")->fetchAll();
    print_r($tenants);

    echo "\n=== DOMAINS ===\n";
    $domains = $pdo->query("SELECT tenant_id, domain, type FROM tenant_domains")->fetchAll();
    print_r($domains);

    echo "\n=== USERS FOR agencytest89@test.com ===\n";
    $stmt = $pdo->prepare("SELECT user_id, tenant_id, role_id, name, email, password_hash, status FROM users WHERE email = :email");
    $stmt->execute(['email' => 'agencytest89@test.com']);
    $users = $stmt->fetchAll();
    print_r($users);

    echo "\n=== SQL GUARD LOG (Last 50 lines) ===\n";
    $logFile = __DIR__ . '/logs/sql_guard.log';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $lastLines = array_slice($lines, -50);
        echo implode("", $lastLines);
    } else {
        echo "No sql_guard.log found.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
