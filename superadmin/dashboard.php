<?php
/**
 * Super Admin Dashboard
 * PHP 7.1+
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_role('super_admin');

$adminName = $_SESSION['super_auth']['name'] ?? 'Super Admin';

/* =====================================================
   FETCH STATS (AGGREGATE)
   ===================================================== */

// Tenants counts
$tenantStats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'active') as active,
        SUM(status = 'suspended') as suspended,
        SUM(status = 'pending') as pending
    FROM tenants
")->fetch(PDO::FETCH_ASSOC);

// Global metrics
$globalMetrics = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM offers) as total_offers,
        (SELECT COUNT(*) FROM clicks) as total_clicks,
        (SELECT COUNT(*) FROM conversions) as total_conversions,
        (SELECT SUM(payout) FROM conversions WHERE status = 'approved') as total_payout,
        (SELECT SUM(revenue) FROM conversions WHERE status = 'approved') as total_revenue
")->fetch(PDO::FETCH_ASSOC);

$totalPayout = (float)($globalMetrics['total_payout'] ?? 0.00);
$totalRevenue = (float)($globalMetrics['total_revenue'] ?? 0.00);
$totalProfit = $totalRevenue - $totalPayout;

// Recent tenant signups
$recentTenants = $pdo->query("
    SELECT id, name, slug, company_name, status, created_at 
    FROM tenants 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Recent system errors (from error_logs table)
$recentErrors = $pdo->query("
    SELECT id, error_code, requested_url, ip_address, logged_at 
    FROM error_logs 
    ORDER BY logged_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Top tenants by clicks/conversions
$topTenants = $pdo->query("
    SELECT 
        t.id, t.name, t.slug, t.status,
        (SELECT COUNT(*) FROM clicks WHERE tenant_id = t.id) as clicks_count,
        (SELECT COUNT(*) FROM conversions WHERE tenant_id = t.id) as conversions_count,
        (SELECT SUM(revenue) FROM conversions WHERE tenant_id = t.id AND status = 'approved') as revenue_sum
    FROM tenants t
    ORDER BY conversions_count DESC, clicks_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SaaS Super Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .brand i {
            font-size: 24px;
        }

        .nav-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

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

        .nav-item a i {
            width: 20px;
        }

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

        /* Main Content Container */
        .content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
        }

        .header p {
            color: #94a3b8;
            margin-top: 4px;
        }

        /* Grid layouts */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 600;
        }

        .stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .icon-blue { background-color: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .icon-purple { background-color: rgba(168, 85, 247, 0.15); color: #a855f7; }
        .icon-green { background-color: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .icon-red { background-color: rgba(239, 68, 68, 0.15); color: #ef4444; }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
        }

        .stat-desc {
            font-size: 12px;
            color: #64748b;
        }

        /* Tables & Lists */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
            margin-bottom: 40px;
        }

        .section-card {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 28px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
        }

        .btn-view-all {
            color: #a855f7;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px 16px;
            color: #94a3b8;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-active { background-color: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .status-suspended { background-color: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .status-pending { background-color: rgba(234, 179, 8, 0.15); color: #eab308; }

        .list-items {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px;
            background-color: #0f172a;
            border-radius: 12px;
            border-left: 4px solid #ef4444;
        }

        .error-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .error-url {
            font-weight: 600;
            font-size: 14px;
            color: #f1f5f9;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .error-meta {
            font-size: 12px;
            color: #64748b;
        }

        .error-code {
            background-color: rgba(239, 68, 68, 0.2);
            color: #f87171;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
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
            <li class="nav-item active">
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
                <h1>Global Dashboard</h1>
                <p>Real-time telemetry and control over all customer networks</p>
            </div>
            <div style="background-color: #1e293b; padding: 12px 20px; border-radius: 12px; display: flex; align-items: center; gap: 8px;">
                <span class="status-badge status-active"></span>
                <span style="font-weight: 600; font-size: 14px;">Platform Online</span>
            </div>
        </div>

        <!-- STATS CARD GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span>TOTAL TENANTS</span>
                    <div class="stat-icon icon-purple">
                        <i class="fas fa-network-wired"></i>
                    </div>
                </div>
                <div class="stat-value"><?=$tenantStats['total']?></div>
                <div class="stat-desc">
                    <span style="color: #22c55e; font-weight: 600;"><?=$tenantStats['active']?> Active</span> &middot; 
                    <span style="color: #ef4444; font-weight: 600;"><?=$tenantStats['suspended']?> Suspended</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span>PLATFORM CLICKS</span>
                    <div class="stat-icon icon-blue">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                </div>
                <div class="stat-value"><?=number_format($globalMetrics['total_clicks'])?></div>
                <div class="stat-desc">Clicks across all tenant networks</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span>PLATFORM CONVERSIONS</span>
                    <div class="stat-icon icon-green">
                        <i class="fas fa-check-double"></i>
                    </div>
                </div>
                <div class="stat-value"><?=number_format($globalMetrics['total_conversions'])?></div>
                <div class="stat-desc">Conversions generated globally</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span>PLATFORM REVENUE</span>
                    <div class="stat-icon icon-purple">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <div class="stat-value">$<?=number_format($totalRevenue, 2)?></div>
                <div class="stat-desc">Profit: $<?=number_format($totalProfit, 2)?></div>
            </div>
        </div>

        <!-- GRID SECTION -->
        <div class="dashboard-grid">
            <!-- Left Side: Top Tenants -->
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">Top Customer Networks</h3>
                    <a href="tenants.php" class="btn-view-all">Manage Tenants <i class="fas fa-chevron-right" style="font-size: 10px;"></i></a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Network Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Clicks</th>
                            <th>Conversions</th>
                            <th>Gross Rev</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topTenants)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b;">No active networks found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topTenants as $t): ?>
                                <tr>
                                    <td style="font-weight: 600;">
                                        <a href="tenant_view.php?id=<?=$t['id']?>" style="color: #f1f5f9; text-decoration: none; border-bottom: 1px dashed #a855f7;">
                                            <?=htmlspecialchars($t['name'])?>
                                        </a>
                                    </td>
                                    <td><code><?=$t['slug']?></code></td>
                                    <td>
                                        <span class="status-badge status-<?=$t['status']?>"><?=ucfirst($t['status'])?></span>
                                    </td>
                                    <td><?=number_format($t['clicks_count'])?></td>
                                    <td><?=number_format($t['conversions_count'])?></td>
                                    <td style="font-weight: 600; color: #22c55e;">$<?=number_format((float)($t['revenue_sum'] ?? 0.00), 2)?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Right Side: Recent Errors -->
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">Platform Error Log</h3>
                    <a href="audit_logs.php" class="btn-view-all">All Logs <i class="fas fa-chevron-right" style="font-size: 10px;"></i></a>
                </div>
                <div class="list-items">
                    <?php if (empty($recentErrors)): ?>
                        <div style="text-align: center; color: #64748b; padding: 20px;">No recent platform errors.</div>
                    <?php else: ?>
                        <?php foreach ($recentErrors as $err): ?>
                            <div class="list-item">
                                <div class="error-info">
                                    <span class="error-url" title="<?=htmlspecialchars($err['requested_url'])?>">
                                        <?=htmlspecialchars(parse_url($err['requested_url'], PHP_URL_PATH))?>
                                    </span>
                                    <span class="error-meta"><?=$err['ip_address']?> &middot; <?=date('H:i', strtotime($err['logged_at']))?></span>
                                </div>
                                <span class="error-code"><?=$err['error_code']?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- BOTTOM GRID -->
        <div style="display: grid; grid-template-columns: 1fr; gap: 32px;">
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">Recent Network Registrations</h3>
                    <a href="tenant_create.php" style="background-color: #a855f7; color: white; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                        <i class="fas fa-plus"></i> New Tenant
                    </a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <th>Slug</th>
                            <th>Owner</th>
                            <th>Email</th>
                            <th>Registered On</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTenants)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b;">No recent signups.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentTenants as $rt): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?=htmlspecialchars($rt['company_name'] ?? $rt['name'])?></td>
                                    <td><code><?=$rt['slug']?></code></td>
                                    <td><?=htmlspecialchars($rt['owner_name'] ?? 'N/A')?></td>
                                    <td><?=htmlspecialchars($rt['owner_email'] ?? 'N/A')?></td>
                                    <td><?=date('Y-m-d H:i', strtotime($rt['created_at']))?></td>
                                    <td>
                                        <span class="status-badge status-<?=$rt['status']?>"><?=ucfirst($rt['status'])?></span>
                                    </td>
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
