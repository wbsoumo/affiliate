<?php
define('APP_INIT', true);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_any_role(['admin', 'manager']);

$adminName = $_SESSION['user_name'] ?? 'Admin';

// Date filter
$from = $_GET['from'] ?? null;
$to   = $_GET['to'] ?? null;

$where = '';
$params = [];

if ($from && $to) {
    $where = "WHERE DATE(c.created_at) BETWEEN :from AND :to";
    $params['from'] = $from;
    $params['to']   = $to;
}

$sql = "
SELECT
    o.offer_id,
    o.offer_name,
    COUNT(DISTINCT cl.click_id) AS clicks,
    COUNT(c.conversion_id) AS conversions,
    SUM(CASE WHEN c.status='approved' THEN c.payout ELSE 0 END) AS payout,
    SUM(CASE WHEN c.status='approved' THEN c.revenue ELSE 0 END) AS revenue
FROM offers o
LEFT JOIN clicks cl ON cl.offer_id = o.offer_id
LEFT JOIN conversions c ON c.offer_id = o.offer_id
{$where}
GROUP BY o.offer_id
ORDER BY revenue DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offer Reports | Taskbazi</title>
    
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
                        <h1 class="m-0"><i class="fas fa-chart-bar text-primary mr-2"></i>Offer Performance Report</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Offer Reports</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="container-fluid">
                
                <!-- Filters Card -->
                <div class="card card-dashboard">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold"><i class="fas fa-filter mr-1"></i> Date Filter</h3>
                    </div>
                    <div class="card-body">
                        <form method="get" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="from" class="mr-2">From:</label>
                                <input type="date" name="from" id="from" class="form-control" value="<?= htmlspecialchars($from ?? '') ?>">
                            </div>
                            <div class="form-group mr-3">
                                <label for="to" class="mr-2">To:</label>
                                <input type="date" name="to" id="to" class="form-control" value="<?= htmlspecialchars($to ?? '') ?>">
                            </div>
                            <button type="submit" class="btn btn-primary font-weight-bold">
                                <i class="fas fa-search mr-1"></i> Filter
                            </button>
                            <?php if ($from || $to): ?>
                                <a href="reports_offers.php" class="btn btn-default font-weight-bold ml-2">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Data Table Card -->
                <div class="card card-dashboard">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold">Offer Stats</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Offer</th>
                                        <th>Clicks</th>
                                        <th>Conversions</th>
                                        <th>Payout</th>
                                        <th>Revenue</th>
                                        <th>Profit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($rows)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rows as $r): 
                                            $profit = $r['revenue'] - $r['payout'];
                                        ?>
                                            <tr>
                                                <td class="font-weight-bold"><?= htmlspecialchars($r['offer_name']) ?></td>
                                                <td><?= number_format($r['clicks']) ?></td>
                                                <td><?= number_format($r['conversions']) ?></td>
                                                <td class="text-danger font-weight-bold">$<?= number_format($r['payout'], 2) ?></td>
                                                <td class="text-success font-weight-bold">$<?= number_format($r['revenue'], 2) ?></td>
                                                <td class="<?= ($profit >= 0) ? 'text-success' : 'text-danger' ?> font-weight-bold">
                                                    $<?= number_format($profit, 2) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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
