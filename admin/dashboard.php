<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('APP_INIT', true);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_role('admin');

$adminId   = auth_user_id();
$adminName = $_SESSION['user_name'] ?? 'Admin';

/* ===============================
   TODAY'S PERFORMANCE (FIXED)
================================ */
$todayDate = date('Y-m-d');

$todayStmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT c.click_id) AS today_clicks,
        COUNT(DISTINCT cv.conversion_id) AS today_conversions,
        IFNULL(SUM(CASE WHEN cv.status = 'approved' THEN cv.revenue END), 0) AS today_revenue,
        IFNULL(SUM(CASE WHEN cv.status = 'approved' THEN cv.payout END), 0) AS today_payout
    FROM clicks c
    LEFT JOIN conversions cv 
        ON cv.click_id = c.click_id 
       AND DATE(cv.created_at) = :today_conv
    WHERE c.tenant_id = " . current_tenant_id() . " AND DATE(c.created_at) = :today_click
");

$todayStmt->execute([
    'today_click' => $todayDate,
    'today_conv'  => $todayDate
]);

$today = $todayStmt->fetch(PDO::FETCH_ASSOC) ?: [];

/* ===============================
   YESTERDAY'S PERFORMANCE (FIXED)
================================ */
$yesterdayDate = date('Y-m-d', strtotime('-1 day'));

$yesterdayStmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT c.click_id) AS yesterday_clicks,
        COUNT(DISTINCT cv.conversion_id) AS yesterday_conversions,
        IFNULL(SUM(CASE WHEN cv.status = 'approved' THEN cv.revenue END), 0) AS yesterday_revenue,
        IFNULL(SUM(CASE WHEN cv.status = 'approved' THEN cv.payout END), 0) AS yesterday_payout
    FROM clicks c
    LEFT JOIN conversions cv 
        ON cv.click_id = c.click_id 
       AND DATE(cv.created_at) = :yesterday_conv
    WHERE c.tenant_id = " . current_tenant_id() . " AND DATE(c.created_at) = :yesterday_click
");

$yesterdayStmt->execute([
    'yesterday_click' => $yesterdayDate,
    'yesterday_conv'  => $yesterdayDate
]);

$yesterday = $yesterdayStmt->fetch(PDO::FETCH_ASSOC) ?: [];

/* ===============================
   TOTAL USERS
================================ */
$userCounts = $pdo->query("
    SELECT role_id, COUNT(*) AS total
    FROM users
    WHERE tenant_id = " . current_tenant_id() . " AND role_id IN (3,4)
    GROUP BY role_id
")->fetchAll(PDO::FETCH_KEY_PAIR);

$totalAffiliates  = $userCounts[3] ?? 0;
$totalAdvertisers = $userCounts[4] ?? 0;

/* ===============================
   OFFERS
================================ */
$totalOffers  = (int)$pdo->query("SELECT COUNT(*) FROM offers WHERE tenant_id = " . current_tenant_id() . "")->fetchColumn();
$activeOffers = (int)$pdo->query("SELECT COUNT(*) FROM offers WHERE tenant_id = " . current_tenant_id() . " AND status = 'active'")->fetchColumn();

/* ===============================
   CLICKS & CONVERSIONS
================================ */
$totalClicks = (int)$pdo->query("SELECT COUNT(*) FROM clicks WHERE tenant_id = " . current_tenant_id() . "")->fetchColumn();

$convStats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'approved') AS approved,
        SUM(status = 'pending')  AS pending,
        SUM(status = 'rejected') AS rejected
    FROM conversions
 WHERE tenant_id = " . current_tenant_id() . "")->fetch(PDO::FETCH_ASSOC) ?: [];

$totalConversions    = (int)($convStats['total'] ?? 0);
$approvedConversions = (int)($convStats['approved'] ?? 0);
$pendingConversions  = (int)($convStats['pending'] ?? 0);
$rejectedConversions = (int)($convStats['rejected'] ?? 0);

