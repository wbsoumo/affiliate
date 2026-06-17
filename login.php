<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('APP_INIT', true);

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/auth.php';

$error = null;
$success = null;

// Check for session timeout or logout message
if (isset($_GET['logout'])) {
    $success = 'You have been successfully logged out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_root_domain()) {
        $subdomain = trim($_POST['subdomain'] ?? '');
        $subdomain = strtolower($subdomain);
        if ($subdomain === '') {
            $error = 'Please enter your workspace domain';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $subdomain)) {
            $error = 'Invalid workspace domain format';
        } else {
            // Check if workspace exists
            $stmt = $pdo->prepare("SELECT slug FROM tenants WHERE slug = :slug LIMIT 1");
            $stmt->execute(['slug' => $subdomain]);
            $exists = $stmt->fetch();
            if ($exists) {
                $current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $host_parts = explode(':', $current_host);
                $port_str = isset($host_parts[1]) ? ':' . $host_parts[1] : '';
                $base_domain = $host_parts[0];
                
                if ($base_domain === 'localhost' || $base_domain === '127.0.0.1') {
                    $redirect_url = 'http://' . $subdomain . '.localhost' . $port_str . '/login.php';
                } else {
                    $redirect_url = 'http://' . $subdomain . '.' . $base_domain . $port_str . '/login.php';
                }
                header('Location: ' . $redirect_url);
                exit;
            } else {
                $error = 'Workspace domain does not exist';
            }
        }
    } else {
        $role   = $_POST['role'] ?? '';
        $email  = trim($_POST['email'] ?? '');
        $pass   = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (!in_array($role, ['affiliate', 'advertiser'], true)) {
            $error = 'Please select your account type';
        } elseif ($email === '' || $pass === '') {
            $error = 'Email and password are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            $result = auth_login($email, $pass, $remember);
            
            if (!$result['success']) {
                $error = $result['error'] ?? 'Invalid credentials. Please try again.';
            } else {
                // Role-based redirect with secure session
                session_regenerate_id(true);
                if ($result['role'] === 'affiliate') {
                    header('Location: /affiliate/dashboard.php');
                } else {
                    header('Location: /advertiser/dashboard.php');
                }
                exit;
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
    <title>Taskbazi · Partner Login</title>
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
            display: flex;
            flex-direction: column;
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
        .login-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* ===== LEFT SIDE - TRACKDESK SHOWCASE ===== */
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

        /* CSS Mockup Dashboard Card (Trackdesk Style) */
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

        /* Animated background elements */
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

        /* ===== RIGHT SIDE - LOGIN FORM ===== */
        .form-panel {
            flex: 0.9;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            background-color: #ffffff;
        }

        .form-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .form-header {
            margin-bottom: 32px;
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

        /* Role Selection (Offer18 style cards) */
        .role-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
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

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: 0.2px;
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

        /* Form Options */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            font-size: 14px;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #2563eb;
            border-radius: 4px;
            cursor: pointer;
        }

        .forgot-link {
            color: #2563eb;
            font-weight: 700;
            font-size: 14px;
        }

        /* Submit Button */
        .btn-login {
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
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Register Link */
        .register-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #f1f5f9;
            color: #64748b;
            font-size: 14px;
        }

        .register-link a {
            color: #2563eb;
            font-weight: 700;
        }

        /* Trust Badges */
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

        /* Loading state */
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

        /* Input validation styles */
        .form-control.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .error-message {
            color: #ef4444;
            font-size: 12px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 500;
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
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- LEFT PANEL - Trackdesk Showcase -->
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
                    Manage and scale your affiliate partners, campaigns, and conversions in real-time. Experience zero latency and native SQL security.
                </p>

                <!-- Trackdesk Style Live stats widget -->
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

        <!-- RIGHT PANEL - Login Form / Workspace Finder -->
        <div class="form-panel">
            <div class="form-container">
                <div class="form-header-logo">
                    <img src="/logo.png" alt="Taskbazi Logo">
                </div>

                <?php if (is_root_domain()): ?>
                    <div class="form-header">
                        <h2>Sign in to your workspace</h2>
                        <p>Enter your workspace subdomain to continue</p>
                    </div>

                    <!-- Alert Messages -->
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="workspaceForm" novalidate>
                        <!-- Subdomain Field -->
                        <div class="form-group">
                            <label class="form-label" for="subdomain">Workspace URL</label>
                            <div class="input-wrapper" style="display: flex; align-items: center; position: relative;">
                                <i class="fas fa-globe input-icon" style="top: 50%; transform: translateY(-50%);"></i>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="subdomain" 
                                    name="subdomain" 
                                    placeholder="your-workspace"
                                    value="<?= htmlspecialchars($_POST['subdomain'] ?? '') ?>"
                                    style="padding-right: 140px; font-weight: 500;"
                                    autocomplete="off"
                                    autofocus
                                >
                                <span class="subdomain-suffix" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-weight: 700; font-size: 14px; pointer-events: none; letter-spacing: -0.2px;">
                                    <?= ($_SERVER['HTTP_HOST'] === 'localhost' || explode(':', $_SERVER['HTTP_HOST'])[0] === 'localhost') ? '.localhost' : '.taskbazi.xyz' ?>
                                </span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-login" id="submitBtn" style="margin-top: 24px;">
                            <span>Continue to workspace</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>

                        <!-- Registration Link -->
                        <div class="register-link">
                            <span>New to Taskbazi? </span>
                            <a href="/register.php">Create an agency account &rarr;</a>
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
                        <h2>Sign In</h2>
                        <p>Enter your details to access your dashboard</p>
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

                    <!-- Login Form -->
                    <form method="post" id="loginForm" novalidate>
                        <!-- Role Selection - Offer18 style cards -->
                        <div class="form-group">
                            <label class="form-label">Select Account Type</label>
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

                        <!-- Email Field -->
                        <div class="form-group">
                            <label class="form-label" for="email">Email address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    placeholder="partner@company.com" 
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    autocomplete="email"
                                >
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <label class="form-label" for="password">Password</label>
                                <a href="#" class="forgot-link">Forgot?</a>
                            </div>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    placeholder="••••••••••••"
                                    autocomplete="current-password"
                                >
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="form-options">
                            <label class="remember-me">
                                <input type="checkbox" name="remember" value="1" <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                                <span>Remember me for 30 days</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-login" id="submitBtn">
                            <span>Sign in to dashboard</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>

                        <!-- Registration Link -->
                        <div class="register-link">
                            <span>New to this network? </span>
                            <a href="/register.php">Create an account &rarr;</a>
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

            function clearError(input) {
                input.classList.remove('error');
                const existingError = input.parentElement.nextElementSibling;
                if (existingError && existingError.classList.contains('error-message')) {
                    existingError.remove();
                }
            }

            function showError(input, message) {
                input.classList.add('error');
                const existingError = input.parentElement.nextElementSibling;
                if (existingError && existingError.classList.contains('error-message')) {
                    existingError.remove();
                }
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                input.parentElement.parentElement.appendChild(errorDiv);
            }

            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            if (isRoot) {
                const form = document.getElementById('workspaceForm');
                const subdomainInput = document.getElementById('subdomain');
                const submitBtn = document.getElementById('submitBtn');

                if (form) {
                    form.addEventListener('submit', function(e) {
                        let isValid = true;
                        const val = subdomainInput.value.trim();
                        if (!val) {
                            showError(subdomainInput, 'Workspace domain is required');
                            isValid = false;
                        } else if (!/^[a-z0-9\-]+$/i.test(val)) {
                            showError(subdomainInput, 'Invalid workspace domain format');
                            isValid = false;
                        }

                        if (!isValid) {
                            e.preventDefault();
                            return false;
                        }

                        submitBtn.classList.add('btn-loading');
                        submitBtn.innerHTML = `
                            <span>Redirecting to workspace...</span>
                            <i class="fas fa-spinner"></i>
                        `;
                    });

                    subdomainInput.addEventListener('input', function() {
                        clearError(this);
                    });
                }
            } else {
                const form = document.getElementById('loginForm');
                const submitBtn = document.getElementById('submitBtn');
                const emailInput = document.getElementById('email');
                const passwordInput = document.getElementById('password');
                const roleInputs = document.querySelectorAll('input[name="role"]');

                if (emailInput) {
                    emailInput.addEventListener('blur', function() {
                        if (this.value && !isValidEmail(this.value)) {
                            showError(this, 'Please enter a valid email address');
                        } else {
                            clearError(this);
                        }
                    });

                    emailInput.addEventListener('input', function() {
                        clearError(this);
                    });
                }

                if (passwordInput) {
                    passwordInput.addEventListener('input', function() {
                        clearError(this);
                    });
                }

                if (form) {
                    form.addEventListener('submit', function(e) {
                        let isValid = true;
                        
                        const roleSelected = Array.from(roleInputs).some(input => input.checked);
                        if (!roleSelected) {
                            e.preventDefault();
                            document.querySelector('.role-container').classList.add('error');
                            isValid = false;
                            
                            let roleError = document.querySelector('.role-error');
                            if (!roleError) {
                                roleError = document.createElement('div');
                                roleError.className = 'error-message role-error';
                                roleError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please select your account type';
                                document.querySelector('.role-container').after(roleError);
                            }
                        }

                        if (!emailInput.value) {
                            showError(emailInput, 'Email address is required');
                            isValid = false;
                        } else if (!isValidEmail(emailInput.value)) {
                            showError(emailInput, 'Please enter a valid email address');
                            isValid = false;
                        }

                        if (!passwordInput.value) {
                            showError(passwordInput, 'Password is required');
                            isValid = false;
                        }

                        if (!isValid) {
                            e.preventDefault();
                            return false;
                        }

                        submitBtn.classList.add('btn-loading');
                        submitBtn.innerHTML = `
                            <span>Authenticating...</span>
                            <i class="fas fa-spinner"></i>
                        `;
                    });
                }

                roleInputs.forEach(input => {
                    input.addEventListener('change', function() {
                        document.querySelector('.role-container')?.classList.remove('error');
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
        })();
    </script>
</body>
</html>