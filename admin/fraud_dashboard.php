<?php
define('APP_INIT', true);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_any_role(['admin', 'manager']);

$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fraud Signals Dashboard | Taskbazi</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .card-dashboard {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }
        .card-dashboard .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            padding: 16px 20px;
        }
        .table thead th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            font-weight: 600 !important;
            border-bottom: 1px solid #e2e8f0 !important;
            font-size: 0.85rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="dashboard.php" class="nav-link">Home</a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                    <i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($adminName); ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="../logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-shield-alt text-danger mr-2"></i>Fraud Signals Dashboard</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Fraud Signals</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="container-fluid">
                
                <div class="row">
                    <!-- 1. Fast Conversions -->
                    <div class="col-md-6">
                        <div class="card card-dashboard">
                            <div class="card-header">
                                <h3 class="card-title font-weight-bold text-danger">
                                    <i class="fas fa-bolt mr-2"></i>Fast Conversions (&lt; 5 sec)
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Click ID</th>
                                                <th>Affiliate</th>
                                                <th>Offer</th>
                                                <th>Seconds</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $fast = $pdo->query("
                                                SELECT
                                                    c.click_id,
                                                    u.name AS affiliate,
                                                    o.offer_name,
                                                    TIMESTAMPDIFF(SECOND, cl.created_at, c.created_at) AS seconds_diff
                                                FROM conversions c
                                                INNER JOIN clicks cl ON cl.click_id = c.click_id
                                                INNER JOIN users u ON u.user_id = cl.affiliate_id
                                                INNER JOIN offers o ON o.offer_id = cl.offer_id
                                                WHERE u.tenant_id = " . current_tenant_id() . " AND c.status = 'approved'
                                                  AND TIMESTAMPDIFF(SECOND, cl.created_at, c.created_at) < 5
                                                ORDER BY seconds_diff ASC
                                                LIMIT 50
                                            ")->fetchAll();

                                            if (empty($fast)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">No suspicious fast conversions.</td>
                                                </tr>
                                            <?php else:
                                                foreach ($fast as $r): ?>
                                                <tr>
                                                    <td><code><?= htmlspecialchars($r['click_id']) ?></code></td>
                                                    <td><?= htmlspecialchars($r['affiliate']) ?></td>
                                                    <td><?= htmlspecialchars($r['offer_name']) ?></td>
                                                    <td class="text-danger font-weight-bold"><?= (int)$r['seconds_diff'] ?>s</td>
                                                </tr>
                                                <?php endforeach;
                                            endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Multiple Conversions from Same IP -->
                    <div class="col-md-6">
                        <div class="card card-dashboard">
                            <div class="card-header">
                                <h3 class="card-title font-weight-bold text-danger">
                                    <i class="fas fa-network-wired mr-2"></i>Multiple Conversions from Same IP
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Conversions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $ips = $pdo->query("
                                                SELECT
                                                    INET6_NTOA(cl.ip_address) AS ip,
                                                    COUNT(c.conversion_id) AS cnt
                                                FROM conversions c
                                                INNER JOIN clicks cl ON cl.click_id = c.click_id
                                                WHERE cl.tenant_id = " . current_tenant_id() . " AND c.status = 'approved'
                                                GROUP BY cl.ip_address
                                                HAVING cnt >= 3
                                                ORDER BY cnt DESC
                                                LIMIT 50
                                            ")->fetchAll();

                                            if (empty($ips)): ?>
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted py-3">No duplicate conversion IPs found.</td>
                                                </tr>
                                            <?php else:
                                                foreach ($ips as $r): ?>
                                                <tr>
                                                    <td><code><?= htmlspecialchars($r['ip']) ?></code></td>
                                                    <td class="font-weight-bold text-danger"><?= $r['cnt'] ?></td>
                                                </tr>
                                                <?php endforeach;
                                            endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- 3. High Clicks, Zero Conversions -->
                    <div class="col-md-6">
                        <div class="card card-dashboard">
                            <div class="card-header">
                                <h3 class="card-title font-weight-bold text-danger">
                                    <i class="fas fa-mouse mr-2"></i>High Clicks, Zero Conversions
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Affiliate</th>
                                                <th>Clicks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $badAff = $pdo->query("
                                                SELECT
                                                    u.name,
                                                    COUNT(cl.click_id) AS clicks
                                                FROM users u
                                                LEFT JOIN clicks cl ON cl.affiliate_id = u.user_id
                                                LEFT JOIN conversions c ON c.click_id = cl.click_id
                                                WHERE u.tenant_id = " . current_tenant_id() . " AND u.role_id = (SELECT role_id FROM roles WHERE role_name='affiliate')
                                                GROUP BY u.user_id
                                                HAVING clicks >= 50 AND SUM(CASE WHEN c.conversion_id IS NOT NULL THEN 1 ELSE 0 END) = 0
                                                ORDER BY clicks DESC
                                            ")->fetchAll();

                                            if (empty($badAff)): ?>
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted py-3">No affiliates with zero conversions.</td>
                                                </tr>
                                            <?php else:
                                                foreach ($badAff as $r): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r['name']) ?></td>
                                                    <td class="font-weight-bold text-danger"><?= (int)$r['clicks'] ?></td>
                                                </tr>
                                                <?php endforeach;
                                            endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Postback Abuse / Failures -->
                    <div class="col-md-6">
                        <div class="card card-dashboard">
                            <div class="card-header">
                                <h3 class="card-title font-weight-bold text-danger">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>Postback Abuse / Failures
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th>Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $pb = $pdo->query("
                                                SELECT status, COUNT(*) cnt
                                                FROM postback_logs
                                                WHERE tenant_id = " . current_tenant_id() . " AND status IN ('invalid_token','ip_blocked','duplicate')
                                                GROUP BY status
                                            ")->fetchAll();

                                            if (empty($pb)): ?>
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted py-3">No postback failures logged.</td>
                                                </tr>
                                            <?php else:
                                                foreach ($pb as $r): ?>
                                                <tr>
                                                    <td><span class="badge badge-danger"><?= strtoupper(htmlspecialchars($r['status'])) ?></span></td>
                                                    <td class="font-weight-bold text-danger"><?= (int)$r['cnt'] ?></td>
                                                </tr>
                                                <?php endforeach;
                                            endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            <strong>Taskbazi v3.0</strong>
        </div>
        <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="#">Taskbazi</a>.</strong> All rights reserved.
    </footer>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
