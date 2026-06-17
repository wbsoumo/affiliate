<?php
/**
 * Super Admin - Tenants List & Actions
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

// Handle Actions (Suspend / Activate / Delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $tenantId = (int)$_GET['id'];

    if ($tenantId === 1 && $action === 'suspend') {
        $error = "The primary seed tenant cannot be suspended.";
    } else {
        try {
            if ($action === 'suspend') {
                $stmt = $pdo->prepare("UPDATE tenants SET status = 'suspended' WHERE id = ?");
                $stmt->execute([$tenantId]);
                $message = "Tenant suspended successfully.";
            } elseif ($action === 'activate') {
                $stmt = $pdo->prepare("UPDATE tenants SET status = 'active' WHERE id = ?");
                $stmt->execute([$tenantId]);
                $message = "Tenant activated successfully.";
            } elseif ($action === 'delete') {
                // Soft delete or hard delete. Let's do a soft delete status update.
                $stmt = $pdo->prepare("UPDATE tenants SET status = 'deleted' WHERE id = ?");
                $stmt->execute([$tenantId]);
                $message = "Tenant marked as deleted.";
            }
        } catch (PDOException $e) {
            $error = "Action failed: " . $e->getMessage();
        }
    }
}

// Fetch all tenants (excluding deleted ones by default, unless requested)
$showDeleted = isset($_GET['show_deleted']) ? 1 : 0;
$statusFilter = $_GET['status'] ?? 'all';

$sql = "SELECT t.*, 
          (SELECT COUNT(*) FROM users WHERE tenant_id = t.id) as total_users,
          (SELECT domain FROM tenant_domains WHERE tenant_id = t.id AND is_primary = 1 LIMIT 1) as primary_domain
        FROM tenants t";
$whereClauses = [];

if (!$showDeleted) {
    $whereClauses[] = "t.status != 'deleted'";
}
if ($statusFilter !== 'all') {
    $whereClauses[] = "t.status = " . $pdo->quote($statusFilter);
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY t.created_at DESC";

$tenants = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Tenants · SaaS Super Admin</title>
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
        .btn-add {
            background-color: #a855f7;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-add:hover { background-color: #9333ea; }
        .section-card {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 24px;
        }
        .filters {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            align-items: center;
        }
        .filter-btn {
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.05);
            color: #94a3b8;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .filter-btn.active, .filter-btn:hover {
            background-color: #a855f7;
            color: white;
            border-color: #a855f7;
        }
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
        }
        .alert-success { background-color: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }
        .alert-danger { background-color: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 14px 18px;
            color: #94a3b8;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        td { padding: 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 14px; vertical-align: middle; }
        tr:hover td { background-color: rgba(255, 255, 255, 0.01); }
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
        .status-deleted { background-color: rgba(100, 116, 139, 0.15); color: #94a3b8; }
        .actions-cell { display: flex; gap: 8px; }
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-view { background-color: #334155; color: #f1f5f9; }
        .btn-edit { background-color: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .btn-suspend { background-color: rgba(239, 68, 68, 0.15); color: #f87171; }
        .btn-activate { background-color: rgba(34, 197, 94, 0.15); color: #4ade80; }
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
                <h1>Tenants & Networks</h1>
                <p>Manage SaaS tenant databases, subscription status, limits, and domain configurations.</p>
            </div>
            <a href="tenant_create.php" class="btn-add">
                <i class="fas fa-plus"></i> New Network
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=$message?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?=$error?></div>
        <?php endif; ?>

        <div class="section-card">
            <div class="filters">
                <a href="tenants.php?status=all" class="filter-btn <?=$statusFilter==='all'?'active':''?>">All Networks</a>
                <a href="tenants.php?status=active" class="filter-btn <?=$statusFilter==='active'?'active':''?>">Active</a>
                <a href="tenants.php?status=suspended" class="filter-btn <?=$statusFilter==='suspended'?'active':''?>">Suspended</a>
                <a href="tenants.php?status=pending" class="filter-btn <?=$statusFilter==='pending'?'active':''?>">Pending Setup</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Network ID</th>
                        <th>Network Name</th>
                        <th>Slug / Domain</th>
                        <th>Subscription Plan</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tenants)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #64748b; padding: 30px;">No tenants found matching the filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tenants as $t): ?>
                            <tr>
                                <td><code>#<?=$t['id']?></code></td>
                                <td>
                                    <div style="font-weight: 600;"><?=$t['name']?></div>
                                    <div style="font-size: 12px; color: #64748b;"><?=$t['company_name']?></div>
                                </td>
                                <td>
                                    <div><code><?=$t['slug']?></code></div>
                                    <div style="font-size: 12px; color: #a855f7;"><?=htmlspecialchars($t['primary_domain'] ?? 'No domain mapped')?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 500;"><?=$t['plan_name']?></div>
                                    <div style="font-size: 12px; color: #64748b;">Limit: <?=$t['max_offers']?> Offers</div>
                                </td>
                                <td><?=$t['total_users']?> Users</td>
                                <td>
                                    <span class="status-badge status-<?=$t['status']?>"><?=ucfirst($t['status'])?></span>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="tenant_view.php?id=<?=$t['id']?>" class="btn-action btn-view" title="Inspect & Impersonate"><i class="fas fa-eye"></i> View</a>
                                        <a href="tenant_edit.php?id=<?=$t['id']?>" class="btn-action btn-edit"><i class="fas fa-pen"></i> Edit</a>
                                        <?php if ($t['status'] === 'active'): ?>
                                            <a href="tenants.php?action=suspend&id=<?=$t['id']?>&status=<?=$statusFilter?>" class="btn-action btn-suspend" onclick="return confirm('Suspend this network portal and block all links?')"><i class="fas fa-ban"></i> Suspend</a>
                                        <?php else: ?>
                                            <a href="tenants.php?action=activate&id=<?=$t['id']?>&status=<?=$statusFilter?>" class="btn-action btn-activate"><i class="fas fa-play"></i> Activate</a>
                                        <?php 
                                        endif;
                                        if ($t['id'] !== 1 && $t['status'] !== 'deleted'): ?>
                                            <a href="tenants.php?action=delete&id=<?=$t['id']?>&status=<?=$statusFilter?>" class="btn-action btn-suspend" style="background-color: rgba(239, 68, 68, 0.3); color: #fca5a5;" onclick="return confirm('Mark this tenant as deleted? Warning: This does not wipe data, but marks it inactive.')"><i class="fas fa-trash"></i> Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
