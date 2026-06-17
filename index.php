<?php
/**
 * Dynamic Multi-Tenant Homepage Router
 * PHP 7.1+
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true); // Bypass automatic database require_tenant

require_once __DIR__ . '/app/config/database.php';

// Check if we are on the root SaaS platform domain
if (is_root_domain()) {
    require_once __DIR__ . '/root_landing.php';
    exit;
}

// Subdomain Tenant Context - Enforce Verification manually
$tenant = current_tenant();
if (!$tenant) {
    http_response_code(404);
    include_once __DIR__ . '/404.php';
    exit;
}

if ($tenant['status'] === 'suspended') {
    http_response_code(403);
    show_suspended_screen($tenant);
    exit;
}

if ($tenant['status'] !== 'active') {
    http_response_code(403);
    exit('Access Denied (Tenant Inactive)');
}

$tenantId = (int)$tenant['id'];
$settings = current_tenant_settings() ?: [];
$siteName = htmlspecialchars($settings['site_name'] ?? $tenant['name']);
$primaryColor = htmlspecialchars($settings['primary_color'] ?? '#2563eb');
$logoPath = htmlspecialchars($settings['logo_path'] ?? '/logo.png');
$faviconPath = htmlspecialchars($settings['favicon_path'] ?? '/favicon.png');
$supportEmail = htmlspecialchars($settings['support_email'] ?? $tenant['owner_email']);

// Fetch homepage custom blocks or insert defaults if missing
try {
    $homepageStmt = $pdo->prepare("SELECT * FROM tenant_homepages WHERE tenant_id = :tid LIMIT 1");
    $homepageStmt->execute(['tid' => $tenantId]);
    $homepage = $homepageStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$homepage) {
        // Create default page content
        $defaultFeatures = [
            [
                'icon' => 'fa-bolt',
                'title' => 'Real-Time Tracking',
                'desc' => 'Track every click and conversion instantly with sub-second postback redirects and zero latency.'
            ],
            [
                'icon' => 'fa-shield-halved',
                'title' => 'Fraud Detection',
                'desc' => 'Advanced filter layers verify visitor IPs and block proxy traffic automatically.'
            ],
            [
                'icon' => 'fa-chart-pie',
                'title' => 'Detailed Reports',
                'desc' => 'Analyze campaign metrics, publisher performance, and advertiser payouts in clean tables.'
            ]
        ];
        
        $homepage = [
            'tenant_id' => $tenantId,
            'template_id' => 'classic_hero',
            'hero_title' => 'Start Your Premium Affiliate Network',
            'hero_subtitle' => 'Track conversions, manage payouts, and grow your affiliate partnerships with zero latency.',
            'hero_cta_text' => 'Apply as Partner',
            'hero_cta_url' => '/register.php',
            'features_json' => json_encode($defaultFeatures),
            'about_text' => 'We provide state-of-the-art affiliate network solutions to connect global brands with high-quality publishers. Scale your performance marketing with absolute control.',
            'contact_email' => $supportEmail,
            'social_links_json' => json_encode(['telegram' => '', 'teams' => ''])
        ];
        
        // Insert defaults
        $ins = $pdo->prepare("
            INSERT INTO tenant_homepages (
                tenant_id, template_id, hero_title, hero_subtitle, hero_cta_text, hero_cta_url, 
                features_json, about_text, contact_email, social_links_json
            ) VALUES (
                :tenant_id, :template_id, :hero_title, :hero_subtitle, :hero_cta_text, :hero_cta_url, 
                :features_json, :about_text, :contact_email, :social_links_json
            )
        ");
        $ins->execute($homepage);
    }
} catch (Exception $e) {
    error_log("Homepage query error: " . $e->getMessage());
    // Safe fallbacks
    $homepage = [
        'template_id' => 'classic_hero',
        'hero_title' => 'Start Your Premium Affiliate Network',
        'hero_subtitle' => 'Track conversions, manage payouts, and grow your affiliate partnerships with zero latency.',
        'hero_cta_text' => 'Apply as Partner',
        'hero_cta_url' => '/register.php',
        'features_json' => '[]',
        'about_text' => '',
        'contact_email' => $supportEmail,
        'social_links_json' => '{}'
    ];
}

// Decode JSON variables
$features = json_decode($homepage['features_json'] ?? '[]', true) ?: [];
$socials = json_decode($homepage['social_links_json'] ?? '{}', true) ?: [];
$templateId = $homepage['template_id'] ?? 'classic_hero';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($homepage['hero_title']) ?> · <?= $siteName ?></title>
    <link rel="icon" type="image/png" href="<?= $faviconPath ?>">
    
    <!-- Google Fonts: Inter & Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ===== BASE CSS & THEME VARIABLES ===== */
        :root {
            --primary-color: <?= $primaryColor ?>;
            --primary-rgb: 37, 99, 235; /* Fallback placeholder, overridden dynamic hover */
            --bg-body: #ffffff;
            --bg-alt: #f8fafc;
            --text-dark: #0f172a;
            --text-medium: #334155;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --font-display: 'Outfit', sans-serif;
            --font-sans: 'Inter', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-body);
            color: var(--text-medium);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ===== HARDCODED COMMON HEADER ===== */
        header {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.01);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 76px;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .nav-logo img {
            height: 34px;
            width: auto;
            object-fit: contain;
        }

        .nav-links {
            display: none;
            gap: 24px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-medium);
        }

        @media (min-width: 768px) {
            .nav-links {
                display: flex;
            }
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-link {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-dark);
            padding: 10px 16px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-link:hover {
            color: var(--primary-color);
            background: var(--bg-alt);
        }

        .btn-theme {
            font-size: 14px;
            font-weight: 700;
            background: var(--primary-color);
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 10px;
            transition: all 0.25s;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
            border: none;
            cursor: pointer;
        }

        .btn-theme:hover {
            transform: translateY(-1px);
            opacity: 0.95;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.25);
        }

        /* ===== COMMON CONTENT ===== */
        main {
            flex: 1;
        }

        /* ===== TEMPLATE 1: CLASSIC HERO (DEFAULT) ===== */
        .tpl-classic {
            padding: 60px 0 100px;
        }

        .tpl-classic .hero-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 48px;
            align-items: center;
        }

        @media (min-width: 1024px) {
            .tpl-classic .hero-grid {
                grid-template-columns: 1.2fr 0.8fr;
            }
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: 38px;
            line-height: 1.15;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: 20px;
            letter-spacing: -0.8px;
        }

        @media (min-width: 768px) {
            .hero-title {
                font-size: 48px;
            }
        }

        .hero-subtitle {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 36px;
            line-height: 1.6;
            max-width: 620px;
        }

        .cta-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .cta-primary {
            background: var(--primary-color);
            color: #ffffff;
            padding: 14px 28px;
            font-weight: 700;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.2);
            transition: all 0.25s;
        }

        .cta-primary:hover {
            transform: translateY(-1px);
            opacity: 0.95;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .cta-secondary {
            background: #ffffff;
            border: 2px solid var(--border-color);
            color: var(--text-dark);
            padding: 14px 28px;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.25s;
        }

        .cta-secondary:hover {
            border-color: var(--primary-color);
            background: var(--bg-alt);
        }

        /* Mockup widget styling */
        .mockup-container {
            background: var(--bg-alt);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 20px 40px -15px rgba(0,0,0,0.03);
        }

        .mockup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .mockup-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            display: inline-block;
        }

        .mockup-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .mockup-stat-box {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 10px;
            text-align: center;
        }

        .mockup-label {
            font-size: 9px;
            color: var(--text-light);
            text-transform: uppercase;
            font-weight: 700;
        }

        .mockup-val {
            font-size: 14px;
            font-weight: 800;
            color: var(--text-dark);
            margin-top: 4px;
        }

        /* ===== TEMPLATE 2: CENTERED SaaS ===== */
        .tpl-centered {
            padding: 80px 0 100px;
            text-align: center;
        }

        .tpl-centered .hero-content {
            max-width: 800px;
            margin: 0 auto 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .tpl-centered .cta-stack {
            justify-content: center;
        }

        /* ===== TEMPLATE 3: SPLIT PORTALS ===== */
        .tpl-split {
            padding: 60px 0 100px;
        }

        .tpl-split .split-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
            margin-top: 48px;
        }

        @media (min-width: 768px) {
            .tpl-split .split-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .portal-card {
            background: #ffffff;
            border: 2px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .portal-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -15px rgba(0,0,0,0.05);
        }

        .portal-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--bg-alt);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 24px;
        }

        .portal-title {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .portal-desc {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 32px;
            flex-grow: 1;
        }

        /* ===== COMMON FEATURES GRID SECTION ===== */
        .features-section {
            background: var(--bg-alt);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            padding: 80px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .section-title {
            font-family: var(--font-display);
            font-size: 30px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .feature-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 28px;
            transition: all 0.25s;
        }

        .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -10px rgba(0,0,0,0.05);
        }

        .feature-icon {
            font-size: 22px;
            color: var(--primary-color);
            margin-bottom: 16px;
        }

        .feature-name {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .feature-desc {
            font-size: 13.5px;
            color: var(--text-light);
            line-height: 1.6;
        }

        /* ===== HARDCODED COMMON FOOTER ===== */
        footer {
            background: #ffffff;
            border-top: 1px solid var(--border-color);
            padding: 48px 0;
            margin-top: auto;
        }

        .footer-grid {
            display: flex;
            flex-direction: column;
            gap: 24px;
            align-items: center;
            text-align: center;
        }

        @media (min-width: 768px) {
            .footer-grid {
                flex-direction: row;
                justify-content: space-between;
                text-align: left;
            }
        }

        .footer-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .footer-brand {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .footer-copyright {
            font-size: 12px;
            color: var(--text-light);
        }

        .footer-links {
            display: flex;
            gap: 24px;
            font-weight: 600;
            font-size: 13px;
            color: var(--text-light);
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>

    <!-- COMMON HEADER -->
    <header>
        <div class="container">
            <div class="navbar">
                <a href="/" class="nav-logo">
                    <?php if (!empty($settings['logo_path']) && file_exists(__DIR__ . $settings['logo_path'])): ?>
                        <img src="<?= $logoPath ?>" alt="<?= $siteName ?> Logo">
                    <?php else: ?>
                        <i class="fas fa-crown" style="color: var(--primary-color)"></i>
                    <?php endif; ?>
                    <span><?= $siteName ?></span>
                </a>
                
                <div class="nav-links">
                    <a href="#features">Features</a>
                    <a href="#about">About</a>
                    <a href="mailto:<?= $supportEmail ?>">Support</a>
                </div>
                
                <div class="nav-actions">
                    <a href="/login.php" class="btn-link">Sign In</a>
                    <a href="/register.php" class="btn-theme">Register</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- TEMPLATE 1: CLASSIC PERFORMANCE NETWORK -->
        <?php if ($templateId === 'classic_hero'): ?>
            <section class="tpl-classic">
                <div class="container">
                    <div class="hero-grid">
                        <div class="hero-content">
                            <h1 class="hero-title"><?= htmlspecialchars($homepage['hero_title']) ?></h1>
                            <p class="hero-subtitle"><?= htmlspecialchars($homepage['hero_subtitle']) ?></p>
                            
                            <div class="cta-stack">
                                <a href="<?= htmlspecialchars($homepage['hero_cta_url']) ?>" class="cta-primary">
                                    <?= htmlspecialchars($homepage['hero_cta_text']) ?>
                                </a>
                                <a href="/login.php" class="cta-secondary">Member Login &rarr;</a>
                            </div>
                        </div>
                        
                        <div class="hero-mockup">
                            <div class="mockup-container">
                                <div class="mockup-header">
                                    <span style="font-size: 11px; font-weight: 800; color: var(--text-dark);">
                                        <span class="mockup-dot"></span> Live Analytics Node
                                    </span>
                                    <span style="font-size: 9px; font-weight: 700; color: var(--text-light);">Global Routing</span>
                                </div>
                                <div class="mockup-stats">
                                    <div class="mockup-stat-box">
                                        <div class="mockup-label">Clicks</div>
                                        <div class="mockup-val">120K+</div>
                                    </div>
                                    <div class="mockup-stat-box">
                                        <div class="mockup-label">Conversions</div>
                                        <div class="mockup-val">4.5K+</div>
                                    </div>
                                    <div class="mockup-stat-box">
                                        <div class="mockup-label">Status</div>
                                        <div class="mockup-val" style="color: #10b981;">Online</div>
                                    </div>
                                </div>
                                <div style="font-size: 11px; color: var(--text-light); text-align: center;">
                                    Powered by modern sub-second routing servers.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        
        <!-- TEMPLATE 2: CENTERED SaaS -->
        <?php elseif ($templateId === 'centered_saas'): ?>
            <section class="tpl-centered">
                <div class="container">
                    <div class="hero-content">
                        <h1 class="hero-title"><?= htmlspecialchars($homepage['hero_title']) ?></h1>
                        <p class="hero-subtitle"><?= htmlspecialchars($homepage['hero_subtitle']) ?></p>
                        
                        <div class="cta-stack">
                            <a href="<?= htmlspecialchars($homepage['hero_cta_url']) ?>" class="cta-primary">
                                <?= htmlspecialchars($homepage['hero_cta_text']) ?>
                            </a>
                            <a href="/login.php" class="cta-secondary">Sign In Portal</a>
                        </div>
                    </div>
                </div>
            </section>
            
        <!-- TEMPLATE 3: SPLIT PORTALS -->
        <?php elseif ($templateId === 'split_portals'): ?>
            <section class="tpl-split">
                <div class="container" style="text-align: center;">
                    <h1 class="hero-title" style="margin-bottom: 12px;"><?= htmlspecialchars($homepage['hero_title']) ?></h1>
                    <p class="hero-subtitle" style="margin: 0 auto 36px;"><?= htmlspecialchars($homepage['hero_subtitle']) ?></p>
                    
                    <div class="split-grid">
                        <!-- Publisher Card -->
                        <div class="portal-card">
                            <div>
                                <div class="portal-icon"><i class="fas fa-users"></i></div>
                                <div class="portal-title">Publisher Program</div>
                                <p class="portal-desc">Access high-converting offers, live postbacks, and premium payouts. Join as an affiliate publisher and start monetizing your traffic today.</p>
                            </div>
                            <a href="/register.php" class="cta-primary" style="display: block; text-align: center;">Apply as Publisher &rarr;</a>
                        </div>
                        
                        <!-- Advertiser Card -->
                        <div class="portal-card">
                            <div>
                                <div class="portal-icon"><i class="fas fa-bullhorn"></i></div>
                                <div class="portal-title">Advertiser Gateway</div>
                                <p class="portal-desc">Manage and scale your affiliate offers, track lead performance with pixel integrations, and secure quality publication inventory.</p>
                            </div>
                            <a href="/register.php" class="cta-secondary" style="display: block; text-align: center;">Register Advertiser &rarr;</a>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- DYNAMIC FEATURES SECTION -->
        <?php if (!empty($features)): ?>
            <section class="features-section" id="features">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title">Network Features</h2>
                        <p style="color: var(--text-light); font-size: 14px;">High-tech tools engineered to secure your conversions and payouts</p>
                    </div>
                    
                    <div class="features-grid">
                        <?php foreach ($features as $f): ?>
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas <?= htmlspecialchars($f['icon'] ?? 'fa-check-circle') ?>"></i>
                                </div>
                                <div class="feature-name"><?= htmlspecialchars($f['title'] ?? 'Feature') ?></div>
                                <p class="feature-desc"><?= htmlspecialchars($f['desc'] ?? '') ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- ABOUT SECTION -->
        <?php if (!empty($homepage['about_text'])): ?>
            <section style="padding: 80px 0; border-bottom: 1px solid var(--border-color);" id="about">
                <div class="container" style="max-width: 800px; text-align: center;">
                    <h2 class="section-title" style="margin-bottom: 16px;">About Us</h2>
                    <p style="font-size: 15px; color: var(--text-medium); line-height: 1.7;"><?= nl2br(htmlspecialchars($homepage['about_text'])) ?></p>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- COMMON FOOTER -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-info">
                    <span class="footer-brand"><?= $siteName ?></span>
                    <span class="footer-copyright">&copy; <?= date('Y') ?> All rights reserved.</span>
                </div>
                
                <div class="footer-links">
                    <a href="#">Terms of Service</a>
                    <a href="#">Privacy Policy</a>
                    <a href="mailto:<?= $supportEmail ?>">Contact Support</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
