<?php
define('APP_INIT', true);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_role('affiliate');

$affiliateId = auth_user_id();
$affiliateName = $_SESSION['user_name'] ?? 'Affiliate';

$stmt = $pdo->prepare("
    SELECT
        o.offer_id,
        o.offer_name,
        o.payout,
        o.currency,
        o.category,
        o.preview_url
    FROM offers o
    INNER JOIN affiliate_offer_approval a
        ON a.offer_id = o.offer_id
    WHERE o.tenant_id = " . current_tenant_id() . " AND a.affiliate_id = :aid
      AND a.status = 'approved'
      AND o.status = 'approved'
    ORDER BY o.created_at DESC
");
$stmt->execute(['aid' => $affiliateId]);
$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Approved Offers | Taskbazi</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-stat {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .card-stat .card-header {
            border-radius: 15px 15px 0 0;
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background: rgba(0,0,0,0.02);
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="dashboard.php" class="nav-link">Home</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                    <i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($affiliateName); ?>
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
                        <h1 class="m-0">Approved Offers</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Approved Offers</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="container-fluid">
                <div class="card card-stat">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title">My Approved Offers</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Offer Name</th>
                                        <th>Category</th>
                                        <th>Payout</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($offers)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No approved offers found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($offers as $o): ?>
                                            <tr>
                                                <td class="font-weight-bold"><?= htmlspecialchars($o['offer_name']) ?></td>
                                                <td>
                                                    <span class="badge badge-secondary">
                                                        <?= htmlspecialchars($o['category'] ?? 'General') ?>
                                                    </span>
                                                </td>
                                                <td class="text-success font-weight-bold">
                                                    <?= htmlspecialchars($o['currency']) ?> <?= number_format($o['payout'], 2) ?>
                                                </td>
                                                <td>
                                                    <a href="offer_view.php?id=<?= $o['offer_id'] ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye mr-1"></i> View Offer
                                                    </a>
                                                    <?php if (!empty($o['preview_url'])): ?>
                                                        <a href="<?= htmlspecialchars($o['preview_url']) ?>" target="_blank" class="btn btn-default btn-sm ml-1">
                                                            <i class="fas fa-external-link-alt mr-1"></i> Preview
                                                        </a>
                                                    <?php endif; ?>
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
