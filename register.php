<?php
define('APP_INIT', true);

require_once __DIR__ . '/app/config/database.php';

// AJAX Subdomain Check
if (isset($_GET['action']) && $_GET['action'] === 'check_subdomain') {
    header('Content-Type: application/json');
    $slug = trim($_GET['slug'] ?? '');
    $slug = strtolower($slug);
    
    if ($slug === '') {
        echo json_encode(['available' => false, 'error' => 'Subdomain cannot be empty']);
        exit;
    }
    if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
        echo json_encode(['available' => false, 'error' => 'Subdomain must contain only lowercase letters, numbers, and hyphens']);
        exit;
    }
    
    $reserved = ['www', 'mail', 'api', 'admin', 'superadmin', 'default', 'taskbazi', 'localhost'];
    if (in_array($slug, $reserved, true)) {
        echo json_encode(['available' => false, 'error' => 'This subdomain is reserved']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE slug = :slug LIMIT 1");
    $stmt->execute(['slug' => $slug]);
    if ($stmt->fetch()) {
        echo json_encode(['available' => false, 'error' => 'Subdomain is already taken']);
    } else {
        echo json_encode(['available' => true]);
    }
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_root_domain()) {
        $companyName = trim($_POST['company_name'] ?? '');
        $subdomain   = trim($_POST['subdomain'] ?? '');
        $planName    = trim($_POST['plan_name'] ?? 'Starter');
        $name        = trim($_POST['name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $password    = $_POST['password'] ?? '';
        $mobile      = trim($_POST['mobile'] ?? '');

        // Basic validation
        if ($companyName === '' || $subdomain === '' || $name === '' || $email === '' || $password === '' || $mobile === '') {
            $error = 'All fields are required';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $subdomain)) {
            $error = 'Subdomain must contain only lowercase letters, numbers, and hyphens';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            // Check if subdomain is reserved
            $reserved = ['www', 'mail', 'api', 'admin', 'superadmin', 'default', 'taskbazi', 'localhost'];
            if (in_array($subdomain, $reserved, true)) {
                $error = 'This subdomain is reserved. Please choose another one.';
            } else {
                // Check if subdomain is already taken
                $checkSlug = $pdo->prepare("SELECT id FROM tenants WHERE slug = :slug LIMIT 1");
                $checkSlug->execute(['slug' => $subdomain]);
                if ($checkSlug->fetch()) {
                    $error = 'This subdomain is already in use by another agency';
                } else {
                    $maxOffers = 100;
                    $maxPublishers = 100;
                    $maxAdvertisers = 20;

                    try {
                        $planStmt = $pdo->prepare("SELECT * FROM saas_plans WHERE name = :name LIMIT 1");
                        $planStmt->execute(['name' => $planName]);
                        $planData = $planStmt->fetch();
                        
                        if ($planData) {
                            $maxOffers = $planData['offers_limit'] === 'Unlimited' ? 999999 : (int)$planData['offers_limit'];
                            $maxPublishers = $planData['publishers_limit'] === 'Unlimited' ? 999999 : (int)$planData['publishers_limit'];
                            $maxAdvertisers = $planData['advertisers_limit'] === 'Unlimited' ? 999999 : (int)$planData['advertisers_limit'];
                        }
                    } catch (Exception $e) {
                        // Fallback limits if table is missing
                        if ($planName === 'Professional') {
                            $maxOffers = 500;
                            $maxPublishers = 500;
                            $maxAdvertisers = 100;
                        } elseif ($planName === 'Enterprise') {
                            $maxOffers = 999999;
                            $maxPublishers = 999999;
                            $maxAdvertisers = 999999;
                        }
                    }

                    // Database Transaction to set up new workspace
                    $pdo->beginTransaction();
                    try {
                        // 1. Insert Tenant
                        $insTenant = $pdo->prepare("
                            INSERT INTO tenants (
                                name, slug, company_name, owner_name, owner_email, status, plan_name,
                                max_affiliates, max_advertisers, max_offers, created_at, updated_at
                            ) VALUES (
                                :name, :slug, :company_name, :owner_name, :owner_email, 'active', :plan_name,
                                :max_aff, :max_adv, :max_off, NOW(), NOW()
                            )
                        ");
                        $insTenant->execute([
                            'name'         => $companyName,
                            'slug'         => $subdomain,
                            'company_name' => $companyName,
                            'owner_name'   => $name,
                            'owner_email'  => $email,
                            'plan_name'    => $planName,
                            'max_aff'      => $maxPublishers,
                            'max_adv'      => $maxAdvertisers,
                            'max_off'      => $maxOffers
                        ]);
                        $tenantId = $pdo->lastInsertId();

                        // 2. Insert Domains
                        $current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $base_domain = explode(':', $current_host)[0];
                        
                        // Main domain (production)
                        $insDomainProd = $pdo->prepare("
                            INSERT INTO tenant_domains (tenant_id, domain, type, is_primary, verification_status, ssl_status)
                            VALUES (:tenant_id, :domain, 'subdomain', 1, 'verified', 'none')
                        ");
                        $insDomainProd->execute([
                            'tenant_id' => $tenantId,
                            'domain'    => $subdomain . '.taskbazi.xyz'
                        ]);

                        // Local subdomain for localhost testing
                        $insDomainLocal = $pdo->prepare("
                            INSERT INTO tenant_domains (tenant_id, domain, type, is_primary, verification_status, ssl_status)
                            VALUES (:tenant_id, :domain, 'subdomain', 0, 'verified', 'none')
                        ");
                        $insDomainLocal->execute([
                            'tenant_id' => $tenantId,
                            'domain'    => $subdomain . '.localhost'
                        ]);

                        // 3. Insert Settings
                        $insSettings = $pdo->prepare("
                            INSERT INTO tenant_settings (tenant_id, site_name, logo_path, favicon_path, primary_color, support_email)
                            VALUES (:tenant_id, :site_name, '/favicon.png', '/favicon.png', '#2563eb', :support_email)
                        ");
                        $insSettings->execute([
                            'tenant_id'     => $tenantId,
                            'site_name'     => $companyName,
                            'support_email' => $email
                        ]);

                        // 4. Insert Admin User
                        $roleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'admin' LIMIT 1");
                        $roleStmt->execute();
                        $roleId = $roleStmt->fetchColumn() ?: 1;

                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $insUser = $pdo->prepare("
                            INSERT INTO users (
                                tenant_id, role_id, name, email, password_hash, mobile, status, created_at
                            ) VALUES (
                                :tenant_id, :role_id, :name, :email, :password_hash, :mobile, 'active', NOW()
                            )
                        ");
                        $insUser->execute([
                            'tenant_id'     => $tenantId,
                            'role_id'       => $roleId,
                            'name'          => $name,
                            'email'         => $email,
                            'password_hash' => $passwordHash,
                            'mobile'        => $mobile
                        ]);

                        $pdo->commit();

                        // Redirect to the custom workspace login screen
                        $host_parts = explode(':', $current_host);
                        $port_str = isset($host_parts[1]) ? ':' . $host_parts[1] : '';
                        if ($base_domain === 'localhost' || $base_domain === '127.0.0.1') {
                            $redirect_url = 'http://' . $subdomain . '.localhost' . $port_str . '/admin/login.php?registered=1';
                        } else {
                            $redirect_url = 'http://' . $subdomain . '.taskbazi.xyz' . $port_str . '/admin/login.php?registered=1';
                        }
                        header('Location: ' . $redirect_url);
                        exit;

                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = 'Error during setup: ' . $e->getMessage();
                    }
                }
            }
        }
    } else {
        $role       = $_POST['role'] ?? '';
        $name       = trim($_POST['name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';
        $mobile     = trim($_POST['mobile'] ?? '');
        $telegramId = trim($_POST['telegram_id'] ?? '');
        $teamsId    = trim($_POST['teams_id'] ?? '');

        // Basic validation
        if (!in_array($role, ['affiliate', 'advertiser'], true)) {
            $error = 'Please select your account type';
        } elseif ($name === '' || $email === '' || $password === '' || $mobile === '') {
            $error = 'Name, email, password and mobile are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            // Check duplicate email
            $check = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
            $check->execute(['email' => $email]);

            if ($check->fetch()) {
                $error = 'This email is already registered';
            } else {
                // Get role_id
                $roleStmt = $pdo->prepare("
                    SELECT role_id FROM roles WHERE role_name = :role LIMIT 1
                ");
                $roleStmt->execute(['role' => $role]);
                $roleRow = $roleStmt->fetch();

                if (!$roleRow) {
                    $error = 'Invalid role selected';
                } else {
                    // Hash password
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    // Insert user
                    $stmt = $pdo->prepare("
                        INSERT INTO users (
                            name,
                            email,
                            password_hash,
                            mobile,
                            telegram_id,
                            teams_id,
                            role_id,
                            status,
                            created_at
                        ) VALUES (
                            :name,
                            :email,
                            :password,
                            :mobile,
                            :telegram,
                            :teams,
                            :role_id,
                            'pending',
                            NOW()
                        )
                    ");

                    $stmt->execute([
                        'name'     => $name,
                        'email'    => $email,
                        'password' => $passwordHash,
                        'mobile'   => $mobile,
                        'telegram' => $telegramId !== '' ? $telegramId : null,
                        'teams'    => $teamsId !== '' ? $teamsId : null,
                        'role_id'  => $roleRow['role_id']
                    ]);

                    $success = 'Registration successful! Your account is pending approval.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5">
    <title>Taskbazi · Create Account</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    
    <!-- Google Fonts: Inter & Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ===== RESET & GLOBAL ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #ffffff;
            color: #334155;
            line-height: 1.5;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: #2563eb;
            font-weight: 600;
            transition: color 0.2s;
        }

        a:hover {
            color: #1d4ed8;
        }

        /* ===== MAIN LAYOUT ===== */
        .register-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* ===== LEFT SIDE - SHOWCASE ===== */
        .brand-panel {
            flex: 1.1;
            background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
            border-right: 1px solid rgba(226, 232, 240, 0.8);
            display: none;
            position: relative;
            overflow: hidden;
            padding: 60px 48px;
            flex-direction: column;
            justify-content: space-between;
        }

        @media (min-width: 1024px) {
            .brand-panel {
                display: flex;
            }
        }

        .brand-content {
            position: relative;
            z-index: 10;
            color: #0f172a;
            max-width: 560px;
            margin: auto;
            width: 100%;
        }

        .brand-logo-top {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
        }

        .brand-logo-top img {
            height: 36px;
            width: auto;
            object-fit: contain;
        }

        .brand-logo-top span {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
        }

        .brand-tagline {
            font-family: 'Outfit', sans-serif;
            font-size: 38px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.02em;
            margin-bottom: 16px;
            color: #0f172a;
        }

        .brand-description {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        /* Mockup Card */
        .mock-dashboard-card {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.05);
            margin-bottom: 40px;
            position: relative;
        }

        .mock-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .mock-title {
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mock-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 8px #10b981;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.9); opacity: 0.8; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.8; }
        }

        .mock-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }

        .mock-stat {
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 12px;
        }

        .mock-stat-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .mock-stat-value {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }

        .mock-stat-trend {
            font-size: 10px;
            font-weight: 700;
            color: #10b981;
            margin-top: 2px;
        }

        .mock-logs-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .mock-log-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .mock-log-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #334155;
        }

        .mock-log-badge {
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 800;
        }

        .mock-log-latency {
            font-family: monospace;
            color: #64748b;
        }

        .floating-stats {
            display: flex;
            gap: 32px;
        }

        .stat {
            display: flex;
            flex-direction: column;
        }

        .stat-number {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #2563eb;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Sphere overlay */
        .gradient-sphere {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle at 30% 30%, rgba(37,99,235,0.05) 0%, rgba(124,58,237,0.02) 70%);
            border-radius: 50%;
            top: -200px;
            right: -100px;
            filter: blur(60px);
            z-index: 1;
        }

        /* ===== RIGHT SIDE - REGISTRATION FORM ===== */
        .form-panel {
            flex: 0.9;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 40px 24px;
            background-color: #ffffff;
            overflow-y: auto;
            min-height: 100vh;
        }

        .form-container {
            width: 100%;
            max-width: 520px;
            margin: auto;
        }

        .form-header-logo {
            margin-bottom: 24px;
            text-align: left;
        }

        .form-header-logo img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .form-header {
            margin-bottom: 28px;
        }

        .form-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .form-header p {
            color: #64748b;
            font-size: 14px;
        }

        /* Messages */
        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }

        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #166534;
        }

        /* Grid inputs */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .form-group {
            margin-bottom: 4px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: 0.2px;
        }

        .required::after {
            content: " *";
            color: #ef4444;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 15px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            color: #0f172a;
            transition: all 0.25s;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            background: white;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        /* Role cards */
        .role-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .role-card {
            position: relative;
        }

        .role-card input[type="radio"] {
            display: none;
        }

        .role-card label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 16px 10px;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .role-card:hover label {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .role-card input[type="radio"]:checked + label {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.04);
        }

        .role-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 16px;
            transition: all 0.2s;
        }

        .role-card input[type="radio"]:checked + label .role-icon {
            background: #2563eb;
            color: white;
        }

        .role-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }

        .role-desc {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
            text-align: center;
        }

        /* Password strength meter */
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: width 0.3s, background 0.3s;
        }

        .strength-weak { background: #ef4444; }
        .strength-fair { background: #f59e0b; }
        .strength-good { background: #10b981; }
        .strength-strong { background: #059669; }

        .field-hint {
            font-size: 11px;
            color: #64748b;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-register {
            width: 100%;
            padding: 15px 20px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.25s;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.2);
            margin-top: 10px;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .register-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #f1f5f9;
            color: #64748b;
            font-size: 14px;
        }

        .register-footer a {
            color: #2563eb;
            font-weight: 700;
        }

        .terms-links {
            margin-top: 12px;
            font-size: 12px;
        }

        .trust-badges {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-top: 24px;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 500;
        }

        .trust-badges i {
            margin-right: 4px;
            color: #2563eb;
        }

        /* Mobile validations */
        @media (max-width: 1023px) {
            .form-panel {
                background-color: #f8fafc;
            }
            .form-container {
                background: #ffffff;
                border: 1px solid rgba(226, 232, 240, 0.8);
                border-radius: 20px;
                padding: 36px 28px;
                box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.03);
            }
            .form-header-logo {
                text-align: center;
            }
            .form-header {
                text-align: center;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading & validation errors */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.9;
        }

        .btn-loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .form-control.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .error-text {
            color: #ef4444;
            font-size: 12px;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <!-- LEFT PANEL - Showcase -->
        <div class="brand-panel">
            <div class="gradient-sphere"></div>
            
            <div class="brand-content">
                <div class="brand-logo-top">
                    <img src="/logo.png" alt="Taskbazi Logo">
                    <span>Taskbazi</span>
                </div>

                <h1 class="brand-tagline">
                    Performance marketing tracking, simplified.
                </h1>
                
                <p class="brand-description">
                    Join the leading performance network. Experience high-tech click routing, live API callbacks, and comprehensive SaaS reports.
                </p>

                <!-- Mockup Widget -->
                <div class="mock-dashboard-card">
                    <div class="mock-header">
                        <span class="mock-title">
                            <span class="mock-status-dot"></span>
                            Live Traffic Node Router
                        </span>
                        <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Zone: Global</span>
                    </div>

                    <div class="mock-stats-grid">
                        <div class="mock-stat">
                            <div class="mock-stat-label">Clicks</div>
                            <div class="mock-stat-value">324,180</div>
                            <div class="mock-stat-trend"><i class="fas fa-caret-up"></i> +12.4%</div>
                        </div>
                        <div class="mock-stat">
                            <div class="mock-stat-label">Conversions</div>
                            <div class="mock-stat-value">12,854</div>
                            <div class="mock-stat-trend"><i class="fas fa-caret-up"></i> +8.1%</div>
                        </div>
                        <div class="mock-stat">
                            <div class="mock-stat-label">Active Offers</div>
                            <div class="mock-stat-value">458</div>
                            <div class="mock-stat-trend" style="color:#64748b;">Stable</div>
                        </div>
                    </div>

                    <div class="mock-logs-list">
                        <div class="mock-log-item">
                            <div class="mock-log-info">
                                <span class="mock-log-badge">Click</span>
                                <span>172.56.21.9 &rarr; Offer #42</span>
                            </div>
                            <span class="mock-log-latency">0.12s</span>
                        </div>
                        <div class="mock-log-item">
                            <div class="mock-log-info">
                                <span class="mock-log-badge" style="background:rgba(16,185,129,0.1); color:#10b981;">Conv</span>
                                <span>104.28.18.2 &rarr; Goal Lead</span>
                            </div>
                            <span class="mock-log-latency">0.08s</span>
                        </div>
                    </div>
                </div>

                <div class="floating-stats">
                    <div class="stat">
                        <span class="stat-number">100K+</span>
                        <span class="stat-label">Active Partners</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">$450M+</span>
                        <span class="stat-label">Annual Payouts</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">40+</span>
                        <span class="stat-label">Countries</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL - Registration Form -->
        <div class="form-panel">
            <div class="form-container">
                <div class="form-header-logo">
                    <img src="/logo.png" alt="Taskbazi Logo">
                </div>

                <?php if (is_root_domain()): 
                    try {
                        $plans = $pdo->query("SELECT * FROM saas_plans ORDER BY id ASC")->fetchAll();
                    } catch (Exception $e) {
                        // Safe fallback if table doesn't exist on server yet
                        $plans = [
                            [
                                'name' => 'Starter',
                                'price' => '$99/mo',
                                'color' => '#60a5fa',
                                'offers_limit' => '100',
                                'publishers_limit' => '100',
                                'advertisers_limit' => '20'
                            ],
                            [
                                'name' => 'Professional',
                                'price' => '$299/mo',
                                'color' => '#c084fc',
                                'offers_limit' => '500',
                                'publishers_limit' => '500',
                                'advertisers_limit' => '100'
                            ],
                            [
                                'name' => 'Enterprise',
                                'price' => '$999/mo',
                                'color' => '#34d399',
                                'offers_limit' => 'Unlimited',
                                'publishers_limit' => 'Unlimited',
                                'advertisers_limit' => 'Unlimited'
                            ]
                        ];
                    }
                ?>
                    <div class="form-header">
                        <h2>Create your agency account</h2>
                        <p>Establish your custom tracking network in minutes</p>
                    </div>

                    <!-- Alert Messages -->
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="registerAgencyForm" novalidate>
                        <div class="form-grid">
                            <!-- Company Name -->
                            <div class="form-group full-width">
                                <label class="form-label required" for="company_name">Company name</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-briefcase input-icon"></i>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="company_name" 
                                        name="company_name" 
                                        placeholder="e.g. Acme Marketing Inc"
                                        value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Workspace Subdomain -->
                            <div class="form-group full-width">
                                <label class="form-label required" for="subdomain">Workspace subdomain</label>
                                <div class="input-wrapper" style="display: flex; align-items: center; position: relative;">
                                    <i class="fas fa-globe input-icon" style="top: 50%; transform: translateY(-50%);"></i>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="subdomain" 
                                        name="subdomain" 
                                        placeholder="acme"
                                        value="<?= htmlspecialchars($_POST['subdomain'] ?? '') ?>"
                                        style="padding-right: 140px; font-weight: 600;"
                                        autocomplete="off"
                                        required
                                    >
                                    <span class="subdomain-suffix" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-weight: 700; font-size: 14px; pointer-events: none;">
                                        <?= ($_SERVER['HTTP_HOST'] === 'localhost' || explode(':', $_SERVER['HTTP_HOST'])[0] === 'localhost') ? '.localhost' : '.taskbazi.xyz' ?>
                                    </span>
                                </div>
                                <div id="subdomainCheckResult" class="field-hint" style="margin-top: 6px;">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Only lowercase letters, numbers, and hyphens</span>
                                </div>
                            </div>

                            <!-- Plan Selection Grid -->
                            <div class="form-group full-width">
                                <label class="form-label required">Select Agency Plan</label>
                                <div class="role-container" style="grid-template-columns: repeat(3, 1fr); gap: 10px;">
                                    <?php foreach ($plans as $plan): ?>
                                        <div class="role-card">
                                            <input 
                                                type="radio" 
                                                id="plan_<?= htmlspecialchars($plan['name']) ?>" 
                                                name="plan_name" 
                                                value="<?= htmlspecialchars($plan['name']) ?>" 
                                                <?= (isset($_POST['plan_name']) && $_POST['plan_name'] === $plan['name']) || (!isset($_POST['plan_name']) && $plan['name'] === 'Starter') ? 'checked' : '' ?>
                                            >
                                            <label for="plan_<?= htmlspecialchars($plan['name']) ?>" style="padding: 14px 6px; border-radius: 12px; height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                                                <div style="display: flex; flex-direction: column; align-items: center;">
                                                    <div class="role-icon" style="background: <?= htmlspecialchars($plan['color'] ?? '#2563eb') ?>; color: white; width: 34px; height: 34px; border-radius: 8px; font-size: 14px;">
                                                        <i class="fas fa-cubes"></i>
                                                    </div>
                                                    <span class="role-title" style="font-size: 13px; margin-top: 4px;"><?= htmlspecialchars($plan['name']) ?></span>
                                                </div>
                                                <span class="role-desc" style="font-size: 12px; font-weight: 800; color: #2563eb; margin-top: 4px;"><?= htmlspecialchars($plan['price']) ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Owner Name -->
                            <div class="form-group">
                                <label class="form-label required" for="name">Your name</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user input-icon"></i>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="name" 
                                        name="name" 
                                        placeholder="John Smith"
                                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label class="form-label required" for="email">Business email</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        id="email" 
                                        name="email" 
                                        placeholder="owner@agency.com"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="form-group">
                                <label class="form-label required" for="password">Password</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="password" 
                                        name="password" 
                                        placeholder="Create a password"
                                        required
                                    >
                                </div>
                                <div class="password-strength">
                                    <div class="strength-bar" id="strengthBar"></div>
                                </div>
                            </div>

                            <!-- Mobile -->
                            <div class="form-group">
                                <label class="form-label required" for="mobile">Mobile number</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input 
                                        type="tel" 
                                        class="form-control" 
                                        id="mobile" 
                                        name="mobile" 
                                        placeholder="+1 (234) 567-8900"
                                        value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="form-group full-width">
                                <button type="submit" class="btn-register" id="submitBtn">
                                    <span>Create workspace &rarr;</span>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Footer Links -->
                        <div class="register-footer">
                            <p>Already have an account? <a href="login.php">Sign in &rarr;</a></p>
                            <div class="terms-links">
                                By creating an account, you agree to our 
                                <a href="#">Terms</a> and <a href="#">Privacy</a>
                            </div>
                        </div>

                        <!-- Trust Badges -->
                        <div class="trust-badges">
                            <span><i class="fas fa-shield-alt"></i> SSL Encrypted</span>
                            <span><i class="fas fa-lock"></i> SOC2 Protected</span>
                            <span><i class="fas fa-check-circle"></i> GDPR Compliant</span>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="form-header">
                        <h2>Create your account</h2>
                        <p>Join the network and start earning today</p>
                    </div>

                    <!-- Alert Messages -->
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span><?= htmlspecialchars($success) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Registration Form -->
                    <form method="post" id="registerForm" novalidate>
                        <div class="form-grid">
                            <!-- Role Selection -->
                            <div class="form-group full-width">
                                <label class="form-label required">I am registering as</label>
                                <div class="role-container">
                                    <div class="role-card">
                                        <input type="radio" id="role_affiliate" name="role" value="affiliate" <?= (isset($_POST['role']) && $_POST['role'] === 'affiliate') ? 'checked' : '' ?>>
                                        <label for="role_affiliate">
                                            <div class="role-icon">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <span class="role-title">Affiliate</span>
                                            <span class="role-desc">Publisher Portal</span>
                                        </label>
                                    </div>
                                    <div class="role-card">
                                        <input type="radio" id="role_advertiser" name="role" value="advertiser" <?= (isset($_POST['role']) && $_POST['role'] === 'advertiser') ? 'checked' : '' ?>>
                                        <label for="role_advertiser">
                                            <div class="role-icon">
                                                <i class="fas fa-bullhorn"></i>
                                            </div>
                                            <span class="role-title">Advertiser</span>
                                            <span class="role-desc">Brand Merchant</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Full Name -->
                            <div class="form-group">
                                <label class="form-label required" for="name">Full name</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user input-icon"></i>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="name" 
                                        name="name" 
                                        placeholder="John Smith"
                                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                    >
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label class="form-label required" for="email">Email address</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        id="email" 
                                        name="email" 
                                        placeholder="partner@company.com"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    >
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="form-group">
                                <label class="form-label required" for="password">Password</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="password" 
                                        name="password" 
                                        placeholder="Create a password"
                                    >
                                </div>
                                <div class="password-strength">
                                    <div class="strength-bar" id="strengthBar"></div>
                                </div>
                                <div class="field-hint">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Min 6 chars with upper & numbers</span>
                                </div>
                            </div>

                            <!-- Mobile -->
                            <div class="form-group">
                                <label class="form-label required" for="mobile">Mobile number</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input 
                                        type="tel" 
                                        class="form-control" 
                                        id="mobile" 
                                        name="mobile" 
                                        placeholder="+1 (234) 567-8900"
                                        value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>"
                                    >
                                </div>
                            </div>

                            <!-- Telegram ID -->
                            <div class="form-group">
                                <label class="form-label" for="telegram_id">Telegram ID</label>
                                <div class="input-wrapper">
                                    <i class="fab fa-telegram input-icon"></i>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="telegram_id" 
                                        name="telegram_id" 
                                        placeholder="@username"
                                        value="<?= htmlspecialchars($_POST['telegram_id'] ?? '') ?>"
                                    >
                                </div>
                            </div>

                            <!-- Teams ID -->
                            <div class="form-group">
                                <label class="form-label" for="teams_id">Teams ID</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-video input-icon"></i>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="teams_id" 
                                        name="teams_id" 
                                        placeholder="username@domain.com"
                                        value="<?= htmlspecialchars($_POST['teams_id'] ?? '') ?>"
                                    >
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="form-group full-width">
                                <button type="submit" class="btn-register" id="submitBtn">
                                    <span>Create account</span>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Footer Links -->
                        <div class="register-footer">
                            <p>Already have an account? <a href="login.php">Sign in &rarr;</a></p>
                            <div class="terms-links">
                                By creating an account, you agree to our 
                                <a href="#">Terms</a> and <a href="#">Privacy</a>
                            </div>
                        </div>

                        <!-- Trust Badges -->
                        <div class="trust-badges">
                            <span><i class="fas fa-shield-alt"></i> SSL Encrypted</span>
                            <span><i class="fas fa-lock"></i> SOC2 Protected</span>
                            <span><i class="fas fa-check-circle"></i> GDPR Compliant</span>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        (function() {
            'use strict';

            const isRoot = <?= is_root_domain() ? 'true' : 'false' ?>;

            // Password strength indicator helper
            const passwordInput = document.getElementById('password');
            const strengthBar = document.getElementById('strengthBar');

            if (passwordInput && strengthBar) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    
                    if (password.length >= 6) strength += 20;
                    if (password.length >= 8) strength += 10;
                    if (/[A-Z]/.test(password)) strength += 25;
                    if (/[0-9]/.test(password)) strength += 25;
                    if (/[^A-Za-z0-9]/.test(password)) strength += 20;
                    
                    strength = Math.min(strength, 100);
                    strengthBar.style.width = `${strength}%`;
                    strengthBar.className = 'strength-bar';
                    
                    if (strength < 30) {
                        strengthBar.classList.add('strength-weak');
                    } else if (strength < 50) {
                        strengthBar.classList.add('strength-fair');
                    } else if (strength < 75) {
                        strengthBar.classList.add('strength-good');
                    } else {
                        strengthBar.classList.add('strength-strong');
                    }
                });
            }

            function markError(input, message) {
                input.classList.add('error');
                const existingError = input.parentElement.nextElementSibling;
                if (existingError && existingError.classList.contains('error-text')) {
                    existingError.remove();
                }
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-text';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                input.parentElement.parentElement.appendChild(errorDiv);
                
                input.addEventListener('input', function() {
                    this.classList.remove('error');
                    const error = this.parentElement.nextElementSibling;
                    if (error && error.classList.contains('error-text')) {
                        error.remove();
                    }
                }, { once: true });
            }

            function clearError(input) {
                input.classList.remove('error');
                const existingError = input.parentElement.nextElementSibling;
                if (existingError && existingError.classList.contains('error-text')) {
                    existingError.remove();
                }
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (isRoot) {
                const form = document.getElementById('registerAgencyForm');
                const submitBtn = document.getElementById('submitBtn');
                const companyNameInput = document.getElementById('company_name');
                const subdomainInput = document.getElementById('subdomain');
                const nameInput = document.getElementById('name');
                const emailInput = document.getElementById('email');
                const mobileInput = document.getElementById('mobile');
                const subdomainResult = document.getElementById('subdomainCheckResult');

                // Real-time subdomain availability check with debouncing
                let debounceTimeout = null;
                subdomainInput.addEventListener('input', function() {
                    const slug = this.value.trim().toLowerCase();
                    clearError(this);
                    
                    // Filter invalid characters immediately in input value
                    this.value = slug.replace(/[^a-z0-9\-]/g, '');

                    subdomainResult.innerHTML = '<i class="fas fa-info-circle"></i> Checking availability...';
                    subdomainResult.style.color = '#64748b';

                    clearTimeout(debounceTimeout);
                    if (slug.length < 3) {
                        subdomainResult.innerHTML = '<i class="fas fa-info-circle"></i> Min 3 characters';
                        return;
                    }

                    debounceTimeout = setTimeout(() => {
                        fetch(`register.php?action=check_subdomain&slug=${slug}`)
                            .then(res => res.json())
                            .then(data => {
                                if (data.available) {
                                    subdomainResult.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Subdomain is available';
                                    subdomainResult.style.color = '#10b981';
                                } else {
                                    subdomainResult.innerHTML = `<i class="fas fa-exclamation-circle" style="color: #ef4444;"></i> ${data.error}`;
                                    subdomainResult.style.color = '#ef4444';
                                }
                            })
                            .catch(err => {
                                subdomainResult.innerHTML = '<i class="fas fa-info-circle"></i> Done';
                            });
                    }, 500);
                });

                if (form) {
                    form.addEventListener('submit', function(e) {
                        let isValid = true;

                        if (!companyNameInput.value.trim()) {
                            markError(companyNameInput, 'Company name is required');
                            isValid = false;
                        }

                        const slug = subdomainInput.value.trim();
                        if (!slug) {
                            markError(subdomainInput, 'Subdomain is required');
                            isValid = false;
                        } else if (!/^[a-z0-9\-]+$/.test(slug)) {
                            markError(subdomainInput, 'Only lowercase letters, numbers, and hyphens allowed');
                            isValid = false;
                        }

                        if (!nameInput.value.trim()) {
                            markError(nameInput, 'Your name is required');
                            isValid = false;
                        }

                        if (!emailInput.value.trim()) {
                            markError(emailInput, 'Business email is required');
                            isValid = false;
                        } else if (!emailRegex.test(emailInput.value)) {
                            markError(emailInput, 'Please enter a valid email address');
                            isValid = false;
                        }

                        if (!passwordInput.value) {
                            markError(passwordInput, 'Password is required');
                            isValid = false;
                        } else if (passwordInput.value.length < 6) {
                            markError(passwordInput, 'Password must be at least 6 characters');
                            isValid = false;
                        }

                        if (!mobileInput.value.trim()) {
                            markError(mobileInput, 'Mobile number is required');
                            isValid = false;
                        }

                        if (!isValid) {
                            e.preventDefault();
                            return false;
                        }

                        submitBtn.classList.add('btn-loading');
                        submitBtn.innerHTML = `
                            <span>Setting up your network...</span>
                            <i class="fas fa-spinner"></i>
                        `;
                    });
                }
            } else {
                const form = document.getElementById('registerForm');
                const submitBtn = document.getElementById('submitBtn');
                const nameInput = document.getElementById('name');
                const emailInput = document.getElementById('email');
                const mobileInput = document.getElementById('mobile');

                if (form) {
                    form.addEventListener('submit', function(e) {
                        let isValid = true;
                        
                        const roleSelected = document.querySelector('input[name="role"]:checked');
                        if (!roleSelected) {
                            e.preventDefault();
                            isValid = false;
                            const roleContainer = document.querySelector('.role-container');
                            roleContainer.style.animation = 'shake 0.5s ease';
                            
                            let roleError = document.querySelector('.role-error');
                            if (!roleError) {
                                roleError = document.createElement('div');
                                roleError.className = 'error-text role-error';
                                roleError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please select your account type';
                                roleContainer.parentElement.appendChild(roleError);
                            }
                            
                            setTimeout(() => { roleContainer.style.animation = ''; }, 500);
                        }

                        if (!nameInput.value.trim()) {
                            markError(nameInput, 'Full name is required');
                            isValid = false;
                        }

                        if (!emailInput.value.trim()) {
                            markError(emailInput, 'Email address is required');
                            isValid = false;
                        } else if (!emailRegex.test(emailInput.value)) {
                            markError(emailInput, 'Please enter a valid email address');
                            isValid = false;
                        }

                        if (!passwordInput.value) {
                            markError(passwordInput, 'Password is required');
                            isValid = false;
                        } else if (passwordInput.value.length < 6) {
                            markError(passwordInput, 'Password must be at least 6 characters');
                            isValid = false;
                        }

                        if (!mobileInput.value.trim()) {
                            markError(mobileInput, 'Mobile number is required');
                            isValid = false;
                        }

                        if (!isValid) {
                            e.preventDefault();
                            return false;
                        }

                        submitBtn.classList.add('btn-loading');
                        submitBtn.innerHTML = `
                            <span>Creating account...</span>
                            <i class="fas fa-spinner"></i>
                        `;
                    });
                }

                document.querySelectorAll('input[name="role"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        document.querySelector('.role-error')?.remove();
                    });
                });
            }

            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });

            const style = document.createElement('style');
            style.textContent = `
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-5px); }
                    75% { transform: translateX(5px); }
                }
            `;
            document.head.appendChild(style);
        })();
    </script>
</body>
</html>