<?php
/**
 * Taskbazi SaaS Affiliate Tracker - Premium High-Tech Landing Page
 * Light Mode & Fully Responsive
 * PHP 7.1+
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true);

require_once __DIR__ . '/app/config/database.php';

// Fetch plans dynamically from the database
try {
    $plans = $pdo->query("SELECT * FROM saas_plans ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback plans if database is not set up yet
    $plans = [
        [
            'name' => 'Starter',
            'price' => '$99/mo',
            'offers_limit' => '100',
            'publishers_limit' => '100',
            'advertisers_limit' => '20',
            'description' => 'Great for starting out or testing workflows.',
            'color' => '#3b82f6'
        ],
        [
            'name' => 'Professional',
            'price' => '$299/mo',
            'offers_limit' => '500',
            'publishers_limit' => '500',
            'advertisers_limit' => '100',
            'description' => 'Designed for growing affiliate networks.',
            'color' => '#8b5cf6'
        ],
        [
            'name' => 'Enterprise',
            'price' => '$999/mo',
            'offers_limit' => 'Unlimited',
            'publishers_limit' => 'Unlimited',
            'advertisers_limit' => 'Unlimited',
            'description' => 'Uncapped limits and VIP support for large operations.',
            'color' => '#10b981'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taskbazi · High-Tech Performance Affiliate Tracking Platform</title>
    <!-- Google Fonts: Outfit (Brand/Headers) & Inter (Body) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-main: #f8fafc;
            --bg-white: #ffffff;
            --text-dark: #0f172a;
            --text-medium: #334155;
            --text-light: #64748b;
            --primary: #4f46e5;
            --primary-light: #e0e7ff;
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
            --border-color: rgba(226, 232, 240, 0.8);
            --shadow-premium: 0 20px 40px -15px rgba(15, 23, 42, 0.05);
            --font-display: 'Outfit', sans-serif;
            --font-sans: 'Inter', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-main);
            color: var(--text-medium);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            position: relative;
        }

        /* HEADER / NAVIGATION */
        header {
            border-bottom: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.01);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 900;
            color: var(--text-dark);
            text-decoration: none;
        }

        .logo i {
            color: var(--primary);
            filter: drop-shadow(0 4px 6px rgba(79, 70, 229, 0.2));
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 32px;
        }

        .nav-links a {
            color: var(--text-medium);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
            font-size: 15px;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-cta {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 100px;
            font-weight: 700;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
            border: 1.5px solid transparent;
        }

        .btn-outline {
            border-color: var(--primary);
            color: var(--primary);
            background: transparent;
        }

        .btn-outline:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(79, 70, 229, 0.35);
        }

        /* MOBILE MENU TOGGLE */
        .menu-toggle {
            display: none;
            font-size: 24px;
            color: var(--text-dark);
            cursor: pointer;
        }

        /* HERO SECTION */
        .hero {
            padding: 80px 0 100px;
            position: relative;
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.05) 0%, transparent 60%);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 48px;
            align-items: center;
        }

        .hero-content {
            text-align: left;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-light);
            color: var(--primary);
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: 0.5px;
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(36px, 5.5vw, 56px);
            font-weight: 900;
            line-height: 1.15;
            color: var(--text-dark);
            margin-bottom: 24px;
            letter-spacing: -0.03em;
        }

        .gradient-text {
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc {
            font-size: 18px;
            color: var(--text-light);
            margin-bottom: 36px;
            max-width: 580px;
        }

        .hero-ctas {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        /* Float animation */
        .floating-mockup {
            position: relative;
            width: 100%;
            animation: floatAnim 6s ease-in-out infinite;
        }

        .floating-mockup img {
            width: 100%;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 30px 60px -15px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.05);
        }

        @keyframes floatAnim {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* LIVE TELEMETRY CONSOLE */
        .telemetry-section {
            background: var(--bg-white);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            padding: 80px 0;
        }

        .telemetry-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 48px;
            align-items: center;
        }

        .console-container {
            background-color: #0f172a;
            border-radius: 16px;
            padding: 24px;
            font-family: 'Courier New', Courier, monospace;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            color: #38bdf8;
            font-size: 13px;
            height: 320px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .console-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 12px;
            margin-bottom: 16px;
            color: #94a3b8;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .console-dots {
            display: flex;
            gap: 6px;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .dot-red { background-color: #ef4444; }
        .dot-yellow { background-color: #f59e0b; }
        .dot-green { background-color: #10b981; }

        .console-body {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
            scrollbar-width: none;
        }

        .console-body::-webkit-scrollbar {
            display: none;
        }

        .log-line {
            line-height: 1.4;
            animation: consoleLineFade 0.4s ease forwards;
        }

        .log-time { color: #64748b; }
        .log-success { color: #4ade80; }
        .log-info { color: #fb7185; }

        @keyframes consoleLineFade {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* STATS COUNTERS */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-top: 60px;
        }

        .stat-card {
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            box-shadow: var(--shadow-premium);
        }

        .stat-num {
            font-family: var(--font-display);
            font-size: 36px;
            font-weight: 900;
            color: var(--text-dark);
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-desc {
            color: var(--text-light);
            font-size: 13px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
        }

        /* FEATURES SECTION */
        .features {
            padding: 100px 0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            margin-top: 48px;
        }

        .feature-card {
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px 32px;
            transition: all 0.3s;
            box-shadow: var(--shadow-premium);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.08);
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.05);
        }

        .feature-title {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .feature-desc {
            color: var(--text-medium);
            font-size: 14px;
            line-height: 1.6;
        }

        /* HOW IT WORKS / SYSTEM FLOW */
        .system-workflow {
            background: var(--bg-white);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            padding: 100px 0;
        }

        .workflow-grid {
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            gap: 60px;
            align-items: center;
        }

        .workflow-image img {
            width: 100%;
            height: auto;
            border-radius: 24px;
            box-shadow: var(--shadow-premium), 0 0 0 1px rgba(0, 0, 0, 0.03);
        }

        .workflow-steps {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .step-item {
            display: flex;
            gap: 20px;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            font-family: var(--font-display);
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .step-content h3 {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .step-content p {
            font-size: 14px;
            color: var(--text-light);
        }

        /* PRICING PLANS */
        .pricing {
            padding: 100px 0;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            margin-top: 48px;
            align-items: stretch;
        }

        .price-card {
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 48px 32px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            transition: all 0.3s;
            box-shadow: var(--shadow-premium);
        }

        .price-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 50px -15px rgba(15, 23, 42, 0.08);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .price-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-name {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
        }

        .price-value {
            font-family: var(--font-display);
            font-size: 36px;
            font-weight: 900;
            color: var(--text-dark);
        }

        .price-desc {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.5;
        }

        .price-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
        }

        .price-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-medium);
        }

        .price-features li i {
            color: #10b981;
            font-size: 16px;
        }

        .price-btn {
            width: 100%;
            margin-top: auto;
            padding: 14px;
            text-align: center;
        }

        /* FAQ SECTION */
        .faq {
            padding: 100px 0;
            background: var(--bg-white);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .faq-accordion {
            max-width: 800px;
            margin: 48px auto 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .faq-item {
            background-color: var(--bg-main);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .faq-trigger {
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            user-select: none;
        }

        .faq-trigger i {
            transition: transform 0.3s;
            color: var(--primary);
        }

        .faq-content {
            padding: 0 24px 24px;
            font-size: 14px;
            color: var(--text-medium);
            display: none;
            line-height: 1.6;
        }

        .faq-item.active {
            border-color: rgba(99, 102, 241, 0.3);
            background: #fff;
        }

        .faq-item.active .faq-trigger i {
            transform: rotate(180deg);
        }

        /* CALLOUT / SUPPORT PANEL */
        .callout {
            padding: 80px 0;
            text-align: center;
        }

        .callout-box {
            background: linear-gradient(135deg, var(--primary-light) 0%, rgba(99, 102, 241, 0.02) 100%);
            border: 1.5px solid rgba(99, 102, 241, 0.15);
            border-radius: 30px;
            padding: 60px 40px;
        }

        .callout-box h2 {
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .callout-box p {
            color: var(--text-light);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto 32px;
        }

        .phone-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-family: var(--font-display);
            font-size: 26px;
            font-weight: 800;
            color: var(--text-dark);
            text-decoration: none;
            background: var(--bg-white);
            padding: 16px 32px;
            border-radius: 100px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-premium);
            transition: all 0.3s;
        }

        .phone-btn:hover {
            background: var(--primary);
            color: white;
            border-color: transparent;
            transform: scale(1.03);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }

        .phone-btn i {
            color: var(--primary);
        }

        .phone-btn:hover i {
            color: white;
        }

        /* FOOTER */
        footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 80px 0 40px;
            font-size: 14px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }

        .footer-col h3 {
            color: white;
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer-col ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .footer-col ul a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-col ul a:hover {
            color: white;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 30px;
        }

        /* RESPONSIVE LAYOUT */
        @media (max-width: 1024px) {
            .hero-grid, .telemetry-grid, .workflow-grid {
                grid-template-columns: 1fr;
                gap: 48px;
            }
            .hero-content {
                text-align: center;
            }
            .hero-ctas {
                justify-content: center;
            }
            .hero-desc {
                margin: 0 auto 36px;
            }
            .features-grid, .pricing-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .navbar {
                height: 80px;
            }
            .nav-links {
                display: none; /* Handled by JS on mobile if needed, or simplified */
            }
            .nav-cta {
                display: none;
            }
            .menu-toggle {
                display: block;
            }
            .features-grid, .pricing-grid, .footer-grid, .stats-bar {
                grid-template-columns: 1fr;
            }
            .stat-card {
                padding: 16px;
            }
            .footer-bottom {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- HEADER / NAVIGATION -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="/index.php" class="logo">
                    <i class="fas fa-chart-network"></i>
                    <span>Taskbazi</span>
                </a>
                <ul class="nav-links" id="navLinks">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#workflow">Workflow</a></li>
                    <li><a href="#pricing">Pricing Plans</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="tel:8016222991">Support</a></li>
                </ul>
                <div class="nav-cta">
                    <a href="/login.php" class="btn btn-outline">Sign In</a>
                    <a href="/register.php" class="btn btn-primary">Join Network</a>
                </div>
                <div class="menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </div>
            </nav>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <span class="hero-badge">
                        <i class="fas fa-microchip"></i> High-Performance Tracking
                    </span>
                    <h1 class="hero-title">
                        Uncapped scale <span class="gradient-text">for professional performance networks</span>
                    </h1>
                    <p class="hero-desc">
                        Taskbazi is the premier dynamic affiliate network builder. Spin up custom tracking subdomains, isolate tenant databases, and leverage real-time analytics in a fast, robust PHP engine.
                    </p>
                    <div class="hero-ctas">
                        <a href="#pricing" class="btn btn-primary btn-lg">View Subscriptions</a>
                        <a href="tel:8016222991" class="btn btn-outline btn-lg">
                            <i class="fas fa-phone-volume"></i> Call 8016222991
                        </a>
                    </div>
                </div>
                <div class="floating-mockup">
                    <img src="assets/dashboard_preview.png" alt="Taskbazi Dashboard Preview">
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-num" id="stat-clicks">124M+</div>
                    <div class="stat-desc">Clicks Tracked</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num">2.3ms</div>
                    <div class="stat-desc">Redirect Speed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num">100%</div>
                    <div class="stat-desc">SLA Core Uptime</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num">43+</div>
                    <div class="stat-desc">Global Clusters</div>
                </div>
            </div>
        </div>
    </section>

    <!-- LIVE TELEMETRY CONSOLE -->
    <section class="telemetry-section" id="workflow">
        <div class="container">
            <div class="telemetry-grid">
                <div class="console-container">
                    <div class="console-header">
                        <div class="console-dots">
                            <div class="dot dot-red"></div>
                            <div class="dot dot-yellow"></div>
                            <div class="dot dot-green"></div>
                        </div>
                        <span>Live Core Telemetry</span>
                    </div>
                    <div class="console-body" id="consoleBody">
                        <div class="log-line"><span class="log-time">[17:00:01]</span> System initialized on host taskbazi.xyz</div>
                        <div class="log-line"><span class="log-time">[17:00:03]</span> Connecting database... <span class="log-success">SUCCESS</span></div>
                        <div class="log-line"><span class="log-time">[17:00:05]</span> Explicit tenant scoping: <span class="log-success">ENABLED</span></div>
                        <div class="log-line"><span class="log-time">[17:00:08]</span> Resolving host 'localhost'... resolved as Tenant ID #1</div>
                    </div>
                </div>
                <div class="telemetry-content">
                    <span class="hero-badge" style="background:#fef3c7; color:#d97706;"><i class="fas fa-terminal"></i> Real-Time Console</span>
                    <h2 class="feature-title" style="font-size:32px; margin-bottom:16px;">Explicit SQL Query Safety Check</h2>
                    <p style="color:var(--text-light); margin-bottom:24px; font-size:15px; line-height:1.7;">
                        Taskbazi runs a custom `GuardPDO` statement wrapper in development. It inspects all SQL statements dynamically before execution to enforce tenant isolation boundary checks. Any unscoped query touching critical tables is automatically logged to `sql_guard.log`.
                    </p>
                    <a href="/login.php" class="btn btn-primary">Try Live Dashboard</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Built for Affiliate & Performance Marketing</h2>
                <p>Enterprise tracking performance combined with secure, isolated data boundaries.</p>
            </div>
            <div class="features-grid">
                <!-- Feature 1 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <h3 class="feature-title">Explicit Tenant Scoping</h3>
                    <p class="feature-desc">Complete database protection. Every SQL select, update, and delete query is explicitly filtered by unique tenant constraints at the native execution layer.</p>
                </div>
                <!-- Feature 2 -->
                <div class="feature-icon-box feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3 class="feature-title">Dynamic Domain Routing</h3>
                    <p class="feature-desc">Map custom branding domains or subdomains instantly. Our HTTP host parser dynamically routes users to their matching tenant workspace.</p>
                </div>
                <!-- Feature 3 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="feature-title">Procedural PHP Speed</h3>
                    <p class="feature-desc">Built in optimized PHP 7.1+ using native PDO, bypassing bulky framework overhead to process clicks and log redirects in milliseconds.</p>
                </div>
                <!-- Feature 4 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-arrow-turn-down"></i>
                    </div>
                    <h3 class="feature-title">Automated Postbacks</h3>
                    <p class="feature-desc">Trigger postback logs and firing queues immediately. Deliver conversion metrics dynamically back to your advertisers and publishers.</p>
                </div>
                <!-- Feature 5 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-gear"></i>
                    </div>
                    <h3 class="feature-title">Global Control Portal</h3>
                    <p class="feature-desc">Super admins retain total network control from the centralized `/superadmin` console, complete with telemetry logs and user impersonation tools.</p>
                </div>
                <!-- Feature 6 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-magnifying-glass-chart"></i>
                    </div>
                    <h3 class="feature-title">SubID Telemetry Logs</h3>
                    <p class="feature-desc">Break down incoming clicks by up to 5 custom SubIDs. Identify top publishers, fraud threats, and geo conversion patterns dynamically.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SYSTEM WORKFLOW / HOW IT WORKS -->
    <section class="system-workflow" id="workflow-workflow">
        <div class="container">
            <div class="workflow-grid">
                <div class="workflow-image">
                    <img src="assets/tracking_routes.png" alt="Dynamic Tracking Workflow Diagram">
                </div>
                <div class="workflow-content">
                    <span class="hero-badge" style="background:#dcfce7; color:#15803d;"><i class="fas fa-network-wired"></i> Workflow Matrix</span>
                    <h2 class="hero-title" style="font-size:36px; margin-bottom:16px;">How data flows through Taskbazi</h2>
                    
                    <div class="workflow-steps">
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h3>User Clicks Affiliate Link</h3>
                                <p>The system intercepts the click at <code>click.php</code> and resolves the active tenant dynamically based on the incoming domain name.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h3>Explicit SQL Verification</h3>
                                <p>The request parses incoming SubIDs, validates IP limits, and records the click logs securely scoped under the tenant's ID.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h3>Secure Redirect & Conversion</h3>
                                <p>The user is redirected to the target campaign. When a conversion fires, <code>postback.php</code> triggers the publisher webhook automatically.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING SECTION -->
    <section class="pricing" id="pricing">
        <div class="container">
            <div class="section-header">
                <h2>Flexible pricing built to grow with you</h2>
                <p>Editable from the superadmin dashboard. Choose a subscription package that fits your operations.</p>
            </div>
            
            <div class="pricing-grid">
                <?php foreach ($plans as $p): ?>
                    <div class="price-card" style="border-top: 4px solid <?=$p['color']?>">
                        <div class="price-header">
                            <span class="price-name" style="color: <?=$p['color']?>"><?=$p['name']?></span>
                            <span class="price-value"><?=$p['price']?></span>
                        </div>
                        <p class="price-desc"><?=$p['description']?></p>
                        
                        <ul class="price-features">
                            <li><i class="fas fa-circle-check"></i> Max Offers: <strong><?=$p['offers_limit']?></strong></li>
                            <li><i class="fas fa-circle-check"></i> Max Publishers: <strong><?=$p['publishers_limit']?></strong></li>
                            <li><i class="fas fa-circle-check"></i> Max Advertisers: <strong><?=$p['advertisers_limit']?></strong></li>
                            <li><i class="fas fa-circle-check"></i> Advanced Reports & API Access</li>
                            <li><i class="fas fa-circle-check"></i> SSL Protection Support</li>
                        </ul>
                        
                        <a href="/register.php?plan=<?=urlencode($p['name'])?>" class="btn btn-primary price-btn" style="background: linear-gradient(135deg, <?=$p['color']?> 0%, #0f172a 100%); border:none; box-shadow: 0 4px 14px rgba(0,0,0,0.05);">
                            Select <?=$p['name']?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FAQ SECTION -->
    <section class="faq" id="faq">
        <div class="container">
            <div class="section-header">
                <h2>Frequently Asked Questions</h2>
                <p>Have questions about Taskbazi? Here are the quick technical answers you need.</p>
            </div>
            <div class="faq-accordion">
                <div class="faq-item">
                    <div class="faq-trigger" onclick="toggleFaq(this)">
                        <span>How does tenant database isolation work?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-content">
                        Taskbazi uses explicit tenant scoping. All table entries contain a <code>tenant_id</code>. Whenever queries run, they are strictly appended with tenant SQL limits. The <code>GuardPDO</code> class ensures that no database leaks can happen across accounts.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-trigger" onclick="toggleFaq(this)">
                        <span>Can I map my own custom domains?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-content">
                        Yes! Tenants can register custom tracking and postback domains in their dashboard. The system matches the incoming HTTP host headers against the database dynamically to route traffic instantly.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-trigger" onclick="toggleFaq(this)">
                        <span>Is there a cap on daily click volume?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-content">
                        Volume caps depend on your subscription plan. Starter and Professional plans have soft volume boundaries, while the Enterprise plan supports completely uncapped click and redirect scales.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACT / SUPPORT CALLOUT -->
    <section class="container">
        <div class="callout">
            <div class="callout-box">
                <h2>Ready to scale your affiliate business?</h2>
                <p>Speak to our system engineers today. Get help setting up your tenant spaces, migrating databases, or configuring custom routing protocols.</p>
                <a href="tel:8016222991" class="phone-btn">
                    <i class="fas fa-phone-volume"></i> Call us: +91 8016222991
                </a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3 style="color:#fff;">Taskbazi</h3>
                    <p style="margin-top: 10px; font-size:13px; line-height:1.6; color:#94a3b8;">The industry leading procedural PHP SaaS multi-tenant tracking software for high-growth affiliate networks.</p>
                </div>
                <div class="footer-col">
                    <h3>Modules</h3>
                    <ul>
                        <li><a href="/admin/login.php">Network Admin</a></li>
                        <li><a href="/login.php">Publisher Portal</a></li>
                        <li><a href="/login.php">Advertiser Portal</a></li>
                        <li><a href="/superadmin/login.php">Super Admin</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><a href="tel:8016222991"><i class="fas fa-phone" style="margin-right: 8px;"></i> 8016222991</a></li>
                        <li><a href="mailto:support@taskbazi.xyz"><i class="fas fa-envelope" style="margin-right: 8px;"></i> support@taskbazi.xyz</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Resources</h3>
                    <ul>
                        <li><a href="#features">Documentation</a></li>
                        <li><a href="#workflow">System Workflow</a></li>
                        <li><a href="/register.php">Get Started</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?=date('Y')?> Taskbazi. All rights reserved.</p>
                <p>High-Tech Affiliate Tracking Engines</p>
            </div>
        </div>
    </footer>

    <script>
        // Toggle mobile menu visibility
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            if (navLinks.style.display === 'flex') {
                navLinks.style.display = 'none';
            } else {
                navLinks.style.display = 'flex';
                navLinks.style.flexDirection = 'column';
                navLinks.style.position = 'absolute';
                navLinks.style.top = '80px';
                navLinks.style.left = '0';
                navLinks.style.width = '100%';
                navLinks.style.background = '#ffffff';
                navLinks.style.padding = '20px';
                navLinks.style.borderBottom = '1px solid var(--border-color)';
            }
        }

        // FAQ Collapsible Toggle
        function toggleFaq(trigger) {
            const faqItem = trigger.parentElement;
            const content = faqItem.querySelector('.faq-content');
            
            // Close other items
            document.querySelectorAll('.faq-item').forEach(item => {
                if (item !== faqItem && item.classList.contains('active')) {
                    item.classList.remove('active');
                    item.querySelector('.faq-content').style.display = 'none';
                }
            });

            // Toggle current item
            if (faqItem.classList.contains('active')) {
                faqItem.classList.remove('active');
                content.style.display = 'none';
            } else {
                faqItem.classList.add('active');
                content.style.display = 'block';
            }
        }

        // Live Console log simulation
        const consoleLogs = [
            "Processed conversion payload for Offer ID #3... SUCCESS",
            "Fired publisher postback: https://api.publisher.com/postback?clickid=8a2b3c... SUCCESS",
            "Tenant default mapping resolved: sub.localhost",
            "GuardPDO safety assert: Query is explicitly tenant-scoped. Passed.",
            "Database cleanup task executed... cleared 0 expired sessions",
            "Processed click event from IP 152.12.33.91... device resolved as mobile",
            "Fraud trigger score: 0.05 (clean click request)",
            "System telemetry: Average CPU 1.2%, Memory usage 18MB"
        ];

        const consoleBody = document.getElementById('consoleBody');

        setInterval(() => {
            const randomLog = consoleLogs[Math.floor(Math.random() * consoleLogs.length)];
            const timeStr = new Date().toTimeString().split(' ')[0];
            
            const logLine = document.createElement('div');
            logLine.className = 'log-line';
            
            // Format colors in logs
            let formattedLog = randomLog;
            if (randomLog.includes("SUCCESS")) {
                formattedLog = randomLog.replace("SUCCESS", '<span class="log-success">SUCCESS</span>');
            } else if (randomLog.includes("assert") || randomLog.includes("resolved")) {
                formattedLog = '<span class="log-info">' + randomLog + '</span>';
            }
            
            logLine.innerHTML = `<span class="log-time">[${timeStr}]</span> ${formattedLog}`;
            
            consoleBody.appendChild(logLine);
            consoleBody.scrollTop = consoleBody.scrollHeight;
            
            // Limit output lines
            if (consoleBody.children.length > 20) {
                consoleBody.removeChild(consoleBody.firstChild);
            }
        }, 3000);
    </script>
</body>
</html>
