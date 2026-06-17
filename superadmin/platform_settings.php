<?php
/**
 * Super Admin - Platform Settings & Telemetry
 * PHP 7.1+
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_role('super_admin');

$adminName = $_SESSION['super_auth']['name'] ?? 'Super Admin';

// Get database size telemetry
$dbTelemetry = [];
try {
    $DB_NAME = $config['db_name'] ?? getenv('DB_NAME') ?: 'helnovexaa_affiliate';
    $q = $pdo->prepare("
        SELECT 
            SUM(data_length + index_length) / 1024 / 1024 AS db_size_mb,
            COUNT(*) as tables_count
        FROM information_schema.tables 
        WHERE table_schema = ?
    ");
    $q->execute([$DB_NAME]);
    $dbTelemetry = $q->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dbTelemetry = ['db_size_mb' => 0, 'tables_count' => 0];
}

$mysqlVersion = $pdo->query("SELECT VERSION()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Platform Settings & Telemetry · SaaS Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
        }
        /* Side Navigation */
        .sidebar {
            width: 260px;
            background-color: #1e293b;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: column;
            padding: 24px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
            font-weight: 700;
            font-size: 20px;
            color: #c084fc;
        }
        .brand i { font-size: 24px; }
        .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .nav-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .nav-item.active a, .nav-item a:hover {
            background-color: #334155;
            color: #f8fafc;
        }
        .nav-item a i { width: 20px; }
        .sidebar-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #f8fafc;
            margin-bottom: 16px;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a855f7, #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        /* Content */
        .content { flex: 1; padding: 40px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h1 { font-size: 28px; font-weight: 700; }
        .header p { color: #94a3b8; margin-top: 4px; }
        
        .layout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
        }
        .section-card {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 28px;
        }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #c084fc; }
        
        .meta-list { display: flex; flex-direction: column; gap: 16px; }
        .meta-item { display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 12px; }
        .meta-lbl { font-weight: 600; color: #94a3b8; font-size: 14px; }
        .meta-val { font-family: monospace; font-size: 14px; color: #f1f5f9; }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand">
            <i class="fas fa-chart-network"></i>
            <span>SaaS Control</span>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php">
                    <i class="fas fa-grid-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="tenants.php">
                    <i class="fas fa-users-gear"></i> Tenants / Networks
                </a>
            </li>
            <li class="nav-item">
                <a href="domains.php">
                    <i class="fas fa-globe"></i> Domain Router
                </a>
            </li>
            <li class="nav-item">
                <a href="plans.php">
                    <i class="fas fa-cubes"></i> SaaS Plans
                </a>
            </li>
            <li class="nav-item active">
                <a href="platform_settings.php">
                    <i class="fas fa-sliders"></i> Platform Settings
                </a>
            </li>
            <li class="nav-item">
                <a href="data_browser.php">
                    <i class="fas fa-database"></i> Database Browser
                </a>
            </li>
            <li class="nav-item">
                <a href="audit_logs.php">
                    <i class="fas fa-receipt"></i> System Audit Logs
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="avatar">
                    <?=strtoupper(substr($adminName, 0, 1))?>
                </div>
                <div>
                    <h4 style="font-size: 14px; font-weight: 600;"><?=$adminName?></h4>
                    <span style="font-size: 12px; color: #94a3b8;">Super Admin</span>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content">
        <div class="header">
            <div>
                <h1>Platform Settings & Telemetry</h1>
                <p>Global PHP system performance stats and database environment variables.</p>
            </div>
        </div>

        <div class="layout-grid">
            <!-- Left: System Info -->
            <div class="section-card">
                <h3 class="section-title">Global Server Telemetry</h3>
                <div class="meta-list">
                    <div class="meta-item">
                        <span class="meta-lbl">PHP Version</span>
                        <span class="meta-val"><?=PHP_VERSION?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-lbl">Web Server</span>
                        <span class="meta-val"><?=htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'N/A')?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-lbl">OS Version</span>
                        <span class="meta-val"><?=PHP_OS?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-lbl">Session Name Prefix</span>
                        <span class="meta-val"><code>PHPSESSID_[tenant_slug]</code></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-lbl">Max Execution Time</span>
                        <span class="meta-val"><?=ini_get('max_execution_time')?>s</span>
                    </div>
                    <div class="meta-item" style="border-bottom: none; padding-bottom: 0;">
                        <span class="meta-lbl">Post Max Size</span>
                        <span class="meta-val"><?=ini_get('post_max_size')?></span>
                    </div>
                </div>
            </div>

            <!-- Right: Database Info -->
            <div class="section-card">
                <h3 class="section-title">Database Core telemetry</h3>
                <div class="meta-list">
                    <div class="meta-item">
                        <span class="meta-lbl">MySQL Version</span>
                        <span class="meta-val"><?=$mysqlVersion?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-lbl">Database Size</span>
                        <span class="meta-val"><?=number_format((float)($dbTelemetry['db_size_mb'] ?? 0.00), 2)?> MB</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-lbl">Tables Count</span>
                        <span class="meta-val"><?=$dbTelemetry['tables_count'] ?? 0?> tables</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-lbl">SQL Security Guard</span>
                        <span class="meta-val" style="color: #34d399; font-weight: 600;">ACTIVE (Dev-Only Safety)</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-lbl">PDO Class Wrapper</span>
                        <span class="meta-val"><code>GuardPDO</code></span>
                    </div>
                    <div class="meta-item" style="border-bottom: none; padding-bottom: 0;">
                        <span class="meta-lbl">Tenant Isolation Model</span>
                        <span class="meta-val" style="color: #c084fc;">Shared Database (Single DB, Compound Indexes)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
