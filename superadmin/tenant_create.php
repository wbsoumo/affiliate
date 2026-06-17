<?php
/**
 * Super Admin - Create Tenant Network
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $_POST['slug'] ?? ''));
    $company_name = trim($_POST['company_name'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $owner_email = trim($_POST['owner_email'] ?? '');
    $owner_password = $_POST['owner_password'] ?? '';
    $domain = trim($_POST['domain'] ?? '');
    
    $plan_name = $_POST['plan_name'] ?? 'Starter';
    $max_affiliates = (int)($_POST['max_affiliates'] ?? 100);
    $max_advertisers = (int)($_POST['max_advertisers'] ?? 20);
    $max_offers = (int)($_POST['max_offers'] ?? 100);
    
    $timezone = $_POST['timezone'] ?? 'UTC';
    $currency = $_POST['currency'] ?? 'USD';

    if (empty($name) || empty($slug) || empty($owner_name) || empty($owner_email) || empty($owner_password) || empty($domain)) {
        $error = "All fields marked with * are required.";
    } elseif (!filter_var($owner_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid owner email address.";
    } else {
        // Start Transaction
        $pdo->beginTransaction();
        try {
            // Check if slug exists
            $chkSlug = $pdo->prepare("SELECT 1 FROM tenants WHERE slug = ?");
            $chkSlug->execute([$slug]);
            if ($chkSlug->fetch()) {
                throw new Exception("The tenant slug '{$slug}' is already taken.");
            }

            // Check if domain exists
            $chkDomain = $pdo->prepare("SELECT 1 FROM tenant_domains WHERE domain = ?");
            $chkDomain->execute([$domain]);
            if ($chkDomain->fetch()) {
                throw new Exception("The domain '{$domain}' is already mapped to another tenant.");
            }

            // Insert Tenant
            $tStmt = $pdo->prepare("
                INSERT INTO tenants 
                (name, slug, company_name, owner_name, owner_email, status, plan_name, max_affiliates, max_advertisers, max_offers, timezone, currency, created_at)
                VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, ?, NOW())
            ");
            $tStmt->execute([
                $name, $slug, $company_name, $owner_name, $owner_email,
                $plan_name, $max_affiliates, $max_advertisers, $max_offers,
                $timezone, $currency
            ]);
            $tenantId = (int)$pdo->lastInsertId();

            // Insert Settings
            $sStmt = $pdo->prepare("
                INSERT INTO tenant_settings 
                (tenant_id, site_name, support_email, min_payout, payout_frequency, auto_approve_affiliates, auto_approve_advertisers)
                VALUES (?, ?, ?, 50.00, 'weekly', 0, 0)
            ");
            $sStmt->execute([$tenantId, $name, $owner_email]);

            // Insert Domain mapping
            $dType = (strpos($domain, '.') === false || substr_count($domain, '.') < 2) ? 'custom' : 'subdomain';
            $dStmt = $pdo->prepare("
                INSERT INTO tenant_domains 
                (tenant_id, domain, type, is_primary, verification_status, ssl_status, created_at)
                VALUES (?, ?, ?, 1, 'verified', 'none', NOW())
            ");
            $dStmt->execute([$tenantId, $domain, $dType]);

            // Get admin role ID dynamically
            $roleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'admin' LIMIT 1");
            $roleStmt->execute();
            $roleId = $roleStmt->fetchColumn() ?: 1;

            // Insert Tenant Administrator
            $passHash = password_hash($owner_password, PASSWORD_DEFAULT);
            $uStmt = $pdo->prepare("
                INSERT INTO users 
                (tenant_id, name, email, password_hash, role_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            $uStmt->execute([$tenantId, $owner_name, $owner_email, $passHash, $roleId]);

            $pdo->commit();
            $message = "Tenant network '{$name}' created successfully with Admin: {$owner_email}.";
            
            // Clear post values
            $_POST = [];
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
    <title>Create Tenant Network · SaaS Super Admin</title>
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
        .form-group.full-width {
            grid-column: span 2;
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
                <h1>Create Tenant Network</h1>
                <p>Spin up a new network brand on a dedicated subdomain or custom domain mapping.</p>
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
                    <div class="form-section-title">Network Info</div>
                    
                    <div class="form-group">
                        <label for="name">Network Display Name *</label>
                        <input type="text" id="name" name="name" value="<?=htmlspecialchars($_POST['name']??'')?>" placeholder="e.g. Acme Traffic" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Tenant Subdomain Slug *</label>
                        <input type="text" id="slug" name="slug" value="<?=htmlspecialchars($_POST['slug']??'')?>" placeholder="e.g. acme" required>
                    </div>

                    <div class="form-group">
                        <label for="company_name">Legal Company Name</label>
                        <input type="text" id="company_name" name="company_name" value="<?=htmlspecialchars($_POST['company_name']??'')?>" placeholder="e.g. Acme Media Group Inc.">
                    </div>

                    <div class="form-group">
                        <label for="domain">Resolved Domain Address *</label>
                        <input type="text" id="domain" name="domain" value="<?=htmlspecialchars($_POST['domain']??'')?>" placeholder="e.g. acme.localhost" required>
                    </div>

                    <div class="form-section-title">Initial Owner Admin Account</div>

                    <div class="form-group">
                        <label for="owner_name">Owner Name *</label>
                        <input type="text" id="owner_name" name="owner_name" value="<?=htmlspecialchars($_POST['owner_name']??'')?>" placeholder="e.g. John Doe" required>
                    </div>

                    <div class="form-group">
                        <label for="owner_email">Owner Email *</label>
                        <input type="email" id="owner_email" name="owner_email" value="<?=htmlspecialchars($_POST['owner_email']??'')?>" placeholder="e.g. admin@acme.com" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="owner_password">Secure Password *</label>
                        <input type="password" id="owner_password" name="owner_password" placeholder="Enter secure password" required>
                    </div>

                    <div class="form-section-title">Plan Limits & Defaults</div>

                    <div class="form-group">
                        <label for="plan_name">Subscription Tier</label>
                        <select id="plan_name" name="plan_name">
                            <option value="Starter" <?=($_POST['plan_name']??'')==='Starter'?'selected':''?>>Starter (100 Offers)</option>
                            <option value="Professional" <?=($_POST['plan_name']??'')==='Professional'?'selected':''?>>Professional (500 Offers)</option>
                            <option value="Enterprise" <?=($_POST['plan_name']??'')==='Enterprise'?'selected':''?>>Enterprise (Unlimited)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="max_offers">Max Offers Limit</label>
                        <input type="number" id="max_offers" name="max_offers" value="<?=htmlspecialchars($_POST['max_offers']??'100')?>" required>
                    </div>

                    <div class="form-group">
                        <label for="max_affiliates">Max Publishers Limit</label>
                        <input type="number" id="max_affiliates" name="max_affiliates" value="<?=htmlspecialchars($_POST['max_affiliates']??'100')?>" required>
                    </div>

                    <div class="form-group">
                        <label for="max_advertisers">Max Advertisers Limit</label>
                        <input type="number" id="max_advertisers" name="max_advertisers" value="<?=htmlspecialchars($_POST['max_advertisers']??'20')?>" required>
                    </div>

                    <div class="form-group">
                        <label for="currency">Currency Code</label>
                        <input type="text" id="currency" name="currency" value="<?=htmlspecialchars($_POST['currency']??'USD')?>" required>
                    </div>

                    <div class="form-group">
                        <label for="timezone">Default Timezone</label>
                        <input type="text" id="timezone" name="timezone" value="<?=htmlspecialchars($_POST['timezone']??'UTC')?>" required>
                    </div>

                    <button type="submit" class="btn-submit">Provision Tenant Network</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
