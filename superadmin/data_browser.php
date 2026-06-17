<?php
/**
 * Super Admin - Database browser (Read-only inspector)
 * PHP 7.1+
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_role('super_admin');

$adminName = $_SESSION['super_auth']['name'] ?? 'Super Admin';
$message = '';
$error = '';

// Allowed tables list to prevent injection
$allowedTables = [
    'tenants', 'tenant_domains', 'tenant_settings', 'super_admins', 'users', 'roles', 
    'offers', 'clicks', 'conversions', 'affiliate_offer_approval', 'affiliate_postbacks', 
    'affiliate_offer_postbacks', 'postback_logs', 'affiliate_postback_logs', 'offer_links', 
    'account_managers', 'affiliate_bank_details', 'advertiser_ip_whitelist', 'postback_logs_aff'
];

$table = $_GET['table'] ?? '';
if (!empty($table) && !in_array($table, $allowedTables, true)) {
    $error = "Access to table '{$table}' is restricted or table does not exist.";
    $table = '';
}

$columns = [];
$rows = [];

if (!empty($table)) {
    try {
        // Fetch columns
        $colQuery = $pdo->query("SHOW COLUMNS FROM `{$table}`");
        $columns = $colQuery->fetchAll(PDO::FETCH_COLUMN);
        
        // Fetch first 100 rows
        $rowQuery = $pdo->query("SELECT * FROM `{$table}` ORDER BY 1 DESC LIMIT 100");
        $rows = $rowQuery->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Failed to query table data: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Browser · SaaS Super Admin</title>
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
        .content { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h1 { font-size: 28px; font-weight: 700; }
        .header p { color: #94a3b8; margin-top: 4px; }
        
        .layout-grid {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 32px;
            flex: 1;
        }
        .section-card {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 24px;
            height: fit-content;
        }
        .section-title { font-size: 16px; font-weight: 700; margin-bottom: 16px; color: #c084fc; text-transform: uppercase; letter-spacing: 0.5px; }
        .table-list { list-style: none; display: flex; flex-direction: column; gap: 6px; }
        .table-item a {
            display: block; padding: 8px 12px; color: #94a3b8; text-decoration: none; font-size: 13px; border-radius: 6px;
            font-family: monospace; transition: all 0.2s;
        }
        .table-item.active a, .table-item a:hover { background-color: #334155; color: #f8fafc; }
        
        .table-wrapper { overflow-x: auto; max-width: 100%; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); background-color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; padding: 12px 16px; color: #94a3b8; font-weight: 600; font-size: 11px;
            text-transform: uppercase; border-bottom: 1px solid rgba(255, 255, 255, 0.05); background-color: #1e293b;
        }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 13px; font-family: monospace; white-space: nowrap; max-width: 200px; overflow: hidden; text-overflow: ellipsis; }
        .alert {
            padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500;
            background-color: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2);
        }
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
            <li class="nav-item">
                <a href="platform_settings.php">
                    <i class="fas fa-sliders"></i> Platform Settings
                </a>
            </li>
            <li class="nav-item active">
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
                <h1>Database Telemetry Browser</h1>
                <p>Read-only raw record explorer for safety audit and platform integrity checks.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert"><i class="fas fa-exclamation-triangle"></i> <?=$error?></div>
        <?php endif; ?>

        <div class="layout-grid">
            <!-- Sidebar Table Selection -->
            <div class="section-card">
                <h3 class="section-title font-size:12px;">Select Table</h3>
                <ul class="table-list">
                    <?php foreach ($allowedTables as $tName): ?>
                        <li class="table-item <?=$table===$tName?'active':''?>">
                            <a href="data_browser.php?table=<?=$tName?>"><?=$tName?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Table Data View -->
            <div class="section-card" style="width: 100%; overflow: hidden;">
                <?php if (empty($table)): ?>
                    <div style="text-align: center; color: #64748b; padding: 60px;">
                        <i class="fas fa-table" style="font-size: 48px; margin-bottom: 20px;"></i>
                        <p>Please select a database table from the sidebar list to inspect rows.</p>
                    </div>
                <?php else: ?>
                    <h3 class="section-title">Table content: <code><?=$table?></code> (First 100 rows, DESC)</h3>
                    
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <?php foreach ($columns as $col): ?>
                                        <th <?=$col==='tenant_id'?'style="color:#c084fc; font-weight:bold;"':''?>><?=htmlspecialchars($col)?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr>
                                        <td colspan="<?=count($columns)?>" style="text-align: center; color: #64748b; padding: 30px;">Table is empty.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <?php foreach ($columns as $col): ?>
                                                <td <?=$col==='tenant_id'?'style="color:#c084fc; font-weight:bold; background-color:rgba(192,132,252,0.05);"':''?>>
                                                    <?php 
                                                    if ($r[$col] === null) {
                                                        echo '<em style="color:#64748b;">NULL</em>';
                                                    } else {
                                                        echo htmlspecialchars((string)$r[$col]);
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
