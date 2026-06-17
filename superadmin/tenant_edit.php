<?php
/**
 * Super Admin - Edit Tenant Network
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

// Fetch primary domain mapping
$dStmt = $pdo->prepare("SELECT domain FROM tenant_domains WHERE tenant_id = ? AND is_primary = 1 LIMIT 1");
$dStmt->execute([$tenantId]);
$primaryDomain = $dStmt->fetchColumn() ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    $plan_name = $_POST['plan_name'] ?? 'Starter';
    $max_affiliates = (int)($_POST['max_affiliates'] ?? 100);
    $max_advertisers = (int)($_POST['max_advertisers'] ?? 20);
    $max_offers = (int)($_POST['max_offers'] ?? 100);
    
    $timezone = $_POST['timezone'] ?? 'UTC';
    $currency = $_POST['currency'] ?? 'USD';
    $domain = trim($_POST['domain'] ?? '');

    if (empty($name) || empty($status) || empty($domain)) {
        $error = "Name, Status, and Domain are required fields.";
    } else {
        $pdo->beginTransaction();
        try {
            // Check if domain is taken by another tenant
            $chkDomain = $pdo->prepare("SELECT 1 FROM tenant_domains WHERE domain = ? AND tenant_id != ?");
            $chkDomain->execute([$domain, $tenantId]);
            if ($chkDomain->fetch()) {
                throw new Exception("The domain '{$domain}' is already mapped to another tenant.");
            }

            // Update tenants
            $uStmt = $pdo->prepare("
                UPDATE tenants 
                SET name = ?, company_name = ?, status = ?, plan_name = ?, max_affiliates = ?, max_advertisers = ?, max_offers = ?, timezone = ?, currency = ?
                WHERE id = ?
            ");
            $uStmt->execute([
                $name, $company_name, $status, $plan_name, $max_affiliates, $max_advertisers, $max_offers, $timezone, $currency, $tenantId
            ]);

            // Update primary domain mapping
            if ($primaryDomain !== '') {
                $dType = (strpos($domain, '.') === false || substr_count($domain, '.') < 2) ? 'custom' : 'subdomain';
                $updDomain = $pdo->prepare("UPDATE tenant_domains SET domain = ?, type = ? WHERE tenant_id = ? AND is_primary = 1");
                $updDomain->execute([$domain, $dType, $tenantId]);
            } else {
                $dType = (strpos($domain, '.') === false || substr_count($domain, '.') < 2) ? 'custom' : 'subdomain';
                $updDomain = $pdo->prepare("INSERT INTO tenant_domains (tenant_id, domain, type, is_primary, ssl_status, verification_status) VALUES (?, ?, ?, 1, 'none', 'verified')");
                $updDomain->execute([$tenantId, $domain, $dType]);
            }

            $pdo->commit();
            $message = "Tenant settings updated successfully.";
            
            // Refresh variables
            $tenant['name'] = $name;
            $tenant['company_name'] = $company_name;
            $tenant['status'] = $status;
            $tenant['plan_name'] = $plan_name;
            $tenant['max_affiliates'] = $max_affiliates;
            $tenant['max_advertisers'] = $max_advertisers;
            $tenant['max_offers'] = $max_offers;
            $tenant['timezone'] = $timezone;
            $tenant['currency'] = $currency;
            $primaryDomain = $domain;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Tenant · SaaS Super Admin</title>
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
        .section-card {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 32px;
            max-width: 800px;
        }
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
        }
        .alert-success { background-color: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }
        .alert-danger { background-color: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
        }
        label {
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
        }
        input, select {
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 12px 16px;
            color: white;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.2s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2);
        }
        .form-section-title {
            grid-column: span 2;
            font-size: 16px;
            font-weight: 700;
            color: #c084fc;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: 16px;
            margin-bottom: 16px;
        }
        .btn-submit {
            grid-column: span 2;
            background-color: #a855f7;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 15px;
            margin-top: 16px;
        }
        .btn-submit:hover { background-color: #9333ea; }
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
                <h1>Edit Tenant: <?=htmlspecialchars($tenant['name'])?></h1>
                <p>Modify network configuration and update subscription limit values.</p>
            </div>
            <a href="tenants.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Tenants
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?=$message?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?=$error?></div>
        <?php endif; ?>

        <div class="section-card">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-section-title">General Info</div>
                    
                    <div class="form-group">
                        <label for="name">Network Display Name *</label>
                        <input type="text" id="name" name="name" value="<?=htmlspecialchars($tenant['name'])?>" required>
                    </div>

                    <div class="form-group">
                        <label for="company_name">Legal Company Name</label>
                        <input type="text" id="company_name" name="company_name" value="<?=htmlspecialchars($tenant['company_name'] ?? '')?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Portal Status</label>
                        <select id="status" name="status">
                            <option value="active" <?=$tenant['status']==='active'?'selected':''?>>Active</option>
                            <option value="suspended" <?=$tenant['status']==='suspended'?'selected':''?>>Suspended (Blocked)</option>
                            <option value="pending" <?=$tenant['status']==='pending'?'selected':''?>>Pending Setup</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="domain">Primary Mapped Domain *</label>
                        <input type="text" id="domain" name="domain" value="<?=htmlspecialchars($primaryDomain)?>" required>
                    </div>

                    <div class="form-section-title">Resource Limits & Plan settings</div>

                    <div class="form-group">
                        <label for="plan_name">Subscription Tier</label>
                        <select id="plan_name" name="plan_name">
                            <option value="Starter" <?=$tenant['plan_name']==='Starter'?'selected':''?>>Starter (100 Offers)</option>
                            <option value="Professional" <?=$tenant['plan_name']==='Professional'?'selected':''?>>Professional (500 Offers)</option>
                            <option value="Enterprise" <?=$tenant['plan_name']==='Enterprise'?'selected':''?>>Enterprise (Unlimited)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="max_offers">Max Offers Limit</label>
                        <input type="number" id="max_offers" name="max_offers" value="<?=htmlspecialchars($tenant['max_offers'])?>" required>
                    </div>

                    <div class="form-group">
                        <label for="max_affiliates">Max Publishers Limit</label>
                        <input type="number" id="max_affiliates" name="max_affiliates" value="<?=htmlspecialchars($tenant['max_affiliates'])?>" required>
                    </div>

                    <div class="form-group">
                        <label for="max_advertisers">Max Advertisers Limit</label>
                        <input type="number" id="max_advertisers" name="max_advertisers" value="<?=htmlspecialchars($tenant['max_advertisers'])?>" required>
                    </div>

                    <div class="form-group">
                        <label for="currency">Currency Code</label>
                        <input type="text" id="currency" name="currency" value="<?=htmlspecialchars($tenant['currency'])?>" required>
                    </div>

                    <div class="form-group">
                        <label for="timezone">System Timezone</label>
                        <input type="text" id="timezone" name="timezone" value="<?=htmlspecialchars($tenant['timezone'])?>" required>
                    </div>

                    <button type="submit" class="btn-submit">Save Tenant Settings</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
