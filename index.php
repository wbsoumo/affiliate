<?php
/**
 * Taskbazi SaaS Affiliate Tracker - Enterprise Landing Page
 * Inspired by Affise and Offer18
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
            'color' => '#2563eb'
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
    <title>Taskbazi · Enterprise Affiliate Tracking & Partner Marketing Platform</title>
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
            --primary: #2563eb;
            --primary-light: #eff6ff;
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            --border-color: rgba(226, 232, 240, 0.8);
            --shadow-premium: 0 20px 40px -15px rgba(15, 23, 42, 0.05);
            --shadow-card: 0 10px 30px -10px rgba(15, 23, 42, 0.03);
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
            background: rgba(255, 255, 255, 0.9);
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
            font-size: 26px;
            font-weight: 900;
            color: var(--text-dark);
            text-decoration: none;
        }

        .logo i {
            color: var(--primary);
            filter: drop-shadow(0 4px 6px rgba(37, 99, 235, 0.2));
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
            padding: 12px 26px;
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
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.15);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(37, 99, 235, 0.3);
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
            padding: 90px 0 120px;
            background: radial-gradient(circle at top right, rgba(37, 99, 235, 0.05) 0%, transparent 60%);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 48px;
            align-items: center;
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
            font-size: clamp(38px, 6vw, 62px);
            font-weight: 900;
            line-height: 1.12;
            color: var(--text-dark);
            margin-bottom: 24px;
            letter-spacing: -0.03em;
        }

        .gradient-text {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
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
        }

        .floating-mockup {
            position: relative;
            width: 100%;
            animation: floatAnim 6s ease-in-out infinite;
        }

        .floating-mockup img {
            width: 100%;
            height: auto;
            border-radius: 24px;
            box-shadow: 0 30px 60px -15px rgba(15, 23, 42, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.04);
        }

        @keyframes floatAnim {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* TRUSTED BY & METRICS */
        .metrics-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-top: 60px;
        }

        .metric-card {
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            box-shadow: var(--shadow-premium);
        }

        .metric-num {
            font-family: var(--font-display);
            font-size: 38px;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: 4px;
            line-height: 1;
        }

        .metric-label {
            color: var(--text-light);
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
        }

        /* SECTION HEADERS */
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-family: var(--font-display);
            font-size: 38px;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }

        .section-header p {
            color: var(--text-light);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* INTERACTIVE SOLUTIONS SEGMENT TABS (Affise/Offer18 style) */
        .solutions-section {
            background: var(--bg-white);
            padding: 100px 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .tabs-header {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-bottom: 48px;
        }

        .tab-btn {
            background-color: var(--bg-main);
            border: 1.5px solid var(--border-color);
            color: var(--text-medium);
            padding: 14px 28px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .tab-btn.active {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.15);
        }

        .tab-content {
            display: none;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            animation: fadeIn 0.4s ease;
        }

        .tab-content.active {
            display: grid;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .tab-info h3 {
            font-family: var(--font-display);
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 16px;
        }

        .tab-info p {
            color: var(--text-light);
            font-size: 15px;
            margin-bottom: 24px;
            line-height: 1.7;
        }

        .tab-features-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .tab-features-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-medium);
        }

        .tab-features-list li i {
            color: var(--primary);
        }

        .tab-image img {
            width: 100%;
            height: auto;
            border-radius: 20px;
            box-shadow: var(--shadow-premium), 0 0 0 1px rgba(0,0,0,0.03);
        }

        /* COMPREHENSIVE PRODUCT FEATURES GRID */
        .features-section {
            padding: 100px 0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        .feature-card {
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow-card);
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            border-color: rgba(37, 99, 235, 0.25);
            box-shadow: 0 20px 45px -10px rgba(37, 99, 235, 0.08);
        }

        .feat-icon {
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
        }

        .feat-title {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .feat-desc {
            color: var(--text-medium);
            font-size: 14px;
            line-height: 1.6;
        }

        /* DETAILED WORKFLOW MATRIX */
        .workflow-section {
            background: var(--bg-white);
            padding: 100px 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .workflow-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .workflow-image img {
            width: 100%;
            height: auto;
            border-radius: 24px;
            box-shadow: var(--shadow-premium), 0 0 0 1px rgba(0,0,0,0.03);
        }

        .steps-container {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .step-item {
            display: flex;
            gap: 20px;
        }

        .step-num {
            width: 44px;
            height: 44px;
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

        .step-info h3 {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .step-info p {
            font-size: 14px;
            color: var(--text-light);
        }

        /* LIVE TELEMETRY CONSOLE WIDGET */
        .console-container {
            background-color: #0f172a;
            border-radius: 16px;
            padding: 24px;
            font-family: 'Courier New', Courier, monospace;
            box-shadow: 0 25px 50px rgba(0,0,0,0.18);
            color: #38bdf8;
            font-size: 13px;
            height: 340px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
            margin-top: 40px;
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

        .dot { width: 8px; height: 8px; border-radius: 50%; }
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

        .console-body::-webkit-scrollbar { display: none; }

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

        /* SYSTEM COMPARISON MATRIX */
        .comparison-section {
            padding: 100px 0;
        }

        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg-white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-premium);
            border: 1px solid var(--border-color);
            margin-top: 40px;
        }

        .comparison-table th, .comparison-table td {
            padding: 20px 24px;
            text-align: left;
        }

        .comparison-table th {
            background-color: var(--text-dark);
            color: white;
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 700;
        }

        .comparison-table td {
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        .comparison-table tr:last-child td {
            border-bottom: none;
        }

        .comparison-table tr:hover td {
            background-color: var(--bg-main);
        }

        .compare-label {
            font-weight: 700;
            color: var(--text-dark);
            width: 30%;
        }

        .compare-taskbazi {
            font-weight: 600;
            color: var(--primary);
            background-color: rgba(37, 99, 235, 0.02);
            width: 35%;
        }

        .compare-legacy {
            color: var(--text-light);
            width: 35%;
        }

        /* PRICING PLANS */
        .pricing-section {
            background: var(--bg-white);
            padding: 100px 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            margin-top: 48px;
        }

        .price-card {
            background: var(--bg-main);
            border: 1.5px solid var(--border-color);
            border-radius: 24px;
            padding: 48px 32px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            transition: all 0.3s;
        }

        .price-card:hover {
            transform: translateY(-8px);
            background-color: white;
            border-color: rgba(37, 99, 235, 0.25);
            box-shadow: 0 25px 50px -15px rgba(15, 23, 42, 0.08);
        }

        .price-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-tier-name {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
        }

        .price-val {
            font-family: var(--font-display);
            font-size: 38px;
            font-weight: 900;
            color: var(--text-dark);
        }

        .price-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
            margin-top: 10px;
        }

        .price-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: var(--text-medium);
        }

        .price-features li i {
            color: #10b981;
        }

        .price-btn {
            width: 100%;
            margin-top: auto;
            padding: 14px;
            text-align: center;
        }

        /* TECHNICAL ACCORDION FAQ */
        .faq-section {
            padding: 100px 0;
        }

        .faq-accordion {
            max-width: 800px;
            margin: 48px auto 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .faq-item {
            background-color: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: var(--shadow-card);
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
            border-color: rgba(37, 99, 235, 0.3);
            box-shadow: 0 15px 30px rgba(37, 99, 235, 0.05);
        }

        .faq-item.active .faq-trigger i {
            transform: rotate(180deg);
        }

        /* SECURITY & COMPLIANCE BADGES */
        .security-badge-grid {
            display: flex;
            justify-content: center;
            gap: 48px;
            flex-wrap: wrap;
            margin-top: 60px;
            border-top: 1px solid var(--border-color);
            padding-top: 40px;
        }

        .security-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .security-badge i {
            font-size: 20px;
            color: #10b981;
        }

        /* SUPPORT CALLOUT BOX */
        .callout-section {
            padding: 80px 0;
            text-align: center;
            background: var(--bg-white);
            border-top: 1px solid var(--border-color);
        }

        .callout-box {
            background: linear-gradient(135deg, var(--primary-light) 0%, rgba(255,255,255,0) 100%);
            border: 1.5px solid rgba(37, 99, 235, 0.15);
            border-radius: 30px;
            padding: 60px;
            max-width: 900px;
            margin: 0 auto;
        }

        .callout-box h2 {
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .callout-box p {
            color: var(--text-light);
            font-size: 16px;
            margin-bottom: 32px;
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
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.25);
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
            font-size: 15px;
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
            .hero-grid, .tab-content, .workflow-grid {
                grid-template-columns: 1fr;
                gap: 48px;
            }
            .hero-content {
                text-align: center;
            }
            .hero-ctas {
                justify-content: center;
            }
            .features-grid, .pricing-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav-links, .nav-cta {
                display: none;
            }
            .menu-toggle {
                display: block;
            }
            .features-grid, .pricing-grid, .footer-grid, .metrics-bar {
                grid-template-columns: 1fr;
            }
            .comparison-table {
                display: block;
                overflow-x: auto;
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
                    <li><a href="#solutions">Solutions</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#workflow">Workflow</a></li>
                    <li><a href="#pricing">Pricing</a></li>
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
                        <i class="fas fa-microchip"></i> Next-Gen SaaS Affiliate Architecture
                    </span>
                    <h1 class="hero-title">
                        Enterprise tracking <span class="gradient-text">for professional performance networks</span>
                    </h1>
                    <p class="hero-desc">
                        Taskbazi is the premier dynamic affiliate network builder. Spin up custom tracking subdomains, isolate tenant databases, and leverage real-time analytics in a fast, robust PHP engine.
                    </p>
                    <div class="hero-ctas">
                        <a href="#pricing" class="btn btn-primary btn-lg">Explore Pricing Plans</a>
                        <a href="tel:8016222991" class="btn btn-outline btn-lg">
                            <i class="fas fa-phone-volume"></i> Support Line: 8016222991
                        </a>
                    </div>
                </div>
                <div class="floating-mockup">
                    <img src="assets/dashboard_preview.png" alt="Taskbazi Enterprise Dashboard Preview">
                </div>
            </div>

            <!-- Metrics bar -->
            <div class="metrics-bar">
                <div class="metric-card">
                    <div class="metric-num">124M+</div>
                    <div class="metric-label">Processed Clicks</div>
                </div>
                <div class="metric-card">
                    <div class="metric-num">2.3ms</div>
                    <div class="metric-label">Redirect Latency</div>
                </div>
                <div class="metric-card">
                    <div class="metric-num">100%</div>
                    <div class="metric-label">SLA Core Uptime</div>
                </div>
                <div class="metric-card">
                    <div class="metric-num">0%</div>
                    <div class="metric-label">Redirect Framework Bloat</div>
                </div>
            </div>
        </div>
    </section>

    <!-- INTERACTIVE SOLUTIONS SEGMENT TABS -->
    <section class="solutions-section" id="solutions">
        <div class="container">
            <div class="section-header">
                <h2>Tailored for Performance Marketing</h2>
                <p>Choose your workspace role to see how Taskbazi scales your operations.</p>
            </div>
            
            <div class="tabs-header">
                <button class="tab-btn active" onclick="switchTab(event, 'networks')">
                    <i class="fas fa-network-wired"></i> Affiliate Networks
                </button>
                <button class="tab-btn" onclick="switchTab(event, 'advertisers')">
                    <i class="fas fa-bullhorn"></i> Brands & Advertisers
                </button>
                <button class="tab-btn" onclick="switchTab(event, 'publishers')">
                    <i class="fas fa-users"></i> Publishers & Partners
                </button>
            </div>

            <!-- TAB CONTENT 1: NETWORKS -->
            <div class="tab-content active" id="networks">
                <div class="tab-info">
                    <h3>Empower Your Network Infrastructure</h3>
                    <p>
                        Set up a white-labeled corporate network in seconds. Taskbazi provides full tenant isolation at the database layer so multiple sub-networks run independently on unique custom domains.
                    </p>
                    <ul class="tab-features-list">
                        <li><i class="fas fa-check-circle"></i> White-label subdomains & custom DNS routing</li>
                        <li><i class="fas fa-check-circle"></i> Isolated data boundaries for compliance</li>
                        <li><i class="fas fa-check-circle"></i> Multi-level manager hierarchies</li>
                    </ul>
                </div>
                <div class="tab-image">
                    <img src="assets/dashboard_preview.png" alt="SaaS Network dashboard interface">
                </div>
            </div>

            <!-- TAB CONTENT 2: ADVERTISERS -->
            <div class="tab-content" id="advertisers">
                <div class="tab-info">
                    <h3>Maximize Campaign ROI & Tracking</h3>
                    <p>
                        Track landing page conversions, budget caps, and incoming click leads with complete precision. Leverage automated server-to-server postbacks to optimize budgets.
                    </p>
                    <ul class="tab-features-list">
                        <li><i class="fas fa-check-circle"></i> Real-time postback events delivery</li>
                        <li><i class="fas fa-check-circle"></i> Cap limits validation (daily/total limits)</li>
                        <li><i class="fas fa-check-circle"></i> Integrated IP Whitelists & activity audits</li>
                    </ul>
                </div>
                <div class="tab-image">
                    <img src="assets/tracking_routes.png" alt="Dynamic advertiser conversion flow illustration">
                </div>
            </div>

            <!-- TAB CONTENT 3: PUBLISHERS -->
            <div class="tab-content" id="publishers">
                <div class="tab-info">
                    <h3>Transparent Reports & High-Speed Links</h3>
                    <p>
                        Give your affiliates the tracking tools they need. With instant link builders, five custom SubID parameters, and transparent conversions telemetry, they can drive conversions at scale.
                    </p>
                    <ul class="tab-features-list">
                        <li><i class="fas fa-check-circle"></i> Custom SubID parameters breakdown</li>
                        <li><i class="fas fa-check-circle"></i> Automated links constructor</li>
                        <li><i class="fas fa-check-circle"></i> Fast payout request dashboards</li>
                    </ul>
                </div>
                <div class="tab-image">
                    <img src="assets/dashboard_preview.png" alt="Affiliate tracking dashboard preview">
                </div>
            </div>
        </div>
    </section>

    <!-- COMPREHENSIVE PRODUCT FEATURES GRID -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Comprehensive Feature Architecture</h2>
                <p>Enterprise performance, dynamic routing protocols, and custom developer guards.</p>
            </div>
            
            <div class="features-grid">
                <!-- Card 1 -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
                    <h3 class="feat-title">Explicit Tenant Scoping</h3>
                    <p class="feat-desc">Every SQL statement select, update, and insert is filtered by unique tenant constraints at the execution layer to guarantee isolated workspace boundaries.</p>
                </div>
                <!-- Card 2 -->
                <div class="feature-card"><i class="fas fa-globe"></i>
                    <div class="feature-icon"><i class="fas fa-globe"></i></div>
                    <h3 class="feat-title">Domain Router Engine</h3>
                    <p class="feat-desc">Map custom branding tracking domains or subdomains dynamically. Our HTTP host parser routes traffic instantly to matching tenant database spaces.</p>
                </div>
                <!-- Card 3 -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <h3 class="feat-title">Procedural PHP Core</h3>
                    <p class="feat-desc">Engineered in procedural PHP 7.1+ using native PDO, bypassing heavy framework layers to execute link redirects in under 2.3 milliseconds.</p>
                </div>
                <!-- Card 4 -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-arrow-turn-down"></i></div>
                    <h3 class="feat-title">Automated Postback Loop</h3>
                    <p class="feat-desc">Process incoming server-to-server callbacks instantly, log HTTP response statuses, and fire conversion postbacks to publishers.</p>
                </div>
                <!-- Card 5 -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-terminal"></i></div>
                    <h3 class="feat-title">Developer SQL Guard</h3>
                    <p class="feat-desc">Custom GuardPDO class monitors SQL execution in real time during development, writing warnings to `sql_guard.log` for unscoped queries.</p>
                </div>
                <!-- Card 6 -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h3 class="feat-title">Multi-SubID Telemetry</h3>
                    <p class="feat-desc">Support up to 5 custom SubIDs per click. Provide your publishers with granular telemetry data on traffic placements, device versions, and geos.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- DETAILED WORKFLOW MATRIX -->
    <section class="workflow-section" id="workflow">
        <div class="container">
            <div class="workflow-grid">
                <div class="workflow-image">
                    <img src="assets/tracking_routes.png" alt="Data Routing and Server Telemetry Infographic">
                </div>
                <div class="steps-container">
                    <span class="hero-badge" style="background:#dcfce7; color:#15803d;"><i class="fas fa-network-wired"></i> Workflow Integration</span>
                    <h2 class="hero-title" style="font-size:36px; margin-bottom:16px;">How data flows through Taskbazi</h2>
                    
                    <div class="step-item">
                        <div class="step-num">1</div>
                        <div class="step-info">
                            <h3>Click Interception</h3>
                            <p>The system intercepts incoming traffic at <code>click.php</code> and dynamically matches host headers to resolve the active tenant.</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">2</div>
                        <div class="step-info">
                            <h3>Scoping & Redirect</h3>
                            <p>Parameters are parsed and logged into database tables explicitly scoped under the tenant ID, and the click is forwarded to the landing page in 2.3ms.</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">3</div>
                        <div class="step-info">
                            <h3>Conversion Callback</h3>
                            <p>When a conversion is generated, <code>postback.php</code> verifies tokens, updates caps, and fires publisher postback endpoints automatically.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Animated Telemetry Console -->
            <div class="console-container">
                <div class="console-header">
                    <div class="console-dots">
                        <div class="dot dot-red"></div>
                        <div class="dot dot-yellow"></div>
                        <div class="dot dot-green"></div>
                    </div>
                    <span>Taskbazi Core Router Telemetry</span>
                </div>
                <div class="console-body" id="consoleBody">
                    <div class="log-line"><span class="log-time">[17:20:01]</span> System routing initialized... OK</div>
                    <div class="log-line"><span class="log-time">[17:20:03]</span> Connecting live database node... <span class="log-success">SUCCESS</span></div>
                    <div class="log-line"><span class="log-time">[17:20:06]</span> Emulated prepared statements check: <span class="log-info">DISABLED (NATIVE PREPARES ACTIVE)</span></div>
                    <div class="log-line"><span class="log-time">[17:20:09]</span> Host request 'taskbazi.xyz' resolved dynamically to Tenant ID #1</div>
                </div>
            </div>
        </div>
    </section>

    <!-- TECHNICAL COMPARISON MATRIX -->
    <section class="comparison-section">
        <div class="container">
            <div class="section-header">
                <h2>Technical System Architecture Comparison</h2>
                <p>See why developers prefer Taskbazi's procedural database isolation over legacy frameworks.</p>
            </div>
            
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Capability / Metric</th>
                        <th class="compare-taskbazi">Taskbazi Core</th>
                        <th class="compare-legacy">Legacy Tracking Platforms</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="compare-label">Average Click Redirect Latency</td>
                        <td class="compare-taskbazi">2.3 milliseconds (No framework overhead)</td>
                        <td class="compare-legacy">120 - 250 milliseconds (Bootstrapping lag)</td>
                    </tr>
                    <tr>
                        <td class="compare-label">Multi-Tenant Database Architecture</td>
                        <td class="compare-taskbazi">Isolated workspace scoping via explicit tenant IDs</td>
                        <td class="compare-legacy">Shared schemas lacking real-time developer query guards</td>
                    </tr>
                    <tr>
                        <td class="compare-label">SaaS Domain Mapping</td>
                        <td class="compare-taskbazi">Automated HTTP host matching on custom DNS tracking domains</td>
                        <td class="compare-legacy">Manual config edits or server reboots required</td>
                    </tr>
                    <tr>
                        <td class="compare-label">SQL Execution Integrity</td>
                        <td class="compare-taskbazi">PDO Native Prepares and automatic GuardPDO logs</td>
                        <td class="compare-legacy">Implicit ORM parameters vulnerable to blind injection</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- PRICING PLANS SECTION -->
    <section class="pricing-section" id="pricing">
        <div class="container">
            <div class="section-header">
                <h2>Choose a Plan for Your Network</h2>
                <p>Fully manageable from your Super Admin console. Switch plans as your traffic scales.</p>
            </div>
            
            <div class="pricing-grid">
                <?php foreach ($plans as $p): ?>
                    <div class="price-card" style="border-top: 4px solid <?=$p['color']?>">
                        <div class="price-card-header">
                            <span class="price-tier-name" style="color: <?=$p['color']?>"><?=$p['name']?></span>
                            <span class="price-val"><?=$p['price']?></span>
                        </div>
                        <p class="price-desc"><?=$p['description']?></p>
                        
                        <ul class="price-features">
                            <li><i class="fas fa-circle-check"></i> Max Offers: <strong><?=$p['offers_limit']?></strong></li>
                            <li><i class="fas fa-circle-check"></i> Max Publishers: <strong><?=$p['publishers_limit']?></strong></li>
                            <li><i class="fas fa-circle-check"></i> Max Advertisers: <strong><?=$p['advertisers_limit']?></strong></li>
                            <li><i class="fas fa-circle-check"></i> High-Speed Click Redirects</li>
                            <li><i class="fas fa-circle-check"></i> Custom SSL Mapping Support</li>
                        </ul>
                        
                        <a href="/register.php?plan=<?=urlencode($p['name'])?>" class="btn btn-primary price-btn" style="background: linear-gradient(135deg, <?=$p['color']?> 0%, #0f172a 100%); border: none; box-shadow: 0 4px 14px rgba(0,0,0,0.02)">
                            Select <?=$p['name']?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- TECHNICAL ACCORDION FAQ -->
    <section class="faq-section" id="faq">
        <div class="container">
            <div class="section-header">
                <h2>Technical FAQ</h2>
                <p>Quick, technical answers about our SaaS infrastructure.</p>
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
                        <span>How do I set up custom tracking subdomains?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-content">
                        Tenants register their custom domain or subdomain in the panel, and point their CNAME DNS records to your main server IP. The system matches the incoming HTTP host header dynamically to route the traffic instantly.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-trigger" onclick="toggleFaq(this)">
                        <span>What is the purpose of the GuardPDO class?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-content">
                        It acts as a development safety net. It intercepts all SQL queries before execution, checking if queries that access tenant-owned tables include a <code>tenant_id</code> constraint. If not, it logs a warning.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-trigger" onclick="toggleFaq(this)">
                        <span>Is there an API for fetching reports?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-content">
                        Yes! Taskbazi has a REST API module under the advertiser and admin panels to fetch campaign stats, conversion postback logs, and affiliate payouts.
                    </div>
                </div>
            </div>

            <!-- TRUST BADGES -->
            <div class="security-badge-grid">
                <div class="security-badge">
                    <i class="fas fa-shield-halved"></i> GDPR Compliant boundary
                </div>
                <div class="security-badge">
                    <i class="fas fa-lock"></i> SSL Secured endpoints
                </div>
                <div class="security-badge">
                    <i class="fas fa-cloud-arrow-up"></i> Automated Backups
                </div>
            </div>
        </div>
    </section>

    <!-- SUPPORT / CONTACT CALLOUT SECTION -->
    <section class="callout-section">
        <div class="container">
            <div class="callout-box">
                <h2>Need Custom Enterprise Deployment?</h2>
                <p>Speak to our system engineers today. We can configure dedicated database nodes, setup bulk tracking redirects migrations, or integrate tracking APIs.</p>
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
                <p>Enterprise Tracking Solutions</p>
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

        // Switch Tabs (Affise/Offer18 style)
        function switchTab(evt, tabId) {
            // Hide all tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Deactivate all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show current tab content & active state
            document.getElementById(tabId).classList.add('active');
            evt.currentTarget.classList.add('active');
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
            "Processed conversion payload for Offer ID #5... SUCCESS",
            "Fired publisher postback: https://api.publisher.com/postback?clickid=cf52ba... SUCCESS",
            "Tenant default mapping resolved: client-1.localhost",
            "GuardPDO safety assert: Query is explicitly tenant-scoped. Passed.",
            "Database cleanup task executed... cleared 0 expired sessions",
            "Processed click event from IP 182.23.91.44... device resolved as mobile",
            "Fraud trigger score: 0.01 (clean click request)",
            "System telemetry: Average CPU 0.8%, Memory usage 15MB"
        ];

        const consoleBody = document.getElementById('consoleBody');

        setInterval(() => {
            const randomLog = consoleLogs[Math.floor(Math.random() * consoleLogs.length)];
            const timeStr = new Date().toTimeString().split(' ')[0];
            
            const logLine = document.createElement('div');
            logLine.className = 'log-line';
            
            let formattedLog = randomLog;
            if (randomLog.includes("SUCCESS")) {
                formattedLog = randomLog.replace("SUCCESS", '<span class="log-success">SUCCESS</span>');
            } else if (randomLog.includes("assert") || randomLog.includes("resolved") || randomLog.includes("DISABLED")) {
                formattedLog = '<span class="log-info">' + randomLog + '</span>';
            }
            
            logLine.innerHTML = `<span class="log-time">[${timeStr}]</span> ${formattedLog}`;
            
            consoleBody.appendChild(logLine);
            consoleBody.scrollTop = consoleBody.scrollHeight;
            
            if (consoleBody.children.length > 20) {
                consoleBody.removeChild(consoleBody.firstChild);
            }
        }, 3000);
    </script>
</body>
</html>
