<?php
/**
 * Super Admin - View/Inspect Tenant Network & Impersonation
 * PHP 7.1+
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_role('super_admin');

$adminName = $_SESSION['super_auth']['name'] ?? 'Super Admin';
$error = '';

$tenantId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($tenantId <= 0) {
    header('Location: tenants.php');
    exit;
}

// Fetch Tenant Details
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tenant) {
    header('Location: tenants.php');
    exit;
}

// Handle Impersonation Request
if (isset($_GET['impersonate']) && $_GET['impersonate'] == 1) {
    // Find the first admin user of this tenant
    $uStmt = $pdo->prepare("
        SELECT user_id, name, email 
        FROM users 
        WHERE tenant_id = ? AND role_id = 1 AND status = 'active' 
        LIMIT 1
    ");
    $uStmt->execute([$tenantId]);
    $tenantAdmin = $uStmt->fetch(PDO::FETCH_ASSOC);

    if ($tenantAdmin) {
        // Set up tenant admin session
        $_SESSION['auth'] = [
            'user_id'   => (int)$tenantAdmin['user_id'],
            'tenant_id' => (int)$tenantId,
            'role'      => 'admin',
            'login_at'  => time(),
            'is_impersonating' => true
        ];
        $_SESSION['user_name'] = $tenantAdmin['name'];

        // Get the tenant's primary domain to redirect correctly!
        $dStmt = $pdo->prepare("SELECT domain FROM tenant_domains WHERE tenant_id = ? AND is_primary = 1 LIMIT 1");
        $dStmt->execute([$tenantId]);
        $domain = $dStmt->fetchColumn();

        if ($domain) {
            header("Location: http://{$domain}/admin/dashboard.php");
        } else {
            header("Location: /admin/dashboard.php");
        }
        exit;
    } else {
        $error = "No active administrator account was found for this tenant to impersonate.";
    }
}

// Fetch Stats for this tenant
$clicksCount = (int)$pdo->query("SELECT COUNT(*) FROM clicks WHERE tenant_id = {$tenantId}")->fetchColumn();
$conversionsCount = (int)$pdo->query("SELECT COUNT(*) FROM conversions WHERE tenant_id = {$tenantId}")->fetchColumn();
$offersCount = (int)$pdo->query("SELECT COUNT(*) FROM offers WHERE tenant_id = {$tenantId}")->fetchColumn();
$usersCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE tenant_id = {$tenantId}")->fetchColumn();
$revenueSum = (float)$pdo->query("SELECT SUM(revenue) FROM conversions WHERE tenant_id = {$tenantId} AND status = 'approved'")->fetchColumn();

// Fetch 5 recent clicks for this tenant
$recentClicks = $pdo->prepare("
    SELECT c.click_id, c.created_at, o.offer_name, aff.name as affiliate_name 
    FROM clicks c
    LEFT JOIN offers o ON o.offer_id = c.offer_id
    LEFT JOIN users aff ON aff.user_id = c.affiliate_id
    WHERE c.tenant_id = ?
    ORDER BY c.created_at DESC 
    LIMIT 5
");
$recentClicks->execute([$tenantId]);
$clicks = $recentClicks->fetchAll(PDO::FETCH_ASSOC);

// Fetch primary domain mapping
$dStmt = $pdo->prepare("SELECT domain FROM tenant_domains WHERE tenant_id = ? AND is_primary = 1 LIMIT 1");
$dStmt->execute([$tenantId]);
$primaryDomain = $dStmt->fetchColumn() ?: 'No domain mapped';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Tenant #<?=$tenant['id']?> · SaaS Super Admin</title>
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
        .actions-group { display: flex; gap: 12px; }
        .btn-back {
            background-color: #334155;
            color: #f1f5f9;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-back:hover { background-color: #475569; }
        .btn-impersonate {
            background-color: #eab308;
            color: #0f172a;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-impersonate:hover { background-color: #ca8a04; }
        .grid-view {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
        }
        .section-card {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 32px;
        }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #c084fc; }
        
        /* Stats grid inside view */
        .stats-mini-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-mini-card {
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }
        .stat-mini-val { font-size: 20px; font-weight: 700; color: #f8fafc; }
        .stat-mini-lbl { font-size: 11px; color: #64748b; margin-top: 4px; text-transform: uppercase; }

        .meta-list { display: flex; flex-direction: column; gap: 16px; }
        .meta-item { display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 12px; }
        .meta-lbl { font-weight: 600; color: #94a3b8; font-size: 14px; }
        .meta-val { font-family: monospace; font-size: 14px; color: #f1f5f9; }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            background-color: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2);
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 12px 16px;
            color: #94a3b8;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 13px; }
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
            <li class="nav-item active">
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
                <h1>Inspect Tenant: <?=htmlspecialchars($tenant['name'])?></h1>
                <p>Telemetry, limits, and administrator impersonation panel.</p>
            </div>
            <div class="actions-group">
                <a href="tenants.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="tenant_view.php?id=<?=$tenant['id']?>&impersonate=1" class="btn-impersonate">
                    <i class="fas fa-user-ninja"></i> Impersonate Admin
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert"><i class="fas fa-exclamation-triangle"></i> <?=$error?></div>
        <?php endif; ?>

        <div class="grid-view">
            <!-- Left Side: Stats and lists -->
            <div>
                <div class="section-card">
                    <h3 class="section-title">Network Database Telemetry</h3>
                    
                    <div class="stats-mini-grid">
                        <div class="stat-mini-card">
                            <div class="stat-mini-val"><?=number_format($clicksCount)?></div>
                            <div class="stat-mini-lbl">Clicks</div>
                        </div>
                        <div class="stat-mini-card">
                            <div class="stat-mini-val"><?=number_format($conversionsCount)?></div>
                            <div class="stat-mini-lbl">Conversions</div>
                        </div>
                        <div class="stat-mini-card">
                            <div class="stat-mini-val"><?=number_format($offersCount)?></div>
                            <div class="stat-mini-lbl">Offers</div>
                        </div>
                        <div class="stat-mini-card">
                            <div class="stat-mini-val"><?=number_format($usersCount)?></div>
                            <div class="stat-mini-lbl">Users</div>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <h3 class="section-title">Recent Traffic Activity</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Click ID</th>
                                <th>Date/Time</th>
                                <th>Offer Name</th>
                                <th>Affiliate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clicks)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">No click traffic logged yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($clicks as $c): ?>
                                    <tr>
                                        <td><code><?=substr($c['click_id'], 0, 10)?>...</code></td>
                                        <td><?=$c['created_at']?></td>
                                        <td><?=htmlspecialchars($c['offer_name'] ?? 'N/A')?></td>
                                        <td><?=htmlspecialchars($c['affiliate_name'] ?? 'N/A')?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Side: Meta Info -->
            <div>
                <div class="section-card">
                    <h3 class="section-title">Details & Metadata</h3>
                    
                    <div class="meta-list">
                        <div class="meta-item">
                            <span class="meta-lbl">Tenant ID</span>
                            <span class="meta-val">#<?=$tenant['id']?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-lbl">Tenant Slug</span>
                            <span class="meta-val"><code><?=$tenant['slug']?></code></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-lbl">Primary Domain</span>
                            <span class="meta-val" style="color: #c084fc; font-weight: 600;"><?=$primaryDomain?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-lbl">Subscription Plan</span>
                            <span class="meta-val"><?=$tenant['plan_name']?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-lbl">Limits</span>
                            <span class="meta-val" style="font-size:12px; font-family:inherit;">
                                Max Offers: <?=$tenant['max_offers']?><br>
                                Max Pubs: <?=$tenant['max_affiliates']?><br>
                                Max Advs: <?=$tenant['max_advertisers']?>
                            </span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-lbl">Registered On</span>
                            <span class="meta-val"><?=date('Y-m-d H:i', strtotime($tenant['created_at']))?></span>
                        </div>
                        <div class="meta-item" style="border-bottom: none; padding-bottom: 0;">
                            <span class="meta-lbl">Billing Status</span>
                            <span class="meta-val" style="text-transform: capitalize;"><?=$tenant['status']?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
