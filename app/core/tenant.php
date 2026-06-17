<?php
/**
 * Core Tenant Resolver and Helpers
 * PHP 7.1+
 */

if (!defined('APP_INIT')) {
    die('Direct access not allowed');
}

class TenantResolver {
    private static $tenant = null;
    private static $settings = null;
    private static $resolved = false;

    /**
     * Resolve tenant from host
     */
    public static function resolve() {
        if (self::$resolved) {
            return;
        }

        global $pdo;
        self::$resolved = true;

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Strip port if present
        $host = explode(':', $host)[0];

        try {
            // Resolve domain
            $stmt = $pdo->prepare("
                SELECT tenant_id, type 
                FROM tenant_domains 
                WHERE domain = :domain 
                LIMIT 1
            ");
            $stmt->execute(['domain' => $host]);
            $domainInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$domainInfo) {
                // If not found, and we are on localhost, fall back to default tenant 1
                if ($host === 'localhost' || $host === '127.0.0.1') {
                    $domainInfo = ['tenant_id' => 1, 'type' => 'root'];
                } else {
                    return; // No tenant resolved
                }
            }

            $tenantId = (int)$domainInfo['tenant_id'];

            // Fetch tenant details
            $tStmt = $pdo->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
            $tStmt->execute(['id' => $tenantId]);
            $tenantData = $tStmt->fetch(PDO::FETCH_ASSOC);

            if ($tenantData) {
                self::$tenant = $tenantData;

                // Fetch tenant settings
                $sStmt = $pdo->prepare("SELECT * FROM tenant_settings WHERE tenant_id = :id LIMIT 1");
                $sStmt->execute(['id' => $tenantId]);
                self::$settings = $sStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (PDOException $e) {
            error_log("Tenant resolution database error: " . $e->getMessage());
        }
    }

    public static function getTenant() {
        self::resolve();
        return self::$tenant;
    }

    public static function getTenantId() {
        self::resolve();
        return self::$tenant ? (int)self::$tenant['id'] : null;
    }

    public static function getSettings() {
        self::resolve();
        return self::$settings;
    }
}

/* =====================================================
   GLOBAL SAAS TENANCY HELPER FUNCTIONS
   ===================================================== */

/**
 * Get current tenant details
 */
function current_tenant() {
    return TenantResolver::getTenant();
}

/**
 * Get current tenant settings
 */
function current_tenant_settings() {
    return TenantResolver::getSettings();
}

/**
 * Get current tenant ID
 */
function current_tenant_id() {
    return TenantResolver::getTenantId();
}

/**
 * Assert resolved active tenant or block/redirect
 */
function require_tenant() {
    // If Super Admin context, do not require tenant resolver
    if (defined('SUPER_ADMIN_CONTEXT') && SUPER_ADMIN_CONTEXT === true) {
        return;
    }

    $tenant = current_tenant();
    if (!$tenant) {
        http_response_code(404);
        include_once __DIR__ . '/../../404.php';
        exit;
    }

    if ($tenant['status'] === 'suspended') {
        http_response_code(403);
        show_suspended_screen($tenant);
        exit;
    }

    if ($tenant['status'] !== 'active') {
        http_response_code(403);
        exit('Access Denied (Tenant Inactive)');
    }
}

/**
 * Get explicit SQL WHERE clause segment
 */
function tenant_where($alias = null) {
    $prefix = $alias ? "`{$alias}`." : "";
    return "{$prefix}`tenant_id` = :tenant_id";
}

/**
 * Get params array for binding tenant_id
 */
function tenant_params() {
    return ['tenant_id' => current_tenant_id()];
}

/**
 * Security boundary checker: validates that a specific entity belongs to the current tenant
 */
function assert_same_tenant($table, $id, $id_column = null) {
    global $pdo;
    
    // Super admins bypass this check
    if (defined('SUPER_ADMIN_CONTEXT') && SUPER_ADMIN_CONTEXT === true) {
        return true;
    }

    $tenantId = current_tenant_id();
    if (!$tenantId) {
        die('Access Denied (No tenant context)');
    }

    if ($id_column === null) {
        // Automatically determine ID column
        if ($table === 'users') {
            $id_column = 'user_id';
        } elseif ($table === 'offers') {
            $id_column = 'offer_id';
        } elseif ($table === 'clicks') {
            $id_column = 'click_id';
        } elseif ($table === 'conversions') {
            $id_column = 'conversion_id';
        } else {
            $id_column = 'id';
        }
    }

    $stmt = $pdo->prepare("SELECT tenant_id FROM `{$table}` WHERE `{$id_column}` = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $rowTenantId = $stmt->fetchColumn();

    if ($rowTenantId === false) {
        http_response_code(404);
        exit('Entity not found');
    }

    if ((int)$rowTenantId !== (int)$tenantId) {
        http_response_code(403);
        exit('Access Denied: Cross-tenant data access blocked.');
    }

    return true;
}

/**
 * Render the suspended tenant screen
 */
function show_suspended_screen($tenant) {
    $siteName = htmlspecialchars($tenant['name']);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Account Suspended | <?=$siteName?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Inter', sans-serif;
                background-color: #f3f4f6;
                color: #1f2937;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
            }
            .card {
                background: white;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                text-align: center;
                max-width: 480px;
                width: 90%;
            }
            .icon {
                font-size: 64px;
                color: #ef4444;
                margin-bottom: 24px;
            }
            h1 {
                font-size: 24px;
                margin-bottom: 16px;
                font-weight: 700;
            }
            p {
                color: #4b5563;
                line-height: 1.6;
                margin-bottom: 24px;
            }
            .btn {
                display: inline-block;
                background-color: #3b82f6;
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: background 0.2s;
            }
            .btn:hover {
                background-color: #2563eb;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon">⚠️</div>
            <h1>Portal Suspended</h1>
            <p>The affiliate network for <strong><?=$siteName?></strong> has been suspended due to billing status or system settings. Please contact support or the platform administrator if you think this is a mistake.</p>
            <a href="mailto:<?=$tenant['owner_email'] ?? 'support@saas.com'?>" class="btn">Contact Administrator</a>
        </div>
    </body>
    </html>
    <?php
}

/* =====================================================
   DEVELOPMENT-ONLY SQL SAFETY GUARD
   ===================================================== */

class GuardPDO extends PDO {
    private static $tenantTables = [
        'users', 'offers', 'clicks', 'conversions', 'affiliate_offer_approval', 
        'affiliate_postbacks', 'affiliate_offer_postbacks', 'postback_logs', 
        'affiliate_postback_logs', 'offer_links', 'account_managers', 
        'affiliate_bank_details', 'advertiser_ip_whitelist', 'postback_logs_aff'
    ];

    public function prepare($query, $options = []) {
        $this->checkQuerySafety($query);
        return parent::prepare($query, $options);
    }

    public function query($query, ...$args) {
        $this->checkQuerySafety($query);
        return parent::query($query, ...$args);
    }

    public function exec($query) {
        $this->checkQuerySafety($query);
        return parent::exec($query);
    }

    /**
     * Inspect SQL query for tenant isolation violations
     */
    private function checkQuerySafety($query) {
        // Skip safety checks if Super Admin context is explicitly active
        if (defined('SUPER_ADMIN_CONTEXT') && SUPER_ADMIN_CONTEXT === true) {
            return;
        }

        // If no tenant resolved, we are not in a tenant context (e.g. initial resolver or main site)
        $tenantId = current_tenant_id();
        if ($tenantId === null) {
            return;
        }

        // Check if query touches any tenant table
        $touchesTenantTable = false;
        $matchedTable = '';
        foreach (self::$tenantTables as $table) {
            if (preg_match('/\b' . preg_quote($table, '/') . '\b/i', $query)) {
                $touchesTenantTable = true;
                $matchedTable = $table;
                break;
            }
        }

        // Check if query is missing tenant_id scoping
        if ($touchesTenantTable && !preg_match('/\btenant_id\b/i', $query)) {
            $msg = "SQL Guard Violation: Unscoped query against tenant table `{$matchedTable}`. SQL: " . $query;
            error_log($msg);
            
            // Log to custom file
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            file_put_contents(
                $logDir . '/sql_guard.log', 
                "[" . date('Y-m-d H:i:s') . "] [TENANT #{$tenantId}] {$msg}\n", 
                FILE_APPEND | LOCK_EX
            );

            // In development, trigger a warning to raise immediate developer awareness
            trigger_error($msg, E_USER_WARNING);
        }
    }
}
