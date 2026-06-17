<?php
/**
 * Admin Login - Redesigned Premium Version
 * PHP 7.1+
 */

define('APP_INIT', true);

require_once __DIR__ . '/../app/core/auth.php';

$error = null;

// If already logged in and admin → redirect
if (isset($_SESSION['auth']) && $_SESSION['auth']['role'] === 'admin') {
    header('Location: /admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required';
    } else {
        $result = auth_login($email, $password);

        if (!$result['success']) {
            $error = $result['error'];
        } else {
            // EXTRA SAFETY: only admin allowed here
            if ($_SESSION['auth']['role'] !== 'admin') {
                auth_logout();
                $error = 'Access denied. Admin privileges required.';
            } else {
                header('Location: /admin/dashboard.php');
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
    <title>Taskbazi · Administrator Login</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5">
    
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
            background-color: #f8fafc;
            color: #334155;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background grid */
        .grid-background {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(37, 99, 235, 0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(37, 99, 235, 0.015) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 1;
        }

        .glow-orb {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle at 30% 30%, rgba(37, 99, 235, 0.06), rgba(124, 58, 237, 0.04));
            border-radius: 50%;
            top: -200px;
            right: -200px;
            filter: blur(80px);
            animation: float 20s infinite alternate;
            z-index: 1;
        }

        .glow-orb-2 {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle at 70% 70%, rgba(59, 130, 246, 0.04), rgba(139, 92, 246, 0.03));
            border-radius: 50%;
            bottom: -200px;
            left: -100px;
            filter: blur(80px);
            animation: float 25s infinite alternate-reverse;
            z-index: 1;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, 30px) scale(1.1); }
        }

        /* ===== MAIN CONTAINER ===== */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            margin: 20px;
        }

        /* ===== PREMIUM CARD ===== */
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

        /* ===== HEADER SECTION ===== */
        .card-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.2);
            padding: 6px 14px;
            border-radius: 100px;
            margin-bottom: 20px;
        }

        .admin-badge i {
            color: #2563eb;
            font-size: 13px;
        }

        .admin-badge span {
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .lock-icon {
            width: 72px;
            height: 72px;
            background: rgba(37, 99, 235, 0.05);
            border: 1.5px solid rgba(37, 99, 235, 0.15);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
        }

        .lock-icon i {
            font-size: 30px;
            color: #2563eb;
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

        /* ===== ERROR MESSAGE ===== */
        .error-alert {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 24px;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* ===== FORM ===== */
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

        .form-label i {
            color: #2563eb;
            font-size: 13px;
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
            transition: color 0.2s;
            z-index: 2;
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
            border-color: #2563eb;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .form-control::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        /* ===== FORM OPTIONS ===== */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 24px 0 28px;
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
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #1d4ed8;
        }

        /* ===== SUBMIT BUTTON ===== */
        .btn-login {
            width: 100%;
            padding: 15px 20px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.25s;
            position: relative;
            overflow: hidden;
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

        .btn-login i {
            font-size: 16px;
            transition: transform 0.2s;
        }

        .btn-login:hover i {
            transform: translateX(4px);
        }

        /* ===== SECURITY BADGES ===== */
        .security-badges {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid rgba(226, 232, 240, 0.8);
        }

        .security-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
        }

        .security-item i {
            color: #2563eb;
            font-size: 11px;
        }

        /* ===== ADMIN FOOTER ===== */
        .admin-footer {
            margin-top: 28px;
            text-align: center;
        }

        .admin-footer p {
            color: #64748b;
            font-size: 13px;
        }

        .admin-footer a {
            color: #2563eb;
            font-weight: 600;
            transition: color 0.2s;
        }

        .admin-footer a:hover {
            color: #1d4ed8;
        }

        /* ===== LOADING STATE ===== */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.9;
        }

        .btn-loading i.fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* ===== ERROR STATE ===== */
        .form-control.error {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .error-text {
            color: #ef4444;
            font-size: 12px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            .login-container {
                margin: 12px;
            }

            .admin-card {
                padding: 36px 24px;
            }

            .lock-icon {
                width: 64px;
                height: 64px;
                border-radius: 18px;
            }

            .lock-icon i {
                font-size: 26px;
            }

            .card-header h1 {
                font-size: 22px;
            }

            .form-control {
                padding: 14px 16px 14px 48px;
                font-size: 15px;
            }

            .btn-login {
                padding: 14px 20px;
            }

            .security-badges {
                flex-direction: column;
                gap: 10px;
                align-items: center;
            }
        }

        /* Shake animation for errors */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.4s ease;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="grid-background"></div>
    <div class="glow-orb"></div>
    <div class="glow-orb-2"></div>

    <!-- Login Container -->
    <div class="login-container">
        <div class="admin-card">
            <div class="card-header">
                <img src="/logo.png" alt="Taskbazi Logo" style="height: 48px; width: auto; object-fit: contain; margin-bottom: 20px;">
                <h1>Administrator Login</h1>
                <p>Enter your credentials to access the control panel</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="post" id="loginForm" novalidate autocomplete="off">
                <!-- Email Field -->
                <div class="form-group">
                    <label class="form-label" for="email">
                        <i class="fas fa-envelope"></i>
                        <span>EMAIL ADDRESS</span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            placeholder="admin@taskbazi.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            autocomplete="off"
                            required
                        >
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock"></i>
                        <span>PASSWORD</span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••••••"
                            autocomplete="off"
                            required
                        >
                    </div>
                </div>

                <!-- Form Options -->
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" value="1">
                        <span>Remember this device</span>
                    </label>
                    <a href="#" class="forgot-link">
                        <i class="fas fa-key"></i> Reset 2FA
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="submitBtn">
                    <span>Access Admin Panel</span>
                    <i class="fas fa-arrow-right"></i>
                </button>

                <!-- Security Badges -->
                <div class="security-badges">
                    <span class="security-item">
                        <i class="fas fa-shield-halved"></i>
                        <span>256-bit SSL</span>
                    </span>
                    <span class="security-item">
                        <i class="fas fa-fingerprint"></i>
                        <span>2FA Protected</span>
                    </span>
                    <span class="security-item">
                        <i class="fas fa-clock"></i>
                        <span>Session Timeout</span>
                    </span>
                </div>
            </form>

            <!-- Footer -->
            <div class="admin-footer">
                <p>© <?= date('Y') ?> Taskbazi &middot; <a href="#">Privacy</a> · <a href="#">Security</a></p>
            </div>
        </div>
    </div>

    <script>
        (function() {
            'use strict';

            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            // Email validation helper
            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            // Show error for field
            function markError(input, message) {
                input.classList.add('error');
                
                // Remove existing error
                const existingError = input.parentElement.nextElementSibling;
                if (existingError && existingError.classList.contains('error-text')) {
                    existingError.remove();
                }
                
                // Add new error
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-text';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                input.parentElement.parentElement.appendChild(errorDiv);
                
                // Remove error on input
                input.addEventListener('input', function() {
                    this.classList.remove('error');
                    const error = this.parentElement.nextElementSibling;
                    if (error && error.classList.contains('error-text')) {
                        error.remove();
                    }
                }, { once: true });
            }

            // Clear field error
            function clearError(input) {
                input.classList.remove('error');
                const existingError = input.parentElement.nextElementSibling;
                if (existingError && existingError.classList.contains('error-text')) {
                    existingError.remove();
                }
            }

            // Form submission
            if (form) {
                form.addEventListener('submit', function(e) {
                    let isValid = true;

                    // Validate email
                    if (!emailInput.value.trim()) {
                        markError(emailInput, 'Email address is required');
                        isValid = false;
                    } else if (!isValidEmail(emailInput.value.trim())) {
                        markError(emailInput, 'Please enter a valid email address');
                        isValid = false;
                    }

                    // Validate password
                    if (!passwordInput.value) {
                        markError(passwordInput, 'Password is required');
                        isValid = false;
                    }

                    if (!isValid) {
                        e.preventDefault();
                        
                        // Add shake animation to card
                        const card = document.querySelector('.admin-card');
                        card.classList.add('shake');
                        setTimeout(() => {
                            card.classList.remove('shake');
                        }, 400);
                        
                        return false;
                    }

                    // Add loading state
                    submitBtn.classList.add('btn-loading');
                    submitBtn.innerHTML = `
                        <span>Authenticating...</span>
                        <i class="fas fa-spinner"></i>
                    `;
                });
            }

            // Real-time email validation
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    if (this.value && !isValidEmail(this.value)) {
                        markError(this, 'Please enter a valid email address');
                    } else {
                        clearError(this);
                    }
                });
            }

            // Auto-dismiss error alert after 5 seconds
            const errorAlert = document.querySelector('.error-alert');
            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.style.transition = 'opacity 0.5s ease';
                    errorAlert.style.opacity = '0';
                    setTimeout(() => errorAlert.remove(), 500);
                }, 5000);
            }

            // Focus on email field
            if (emailInput) {
                emailInput.focus();
            }

            // Prevent zoom on mobile for inputs
            if (window.innerWidth <= 768) {
                const inputs = document.querySelectorAll('.form-control');
                inputs.forEach(input => {
                    input.style.fontSize = '16px';
                });
            }

            // Add subtle tilt effect (optional)
            const card = document.querySelector('.admin-card');
            if (card && window.innerWidth >= 1024) {
                card.addEventListener('mousemove', function(e) {
                    const rect = this.getBoundingClientRect();
                    const x = (e.clientX - rect.left) / rect.width - 0.5;
                    const y = (e.clientY - rect.top) / rect.height - 0.5;
                    
                    this.style.transform = `perspective(1000px) rotateY(${x * 2}deg) rotateX(${y * -2}deg) translateY(-2px)`;
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'perspective(1000px) rotateY(0) rotateX(0) translateY(0)';
                });
            }

            // Prevent form resubmission on page refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        })();
    </script>
</body>
</html>