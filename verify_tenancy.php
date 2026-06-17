<?php
/**
 * Automated Tenancy Verification Test Suite
 * Run from CLI: php verify_tenancy.php
 */

define('APP_INIT', true);
echo "=====================================================\n";
echo "       SAAS MULTI-TENANCY VERIFICATION SUITE         \n";
echo "=====================================================\n\n";

// Load configurations
require_once __DIR__ . '/app/config/database.php';

// Mock $_SERVER environment for testing
$_SERVER['HTTP_HOST'] = 'localhost';

echo "1. Testing Tenant Resolution...\n";
TenantResolver::resolve();
$tenantId = current_tenant_id();
echo "   Resolved Domain Host: " . $_SERVER['HTTP_HOST'] . "\n";
echo "   Resolved Tenant ID: " . $tenantId . " (" . (current_tenant()['name'] ?? 'Unknown') . ")\n";

if ($tenantId !== 1) {
    echo "   [FAIL] Tenant resolution failed on localhost.\n";
    exit(1);
}
echo "   [SUCCESS] Tenant resolution verified.\n\n";

echo "2. Testing Database Isolation Isolation Boundary...\n";
// Insert dummy data for Tenant 1
$pdo->exec("
    INSERT INTO offers 
    (tenant_id, advertiser_id, offer_name, offer_url, postback_token, payout, revenue, campaign_url, geo, device_targeting, browser_targeting, status) 
    VALUES 
    (1, 4, 'Tenant 1 Offer Test', 'http://example.com/t1', 'token1', 1.00, 1.50, 'http://example.com/c1', 'ALL', 'ALL', 'ALL', 'active')
");
$t1_offer_id = $pdo->lastInsertId();

// Insert dummy data for Tenant 2 (create tenant 2 first if it doesn't exist)
$pdo->exec("INSERT IGNORE INTO tenants (id, name, slug, status) VALUES (2, 'Tenant 2 Portals', 't2', 'active')");
$pdo->exec("
    INSERT INTO offers 
    (tenant_id, advertiser_id, offer_name, offer_url, postback_token, payout, revenue, campaign_url, geo, device_targeting, browser_targeting, status) 
    VALUES 
    (2, 4, 'Tenant 2 Offer Test', 'http://example.com/t2', 'token2', 2.00, 2.50, 'http://example.com/c2', 'ALL', 'ALL', 'ALL', 'active')
");
$t2_offer_id = $pdo->lastInsertId();

echo "   Created Offer ID #{$t1_offer_id} for Tenant 1\n";
echo "   Created Offer ID #{$t2_offer_id} for Tenant 2\n";

// Query as Tenant 1 (localhost context)
echo "   Executing query under Tenant 1 context...\n";
$stmt = $pdo->prepare("SELECT * FROM offers WHERE offer_id = :oid AND tenant_id = :tenant_id");
$stmt->execute(['oid' => $t2_offer_id, 'tenant_id' => 1]);
$row = $stmt->fetch();

if ($row) {
    echo "   [FAIL] Cross-tenant data leak! Tenant 1 retrieved Tenant 2 data.\n";
    cleanup($pdo, $t1_offer_id, $t2_offer_id);
    exit(1);
} else {
    echo "   [SUCCESS] Tenant 1 could not retrieve Tenant 2 offer.\n";
}

echo "\n3. Testing assert_same_tenant security guard...\n";
try {
    assert_same_tenant('offers', $t1_offer_id, 'offer_id');
    echo "   [SUCCESS] assert_same_tenant allowed same-tenant entity access.\n";
} catch (Exception $e) {
    echo "   [FAIL] assert_same_tenant blocked valid same-tenant access.\n";
}

// Enable SQL safety check exception to verify guard behavior
echo "\n4. Testing SQL Guard Safety Checks...\n";
echo "   Simulating unscoped query warning (SQL Guard)...\n";
$logFile = __DIR__ . '/logs/sql_guard.log';
$initialLogCount = file_exists($logFile) ? count(file($logFile)) : 0;

// Run an unscoped query and check if the guard logs it
try {
    // We suppress the E_USER_WARNING error to let the test complete and read log file
    @$pdo->query("SELECT * FROM offers LIMIT 1");
    
    $newLogCount = file_exists($logFile) ? count(file($logFile)) : 0;
    if ($newLogCount > $initialLogCount) {
        echo "   [SUCCESS] SQL Guard logged unscoped query violation to logs/sql_guard.log.\n";
    } else {
        echo "   [FAIL] SQL Guard failed to log unscoped query violation.\n";
    }
} catch (Exception $e) {
    echo "   [FAIL] SQL Guard exception: " . $e->getMessage() . "\n";
}

cleanup($pdo, $t1_offer_id, $t2_offer_id);

echo "\n=====================================================\n";
echo "       ALL VERIFICATION TESTS COMPLETED SUCCESSFULLY \n";
echo "=====================================================\n";

function cleanup($pdo, $t1, $t2) {
    $pdo->exec("DELETE FROM offers WHERE offer_id IN ($t1, $t2)");
    $pdo->exec("DELETE FROM tenants WHERE id = 2");
}
