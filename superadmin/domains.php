<?php
/**
 * Super Admin - Domain Router Settings
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

// Handle domain deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $domainId = (int)$_GET['id'];
    
    // Safety check: do not delete primary domain of default tenant (id=1)
    $stmt = $pdo->prepare("SELECT tenant_id, is_primary FROM tenant_domains WHERE id = ?");
    $stmt->execute([$domainId]);
    $domainInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($domainInfo && $domainInfo['tenant_id'] === 1 && $domainInfo['is_primary'] == 1) {
        $error = "The primary domain of the seed tenant cannot be deleted.";
    } else {
        $del = $pdo->prepare("DELETE FROM tenant_domains WHERE id = ?");
        $del->execute([$domainId]);
        $message = "Domain routing mapping deleted successfully.";
    }
}

// Handle domain creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_domain'])) {
    $tenantId = (int)($_POST['tenant_id'] ?? 0);
    $domain = strtolower(trim($_POST['domain'] ?? ''));
    $type = $_POST['type'] ?? 'subdomain';
    $isPrimary = isset($_POST['is_primary']) ? 1 : 0;

    if ($tenantId <= 0 || empty($domain)) {
        $error = "Tenant and Domain are required fields.";
    } else {
        try {
            // Check if domain already mapped
            $chk = $pdo->prepare("SELECT 1 FROM tenant_domains WHERE domain = ?");
            $chk->execute([$domain]);
            if ($chk->fetch()) {
                throw new Exception("The domain '{$domain}' is already mapped in the routing table.");
            }

            if ($isPrimary) {
                // Remove primary status of other domains of this tenant
                $clearPrimary = $pdo->prepare("UPDATE tenant_domains SET is_primary = 0 WHERE tenant_id = ?");
                $clearPrimary->execute([$tenantId]);
            }

            $ins = $pdo->prepare("
                INSERT INTO tenant_domains (tenant_id, domain, type, is_primary, verification_status, ssl_status, created_at)
                VALUES (?, ?, ?, ?, 'verified', 'none', NOW())
            ");
            $ins->execute([$tenantId, $domain, $type, $isPrimary]);
            
            $message = "Domain route '{$domain}' mapped successfully.";
            $_POST = [];
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Fetch all domain routes
$routes = $pdo->query("
    SELECT td.*, t.name as tenant_name, t.slug as tenant_slug 
    FROM tenant_domains td
    INNER JOIN tenants t ON t.id = td.tenant_id
    ORDER BY td.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch tenants for dropdown
$tenants = $pdo->query("SELECT id, name FROM tenants WHERE status != 'deleted' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Domain Router · SaaS Super Admin</title>
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
        
        .layout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
        }
        .section-card {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 28px;
        }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #c084fc; }
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
        }
        .alert-success { background-color: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }
        .alert-danger { background-color: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        
        /* Form */
        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        label { font-size: 13px; font-weight: 600; color: #94a3b8; }
        input, select {
            background-color: #0f172a; border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px; padding: 12px 16px; color: white; font-family: inherit; font-size: 14px;
        }
        input:focus, select:focus { outline: none; border-color: #a855f7; box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2); }
        .checkbox-group { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .btn-submit {
            background-color: #a855f7; color: white; border: none; padding: 12px; border-radius: 10px;
            font-weight: 700; cursor: pointer; transition: background 0.2s; font-size: 14px; width: 100%;
        }
        .btn-submit:hover { background-color: #9333ea; }
        
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; padding: 12px 16px; color: #94a3b8; font-weight: 600; font-size: 12px;
            text-transform: uppercase; border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        td { padding: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 14px; vertical-align: middle; }
        .badge { display: inline-flex; padding: 4px 8px; border-radius: 100px; font-size: 11px; font-weight: 600; }
        .badge-primary { background-color: rgba(168, 85, 247, 0.2); color: #c084fc; }
        .badge-secondary { background-color: rgba(148, 163, 184, 0.15); color: #94a3b8; }
        .badge-ssl { background-color: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .btn-delete { background-color: rgba(239, 68, 68, 0.15); color: #f87171; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; }
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
            <li class="nav-item active">
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
                <h1>Domain Router & Mapping</h1>
                <p>Manage hostname route resolution mapping domains to tenant networks.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=$message?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?=$error?></div>
        <?php endif; ?>

        <div class="layout-grid">
            <!-- Left: Routes Table -->
            <div class="section-card">
                <h3 class="section-title">Active Routing Tables</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Tenant Network</th>
                            <th>Hostname Domain</th>
                            <th>Routing Type</th>
                            <th>Scope</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($routes)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">No routing records mapped.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($routes as $r): ?>
                                <tr>
                                    <td>
                                        <strong style="color: #f1f5f9;"><?=$r['tenant_name']?></strong>
                                        <div style="font-size: 12px; color: #64748b;">Slug: <code><?=$r['tenant_slug']?></code></div>
                                    </td>
                                    <td><code style="font-size: 14px; color: #c084fc;"><?=$r['domain']?></code></td>
                                    <td><span style="text-transform: capitalize;"><?=$r['type']?></span></td>
                                    <td>
                                        <?php if ($r['is_primary']): ?>
                                            <span class="badge badge-primary">Primary Route</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Alias Domain</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="domains.php?action=delete&id=<?=$r['id']?>" class="btn-delete" onclick="return confirm('Delete this routing record? The network will no longer load under this hostname.')"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Right: Add Route Form -->
            <div class="section-card" style="height: fit-content;">
                <h3 class="section-title">Create Domain Route</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="tenant_id">Tenant Network *</label>
                        <select id="tenant_id" name="tenant_id" required>
                            <option value="">-- Select Tenant --</option>
                            <?php foreach ($tenants as $t): ?>
                                <option value="<?=$t['id']?>" <?=(($_POST['tenant_id']??'')==$t['id'])?'selected':''?>><?=$t['name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="domain">Hostname Domain Address *</label>
                        <input type="text" id="domain" name="domain" placeholder="e.g. tracking.acme.com" value="<?=htmlspecialchars($_POST['domain']??'')?>" required>
                    </div>

                    <div class="form-group">
                        <label for="type">Routing Record Type</label>
                        <select id="type" name="type">
                            <option value="subdomain">Subdomain</option>
                            <option value="custom">Custom Domain</option>
                            <option value="root">Root Domain</option>
                        </select>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="is_primary" name="is_primary" value="1">
                        <label for="is_primary">Set as Primary domain route</label>
                    </div>

                    <button type="submit" name="add_domain" class="btn-submit">Add Route Mapping</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
