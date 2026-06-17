<?php
/**
 * Agency Homepage Virtual Editor
 * PHP 7.1+
 */

define('APP_INIT', true);
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_role('admin');

$adminName = $_SESSION['user_name'] ?? 'Admin';
$tenant = current_tenant();
$tenantId = (int)$tenant['id'];
$settings = current_tenant_settings() ?: [];
$siteName = htmlspecialchars($settings['site_name'] ?? $tenant['name']);
$primaryColor = htmlspecialchars($settings['primary_color'] ?? '#2563eb');

$success = $error = null;

// Fetch current homepage settings
try {
    $homepageStmt = $pdo->prepare("SELECT * FROM tenant_homepages WHERE tenant_id = :tid LIMIT 1");
    $homepageStmt->execute(['tid' => $tenantId]);
    $homepage = $homepageStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to load homepage settings: " . $e->getMessage());
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_homepage'])) {
    $templateId = trim($_POST['template_id'] ?? 'classic_hero');
    $heroTitle = trim($_POST['hero_title'] ?? '');
    $heroSubtitle = trim($_POST['hero_subtitle'] ?? '');
    $heroCtaText = trim($_POST['hero_cta_text'] ?? '');
    $heroCtaUrl = trim($_POST['hero_cta_url'] ?? '');
    $aboutText = trim($_POST['about_text'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    
    // Build Features Array
    $features = [];
    for ($i = 1; $i <= 3; $i++) {
        $icon = trim($_POST["feature_icon_{$i}"] ?? '');
        $title = trim($_POST["feature_title_{$i}"] ?? '');
        $desc = trim($_POST["feature_desc_{$i}"] ?? '');
        
        if ($title !== '') {
            $features[] = [
                'icon' => $icon !== '' ? $icon : 'fa-check-circle',
                'title' => $title,
                'desc' => $desc
            ];
        }
    }
    
    $featuresJson = json_encode($features);
    
    // Build Social Links
    $socials = [
        'telegram' => trim($_POST['social_telegram'] ?? ''),
        'teams' => trim($_POST['social_teams'] ?? '')
    ];
    $socialsJson = json_encode($socials);
    
    try {
        if ($homepage) {
            // Update
            $upd = $pdo->prepare("
                UPDATE tenant_homepages 
                SET template_id = :template_id,
                    hero_title = :hero_title,
                    hero_subtitle = :hero_subtitle,
                    hero_cta_text = :hero_cta_text,
                    hero_cta_url = :hero_cta_url,
                    features_json = :features_json,
                    about_text = :about_text,
                    contact_email = :contact_email,
                    social_links_json = :social_links_json,
                    updated_at = NOW()
                WHERE tenant_id = :tenant_id
            ");
            $upd->execute([
                'template_id' => $templateId,
                'hero_title' => $heroTitle,
                'hero_subtitle' => $heroSubtitle,
                'hero_cta_text' => $heroCtaText,
                'hero_cta_url' => $heroCtaUrl,
                'features_json' => $featuresJson,
                'about_text' => $aboutText,
                'contact_email' => $contactEmail,
                'social_links_json' => $socialsJson,
                'tenant_id' => $tenantId
            ]);
            $success = "Homepage customization saved successfully!";
        } else {
            // Insert
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
                'template_id' => $templateId,
                'hero_title' => $heroTitle,
                'hero_subtitle' => $heroSubtitle,
                'hero_cta_text' => $heroCtaText,
                'hero_cta_url' => $heroCtaUrl,
                'features_json' => $featuresJson,
                'about_text' => $aboutText,
                'contact_email' => $contactEmail,
                'social_links_json' => $socialsJson
            ]);
            $success = "Homepage created and saved successfully!";
        }
        
        // Refresh local data
        $homepageStmt->execute(['tid' => $tenantId]);
        $homepage = $homepageStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Error saving homepage settings: " . $e->getMessage();
    }
}

// Decode variables
$features = json_decode($homepage['features_json'] ?? '[]', true) ?: [];
$socials = json_decode($homepage['social_links_json'] ?? '{}', true) ?: [];

// Pre-fill fields or set defaults
$templateId = $homepage['template_id'] ?? 'classic_hero';
$heroTitle = $homepage['hero_title'] ?? 'Start Your Premium Affiliate Network';
$heroSubtitle = $homepage['hero_subtitle'] ?? 'Track conversions, manage payouts, and grow your affiliate partnerships with zero latency.';
$heroCtaText = $homepage['hero_cta_text'] ?? 'Apply as Partner';
$heroCtaUrl = $homepage['hero_cta_url'] ?? '/register.php';
$aboutText = $homepage['about_text'] ?? '';
$contactEmail = $homepage['contact_email'] ?? $settings['support_email'] ?? $tenant['owner_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website Editor | Admin Panel | <?= $siteName ?></title>
    
    <!-- Google Font: Source Sans Pro & Outfit -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <style>
        :root {
            --primary-color: <?= $primaryColor ?>;
            --primary-gradient: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            --bg-editor: #ffffff;
            --bg-alt: #f8fafc;
            --text-dark: #0f172a;
            --text-medium: #334155;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --font-display: 'Outfit', sans-serif;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-alt);
        }
        
        .editor-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }
        
        @media (min-width: 1200px) {
            .editor-container {
                grid-template-columns: 1.1fr 0.9fr;
            }
        }
        
        .card-dashboard {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .card-dashboard .card-header {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 20px 24px;
        }
        
        .card-title-enhanced {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
        }
        
        .card-body-enhanced {
            padding: 24px;
        }
        
        /* Template Radio Selectors */
        .template-selector-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .template-card {
            position: relative;
        }
        
        .template-card input[type="radio"] {
            display: none;
        }
        
        .template-card label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 16px 10px;
            background: #ffffff;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            height: 100%;
        }
        
        .template-card:hover label {
            border-color: #cbd5e1;
            background: var(--bg-alt);
        }
        
        .template-card input[type="radio"]:checked + label {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.03);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.05);
        }
        
        .template-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--bg-alt);
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            font-size: 16px;
            transition: all 0.2s;
        }
        
        .template-card input[type="radio"]:checked + label .template-icon {
            background: var(--primary-color);
            color: white;
        }
        
        .template-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-dark);
            text-align: center;
        }
        
        /* Form stylings */
        .form-group-enhanced {
            margin-bottom: 20px;
        }
        
        .form-group-enhanced label {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
            display: block;
        }
        
        .form-control-enhanced {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            color: var(--text-medium);
            background: var(--bg-alt);
            transition: all 0.25s;
        }
        
        .form-control-enhanced:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
        }
        
        .features-editor-box {
            background: var(--bg-alt);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 16px;
        }
        
        .features-editor-title {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .features-editor-title i {
            color: var(--primary-color);
        }
        
        /* Preview container */
        .preview-sticky {
            position: sticky;
            top: 100px;
        }
        
        .preview-device {
            background: #1e293b;
            border-radius: 20px;
            padding: 12px;
            box-shadow: 0 20px 45px -15px rgba(15,23,42,0.15);
            border: 4px solid #334155;
            aspect-ratio: 16 / 10;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .preview-iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 10px;
            background: #ffffff;
        }
        
        .btn-save {
            background: var(--primary-color);
            color: white;
            font-weight: 700;
            padding: 14px 24px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
            transition: all 0.25s;
            width: 100%;
        }
        
        .btn-save:hover {
            opacity: 0.95;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.25);
            transform: translateY(-1px);
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: 600;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="homepage_editor.php" class="nav-link active">Website Editor</a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-itemDropdownDropdown nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                    <div class="admin-avatar mr-2">
                        <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($adminName); ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="profile.php" class="dropdown-item"><i class="fas fa-user mr-2"></i> Profile</a>
                    <a href="settings.php" class="dropdown-item"><i class="fas fa-cog mr-2"></i> Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="../logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button"><i class="fas fa-expand-arrows-alt"></i></a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="dashboard.php" class="brand-link text-center">
            <span class="brand-text font-weight-light" style="font-size: 1.5rem;">
                <i class="fas fa-crown mr-2"></i>
                <strong>Admin</strong>
            </span>
        </a>

        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">CAMPAIGNS</li>
                    <li class="nav-item">
                        <a href="offers.php" class="nav-link">
                            <i class="nav-icon fas fa-bullhorn"></i>
                            <p>Manage Campaigns</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="create_offer.php" class="nav-link">
                            <i class="nav-icon fas fa-plus"></i>
                            <p>Create Campaign</p>
                        </a>
                    </li>

                    <li class="nav-header">REPORTS</li>
                    <li class="nav-item">
                        <a href="reports_campaigns.php" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Campaign Report</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reports_affiliates.php" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Affiliate Report</p>
                        </a>
                    </li>

                    <li class="nav-header">ACCOUNT</li>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link">
                            <i class="nav-icon fas fa-user-circle"></i>
                            <p>My Profile</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="homepage_editor.php" class="nav-link active">
                            <i class="nav-icon fas fa-file-signature"></i>
                            <p>Website Editor</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Settings</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0" style="font-family: var(--font-display); font-weight: 800;">Virtual Website Editor</h1>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="container-fluid">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>

                <div class="editor-container">
                    <!-- LEFT COLUMN: Editor Form -->
                    <div class="editor-form-col">
                        <form method="post">
                            <div class="card-dashboard">
                                <div class="card-header">
                                    <h3 class="card-title-enhanced">1. Choose Landing Page Template</h3>
                                </div>
                                <div class="card-body-enhanced">
                                    <div class="template-selector-grid">
                                        <div class="template-card">
                                            <input type="radio" id="tpl_classic" name="template_id" value="classic_hero" <?= $templateId === 'classic_hero' ? 'checked' : '' ?>>
                                            <label for="tpl_classic">
                                                <div class="template-icon"><i class="fas fa-table-columns"></i></div>
                                                <span class="template-title">Classic Hero</span>
                                            </label>
                                        </div>
                                        <div class="template-card">
                                            <input type="radio" id="tpl_saas" name="template_id" value="centered_saas" <?= $templateId === 'centered_saas' ? 'checked' : '' ?>>
                                            <label for="tpl_saas">
                                                <div class="template-icon"><i class="fas fa-align-center"></i></div>
                                                <span class="template-title">Centered SaaS</span>
                                            </label>
                                        </div>
                                        <div class="template-card">
                                            <input type="radio" id="tpl_split" name="template_id" value="split_portals" <?= $templateId === 'split_portals' ? 'checked' : '' ?>>
                                            <label for="tpl_split">
                                                <div class="template-icon"><i class="fas fa-columns"></i></div>
                                                <span class="template-title">Split Portals</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="alert alert-info" style="font-size: 12.5px; margin: 0; padding: 12px 16px; border-radius: 8px;">
                                        <i class="fas fa-circle-info mr-2"></i> Logo, favicon, primary color and brand header/footer are synced automatically from your <a href="settings.php" class="font-weight-bold" style="color: var(--primary-color);">General Settings</a>.
                                    </div>
                                </div>
                            </div>

                            <div class="card-dashboard">
                                <div class="card-header">
                                    <h3 class="card-title-enhanced">2. Customize Hero Block</h3>
                                </div>
                                <div class="card-body-enhanced">
                                    <div class="form-group-enhanced">
                                        <label for="hero_title">Hero Title</label>
                                        <input type="text" name="hero_title" id="hero_title" class="form-control-enhanced" value="<?= htmlspecialchars($heroTitle) ?>" required>
                                    </div>
                                    
                                    <div class="form-group-enhanced">
                                        <label for="hero_subtitle">Hero Subtitle</label>
                                        <textarea name="hero_subtitle" id="hero_subtitle" class="form-control-enhanced" rows="3" required><?= htmlspecialchars($heroSubtitle) ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group-enhanced">
                                                <label for="hero_cta_text">CTA Button Text</label>
                                                <input type="text" name="hero_cta_text" id="hero_cta_text" class="form-control-enhanced" value="<?= htmlspecialchars($heroCtaText) ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-enhanced">
                                                <label for="hero_cta_url">CTA Button Link</label>
                                                <input type="text" name="hero_cta_url" id="hero_cta_url" class="form-control-enhanced" value="<?= htmlspecialchars($heroCtaUrl) ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-dashboard">
                                <div class="card-header">
                                    <h3 class="card-title-enhanced">3. Customize Features Block</h3>
                                </div>
                                <div class="card-body-enhanced">
                                    <?php for ($i = 1; $i <= 3; $i++): 
                                        $fIcon = $features[$i - 1]['icon'] ?? 'fa-bolt';
                                        $fTitle = $features[$i - 1]['title'] ?? '';
                                        $fDesc = $features[$i - 1]['desc'] ?? '';
                                    ?>
                                        <div class="features-editor-box">
                                            <div class="features-editor-title"><i class="fas fa-cubes"></i> Feature Highlight #<?= $i ?></div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group-enhanced" style="margin-bottom: 8px;">
                                                        <label>FontAwesome Icon</label>
                                                        <input type="text" name="feature_icon_<?= $i ?>" class="form-control-enhanced" placeholder="fa-bolt" value="<?= htmlspecialchars($fIcon) ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="form-group-enhanced" style="margin-bottom: 8px;">
                                                        <label>Feature Title</label>
                                                        <input type="text" name="feature_title_<?= $i ?>" class="form-control-enhanced" placeholder="e.g. Real-time Analytics" value="<?= htmlspecialchars($fTitle) ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group-enhanced" style="margin-bottom: 0;">
                                                <label>Feature Description</label>
                                                <input type="text" name="feature_desc_<?= $i ?>" class="form-control-enhanced" placeholder="e.g. Track conversions in real-time" value="<?= htmlspecialchars($fDesc) ?>">
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="card-dashboard">
                                <div class="card-header">
                                    <h3 class="card-title-enhanced">4. About, Contact & Social</h3>
                                </div>
                                <div class="card-body-enhanced">
                                    <div class="form-group-enhanced">
                                        <label for="about_text">About Us Summary</label>
                                        <textarea name="about_text" id="about_text" class="form-control-enhanced" rows="4" placeholder="Briefly describe your agency network..."><?= htmlspecialchars($aboutText) ?></textarea>
                                    </div>
                                    
                                    <div class="form-group-enhanced">
                                        <label for="contact_email">Public Contact/Support Email</label>
                                        <input type="email" name="contact_email" id="contact_email" class="form-control-enhanced" value="<?= htmlspecialchars($contactEmail) ?>">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group-enhanced">
                                                <label>Telegram Handle</label>
                                                <input type="text" name="social_telegram" class="form-control-enhanced" placeholder="t.me/yourusername" value="<?= htmlspecialchars($socials['telegram'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-enhanced">
                                                <label>Teams ID</label>
                                                <input type="text" name="social_teams" class="form-control-enhanced" placeholder="Microsoft Teams Channel Link" value="<?= htmlspecialchars($socials['teams'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="save_homepage" class="btn-save">
                                <i class="fas fa-circle-check mr-2"></i> Save Changes & Update Site
                            </button>
                        </form>
                    </div>

                    <!-- RIGHT COLUMN: Interactive Live Preview -->
                    <div class="editor-preview-col d-none d-xl-block">
                        <div class="preview-sticky">
                            <div style="font-family: var(--font-display); font-weight: 800; font-size: 16px; margin-bottom: 12px; color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-eye" style="color: var(--primary-color);"></i> Real-Time Landing Page Preview
                            </div>
                            <div class="preview-device">
                                <iframe src="../index.php?t=<?= time() ?>" class="preview-iframe" id="sitePreview"></iframe>
                            </div>
                            <div style="font-size: 12px; color: var(--text-light); text-align: center; margin-top: 12px;">
                                <i class="fas fa-desktop mr-1"></i> Preview renders the live content mapped to your subdomain context.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery & Bootstrap -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

</body>
</html>
