<?php
/**
 * Taskbazi SaaS Affiliate Tracker - Premium Landing Page
 * PHP 7.1+
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true); // Allow DB queries without requiring a resolved tenant slug

require_once __DIR__ . '/app/config/database.php';

// Fetch plans dynamically from the database
try {
    $plans = $pdo->query("SELECT * FROM saas_plans ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback static plans in case the database is not initialized yet
    $plans = [
        [
            'name' => 'Starter',
            'price' => '$99/mo',
            'offers_limit' => '100',
            'publishers_limit' => '100',
            'advertisers_limit' => '20',
            'description' => 'Great for starting out or testing workflows.',
            'color' => '#60a5fa'
        ],
        [
            'name' => 'Professional',
            'price' => '$299/mo',
            'offers_limit' => '500',
            'publishers_limit' => '500',
            'advertisers_limit' => '100',
            'description' => 'Designed for growing affiliate networks.',
            'color' => '#c084fc'
        ],
        [
            'name' => 'Enterprise',
            'price' => '$999/mo',
            'offers_limit' => 'Unlimited',
            'publishers_limit' => 'Unlimited',
            'advertisers_limit' => 'Unlimited',
            'description' => 'Uncapped limits and VIP support for large operations.',
            'color' => '#34d399'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taskbazi · Premium Multi-Tenant Performance Tracking Platform</title>
    <!-- Google Fonts: Outfit (Display) & Inter (Sans) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-dark: #0b0f19;
            --card-bg: rgba(17, 24, 39, 0.7);
            --border-glow: rgba(99, 102, 241, 0.15);
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --font-sans: 'Inter', sans-serif;
            --font-display: 'Outfit', sans-serif;
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
            background-color: var(--bg-dark);
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Ambient Glow Backgrounds */
        .glow-sphere {
            position: absolute;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(168, 85, 247, 0.05) 50%, transparent 100%);
            border-radius: 50%;
            top: -200px;
            right: -200px;
            filter: blur(100px);
            z-index: 0;
            pointer-events: none;
        }

        .glow-sphere-2 {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.1) 0%, rgba(59, 130, 246, 0.05) 50%, transparent 100%);
            border-radius: 50%;
            bottom: 20%;
            left: -200px;
            filter: blur(80px);
            z-index: 0;
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            position: relative;
            z-index: 10;
        }

        /* HEADER / NAVIGATION */
        header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(11, 15, 25, 0.8);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 100;
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
            font-weight: 800;
            color: #fff;
            text-decoration: none;
        }

        .logo i {
            color: var(--primary);
            filter: drop-shadow(0 0 8px var(--primary-glow));
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 32px;
        }

        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            font-size: 15px;
        }

        .nav-links a:hover {
            color: #fff;
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
            padding: 10px 22px;
            border-radius: 100px;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-outline {
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            background: rgba(255, 255, 255, 0.03);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #4f46e5 100%);
            color: white;
            box-shadow: 0 4px 14px var(--primary-glow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--primary-glow);
        }

        /* HERO SECTION */
        .hero {
            padding: 100px 0 140px;
            text-align: center;
            position: relative;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            color: #818cf8;
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
            letter-spacing: 0.5px;
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(32px, 5vw, 60px);
            font-weight: 800;
            line-height: 1.15;
            max-width: 900px;
            margin: 0 auto 24px;
            color: #fff;
            letter-spacing: -0.02em;
        }

        .gradient-text {
            background: linear-gradient(135deg, #a855f7 0%, #6366f1 50%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc {
            font-size: 18px;
            color: var(--text-muted);
            max-width: 650px;
            margin: 0 auto 40px;
        }

        /* Interactive Statistics Widget */
        .hero-stats-panel {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 32px;
            max-width: 800px;
            margin: 60px auto 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
        }

        .stat-item {
            text-align: center;
        }

        .stat-val {
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }

        .stat-lbl {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* FEATURES SECTION */
        .features {
            padding: 100px 0;
            background: rgba(17, 24, 39, 0.4);
            border-top: 1px solid rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-family: var(--font-display);
            font-size: 36px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 12px;
        }

        .section-header p {
            color: var(--text-muted);
            font-size: 16px;
            max-width: 500px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        .feature-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.3s;
            backdrop-filter: blur(12px);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.1);
        }

        .feat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 24px;
        }

        .feat-title {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 12px;
        }

        .feat-desc {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        /* PRICING SECTION */
        .pricing {
            padding: 120px 0;
            position: relative;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            align-items: stretch;
        }

        .price-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            transition: all 0.3s;
            position: relative;
            backdrop-filter: blur(12px);
        }

        .price-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            border-color: rgba(255,255,255,0.1);
        }

        .price-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-tier-name {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 700;
        }

        .price-val {
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 800;
            color: #fff;
        }

        .price-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .price-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 24px;
        }

        .price-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: var(--text-main);
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

        /* SUPPORT/CALLOUT SECTION */
        .callout {
            padding: 80px 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(168, 85, 247, 0.04) 100%);
            border-radius: 30px;
            border: 1px solid rgba(99, 102, 241, 0.1);
            text-align: center;
            margin-bottom: 100px;
        }

        .callout h2 {
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 12px;
        }

        .callout p {
            color: var(--text-muted);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto 32px;
        }

        .phone-link {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-family: var(--font-display);
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.05);
            padding: 14px 28px;
            border-radius: 100px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s;
        }

        .phone-link:hover {
            background: var(--primary);
            box-shadow: 0 8px 20px var(--primary-glow);
            border-color: transparent;
            transform: scale(1.03);
        }

        .phone-link i {
            color: #818cf8;
            transition: color 0.3s;
        }

        .phone-link:hover i {
            color: #fff;
        }

        /* FOOTER */
        footer {
            background: rgba(11, 15, 25, 0.95);
            padding: 60px 0 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            font-size: 14px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-col h3 {
            color: #fff;
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .footer-col ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .footer-col ul a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-col ul a:hover {
            color: #fff;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 30px;
        }

        @media (max-width: 1024px) {
            .features-grid, .pricing-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .hero-stats-panel {
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .features-grid, .pricing-grid, .footer-grid, .hero-stats-panel {
                grid-template-columns: 1fr;
            }
            .navbar {
                height: auto;
                padding: 20px 0;
                flex-direction: column;
                gap: 16px;
            }
            .nav-links {
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="glow-sphere"></div>
    <div class="glow-sphere-2"></div>

    <!-- HEADER / NAVIGATION -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="/index.php" class="logo">
                    <i class="fas fa-chart-network"></i>
                    <span>Taskbazi</span>
                </a>
                <ul class="nav-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#technology">Technology</a></li>
                    <li><a href="#pricing">Pricing Plans</a></li>
                    <li><a href="tel:8016222991">Support</a></li>
                </ul>
                <div class="nav-cta">
                    <a href="/login.php" class="btn btn-outline">Sign In</a>
                    <a href="/register.php" class="btn btn-primary">Join Network</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="container">
            <span class="hero-badge">
                <i class="fas fa-bolt"></i> State-of-the-Art Multi-Tenant SaaS
            </span>
            <h1 class="hero-title">
                Next-Gen performance tracking <span class="gradient-text">engineered for enterprise scale</span>
            </h1>
            <p class="hero-desc">
                Taskbazi is the premier dynamic affiliate network builder. Spin up custom tracking subdomains, isolate tenant databases, and leverage real-time analytics in a fast, robust PHP engine.
            </p>
            <div class="hero-cta">
                <a href="#pricing" class="btn btn-primary btn-lg">View Subscription Plans</a>
                <a href="tel:8016222991" class="btn btn-outline btn-lg" style="margin-left: 16px;">
                    <i class="fas fa-phone"></i> Call 8016222991
                </a>
            </div>

            <!-- Stats Widget -->
            <div class="hero-stats-panel">
                <div class="stat-item">
                    <div class="stat-val">100M+</div>
                    <div class="stat-lbl">Clicks Tracked</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val">2.5ms</div>
                    <div class="stat-lbl">Redirect Latency</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val">99.99%</div>
                    <div class="stat-lbl">SLA Uptime</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Why top networks trust Taskbazi</h2>
                <p>Enterprise tracking performance combined with robust, secure tenant isolation.</p>
            </div>
            <div class="features-grid">
                <!-- Card 1 -->
                <div class="feature-card">
                    <div class="feat-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="feat-title">Explicit Tenant Scoping</h3>
                    <p class="feat-desc">Complete database protection. Every SQL select, update, and insert is filtered by unique tenant constraints at the native execution layer.</p>
                </div>
                <!-- Card 2 -->
                <div class="feature-card">
                    <div class="feat-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3 class="feat-title">Dynamic Domain Routing</h3>
                    <p class="feat-desc">Map custom branding domains or subdomains instantly. Our HTTP host parser dynamically routes users to their matching tenant workspace.</p>
                </div>
                <!-- Card 3 -->
                <div class="feature-card">
                    <div class="feat-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <h3 class="feat-title">Procedural PHP Speed</h3>
                    <p class="feat-desc">Built in optimized PHP 7.1+ using native PDO, bypassing bulky framework overhead to process clicks in milliseconds.</p>
                </div>
                <!-- Card 4 -->
                <div class="feature-card">
                    <div class="feat-icon">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <h3 class="feat-title">Developer SQL Guard</h3>
                    <p class="feat-desc">Custom GuardPDO class monitors execution paths in real time, alerting developers to any query safety violations before staging.</p>
                </div>
                <!-- Card 5 -->
                <div class="feature-card">
                    <div class="feat-icon">
                        <i class="fas fa-arrows-spin"></i>
                    </div>
                    <h3 class="feat-title">Automated Postbacks</h3>
                    <p class="feat-desc">Trigger postback logs and firing queues immediately. Deliver conversion metrics dynamically back to your advertisers and publishers.</p>
                </div>
                <!-- Card 6 -->
                <div class="feature-card">
                    <div class="feat-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <h3 class="feat-title">Global Control Portal</h3>
                    <p class="feat-desc">Super admins retain total network control from the centralized `/superadmin` console, complete with telemetry logs and user impersonation tools.</p>
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
                            <span class="price-tier-name" style="color: <?=$p['color']?>"><?=$p['name']?></span>
                            <span class="price-val"><?=$p['price']?></span>
                        </div>
                        <p class="price-desc"><?=$p['description']?></p>
                        
                        <ul class="price-features">
                            <li><i class="fas fa-circle-check"></i> Max Offers: <strong><?=$p['offers_limit']?></strong></li>
                            <li><i class="fas fa-circle-check"></i> Max Publishers: <strong><?=$p['publishers_limit']?></strong></li>
                            <li><i class="fas fa-circle-check"></i> Max Advertisers: <strong><?=$p['advertisers_limit']?></strong></li>
                            <li><i class="fas fa-circle-check"></i> Advanced Reports & API Access</li>
                            <li><i class="fas fa-circle-check"></i> SSL Protection Support</li>
                        </ul>
                        
                        <a href="/register.php?plan=<?=urlencode($p['name'])?>" class="btn btn-primary price-btn" style="background: linear-gradient(135deg, <?=$p['color']?> 0%, #1e293b 100%); box-shadow: 0 4px 12px rgba(255,255,255,0.02)">
                            Select <?=$p['name']?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SUPPORT / CALLOUT -->
    <section class="container">
        <div class="callout">
            <h2>Ready to speak with our scaling specialists?</h2>
            <p>Whether you need custom volume allocations, custom tenant domain setups, or migration support, our team is available 24/7.</p>
            <a href="tel:8016222991" class="phone-link">
                <i class="fas fa-phone-volume"></i> +91 8016222991
            </a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3 style="color:#fff;">Taskbazi</h3>
                    <p style="margin-top: 10px; font-size:13px; line-height:1.6;">The industry leading procedural PHP SaaS multi-tenant tracking software for high-growth affiliate networks.</p>
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
                        <li><a href="#technology">System Security</a></li>
                        <li><a href="/register.php">Get Started</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?=date('Y')?> Taskbazi. All rights reserved.</p>
                <p>Designed for Ultimate Performance Tracking</p>
            </div>
        </div>
    </footer>
</body>
</html>