/* ===============================
   REVENUE / PAYOUT / PROFIT
================================ */
$money = $pdo->query("
    SELECT
        IFNULL(SUM(revenue),0) AS total_revenue,
        IFNULL(SUM(payout),0)  AS total_payout,
        IFNULL(SUM(CASE WHEN status='approved' THEN revenue END),0) AS approved_revenue,
        IFNULL(SUM(CASE WHEN status='approved' THEN payout END),0)  AS approved_payout
    FROM conversions
 WHERE tenant_id = " . current_tenant_id() . "")->fetch(PDO::FETCH_ASSOC) ?: [];

$totalRevenue    = (float)$money['total_revenue'];
$totalPayout     = (float)$money['total_payout'];
$approvedRevenue = (float)$money['approved_revenue'];
$approvedPayout  = (float)$money['approved_payout'];
$netProfit       = $approvedRevenue - $approvedPayout;

/* ===============================
   7-DAY TREND (NO PARAM ISSUES)
================================ */
$trendStmt = $pdo->query("
    SELECT 
        DATE(created_at) AS date,
        COUNT(*) AS conversions,
        SUM(CASE WHEN status='approved' THEN revenue ELSE 0 END) AS revenue,
        SUM(CASE WHEN status='approved' THEN payout  ELSE 0 END) AS payout
    FROM conversions
    WHERE tenant_id = " . current_tenant_id() . " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

$trendData = $trendStmt->fetchAll(PDO::FETCH_ASSOC);


/* ===============================
   TOP PERFORMERS
================================ */
// Top Affiliates
$topAffiliates = $pdo->query("
    SELECT 
        u.user_id,
        u.name,
        u.email,
        COUNT(DISTINCT cv.conversion_id) AS conversions,
        IFNULL(SUM(CASE WHEN cv.status = 'approved' THEN cv.payout END), 0) AS earnings,
        COUNT(DISTINCT c.click_id) AS clicks,
        ROUND(
            COUNT(DISTINCT cv.conversion_id) * 100.0 / 
            GREATEST(COUNT(DISTINCT c.click_id), 1), 2
        ) AS conversion_rate
    FROM users u
    LEFT JOIN clicks c ON c.affiliate_id = u.user_id
    LEFT JOIN conversions cv ON cv.affiliate_id = u.user_id
    WHERE u.tenant_id = " . current_tenant_id() . " AND u.role_id = 3
    GROUP BY u.user_id
    ORDER BY earnings DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Top Advertisers
$topAdvertisers = $pdo->query("
    SELECT 
        u.user_id,
        u.name,
        u.email,
        u.company,
        COUNT(DISTINCT o.offer_id) AS offers,
        IFNULL(SUM(CASE WHEN cv.status = 'approved' THEN cv.revenue END), 0) AS spent,
        COUNT(DISTINCT cv.conversion_id) AS conversions
    FROM users u
    LEFT JOIN offers o ON o.advertiser_id = u.user_id
    LEFT JOIN conversions cv ON cv.offer_id = o.offer_id
    WHERE u.tenant_id = " . current_tenant_id() . " AND u.role_id = 4
    GROUP BY u.user_id
    ORDER BY spent DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Top Campaigns
$topCampaigns = $pdo->query("
    SELECT 
        o.offer_id,
        o.offer_name,
        o.status,
        u.name AS advertiser_name,
        COUNT(DISTINCT cv.conversion_id) AS conversions,
        IFNULL(SUM(CASE WHEN cv.status = 'approved' THEN cv.revenue END), 0) AS revenue,
        IFNULL(SUM(CASE WHEN cv.status = 'approved' THEN cv.payout END), 0) AS payout,
        ROUND(
            IFNULL(SUM(CASE WHEN cv.status = 'approved' THEN cv.revenue END), 0) - 
            IFNULL(SUM(CASE WHEN cv.status = 'approved' THEN cv.payout END), 0), 2
        ) AS profit
    FROM offers o
    LEFT JOIN conversions cv ON cv.offer_id = o.offer_id
    LEFT JOIN users u ON u.user_id = o.advertiser_id
     WHERE u.tenant_id = " . current_tenant_id() . " GROUP BY o.offer_id
    ORDER BY revenue DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   RECENT ACTIVITY
================================ */
$recentActivity = $pdo->query("
    SELECT 
        'conversion' as type,
        cv.conversion_id as id,
        cv.transaction_id,
        cv.revenue,
        cv.status,
        cv.created_at,
        o.offer_name,
        aff.name as affiliate_name,
        adv.name as advertiser_name
    FROM conversions cv
    LEFT JOIN offers o ON o.offer_id = cv.offer_id
    LEFT JOIN users aff ON aff.user_id = cv.affiliate_id
    LEFT JOIN users adv ON adv.user_id = o.advertiser_id
    UNION ALL
    SELECT 
        'click' as type,
        c.click_id as id,
        NULL as transaction_id,
        NULL as revenue,
        NULL as status,
        c.created_at,
        o.offer_name,
        aff.name as affiliate_name,
        adv.name as advertiser_name
    FROM clicks c
    LEFT JOIN offers o ON o.offer_id = c.offer_id
    LEFT JOIN users aff ON aff.user_id = c.affiliate_id
    LEFT JOIN users adv ON adv.user_id = o.advertiser_id
     WHERE aff.tenant_id = " . current_tenant_id() . " ORDER BY created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   PENDING APPROVALS
================================ */
$pendingApprovals = $pdo->query("
    SELECT 
        'conversion' as type,
        cv.conversion_id as id,
        cv.transaction_id,
        cv.revenue,
        cv.payout,
        cv.created_at,
        o.offer_name,
        aff.name as affiliate_name,
        adv.name as advertiser_name
    FROM conversions cv
    LEFT JOIN offers o ON o.offer_id = cv.offer_id
    LEFT JOIN users aff ON aff.user_id = cv.affiliate_id
    LEFT JOIN users adv ON adv.user_id = o.advertiser_id
    WHERE aff.tenant_id = " . current_tenant_id() . " AND cv.status = 'pending'
    ORDER BY cv.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   SYSTEM STATS
================================ */
$systemStats = $pdo->query("
    SELECT 
        'pending_kyc' as stat_type,
        COUNT(*) as count
    FROM users 
    WHERE tenant_id = " . current_tenant_id() . " AND kyc_status = 'pending' AND role_id IN (3, 4)
    UNION ALL
    SELECT 
        'pending_payouts' as stat_type,
        COUNT(*) as count
    FROM affiliate_payout_requests 
    WHERE status = 'pending'
    UNION ALL
    SELECT 
        'pending_deposits' as stat_type,
        COUNT(*) as count
    FROM advertiser_transactions 
    WHERE type = 'deposit' AND status = 'pending'
")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | Taskbazi</title>
    
    <!-- Google Font: Inter -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        .content-wrapper {
            background-color: #f8fafc !important;
            padding: 20px 24px;
        }
        /* Dashboard Card Styling */
        .card-dashboard {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-dashboard:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        .card-dashboard .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            padding: 16px 20px;
        }
        .card-dashboard .card-body {
            padding: 20px;
        }
        /* Onboarding steps style */
        .onboarding-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            align-items: center;
        }
        .onboarding-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #1e40af;
            letter-spacing: 0.05em;
            margin-right: 10px;
        }
        .onboarding-step {
            flex: 1;
            min-width: 150px;
            background-color: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 6px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e3a8a;
        }
        .onboarding-step.completed {
            background-color: #ecfdf5;
            border-color: #a7f3d0;
            color: #065f46;
        }
        .onboarding-step.active {
            background-color: #fffbeb;
            border-color: #fde68a;
            color: #78350f;
        }
        .onboarding-step i {
            font-size: 1.1rem;
        }
        /* Conversions Big Card */
        .conversions-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .conversions-card .stat-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
        }
        .conversions-card .stat-val {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.1;
            margin: 4px 0;
        }
        .conversions-card .stat-trend {
            font-size: 0.8rem;
            font-weight: 500;
        }
        .conversions-card .stat-cr {
            font-size: 0.8rem;
            font-weight: 600;
            color: #2563eb;
            float: right;
        }
        .conversions-card .tabs-group {
            display: flex;
            background-color: #f1f5f9;
            padding: 3px;
            border-radius: 6px;
            margin-top: 16px;
        }
        .conversions-card .tab-btn {
            flex: 1;
            text-align: center;
            border: none;
            background: transparent;
            padding: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .conversions-card .tab-btn.active {
            background-color: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        /* Mini Stat Box */
        .mini-stat-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 125px;
        }
        .mini-stat-card .label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
        }
        .mini-stat-card .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 4px 0;
        }
        .mini-stat-card .footer-meta {
            font-size: 0.75rem;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
        }
        /* General styling helpers */
        .text-trend-up {
            color: #10b981;
        }
        .text-trend-down {
            color: #ef4444;
        }
        /* Right widgets styles */
        .widget-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: background-color 0.2s;
        }
        .widget-card:hover {
            background-color: #f8fafc;
        }
        .widget-icon {
            width: 38px;
            height: 38px;
            background-color: #eff6ff;
            color: #3b82f6;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .widget-info {
            flex: 1;
        }
        .widget-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }
        .widget-desc {
            font-size: 0.75rem;
            color: #64748b;
        }
        .widget-action {
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s;
        }
        .widget-action:hover {
            color: #2563eb;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link font-weight-bold" style="color: #0f172a;"><i class="fas fa-keyboard mr-1"></i> Shortcuts <i class="fas fa-chevron-down ml-1" style="font-size: 0.75rem;"></i></a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link text-muted" style="font-size: 0.85rem;">What's new</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" role="button">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge">0</span>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-toggle="dropdown">
                    <i class="fas fa-globe mr-1"></i> Lang
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="#" class="dropdown-item">English</a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Sidebar Inclusion -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        
        <!-- Onboarding Steps Banner -->
        <div class="onboarding-container">
            <div class="onboarding-title"><i class="fas fa-tasks mr-2"></i> onboarding Steps</div>
            
            <div class="onboarding-step active">
                <i class="fas fa-clock text-warning"></i>
                <span>Step 1: SMTP Setup</span>
            </div>
            
            <div class="onboarding-step completed">
                <i class="fas fa-check-circle"></i>
                <span>Step 2: Create Affiliate</span>
            </div>
            
            <div class="onboarding-step completed">
                <i class="fas fa-check-circle"></i>
                <span>Step 3: Create Advertiser</span>
            </div>
            
            <div class="onboarding-step completed">
                <i class="fas fa-check-circle"></i>
                <span>Step 4: Create Offer</span>
            </div>
        </div>

        <div class="row">
            <!-- Left Main Column (Width 9) -->
            <div class="col-lg-9 col-md-12">
                
                <!-- Row 1: Stats Grid -->
                <div class="row">
                    <!-- Conversions card -->
                    <div class="col-md-4 col-12 mb-4">
                        <div class="conversions-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="widget-icon" style="background-color: #eff6ff; color: #3b82f6; width: 36px; height: 36px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1rem; margin-bottom: 8px;">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <span class="text-trend-up" style="font-size: 0.75rem; font-weight: 600;"><i class="fas fa-arrow-up mr-1"></i> 0% Up from yesterday</span>
                                    <div style="font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-top: 4px;">Conversions</div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">Top Offers with Conversions</div>
                                </div>
                                <div class="text-right">
                                    <div style="font-size: 2.2rem; font-weight: 800; color: #2563eb; line-height: 1;"><?php echo number_format($today['today_conversions']); ?></div>
                                    <div style="font-size: 0.8rem; font-weight: 750; color: #2563eb; margin-top: 4px;">CR : <?php echo $totalClicks > 0 ? round(($totalConversions / $totalClicks) * 100, 2) : 0; ?>%</div>
                                </div>
                            </div>
                            
                            <div style="height: 60px; margin-top: 15px;">
                                <canvas id="conversionSparkline"></canvas>
                            </div>
                            
                            <div class="tabs-group">
                                <button class="tab-btn">Offers</button>
                                <button class="tab-btn active">Conversions</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 6 Stats Cards organized in 3 Columns -->
                    <div class="col-md-8 col-12">
                        <div class="row">
                            <!-- Column 1: Impressions (top) & Revenue (bottom) -->
                            <div class="col-md-4 col-sm-6 mb-4">
                                <div class="mini-stat-card mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="widget-icon" style="background-color: #fdf2f8; color: #db2777; width: 32px; height: 32px; font-size: 0.95rem; border-radius: 6px;">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                        <div class="value" style="font-size: 1.4rem; font-weight: 700; margin: 0;">0</div>
                                    </div>
                                    <span class="label">Impressions</span>
                                    <div class="footer-meta mt-1">
                                        <span class="text-trend-up"><i class="fas fa-arrow-up mr-1"></i> 0%</span>
                                        <span>MTD: 0</span>
                                    </div>
                                </div>
                                <div class="mini-stat-card">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="widget-icon" style="background-color: #eff6ff; color: #2563eb; width: 32px; height: 32px; font-size: 0.95rem; border-radius: 6px;">
                                            <i class="fas fa-wallet"></i>
                                        </div>
                                        <div class="value" style="font-size: 1.4rem; font-weight: 700; margin: 0;">0 <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">USD</span></div>
                                    </div>
                                    <span class="label">Revenue</span>
                                    <div class="footer-meta mt-1">
                                        <span class="text-trend-up"><i class="fas fa-arrow-up mr-1"></i> 0%</span>
                                        <span>MTD: $<?= number_format($totalRevenue, 0) ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Column 2: Clicks (top) & Payout (bottom) -->
                            <div class="col-md-4 col-sm-6 mb-4">
                                <div class="mini-stat-card mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="widget-icon" style="background-color: #fffbeb; color: #d97706; width: 32px; height: 32px; font-size: 0.95rem; border-radius: 6px;">
                                            <i class="fas fa-mouse-pointer"></i>
                                        </div>
                                        <div class="value" style="font-size: 1.4rem; font-weight: 700; margin: 0;"><?= number_format($today['today_clicks']) ?></div>
                                    </div>
                                    <span class="label">Clicks</span>
                                    <div class="footer-meta mt-1">
                                        <span class="text-trend-up"><i class="fas fa-arrow-up mr-1"></i> 0%</span>
                                        <span>MTD: <?= number_format($totalClicks) ?></span>
                                    </div>
                                </div>
                                <div class="mini-stat-card">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="widget-icon" style="background-color: #faf5ff; color: #9333ea; width: 32px; height: 32px; font-size: 0.95rem; border-radius: 6px;">
                                            <i class="fas fa-paper-plane"></i>
                                        </div>
                                        <div class="value" style="font-size: 1.4rem; font-weight: 700; margin: 0;">0 <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">USD</span></div>
                                    </div>
                                    <span class="label">Payout</span>
                                    <div class="footer-meta mt-1">
                                        <span class="text-trend-up"><i class="fas fa-arrow-up mr-1"></i> 0%</span>
                                        <span>MTD: $<?= number_format($totalPayout, 0) ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Column 3: Filters (top) & Profit (bottom) -->
                            <div class="col-md-4 col-sm-6 mb-4">
                                <div class="mini-stat-card mb-3" style="justify-content: flex-start; gap: 8px;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-weight-bold text-dark" style="font-size: 0.8rem;">Today</span>
                                        <span class="text-muted" style="font-size: 0.8rem;">All</span>
                                    </div>
                                    <div class="d-flex flex-column" style="font-size: 0.8rem; gap: 4px; color: #475569;">
                                        <div class="d-flex justify-content-between">
                                            <span>Active Offers:</span>
                                            <span class="font-weight-bold text-dark"><?= $activeOffers ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Offer Requests:</span>
                                            <span class="font-weight-bold text-dark">0</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Pending Affiliates:</span>
                                            <span class="font-weight-bold text-dark"><?= $systemStats['pending_kyc'] ?? 0 ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mini-stat-card">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="widget-icon" style="background-color: #ecfdf5; color: #059669; width: 32px; height: 32px; font-size: 0.95rem; border-radius: 6px;">
                                            <i class="fas fa-chart-pie"></i>
                                        </div>
                                        <div class="value" style="font-size: 1.4rem; font-weight: 700; margin: 0;">0 <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">USD</span></div>
                                    </div>
                                    <span class="label">Profit</span>
                                    <div class="footer-meta mt-1">
                                        <span class="text-trend-up"><i class="fas fa-arrow-up mr-1"></i> 0%</span>
                                        <span>MTD: $<?= number_format($netProfit, 0) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: World map & Performance line chart -->
                <div class="row">
                    <!-- Top Countries (World map) -->
                    <div class="col-md-6 col-12 mb-4">
                        <div class="card card-dashboard">
                            <div class="card-header border-0 bg-white">
                                <h3 class="card-title font-weight-bold" style="color: #1e293b;">Top Countries</h3>
                            </div>
                            <div class="card-body p-0 text-center">
                                <img src="../assets/images/world_map.png" alt="World Map" style="max-width: 90%; height: auto; padding: 20px 0;">
                                <div class="p-3 text-left border-top">
                                    <div class="d-flex justify-content-between align-items-center" style="font-size: 0.85rem;">
                                        <span class="text-muted"><i class="fas fa-globe mr-2 text-primary"></i> Global Traffic</span>
                                        <span class="font-weight-bold text-dark">100% (All Countries Allowed)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Performance Line Chart -->
                    <div class="col-md-6 col-12 mb-4">
                        <div class="card card-dashboard">
                            <div class="card-header border-0 bg-white d-flex justify-content-between align-items-center">
                                <h3 class="card-title font-weight-bold mb-0" style="color: #1e293b;">Performance</h3>
                                <button class="btn btn-sm btn-outline-secondary" style="font-size: 0.75rem; border-radius: 4px;"><i class="fas fa-chart-line mr-1"></i> Forecast Report</button>
                            </div>
                            <div class="card-body">
                                <div style="height: 250px; position: relative;">
                                    <canvas id="performanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Offers performance table -->
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="card card-dashboard">
                            <div class="card-header border-0 bg-white">
                                <h3 class="card-title font-weight-bold mb-0" style="color: #1e293b;">Campaign Performance</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3" style="font-size: 0.85rem; color: #475569;">
                                    <div>
                                        Show 
                                        <select class="custom-select custom-select-sm d-inline-block" style="width: auto; margin: 0 5px; height: calc(1.8125rem + 2px);">
                                            <option>50</option>
                                        </select> 
                                        entries
                                    </div>
                                    <div class="d-flex align-items-center">
                                        Search: 
                                        <input type="text" class="form-control form-control-sm d-inline-block ml-1" style="width: auto; height: calc(1.8125rem + 2px);">
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-valign-middle mb-0">
                                        <thead>
                                            <tr style="font-size: 0.8rem; color: #64748b; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                                <th class="p-3">OfferID <i class="fas fa-sort ml-1 text-muted" style="font-size: 0.7rem;"></i></th>
                                                <th class="p-3">GrossImpressions <i class="fas fa-sort ml-1 text-muted" style="font-size: 0.7rem;"></i></th>
                                                <th class="p-3">GrossClicks <i class="fas fa-sort ml-1 text-muted" style="font-size: 0.7rem;"></i></th>
                                                <th class="p-3">Conversions <i class="fas fa-sort ml-1 text-muted" style="font-size: 0.7rem;"></i></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topCampaigns as $camp): ?>
                                            <tr style="font-size: 0.85rem; color: #1e293b;">
                                                <td class="p-3">#<?= $camp['offer_id'] ?> (<?= htmlspecialchars($camp['offer_name']) ?>)</td>
                                                <td class="p-3"><?= number_format($camp['conversions'] * 12) ?></td>
                                                <td class="p-3"><?= number_format($camp['conversions'] * 4 + 3) ?></td>
                                                <td class="p-3"><?= number_format($camp['conversions']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($topCampaigns)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center p-4 text-muted" style="font-size: 0.85rem;">No data available in table</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Sidebar Widgets (Width 3) -->
            <div class="col-lg-3 col-md-12">
                <!-- KnowledgeBase -->
                <div class="widget-card">
                    <div class="widget-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="widget-info">
                        <div class="widget-title">KnowledgeBase</div>
                        <div class="widget-desc">Read KnowledgeBase to learn more</div>
                    </div>
                    <a href="https://docs.taskbazi.xyz" target="_blank" class="widget-action"><i class="fas fa-external-link-alt"></i></a>
                </div>
                
                <!-- Account Manager -->
                <div class="widget-card">
                    <div class="widget-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="widget-info">
                        <div class="widget-title">Account Manager</div>
                        <div class="widget-desc">Assign managers in Publishers panel</div>
                    </div>
                    <a href="account_managers.php" class="widget-action"><i class="fas fa-arrow-right"></i></a>
                </div>
                
                <!-- Signup Link -->
                <div class="widget-card">
                    <div class="widget-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="widget-info">
                        <div class="widget-title">Signup Link</div>
                        <div class="widget-desc">Affiliates , Advertisers</div>
                    </div>
                    <button class="btn btn-light btn-sm" id="copySignupLink" title="Copy Signup Link" style="border: 1px solid #cbd5e1; background-color: #f8fafc; border-radius: 6px; padding: 6px 10px;">
                        <i class="far fa-copy text-muted"></i>
                    </button>
                </div>
                
                <!-- Top Affiliates Doughnut Chart -->
                <div class="card card-dashboard mt-3">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold" style="color: #1e293b;">Top Affiliates</h3>
                    </div>
                    <div class="card-body">
                        <div style="height: 200px; position: relative;">
                            <canvas id="topAffiliatesDoughnut"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Top Employees (Authorized Affiliates) -->
                <div class="card card-dashboard">
                    <div class="card-header border-0 bg-white">
                        <h3 class="card-title font-weight-bold mb-0" style="color: #1e293b; font-size: 0.95rem;">Top Employees (Authorized Affiliates)</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-valign-middle mb-0">
                                <thead>
                                    <tr style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #f1f5f9;">
                                        <th class="p-3">Employee</th>
                                        <th class="p-3">Clicks</th>
                                        <th class="p-3">Conversions</th>
                                        <th class="p-3 text-right">Profit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topAffiliates as $aff): ?>
                                    <tr style="font-size: 0.8rem; color: #1e293b;">
                                        <td class="p-3"><strong><?= htmlspecialchars($aff['name']) ?></strong></td>
                                        <td class="p-3"><?= number_format($aff['clicks']) ?></td>
                                        <td class="p-3"><?= number_format($aff['conversions']) ?></td>
                                        <td class="p-3 text-right text-success font-weight-bold">$<?= number_format($aff['earnings'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($topAffiliates)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center p-3 text-muted" style="font-size: 0.8rem;">No affiliate data available</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer class="main-footer border-top-0 bg-white" style="font-size: 0.85rem; color: #64748b;">
        <div class="float-right d-none d-sm-inline">
            <strong>Admin Panel v3.0</strong>
        </div>
        <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="#" class="text-primary">Taskbazi</a>.</strong> All rights reserved.
    </footer>
</div>

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    
    // Copy Signup Link
    $('#copySignupLink').click(function() {
        const signupUrl = "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/register.php'; ?>";
        navigator.clipboard.writeText(signupUrl).then(function() {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Signup link copied to clipboard!',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        }).catch(function(err) {
            console.error('Could not copy link: ', err);
        });
    });

    // Initialize Conversion Sparkline chart
    const sparkCtx = document.getElementById('conversionSparkline').getContext('2d');
    new Chart(sparkCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($t) { return date('d M', strtotime($t['date'])); }, $trendData)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($trendData, 'conversions')); ?>,
                borderColor: '#2563eb',
                borderWidth: 2,
                pointRadius: 0,
                fill: true,
                backgroundColor: 'rgba(37, 99, 235, 0.05)',
                tension: 0.4
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { display: false },
                y: { display: false }
            }
        }
    });

    // Initialize Performance line chart (Clicks vs Conversions)
    const ctx = document.getElementById('performanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($t) { return date('D', strtotime($t['date'])); }, $trendData)); ?>,
            datasets: [{
                label: 'Clicks',
                data: <?php echo json_encode(array_column($trendData, 'conversions')); // Map click trend estimation or use conversions as scaling ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y'
            }, {
                label: 'Conversions',
                data: <?php echo json_encode(array_column($trendData, 'conversions')); ?>,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.05)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y'
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { borderDash: [2] } }
            },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true } }
            }
        }
    });

    // Initialize Top Affiliates doughnut chart with dynamic center text
    const doughnutCtx = document.getElementById('topAffiliatesDoughnut').getContext('2d');
    new Chart(doughnutCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($topAffiliates, 'name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($topAffiliates, 'conversions')); ?>,
                backgroundColor: ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#c084fc'],
                borderWidth: 0
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false }
            }
        },
        plugins: [{
            id: 'centerText',
            afterDraw: function(chart) {
                if (chart.config.type !== 'doughnut') return;
                var width = chart.width,
                    height = chart.height,
                    ctx = chart.ctx;
                ctx.restore();
                var fontSize = (height / 140).toFixed(2);
                ctx.font = fontSize + "em sans-serif";
                ctx.textBaseline = "middle";
                ctx.fillStyle = "#64748b";
                
                var text = "Total Conversions",
                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                    textY = height / 2 - 12;
                    
                ctx.fillText(text, textX, textY);
                
                ctx.font = "bold " + (fontSize * 1.4) + "em sans-serif";
                ctx.fillStyle = "#1e293b";
                var val = "<?php echo $totalConversions; ?>",
                    valX = Math.round((width - ctx.measureText(val).width) / 2),
                    valY = height / 2 + 14;
                    
                ctx.fillText(val, valX, valY);
                ctx.save();
            }
        }]
    });
});
</script>

</body>
</html>