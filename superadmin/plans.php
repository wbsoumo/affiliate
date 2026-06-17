<?php
/**
 * Super Admin - SaaS Plan Management
 * PHP 7.1+
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_role('super_admin');

$adminName = $_SESSION['super_auth']['name'] ?? 'Super Admin';

// Count tenants per plan
$plansDistribution = $pdo->query("
    SELECT plan_name, COUNT(*) as count 
    FROM tenants 
    WHERE status != 'deleted' 
    GROUP BY plan_name
")->fetchAll(PDO::FETCH_KEY_PAIR);

$plans = [
    [
        'name' => 'Starter',
        'price' => '$99/mo',
        'offers_limit' => 100,
        'publishers_limit' => 100,
        'advertisers_limit' => 20,
        'description' => 'Great for starting out or testing workflows.',
        'color' => '#60a5fa',
        'count' => $plansDistribution['Starter'] ?? 0
    ],
    [
        'name' => 'Professional',
        'price' => '$299/mo',
        'offers_limit' => 500,
        'publishers_limit' => 500,
        'advertisers_limit' => 100,
        'description' => 'Designed for growing affiliate networks.',
        'color' => '#c084fc',
        'count' => $plansDistribution['Professional'] ?? 0
    ],
    [
        'name' => 'Enterprise',
        'price' => '$999/mo',
        'offers_limit' => 'Unlimited',
        'publishers_limit' => 'Unlimited',
        'advertisers_limit' => 'Unlimited',
        'description' => 'Uncapped limits and VIP support for large operations.',
        'color' => '#34d399',
        'count' => $plansDistribution['Enterprise'] ?? 0
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SaaS Pricing Plans · SaaS Super Admin</title>
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
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 32px;
            margin-top: 24px;
        }
        .plan-card {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: relative;
            overflow: hidden;
        }
        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .plan-name { font-size: 20px; font-weight: 700; }
        .plan-price { font-size: 28px; font-weight: 800; }
        .plan-desc { font-size: 14px; color: #94a3b8; line-height: 1.5; }
        
        .plan-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 20px;
            margin-top: 10px;
        }
        .plan-features li { display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .plan-features li i { color: #34d399; }
        
        .plan-distribution {
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            font-weight: 600;
            margin-top: auto;
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
            <li class="nav-item active">
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
                <h1>SaaS Subscription Tiers</h1>
                <p>Manage subscription packages, pricing limits, and review tenant distributions.</p>
            </div>
        </div>

        <div class="plans-grid">
            <?php foreach ($plans as $p): ?>
                <div class="plan-card" style="border-top: 4px solid <?=$p['color']?>">
                    <div class="plan-header">
                        <span class="plan-name" style="color: <?=$p['color']?>"><?=$p['name']?></span>
                        <span class="plan-price"><?=$p['price']?></span>
                    </div>
                    <p class="plan-desc"><?=$p['description']?></p>
                    
                    <ul class="plan-features">
                        <li><i class="fas fa-check-circle"></i> Max Offers: <strong><?=$p['offers_limit']?></strong></li>
                        <li><i class="fas fa-check-circle"></i> Max Publishers: <strong><?=$p['publishers_limit']?></strong></li>
                        <li><i class="fas fa-check-circle"></i> Max Advertisers: <strong><?=$p['advertisers_limit']?></strong></li>
                        <li><i class="fas fa-check-circle"></i> Full API & Reports access</li>
                    </ul>
                    
                    <div class="plan-distribution">
                        <i class="fas fa-network-wired" style="color: <?=$p['color']?>; margin-right: 8px;"></i>
                        <span><?=$p['count']?> Active Portals</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
