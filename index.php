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
            'social_links_json' => json_encode(['telegram' => '', 'teams' => '']),
            'layout_json' => null
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
        $ins->execute([
            'tenant_id' => $tenantId,
            'template_id' => $homepage['template_id'],
            'hero_title' => $homepage['hero_title'],
            'hero_subtitle' => $homepage['hero_subtitle'],
            'hero_cta_text' => $homepage['hero_cta_text'],
            'hero_cta_url' => $homepage['hero_cta_url'],
            'features_json' => $homepage['features_json'],
            'about_text' => $homepage['about_text'],
            'contact_email' => $homepage['contact_email'],
            'social_links_json' => $homepage['social_links_json']
        ]);
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
        'social_links_json' => '{}',
        'layout_json' => null
    ];
}

// Pre-fill or build layout_json from legacy if empty
$layoutJson = $homepage['layout_json'] ?? '';
if (empty($layoutJson)) {
    // Decode legacy features
    $legacyFeatures = json_decode($homepage['features_json'] ?? '[]', true) ?: [];
    if (empty($legacyFeatures)) {
        $legacyFeatures = [
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
    }

    $defaultSections = [
        [
            'id' => 'sec_' . uniqid(),
            'type' => 'hero',
            'settings' => [
                'title' => $homepage['hero_title'] ?? 'Start Your Premium Affiliate Network',
                'subtitle' => $homepage['hero_subtitle'] ?? 'Track conversions, manage payouts, and grow your affiliate partnerships with zero latency.',
                'cta_text' => $homepage['hero_cta_text'] ?? 'Apply as Partner',
                'cta_url' => $homepage['hero_cta_url'] ?? '/register.php',
                'secondary_cta_text' => 'Member Login',
                'secondary_cta_url' => '/login.php',
                'layout_style' => ($homepage['template_id'] ?? 'classic_hero') === 'centered_saas' ? 'centered' : 'split_right',
                'bg_type' => 'gradient',
                'bg_color' => '#2563eb',
                'bg_gradient' => 'linear-gradient(135deg, #2563eb 0%, #1e40af 100%)',
                'bg_image' => '',
                'image_url' => ''
            ]
        ]
    ];

    if (($homepage['template_id'] ?? 'classic_hero') === 'split_portals') {
        $defaultSections[] = [
            'id' => 'sec_' . uniqid(),
            'type' => 'split_content',
            'settings' => [
                'title' => 'Publisher Program',
                'subtitle' => 'Access high-converting offers, live postbacks, and premium payouts.',
                'description' => 'Join as an affiliate publisher and start monetizing your traffic today.',
                'image_url' => '',
                'image_align' => 'left',
                'cta_text' => 'Apply as Publisher',
                'cta_url' => '/register.php',
                'bg_color' => '#ffffff'
            ]
        ];
        $defaultSections[] = [
            'id' => 'sec_' . uniqid(),
            'type' => 'split_content',
            'settings' => [
                'title' => 'Advertiser Gateway',
                'subtitle' => 'Manage and scale your affiliate offers, track lead performance.',
                'description' => 'Manage and scale your affiliate offers, track lead performance with pixel integrations, and secure quality publication inventory.',
                'image_url' => '',
                'image_align' => 'right',
                'cta_text' => 'Register Advertiser',
                'cta_url' => '/register.php',
                'bg_color' => '#f8fafc'
            ]
        ];
    } else {
        $defaultSections[] = [
            'id' => 'sec_' . uniqid(),
            'type' => 'features',
            'settings' => [
                'title' => 'Network Features',
                'subtitle' => 'High-tech tools engineered to secure your conversions and payouts',
                'columns' => 3,
                'items' => $legacyFeatures
            ]
        ];
    }

    if (!empty($homepage['about_text'])) {
        $defaultSections[] = [
            'id' => 'sec_' . uniqid(),
            'type' => 'split_content',
            'settings' => [
                'title' => 'About Us',
                'subtitle' => 'Who we are and what we stand for',
                'description' => $homepage['about_text'],
                'image_url' => '',
                'image_align' => 'right',
                'cta_text' => '',
                'cta_url' => '',
                'bg_color' => '#ffffff'
            ]
        ];
    }

    $layoutJson = json_encode($defaultSections);

    // Save back to DB so it heals immediately
    try {
        $upd = $pdo->prepare("UPDATE tenant_homepages SET layout_json = :layout_json WHERE tenant_id = :tenant_id");
        $upd->execute([
            'layout_json' => $layoutJson,
            'tenant_id' => $tenantId
        ]);
    } catch (Exception $ex) {
        error_log("Failed to save dynamic homepage layout: " . $ex->getMessage());
    }
}

// Decode layout sections
$sections = json_decode($layoutJson, true) ?: [];
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
        <?php foreach ($sections as $sec):
            $s = $sec['settings'] ?? [];
            $type = $sec['type'] ?? '';
            $id = htmlspecialchars($sec['id'] ?? '');
        ?>

            <!-- HERO SECTION -->
            <?php if ($type === 'hero'):
                $bgStyle = '';
                $textClass = 'text-white';
                if (($s['bg_type'] ?? 'gradient') === 'gradient') {
                    $bgStyle = 'background: var(--primary-gradient);';
                } elseif (($s['bg_type'] ?? 'gradient') === 'color') {
                    $bgColor = $s['bg_color'] ?? '#2563eb';
                    $bgStyle = 'background: ' . $bgColor . ';';
                    if (in_array(strtolower(trim($bgColor)), ['#fff', '#ffffff', 'white', '#f8fafc', '#f1f5f9', '#e2e8f0'])) {
                        $textClass = 'text-dark';
                    }
                } elseif (($s['bg_type'] ?? 'gradient') === 'image' && !empty($s['bg_image'])) {
                    $bgStyle = 'background: linear-gradient(rgba(15, 23, 42, 0.6), rgba(15, 23, 42, 0.6)), url(' . htmlspecialchars($s['bg_image']) . ') center/cover no-repeat;';
                } else {
                    $bgStyle = 'background: var(--primary-gradient);';
                }
                
                $layout = $s['layout_style'] ?? 'split_right';
            ?>
                <section class="hero-block <?= $textClass ?>" style="<?= $bgStyle ?> padding: 80px 0; border-bottom: 1px solid var(--border-color);" id="<?= $id ?>">
                    <div class="container">
                        <?php if ($layout === 'centered'): ?>
                            <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                                <h1 class="hero-title" style="<?= $textClass === 'text-dark' ? '' : 'color: #ffffff;' ?> margin-bottom: 20px; font-family: var(--font-display); font-weight: 900; line-height: 1.15; letter-spacing: -0.8px;"><?= htmlspecialchars($s['title'] ?? '') ?></h1>
                                <p class="hero-subtitle" style="<?= $textClass === 'text-dark' ? 'color: var(--text-medium);' : 'color: rgba(255,255,255,0.9);' ?> margin-bottom: 36px; font-size: 17px; line-height: 1.6;"><?= htmlspecialchars($s['subtitle'] ?? '') ?></p>
                                <div class="cta-stack" style="justify-content: center; display: flex; gap: 14px; flex-wrap: wrap;">
                                    <?php if (!empty($s['cta_text'])): ?>
                                        <a href="<?= htmlspecialchars($s['cta_url'] ?? '#') ?>" class="btn-theme" style="<?= $textClass === 'text-dark' ? 'background: var(--primary-color); color: #ffffff;' : 'background: #ffffff; color: var(--primary-color);' ?> padding: 14px 28px; font-weight: 700; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,0.15); transition: all 0.25s;"><?= htmlspecialchars($s['cta_text']) ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($s['secondary_cta_text'])): ?>
                                        <a href="<?= htmlspecialchars($s['secondary_cta_url'] ?? '#') ?>" class="cta-secondary" style="<?= $textClass === 'text-dark' ? 'border: 2px solid var(--border-color); color: var(--text-dark);' : 'border: 2px solid rgba(255,255,255,0.4); color: #ffffff;' ?> padding: 14px 28px; font-weight: 700; border-radius: 12px; transition: all 0.25s;"><?= htmlspecialchars($s['secondary_cta_text']) ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: 
                            $imgLeft = ($layout === 'split_left');
                        ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 48px; align-items: center;">
                                <div style="order: <?= $imgLeft ? '2' : '1' ?>;">
                                    <h1 class="hero-title" style="<?= $textClass === 'text-dark' ? '' : 'color: #ffffff;' ?> margin-bottom: 20px; font-family: var(--font-display); font-weight: 900; line-height: 1.15; letter-spacing: -0.8px; font-size: 44px;"><?= htmlspecialchars($s['title'] ?? '') ?></h1>
                                    <p class="hero-subtitle" style="<?= $textClass === 'text-dark' ? 'color: var(--text-medium);' : 'color: rgba(255,255,255,0.9);' ?> margin-bottom: 36px; font-size: 16px; line-height: 1.6;"><?= htmlspecialchars($s['subtitle'] ?? '') ?></p>
                                    <div class="cta-stack" style="display: flex; gap: 14px; flex-wrap: wrap;">
                                        <?php if (!empty($s['cta_text'])): ?>
                                            <a href="<?= htmlspecialchars($s['cta_url'] ?? '#') ?>" class="btn-theme" style="<?= $textClass === 'text-dark' ? 'background: var(--primary-color); color: #ffffff;' : 'background: #ffffff; color: var(--primary-color);' ?> padding: 14px 28px; font-weight: 700; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,0.15); transition: all 0.25s;"><?= htmlspecialchars($s['cta_text']) ?></a>
                                        <?php endif; ?>
                                        <?php if (!empty($s['secondary_cta_text'])): ?>
                                            <a href="<?= htmlspecialchars($s['secondary_cta_url'] ?? '#') ?>" class="cta-secondary" style="<?= $textClass === 'text-dark' ? 'border: 2px solid var(--border-color); color: var(--text-dark);' : 'border: 2px solid rgba(255,255,255,0.4); color: #ffffff;' ?> padding: 14px 28px; font-weight: 700; border-radius: 12px; transition: all 0.25s;"><?= htmlspecialchars($s['secondary_cta_text']) ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="order: <?= $imgLeft ? '1' : '2' ?>;">
                                    <?php if (!empty($s['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($s['image_url']) ?>" alt="Hero Illustration" style="max-width: 100%; border-radius: 16px; box-shadow: 0 15px 30px rgba(0,0,0,0.15);">
                                    <?php else: ?>
                                        <!-- Fallback standard performance node illustration -->
                                        <div class="mockup-container" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); border-radius: 20px; padding: 24px; box-shadow: 0 20px 40px -15px rgba(0,0,0,0.2);">
                                            <div class="mockup-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 12px; margin-bottom: 18px;">
                                                <span style="font-size: 11px; font-weight: 800; color: <?= $textClass === 'text-dark' ? 'var(--text-dark)' : '#ffffff' ?>;">
                                                    <span class="mockup-dot" style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block;"></span> Live Analytics Node
                                                </span>
                                                <span style="font-size: 9px; font-weight: 700; color: <?= $textClass === 'text-dark' ? 'var(--text-light)' : 'rgba(255,255,255,0.7)' ?>;">Global Routing</span>
                                            </div>
                                            <div class="mockup-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 18px;">
                                                <div class="mockup-stat-box" style="background: rgba(255,255,255,0.9); border-radius: 10px; padding: 10px; text-align: center;">
                                                    <div class="mockup-label" style="font-size: 9px; color: var(--text-light); text-transform: uppercase; font-weight: 700;">Clicks</div>
                                                    <div class="mockup-val" style="font-size: 14px; font-weight: 800; color: var(--text-dark); margin-top: 4px;">120K+</div>
                                                </div>
                                                <div class="mockup-stat-box" style="background: rgba(255,255,255,0.9); border-radius: 10px; padding: 10px; text-align: center;">
                                                    <div class="mockup-label" style="font-size: 9px; color: var(--text-light); text-transform: uppercase; font-weight: 700;">Conversions</div>
                                                    <div class="mockup-val" style="font-size: 14px; font-weight: 800; color: var(--text-dark); margin-top: 4px;">4.5K+</div>
                                                </div>
                                                <div class="mockup-stat-box" style="background: rgba(255,255,255,0.9); border-radius: 10px; padding: 10px; text-align: center;">
                                                    <div class="mockup-label" style="font-size: 9px; color: var(--text-light); text-transform: uppercase; font-weight: 700;">Status</div>
                                                    <div class="mockup-val" style="font-size: 14px; font-weight: 800; color: #10b981; margin-top: 4px;">Online</div>
                                                </div>
                                            </div>
                                            <div style="font-size: 11px; color: <?= $textClass === 'text-dark' ? 'var(--text-medium)' : 'rgba(255,255,255,0.8)' ?>; text-align: center;">
                                                Powered by modern sub-second routing servers.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                
            <!-- FEATURES GRID SECTION -->
            <?php elseif ($type === 'features'):
                $cols = $s['columns'] ?? 3;
                $gridStyle = 'repeat(auto-fit, minmax(280px, 1fr))';
                if ($cols == 2) $gridStyle = 'repeat(auto-fit, minmax(400px, 1fr))';
                elseif ($cols == 4) $gridStyle = 'repeat(auto-fit, minmax(220px, 1fr))';
            ?>
                <section class="features-section" style="padding: 80px 0; background: var(--bg-alt); border-bottom: 1px solid var(--border-color);" id="<?= $id ?>">
                    <div class="container">
                        <div class="section-header" style="text-align: center; margin-bottom: 48px;">
                            <h2 class="section-title" style="font-family: var(--font-display); font-size: 32px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;"><?= htmlspecialchars($s['title'] ?? '') ?></h2>
                            <p style="color: var(--text-light); font-size: 15px; max-width: 600px; margin: 0 auto;"><?= htmlspecialchars($s['subtitle'] ?? '') ?></p>
                        </div>
                        
                        <div class="features-grid" style="display: grid; grid-template-columns: <?= $gridStyle ?>; gap: 24px;">
                            <?php foreach (($s['items'] ?? []) as $f): ?>
                                <div class="feature-card" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 28px; transition: all 0.25s;">
                                    <div class="feature-icon" style="font-size: 24px; color: var(--primary-color); margin-bottom: 16px;">
                                        <i class="fas <?= htmlspecialchars($f['icon'] ?? 'fa-check-circle') ?>"></i>
                                    </div>
                                    <div class="feature-name" style="font-family: var(--font-display); font-size: 18px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;"><?= htmlspecialchars($f['title'] ?? '') ?></div>
                                    <p class="feature-desc" style="font-size: 13.5px; color: var(--text-light); line-height: 1.6;"><?= htmlspecialchars($f['desc'] ?? '') ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

            <!-- SPLIT CONTENT SECTION -->
            <?php elseif ($type === 'split_content'):
                $alignLeft = (($s['image_align'] ?? 'right') === 'left');
            ?>
                <section class="split-content-section" style="padding: 80px 0; background: <?= htmlspecialchars($s['bg_color'] ?? '#ffffff') ?>; border-bottom: 1px solid var(--border-color);" id="<?= $id ?>">
                    <div class="container">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 48px; align-items: center;">
                            <div style="order: <?= $alignLeft ? '2' : '1' ?>;">
                                <?php if (!empty($s['subtitle'])): ?>
                                    <span style="font-size: 11px; font-weight: 800; color: var(--primary-color); text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 8px;"><?= htmlspecialchars($s['subtitle']) ?></span>
                                <?php endif; ?>
                                <h2 class="section-title" style="font-family: var(--font-display); font-size: 32px; font-weight: 800; color: var(--text-dark); margin-bottom: 16px; line-height: 1.2;"><?= htmlspecialchars($s['title'] ?? '') ?></h2>
                                <p style="font-size: 15px; color: var(--text-medium); line-height: 1.7; margin-bottom: 24px; white-space: pre-line;"><?= htmlspecialchars($s['description'] ?? '') ?></p>
                                
                                <?php if (!empty($s['cta_text'])): ?>
                                    <a href="<?= htmlspecialchars($s['cta_url'] ?? '#') ?>" class="btn-theme" style="display: inline-block; font-size: 14px; font-weight: 700; background: var(--primary-color); color: #ffffff; padding: 12px 24px; border-radius: 10px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15); transition: all 0.25s;"><?= htmlspecialchars($s['cta_text']) ?></a>
                                <?php endif; ?>
                            </div>
                            
                            <div style="order: <?= $alignLeft ? '1' : '2' ?>; text-align: center;">
                                <?php if (!empty($s['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($s['image_url']) ?>" alt="Split Graphic" style="max-width: 100%; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: 1px solid var(--border-color);">
                                <?php else: ?>
                                    <!-- Fallback standard mockup icon/box -->
                                    <div style="width: 120px; height: 120px; border-radius: 50%; background: rgba(37, 99, 235, 0.05); color: var(--primary-color); display: inline-flex; align-items: center; justify-content: center; font-size: 44px; margin: 0 auto;">
                                        <i class="fas fa-layer-group"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

            <!-- STATS GRID SECTION -->
            <?php elseif ($type === 'stats'): ?>
                <section class="stats-section" style="padding: 60px 0; background: <?= htmlspecialchars($s['bg_color'] ?? '#f8fafc') ?>; border-bottom: 1px solid var(--border-color);" id="<?= $id ?>">
                    <div class="container">
                        <?php if (!empty($s['title'])): ?>
                            <h3 style="font-family: var(--font-display); font-size: 24px; font-weight: 800; color: var(--text-dark); text-align: center; margin-bottom: 36px;"><?= htmlspecialchars($s['title']) ?></h3>
                        <?php endif; ?>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <?php foreach (($s['items'] ?? []) as $item): ?>
                                <div class="stat-card" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
                                    <?php if (!empty($item['icon'])): ?>
                                        <div style="font-size: 20px; color: var(--primary-color); margin-bottom: 8px;">
                                            <i class="fas <?= htmlspecialchars($item['icon']) ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div style="font-family: var(--font-display); font-size: 28px; font-weight: 900; color: var(--text-dark); line-height: 1.1;"><?= htmlspecialchars($item['number'] ?? '') ?></div>
                                    <div style="font-size: 12.5px; color: var(--text-light); font-weight: 600; margin-top: 4px;"><?= htmlspecialchars($item['label'] ?? '') ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

            <!-- TESTIMONIALS SECTION -->
            <?php elseif ($type === 'testimonials'): ?>
                <section class="testimonials-section" style="padding: 80px 0; background: #ffffff; border-bottom: 1px solid var(--border-color);" id="<?= $id ?>">
                    <div class="container">
                        <div class="section-header" style="text-align: center; margin-bottom: 48px;">
                            <h2 class="section-title" style="font-family: var(--font-display); font-size: 32px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;"><?= htmlspecialchars($s['title'] ?? '') ?></h2>
                            <p style="color: var(--text-light); font-size: 15px; max-width: 600px; margin: 0 auto;"><?= htmlspecialchars($s['subtitle'] ?? '') ?></p>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                            <?php foreach (($s['items'] ?? []) as $t): ?>
                                <div class="testimonial-card" style="background: var(--bg-alt); border: 1px solid var(--border-color); border-radius: 16px; padding: 28px; display: flex; flex-direction: column; justify-content: space-between;">
                                    <p style="font-size: 14px; color: var(--text-medium); line-height: 1.6; font-style: italic; margin-bottom: 20px;">"<?= htmlspecialchars($t['quote'] ?? '') ?>"</p>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <?php if (!empty($t['avatar'])): ?>
                                            <img src="<?= htmlspecialchars($t['avatar']) ?>" alt="Avatar" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--primary-color); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                                                <?= strtoupper(substr($t['name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight: 700; font-size: 14px; color: var(--text-dark);"><?= htmlspecialchars($t['name'] ?? '') ?></div>
                                            <div style="font-size: 11.5px; color: var(--text-light); font-weight: 500;"><?= htmlspecialchars($t['role'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

            <!-- FAQ SECTION -->
            <?php elseif ($type === 'faq'): ?>
                <section class="faq-section" style="padding: 80px 0; background: var(--bg-alt); border-bottom: 1px solid var(--border-color);" id="<?= $id ?>">
                    <div class="container" style="max-width: 800px;">
                        <div class="section-header" style="text-align: center; margin-bottom: 48px;">
                            <h2 class="section-title" style="font-family: var(--font-display); font-size: 32px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;"><?= htmlspecialchars($s['title'] ?? '') ?></h2>
                            <p style="color: var(--text-light); font-size: 15px;"><?= htmlspecialchars($s['subtitle'] ?? '') ?></p>
                        </div>
                        
                        <div class="faq-list" style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach (($s['items'] ?? []) as $faqIdx => $faq): ?>
                                <details style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px; padding: 16px 20px; cursor: pointer; transition: all 0.25s;" <?= $faqIdx === 0 ? 'open' : '' ?>>
                                    <summary style="font-weight: 700; font-size: 15px; color: var(--text-dark); display: flex; justify-content: space-between; align-items: center; outline: none; list-style: none;">
                                        <span><?= htmlspecialchars($faq['question'] ?? '') ?></span>
                                        <span style="font-size: 12px; color: var(--primary-color);"><i class="fas fa-chevron-down"></i></span>
                                    </summary>
                                    <p style="font-size: 13.5px; color: var(--text-medium); line-height: 1.6; margin-top: 12px; cursor: default; border-top: 1px solid var(--border-color); padding-top: 12px;"><?= nl2br(htmlspecialchars($faq['answer'] ?? '')) ?></p>
                                </details>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

            <!-- CTA BANNER SECTION -->
            <?php elseif ($type === 'cta_banner'):
                $bgColor = $s['bg_color'] ?? '#0f172a';
                $textColorClass = 'text-white';
                if (in_array(strtolower(trim($bgColor)), ['#fff', '#ffffff', 'white', '#f8fafc', '#f1f5f9'])) {
                    $textColorClass = 'text-dark';
                }
            ?>
                <section class="cta-banner-section" style="padding: 80px 0; background: <?= htmlspecialchars($bgColor) ?>; border-bottom: 1px solid var(--border-color);" id="<?= $id ?>">
                    <div class="container" style="text-align: center; max-width: 800px;">
                        <h2 style="font-family: var(--font-display); font-size: 32px; font-weight: 800; <?= $textColorClass === 'text-dark' ? '' : 'color: #ffffff;' ?> margin-bottom: 12px; line-height: 1.25;"><?= htmlspecialchars($s['title'] ?? '') ?></h2>
                        <p style="font-size: 15px; <?= $textColorClass === 'text-dark' ? 'color: var(--text-medium);' : 'color: rgba(255,255,255,0.8);' ?> margin-bottom: 28px; line-height: 1.6;"><?= htmlspecialchars($s['subtitle'] ?? '') ?></p>
                        
                        <?php if (!empty($s['cta_text'])): ?>
                            <a href="<?= htmlspecialchars($s['cta_url'] ?? '#') ?>" class="btn-theme" style="display: inline-block; font-size: 14px; font-weight: 700; background: var(--primary-color); color: #ffffff; padding: 12px 24px; border-radius: 10px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15); transition: all 0.25s;"><?= htmlspecialchars($s['cta_text']) ?></a>
                        <?php endif; ?>
                    </div>
                </section>

            <!-- TRUST BADGES SECTION -->
            <?php elseif ($type === 'trust_badges'): ?>
                <section class="trust-badges-section" style="padding: 48px 0; background: #ffffff; border-bottom: 1px solid var(--border-color);" id="<?= $id ?>">
                    <div class="container">
                        <?php if (!empty($s['title'])): ?>
                            <div style="font-family: var(--font-sans); font-size: 11px; font-weight: 700; color: var(--text-light); text-transform: uppercase; text-align: center; letter-spacing: 1.5px; margin-bottom: 24px;"><?= htmlspecialchars($s['title']) ?></div>
                        <?php endif; ?>
                        
                        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 40px;">
                            <?php foreach (($s['items'] ?? []) as $badge): ?>
                                <?php if (!empty($badge['image_url'])): ?>
                                    <a href="<?= htmlspecialchars($badge['link'] ?? '#') ?>" style="opacity: 0.6; transition: opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6">
                                        <img src="<?= htmlspecialchars($badge['image_url']) ?>" alt="Partner Logo" style="height: 32px; max-width: 140px; object-fit: contain;">
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

        <?php endforeach; ?>
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
