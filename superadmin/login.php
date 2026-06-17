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
    <title>Taskbazi · Super Admin Login</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5">
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
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
            background-color: #f8fafc;
            color: #334155;
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
                linear-gradient(rgba(168, 85, 247, 0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(168, 85, 247, 0.015) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 1;
        }

        .glow-orb {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle at 30% 30%, rgba(168, 85, 247, 0.06), rgba(99, 102, 241, 0.04));
            border-radius: 50%;
            top: -200px;
            right: -200px;
            filter: blur(80px);
            z-index: 1;
        }

        .glow-orb-2 {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle at 70% 70%, rgba(59, 130, 246, 0.04), rgba(236, 72, 153, 0.03));
            border-radius: 50%;
            bottom: -200px;
            left: -100px;
            filter: blur(80px);
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
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 28px;
            padding: 44px 40px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .admin-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.08);
        }

        .card-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .super-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(168, 85, 247, 0.08);
            border: 1px solid rgba(168, 85, 247, 0.2);
            padding: 6px 14px;
            border-radius: 100px;
            margin-bottom: 20px;
        }

        .super-badge i {
            color: #a855f7;
            font-size: 13px;
        }

        .super-badge span {
            color: #7e22ce;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .lock-icon {
            width: 72px;
            height: 72px;
            background: rgba(168, 85, 247, 0.05);
            border: 1.5px solid rgba(168, 85, 247, 0.15);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
        }

        .lock-icon i {
            font-size: 30px;
            color: #a855f7;
        }

        .card-header h1 {
            font-family: 'Outfit', sans-serif;
            color: #0f172a;
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .card-header p {
            color: #64748b;
            font-size: 14px;
        }

        .error-alert {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 24px;
        }

        .error-alert i {
            color: #ef4444;
            font-size: 16px;
        }

        .error-alert span {
            color: #991b1b;
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
            color: #0f172a;
            font-size: 11px;
            font-weight: 700;
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
            color: #64748b;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            color: #0f172a;
            font-size: 15px;
            transition: all 0.25s;
        }

        .form-control:focus {
            outline: none;
            border-color: #a855f7;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 15px 20px;
            background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%);
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
            box-shadow: 0 4px 14px rgba(168, 85, 247, 0.2);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #7e22ce 0%, #6b21a8 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(168, 85, 247, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer {
            margin-top: 28px;
            text-align: center;
            color: #64748b;
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
                <img src="/logo.png" alt="Taskbazi Logo" style="height: 48px; width: auto; object-fit: contain; margin-bottom: 20px;">
                <h1>Taskbazi Super Admin</h1>
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
                        <input type="email" class="form-control" id="email" name="email" placeholder="superadmin@taskbazi.com" required>
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
                <p>&copy; <?= date('Y') ?> Taskbazi &middot; Global Super Admin Panel</p>
            </div>
        </div>
    </div>
</body>
</html>
