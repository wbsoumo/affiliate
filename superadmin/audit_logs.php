<?php
/**
 * Super Admin - System Logs & SQL Safety Violations
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

$logFile = __DIR__ . '/../logs/sql_guard.log';

// Handle clear log action
if (isset($_GET['action']) && $_GET['action'] === 'clear_guard') {
    if (file_exists($logFile)) {
        file_put_contents($logFile, "");
        $message = "SQL safety guard logs cleared successfully.";
    }
}

// Read SQL Guard Log File
$sqlGuardLogs = [];
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines) {
        // Reverse to show newest first
        $lines = array_reverse($lines);
        // Show first 100 lines
        $lines = array_slice($lines, 0, 100);
        foreach ($lines as $line) {
            // Log format: [Y-m-d H:i:s] [TENANT #ID] SQL Guard Violation: ...
            if (preg_match('/^\[(.*?)\]\s+\[TENANT\s+#(.*?)\]\s+(.*)$/', $line, $matches)) {
                $sqlGuardLogs[] = [
                    'time' => $matches[1],
                    'tenant_id' => $matches[2],
                    'message' => $matches[3]
                ];
            } else {
                $sqlGuardLogs[] = [
                    'time' => 'Unknown',
                    'tenant_id' => 'System',
                    'message' => $line
                ];
            }
        }
    }
}

// Read Database Error Logs
$dbErrors = $pdo->query("
    SELECT el.*, t.name as tenant_name 
    FROM error_logs el
    LEFT JOIN tenants t ON t.id = el.tenant_id
    ORDER BY el.logged_at DESC 
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Audit Logs · SaaS Super Admin</title>
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
        
        .section-card {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 32px;
        }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-title { font-size: 18px; font-weight: 700; color: #c084fc; }
        .btn-clear {
            background-color: rgba(239, 68, 68, 0.15); color: #f87171; text-decoration: none; padding: 10px 18px;
            border-radius: 8px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-clear:hover { background-color: rgba(239, 68, 68, 0.25); }
        .alert {
            padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500;
            background-color: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2);
        }
        
        .tab-wrapper { overflow-x: auto; border-radius: 12px; background-color: #0f172a; border: 1px solid rgba(255, 255, 255, 0.05); }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; padding: 12px 16px; color: #94a3b8; font-weight: 600; font-size: 11px;
            text-transform: uppercase; border-bottom: 1px solid rgba(255, 255, 255, 0.05); background-color: #1e293b;
        }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 13px; vertical-align: top; }
        code { font-family: monospace; color: #f87171; background-color: rgba(239, 68, 68, 0.1); padding: 2px 6px; border-radius: 4px; }
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
            <li class="nav-item">
                <a href="data_browser.php">
                    <i class="fas fa-database"></i> Database Browser
                </a>
            </li>
            <li class="nav-item active">
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
                <h1>System Audit Logs</h1>
                <p>Telemetry for checking tenant isolation safety, database query integrity, and platform exceptions.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert"><i class="fas fa-check-circle"></i> <?=$message?></div>
        <?php endif; ?>

        <!-- SQL GUARD SAFETY VIOLATIONS -->
        <div class="section-card">
            <div class="section-header">
                <h3 class="section-title">SQL Guard Isolation Violations (Recent 100)</h3>
                <a href="audit_logs.php?action=clear_guard" class="btn-clear" onclick="return confirm('Clear all SQL safety guard logs?')"><i class="fas fa-trash-can"></i> Clear Logs</a>
            </div>
            
            <div class="tab-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 150px;">Time</th>
                            <th style="width: 100px;">Tenant ID</th>
                            <th>Guard Log Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sqlGuardLogs)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #64748b; padding: 24px;">No tenant scoping violations logged. The system is secure!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sqlGuardLogs as $log): ?>
                                <tr>
                                    <td><span style="color:#94a3b8; font-family:monospace;"><?=$log['time']?></span></td>
                                    <td><code style="color:#c084fc; background-color:rgba(192,132,252,0.1);">#<?=$log['tenant_id']?></code></td>
                                    <td><span style="font-size:12px; color:#f87171;"><?=$log['message']?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- DATABASE ERROR LOGS -->
        <div class="section-card">
            <div class="section-header">
                <h3 class="section-title">Platform Exception Logs (Recent 100)</h3>
            </div>
            
            <div class="tab-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 150px;">Logged At</th>
                            <th style="width: 120px;">Network / Tenant</th>
                            <th style="width: 80px;">Err Code</th>
                            <th>Request URL</th>
                            <th style="width: 120px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dbErrors)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #64748b; padding: 24px;">No system exception logs recorded.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dbErrors as $err): ?>
                                <tr>
                                    <td><span style="color:#94a3b8; font-family:monospace;"><?=$err['logged_at']?></span></td>
                                    <td><strong><?=htmlspecialchars($err['tenant_name'] ?? 'Super Admin / CLI')?></strong></td>
                                    <td><code><?=$err['error_code']?></code></td>
                                    <td><span style="font-size: 12px; color: #e2e8f0;"><?=htmlspecialchars($err['requested_url'])?></span></td>
                                    <td><span style="font-family:monospace; color:#94a3b8;"><?=$err['ip_address']?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
