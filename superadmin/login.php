<?php
/**
 * Super Admin Login - Premium Control Portal
 * PHP 7.1+
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true);

require_once __DIR__ . '/../app/core/auth.php';

$error = null;

// If already logged in as super admin → redirect
if (isset($_SESSION['super_auth']) && $_SESSION['super_auth']['role'] === 'super_admin') {
    header('Location: /superadmin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required';
    } else {
        $result = auth_super_login($email, $password);

        if (!$result['success']) {
            $error = $result['error'];
        } else {
            header('Location: /superadmin/dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SaaS Super Admin Login · Portal Control</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5">
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #090d16 0%, #111827 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated background details */
        .grid-background {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 1;
        }

        .glow-orb {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle at 30% 30%, rgba(99, 102, 241, 0.12), rgba(168, 85, 247, 0.08));
            border-radius: 50%;
            top: -200px;
            right: -200px;
            filter: blur(100px);
            z-index: 1;
        }

        .glow-orb-2 {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle at 70% 70%, rgba(59, 130, 246, 0.08), rgba(236, 72, 153, 0.05));
            border-radius: 50%;
            bottom: -200px;
            left: -100px;
            filter: blur(100px);
            z-index: 1;
        }

        /* Login Card */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            margin: 20px;
        }

        .admin-card {
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(99, 102, 241, 0.1) inset;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .admin-card:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 30px 60px -12px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(99, 102, 241, 0.2) inset;
        }

        .card-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .super-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(168, 85, 247, 0.15);
            border: 1px solid rgba(168, 85, 247, 0.3);
            padding: 6px 14px;
            border-radius: 100px;
            margin-bottom: 20px;
        }

        .super-badge i {
            color: #c084fc;
            font-size: 13px;
        }

        .super-badge span {
            color: #e9d5ff;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .lock-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(145deg, #1f2937, #111827);
            border: 2px solid rgba(168, 85, 247, 0.3);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
        }

        .lock-icon i {
            font-size: 32px;
            color: #a855f7;
            filter: drop-shadow(0 0 8px rgba(168, 85, 247, 0.5));
        }

        .card-header h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .card-header p {
            color: #9ca3af;
            font-size: 14px;
        }

        .error-alert {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 24px;
        }

        .error-alert i {
            color: #f87171;
            font-size: 16px;
        }

        .error-alert span {
            color: #fca5a5;
            font-size: 13px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #9ca3af;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #4b5563;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(31, 41, 55, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            color: white;
            font-size: 15px;
            transition: all 0.25s;
        }

        .form-control:focus {
            outline: none;
            border-color: #a855f7;
            background: rgba(31, 41, 55, 0.8);
            box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.15);
        }

        .btn-login {
            width: 100%;
            padding: 15px 20px;
            background: linear-gradient(145deg, #a855f7, #7e22ce);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 14px rgba(168, 85, 247, 0.3);
        }

        .btn-login:hover {
            background: linear-gradient(145deg, #7e22ce, #6b21a8);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer {
            margin-top: 24px;
            text-align: center;
            color: #4b5563;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="grid-background"></div>
    <div class="glow-orb"></div>
    <div class="glow-orb-2"></div>

    <div class="login-container">
        <div class="admin-card">
            <div class="card-header">
                <div class="super-badge">
                    <i class="fas fa-crown"></i>
                    <span>GLOBAL SaaS CONTROL</span>
                </div>
                <div class="lock-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h1>SaaS Super Admin</h1>
                <p>Authenticate to access global network settings</p>
            </div>

            <?php if ($error): ?>
                <div class="error-alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <div class="form-group">
                    <label class="form-label" for="email">GLOBAL EMAIL ADDRESS</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control" id="email" name="email" placeholder="superadmin@saas.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">SECURE PASSWORD</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <span>Enter Control Console</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="footer">
                <p>&copy; <?= date('Y') ?> SaaS Affiliate Tracker &middot; Global Super Panel</p>
            </div>
        </div>
    </div>
</body>
</html>
