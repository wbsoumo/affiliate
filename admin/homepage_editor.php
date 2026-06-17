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

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. AJAX Save Layout
    if (isset($_POST['action']) && $_POST['action'] === 'save_layout') {
        header('Content-Type: application/json');
        try {
            $layoutJson = $_POST['layout_json'] ?? '';
            if (json_decode($layoutJson) === null && json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(['success' => false, 'error' => 'Invalid JSON structure']);
                exit;
            }

            if ($homepage) {
                $upd = $pdo->prepare("UPDATE tenant_homepages SET layout_json = :layout_json, updated_at = NOW() WHERE tenant_id = :tenant_id");
                $upd->execute([
                    'layout_json' => $layoutJson,
                    'tenant_id' => $tenantId
                ]);
            } else {
                $ins = $pdo->prepare("INSERT INTO tenant_homepages (tenant_id, layout_json) VALUES (:tenant_id, :layout_json)");
                $ins->execute([
                    'layout_json' => $layoutJson,
                    'tenant_id' => $tenantId
                ]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $ex) {
            echo json_encode(['success' => false, 'error' => $ex->getMessage()]);
        }
        exit;
    }

    // 2. AJAX Image Upload
    if (isset($_POST['action']) && $_POST['action'] === 'upload_image') {
        header('Content-Type: application/json');
        try {
            if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error occurred.']);
                exit;
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            $fileType = $_FILES['image_file']['type'];
            $fileSize = $_FILES['image_file']['size'];

            $extension = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

            if (!in_array($fileType, $allowedTypes) && !in_array($extension, $allowedExtensions)) {
                echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, GIF, WEBP and SVG images are allowed.']);
                exit;
            }

            if ($fileSize > $maxSize) {
                echo json_encode(['success' => false, 'error' => 'File size must be less than 5MB.']);
                exit;
            }

            $uploadDir = __DIR__ . '/../uploads/homepage/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = 'tenant_' . $tenantId . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $filepath)) {
                echo json_encode([
                    'success' => true,
                    'url' => '/uploads/homepage/' . $filename
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
            }
        } catch (Exception $ex) {
            echo json_encode(['success' => false, 'error' => $ex->getMessage()]);
        }
        exit;
    }
}

// Pre-fill or build layout_json from legacy
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website Editor | Admin Panel | <?= $siteName ?></title>
    
    <!-- Google Font: Outfit & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            grid-template-columns: 440px 1fr;
            gap: 24px;
            height: calc(100vh - 160px);
            min-height: 550px;
        }
        
        @media (max-width: 1199.98px) {
            .editor-container {
                grid-template-columns: 1fr;
                height: auto;
            }
        }
        
        .sidebar-panel {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }
        
        .sidebar-header {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 16px 20px;
        }
        
        .sidebar-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
            background: var(--bg-alt);
        }
        
        .sidebar-footer {
            padding: 18px;
            border-top: 1px solid var(--border-color);
            background: #ffffff;
        }
        
        .card-title-enhanced {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 800;
            color: var(--text-dark);
        }
        
        /* Section cards in the sidebar */
        .section-builder-card {
            background: #ffffff;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 16px;
            transition: all 0.25s ease;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.01);
        }
        
        .section-builder-card:hover {
            border-color: #cbd5e1;
        }
        
        .section-card-header {
            padding: 14px 16px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
        }
        
        .section-card-header:hover {
            background: var(--bg-alt);
        }
        
        .section-card-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 14px;
        }
        
        .section-card-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }
        
        /* Section-specific icon colors */
        .icon-hero { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
        .icon-features { background: rgba(245, 158, 11, 0.1); color: #d97706; }
        .icon-split_content { background: rgba(16, 185, 129, 0.1); color: #059669; }
        .icon-stats { background: rgba(6, 182, 212, 0.1); color: #0891b2; }
        .icon-testimonials { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .icon-faq { background: rgba(100, 116, 139, 0.1); color: #475569; }
        .icon-cta_banner { background: rgba(99, 102, 241, 0.1); color: #4f46e5; }
        .icon-trust_badges { background: rgba(16, 185, 129, 0.1); color: #10b981; }

        .section-card-controls {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .section-card-controls button {
            background: none;
            border: none;
            color: var(--text-light);
            padding: 4px;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.15s;
            font-size: 12px;
            line-height: 1;
        }
        
        .section-card-controls button:hover {
            background: #e2e8f0;
            color: var(--text-dark);
        }
        
        .section-card-controls button.btn-delete-section:hover {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .section-card-body {
            padding: 16px;
            border-top: 1px solid var(--border-color);
            background: #ffffff;
            display: none;
        }
        
        /* Custom Inputs */
        .form-group-enhanced {
            margin-bottom: 14px;
        }
        
        .form-group-enhanced label {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-medium);
            margin-bottom: 4px;
            display: block;
        }
        
        .form-control-enhanced {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            color: var(--text-medium);
            background: var(--bg-alt);
            transition: all 0.25s;
        }
        
        .form-control-enhanced:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
        }
        
        /* Nested list items styling */
        .nested-item-box {
            background: var(--bg-alt);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            position: relative;
        }
        
        .nested-item-header {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-light);
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-delete-nested {
            color: #ef4444;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 11px;
            font-weight: 700;
        }
        
        .btn-add-nested {
            background: var(--bg-alt);
            border: 2px dashed var(--border-color);
            color: var(--text-medium);
            font-weight: 700;
            font-size: 12px;
            padding: 8px;
            text-align: center;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-add-nested:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: #ffffff;
        }
        
        /* Image upload container */
        .image-upload-wrapper {
            background: var(--bg-alt);
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            transition: all 0.2s;
            position: relative;
        }
        
        .image-upload-wrapper:hover {
            border-color: var(--primary-color);
        }
        
        .image-preview-thumb {
            max-height: 80px;
            max-width: 100%;
            border-radius: 6px;
            margin-bottom: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .image-upload-btn-label {
            font-size: 12px;
            color: var(--primary-color);
            font-weight: 600;
            cursor: pointer;
            display: block;
            margin: 0;
        }
        
        /* Preview Frame */
        .preview-panel {
            height: 100%;
        }
        
        .preview-sticky {
            position: sticky;
            top: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .preview-device-frame {
            background: #1e293b;
            border-radius: 16px;
            padding: 8px;
            box-shadow: 0 20px 45px -15px rgba(15,23,42,0.15);
            border: 4px solid #334155;
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: calc(100vh - 210px);
            min-height: 450px;
        }
        
        .preview-device-header {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background: #1e293b;
            gap: 6px;
            border-bottom: 1px solid #334155;
        }
        
        .preview-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .preview-dot-red { background: #ef4444; }
        .preview-dot-yellow { background: #eab308; }
        .preview-dot-green { background: #22c55e; }
        
        .preview-address-bar {
            flex: 1;
            background: #334155;
            border-radius: 6px;
            color: #94a3b8;
            font-size: 11px;
            padding: 2px 12px;
            text-align: center;
            margin-left: 20px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            max-width: 400px;
        }
        
        .preview-iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: #ffffff;
            border-radius: 8px;
        }
        
        /* Toast Notification */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 12px 24px;
            border-radius: 8px;
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            transform: translateY(-20px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        }
        
        .toast-notification.show {
            transform: translateY(0);
            opacity: 1;
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
            <li class="nav-item dropdown">
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
                        <a href="create_campaign.php" class="nav-link">
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
                <div class="editor-container">
                    
                    <!-- LEFT COLUMN: Editor Form Panel -->
                    <div class="sidebar-panel">
                        <div class="sidebar-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title-enhanced" style="margin: 0;">
                                <i class="fas fa-layer-group mr-2" style="color: var(--primary-color)"></i>Page Sections
                            </h3>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle font-weight-bold" type="button" id="addSectionDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-plus mr-1"></i> Add Section
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="addSectionDropdown">
                                    <a class="dropdown-item" href="#" onclick="addNewSection('hero'); return false;"><i class="fas fa-window-maximize mr-2 text-primary"></i> Hero Banner</a>
                                    <a class="dropdown-item" href="#" onclick="addNewSection('features'); return false;"><i class="fas fa-cubes mr-2 text-warning"></i> Features Grid</a>
                                    <a class="dropdown-item" href="#" onclick="addNewSection('split_content'); return false;"><i class="fas fa-columns mr-2 text-success"></i> Split Text & Image</a>
                                    <a class="dropdown-item" href="#" onclick="addNewSection('stats'); return false;"><i class="fas fa-chart-line mr-2 text-info"></i> Stats Counter</a>
                                    <a class="dropdown-item" href="#" onclick="addNewSection('testimonials'); return false;"><i class="fas fa-comments mr-2 text-danger"></i> Testimonials</a>
                                    <a class="dropdown-item" href="#" onclick="addNewSection('faq'); return false;"><i class="fas fa-question-circle mr-2 text-secondary"></i> FAQ Accordion</a>
                                    <a class="dropdown-item" href="#" onclick="addNewSection('cta_banner'); return false;"><i class="fas fa-bullhorn mr-2 text-primary"></i> CTA Banner</a>
                                    <a class="dropdown-item" href="#" onclick="addNewSection('trust_badges'); return false;"><i class="fas fa-shield-halved mr-2 text-success"></i> Trust Badges</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sidebar-body" id="sectionsContainer">
                            <!-- Rendered Dynamically by Javascript -->
                        </div>
                        
                        <div class="sidebar-footer">
                            <button class="btn btn-success btn-lg btn-block font-weight-bold shadow-sm" id="btnSaveLayout" onclick="saveLayout()">
                                <i class="fas fa-circle-check mr-2"></i> Save & Publish
                            </button>
                            <div class="alert alert-info mt-3 mb-0" style="font-size: 11.5px; line-height: 1.4; padding: 10px 14px; border-radius: 8px;">
                                <i class="fas fa-lock mr-2"></i> <strong>Header & Footer Locked:</strong> Logo, favicon, colors, name, and support links are synced automatically from <a href="settings.php" class="font-weight-bold" style="color: var(--primary-color);">General Settings</a>.
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Interactive Live Preview -->
                    <div class="preview-panel d-none d-xl-block">
                        <div class="preview-sticky">
                            <div style="font-family: var(--font-display); font-weight: 800; font-size: 16px; margin-bottom: 12px; color: var(--text-dark); display: flex; align-items: center; justify-content: space-between;">
                                <span><i class="fas fa-eye mr-2" style="color: var(--primary-color);"></i>Live Preview</span>
                                <button class="btn btn-xs btn-outline-secondary font-weight-bold" onclick="document.getElementById('sitePreview').src = '../index.php?t=' + Date.now();">
                                    <i class="fas fa-rotate mr-1"></i> Refresh
                                </button>
                            </div>
                            <div class="preview-device-frame">
                                <div class="preview-device-header">
                                    <span class="preview-dot preview-dot-red"></span>
                                    <span class="preview-dot preview-dot-yellow"></span>
                                    <span class="preview-dot preview-dot-green"></span>
                                    <div class="preview-address-bar"><i class="fas fa-lock mr-1 text-success"></i> https://<?= $_SERVER['HTTP_HOST'] ?>/</div>
                                </div>
                                <iframe src="../index.php?t=<?= time() ?>" class="preview-iframe" id="sitePreview"></iframe>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<div id="toastNotification" class="toast-notification"></div>

<!-- jQuery & Bootstrap -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
// State array containing all active sections
let sections = <?= $layoutJson ?>;
let expandedSectionId = null;

// Default layouts for new blocks
const defaultTemplates = {
    hero: {
        title: 'Start Your Premium Affiliate Network',
        subtitle: 'Track conversions, manage payouts, and grow your affiliate partnerships with zero latency.',
        cta_text: 'Apply as Partner',
        cta_url: '/register.php',
        secondary_cta_text: 'Member Login',
        secondary_cta_url: '/login.php',
        layout_style: 'split_right',
        bg_type: 'gradient',
        bg_color: '#2563eb',
        bg_gradient: 'linear-gradient(135deg, #2563eb 0%, #1e40af 100%)',
        bg_image: '',
        image_url: ''
    },
    features: {
        title: 'Network Features',
        subtitle: 'High-tech tools engineered to secure your conversions and payouts',
        columns: 3,
        items: [
            { icon: 'fa-bolt', title: 'Real-Time Tracking', desc: 'Track every click and conversion instantly.' },
            { icon: 'fa-shield-halved', title: 'Fraud Prevention', desc: 'Advanced verification filters.' }
        ]
    },
    split_content: {
        title: 'Build Relationships',
        subtitle: 'Connect with top brands',
        description: 'We provide state-of-the-art affiliate network solutions to connect global brands with high-quality publishers.',
        image_url: '',
        image_align: 'right',
        cta_text: 'Read More',
        cta_url: '#',
        bg_color: '#ffffff'
    },
    stats: {
        title: 'Our Network Performance',
        items: [
            { icon: 'fa-users', number: '10K+', label: 'Active Affiliates' },
            { icon: 'fa-chart-line', number: '50M+', label: 'Impressions' }
        ],
        bg_color: '#f8fafc'
    },
    testimonials: {
        title: 'Trusted by Professional Marketers',
        subtitle: 'See what our global publisher affiliates and advertiser partners have to say',
        items: [
            { avatar: '', name: 'John Doe', role: 'Affiliate Partner', quote: 'Their tracking software is extremely accurate.' }
        ]
    },
    faq: {
        title: 'Frequently Asked Questions',
        subtitle: 'Find quick answers to common questions about our platform',
        items: [
            { question: 'How do I sign up?', answer: 'Click the register button in the header to submit your publisher application.' }
        ]
    },
    cta_banner: {
        title: 'Scale your marketing campaign today',
        subtitle: 'Get access to premium direct publisher inventory with sub-second redirects.',
        cta_text: 'Register Now',
        cta_url: '/register.php',
        bg_color: '#0f172a'
    },
    trust_badges: {
        title: 'Supported Networks & Tracking Integrations',
        items: [
            { image_url: '', link: '#' }
        ]
    }
};

// Render elements on page load
$(document).ready(function() {
    renderSections();
});

// Update standard setting without focus loss
function updateSetting(id, key, val) {
    let sec = sections.find(s => s.id === id);
    if (sec) {
        sec.settings[key] = val;
    }
}

// Update nested list setting
function updateNestedSetting(id, listName, idx, key, val) {
    let sec = sections.find(s => s.id === id);
    if (sec && sec.settings[listName] && sec.settings[listName][idx]) {
        sec.settings[listName][idx][key] = val;
    }
}

// Add a new section block
function addNewSection(type) {
    const id = 'sec_' + Math.random().toString(36).substr(2, 9);
    const settings = JSON.parse(JSON.stringify(defaultTemplates[type]));
    sections.push({ id, type, settings });
    expandedSectionId = id;
    renderSections();
    scrollToSection(id);
}

// Toggle accordion block collapse
function toggleSection(id) {
    const body = $('#body_' + id);
    const isVisible = body.is(':visible');
    
    $('.section-card-body').slideUp(200);
    
    if (!isVisible) {
        body.slideDown(200);
        expandedSectionId = id;
    } else {
        expandedSectionId = null;
    }
}

// Scroll layout sidebar to specific section
function scrollToSection(id) {
    setTimeout(() => {
        const target = $('#card_' + id);
        if (target.length) {
            $('.sidebar-body').animate({
                scrollTop: target.offset().top - $('.sidebar-body').offset().top + $('.sidebar-body').scrollTop()
            }, 300);
        }
    }, 250);
}

// Delete section block
function deleteSection(id, event) {
    if (event) event.stopPropagation();
    if (confirm('Are you sure you want to delete this section?')) {
        sections = sections.filter(s => s.id !== id);
        if (expandedSectionId === id) expandedSectionId = null;
        renderSections();
    }
}

// Move section order
function moveSection(id, direction, event) {
    if (event) event.stopPropagation();
    const idx = sections.findIndex(s => s.id === id);
    if (idx === -1) return;
    
    const targetIdx = direction === 'up' ? idx - 1 : idx + 1;
    if (targetIdx < 0 || targetIdx >= sections.length) return;
    
    const temp = sections[idx];
    sections[idx] = sections[targetIdx];
    sections[targetIdx] = temp;
    
    renderSections();
    scrollToSection(id);
}

// Add child list items
function addNestedItem(id, listName, defaultObj) {
    let sec = sections.find(s => s.id === id);
    if (sec) {
        if (!sec.settings[listName]) sec.settings[listName] = [];
        sec.settings[listName].push(JSON.parse(JSON.stringify(defaultObj)));
        renderSections();
    }
}

// Delete child list items
function deleteNestedItem(id, listName, idx) {
    let sec = sections.find(s => s.id === id);
    if (sec && sec.settings[listName]) {
        sec.settings[listName].splice(idx, 1);
        renderSections();
    }
}

// Save layout configuration via AJAX
function saveLayout() {
    let formData = new FormData();
    formData.append('action', 'save_layout');
    formData.append('layout_json', JSON.stringify(sections));

    const btn = $('#btnSaveLayout');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Saving...');

    fetch('homepage_editor.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Homepage layout published successfully!');
            // Force reload preview frame
            document.getElementById('sitePreview').src = '../index.php?t=' + Date.now();
        } else {
            showToast('danger', 'Error: ' + data.error);
        }
    })
    .catch(err => {
        showToast('danger', 'Failed to connect to the server.');
        console.error(err);
    })
    .finally(() => {
        btn.prop('disabled', false).html('<i class="fas fa-circle-check mr-2"></i> Save & Publish');
    });
}

// AJAX file upload function
function uploadImage(input, secId, keyName, isNested = false, nestedList = '', nestedIdx = 0) {
    const file = input.files[0];
    if (!file) return;

    const wrapper = $(input).closest('.image-upload-wrapper');
    const label = wrapper.find('.image-upload-btn-label');
    const originalText = label.html();
    
    label.html('<i class="fas fa-spinner fa-spin mr-1"></i> Uploading...');
    $(input).prop('disabled', true);

    const formData = new FormData();
    formData.append('action', 'upload_image');
    formData.append('image_file', file);

    fetch('homepage_editor.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (isNested) {
                updateNestedSetting(secId, nestedList, nestedIdx, keyName, data.url);
            } else {
                updateSetting(secId, keyName, data.url);
            }
            
            // Set thumbnail
            let thumb = wrapper.find('.image-preview-thumb');
            if (!thumb.length) {
                thumb = $('<img class="image-preview-thumb">');
                wrapper.prepend(thumb);
            }
            thumb.attr('src', data.url);
            showToast('success', 'Image uploaded successfully!');
        } else {
            showToast('danger', 'Upload failed: ' + data.error);
        }
    })
    .catch(err => {
        showToast('danger', 'Network error during upload.');
        console.error(err);
    })
    .finally(() => {
        label.html(originalText);
        $(input).prop('disabled', false).val('');
    });
}

// Display modern visual Toast notifications
function showToast(type, message) {
    const toast = $('#toastNotification');
    toast.removeClass('show bg-success bg-danger');
    toast.addClass('show bg-' + (type === 'success' ? 'success' : 'danger'));
    toast.html((type === 'success' ? '<i class="fas fa-circle-check mr-2"></i>' : '<i class="fas fa-circle-exclamation mr-2"></i>') + message);
    
    setTimeout(() => {
        toast.removeClass('show');
    }, 3500);
}

// Render dynamic forms inside the sidebar list panel
function renderSections() {
    const container = $('#sectionsContainer');
    container.empty();
    
    if (sections.length === 0) {
        container.html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-window-restore fa-3x mb-3 text-light"></i>
                <p class="font-weight-bold mb-1">Your Homepage is Empty</p>
                <p class="small text-light">Click "Add Section" at the top to build your custom page body layout.</p>
            </div>
        `);
        return;
    }
    
    sections.forEach((sec, idx) => {
        const isExpanded = (sec.id === expandedSectionId);
        const displayStyle = isExpanded ? 'block' : 'none';
        
        let headerTitle = sec.settings.title || sec.type.toUpperCase() + ' Section';
        if (headerTitle.length > 30) headerTitle = headerTitle.substring(0, 27) + '...';
        
        // Define section header type labels and icons
        let typeName = 'Section';
        let iconClass = 'fa-cubes';
        let labelClass = 'icon-features';
        
        if (sec.type === 'hero') { typeName = 'Hero Banner'; iconClass = 'fa-window-maximize'; labelClass = 'icon-hero'; }
        else if (sec.type === 'features') { typeName = 'Features Grid'; iconClass = 'fa-cubes'; labelClass = 'icon-features'; }
        else if (sec.type === 'split_content') { typeName = 'Split Box'; iconClass = 'fa-columns'; labelClass = 'icon-split_content'; }
        else if (sec.type === 'stats') { typeName = 'Stats Grid'; iconClass = 'fa-chart-line'; labelClass = 'icon-stats'; }
        else if (sec.type === 'testimonials') { typeName = 'Testimonials'; iconClass = 'fa-comments'; labelClass = 'icon-testimonials'; }
        else if (sec.type === 'faq') { typeName = 'FAQ Accordion'; iconClass = 'fa-question-circle'; labelClass = 'icon-faq'; }
        else if (sec.type === 'cta_banner') { typeName = 'CTA Banner'; iconClass = 'fa-bullhorn'; labelClass = 'icon-cta_banner'; }
        else if (sec.type === 'trust_badges') { typeName = 'Trust Badges'; iconClass = 'fa-shield-halved'; labelClass = 'icon-trust_badges'; }
        
        let cardHtml = `
            <div class="section-builder-card" id="card_${sec.id}">
                <div class="section-card-header" onclick="toggleSection('${sec.id}')">
                    <div class="section-card-info">
                        <div class="section-card-icon ${labelClass}"><i class="fas ${iconClass}"></i></div>
                        <div>
                            <span class="d-block text-dark">${headerTitle}</span>
                            <span class="d-block text-muted" style="font-size: 10.5px; font-weight: 500;">${typeName}</span>
                        </div>
                    </div>
                    <div class="section-card-controls">
                        <button onclick="moveSection('${sec.id}', 'up', event)" title="Move Up" ${idx === 0 ? 'disabled style="opacity:0.3;"' : ''}><i class="fas fa-arrow-up"></i></button>
                        <button onclick="moveSection('${sec.id}', 'down', event)" title="Move Down" ${idx === sections.length - 1 ? 'disabled style="opacity:0.3;"' : ''}><i class="fas fa-arrow-down"></i></button>
                        <button onclick="deleteSection('${sec.id}', event)" class="btn-delete-section" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                
                <div class="section-card-body" id="body_${sec.id}" style="display: ${displayStyle};">
                    ${renderSectionFields(sec)}
                </div>
            </div>
        `;
        
        container.append(cardHtml);
    });
}

// Render inputs depending on block types
function renderSectionFields(sec) {
    let html = '';
    const s = sec.settings;
    
    if (sec.type === 'hero') {
        html += `
            <div class="form-group-enhanced">
                <label>Headline Title</label>
                <input type="text" class="form-control-enhanced" value="${s.title || ''}" oninput="updateSetting('${sec.id}', 'title', this.value)">
            </div>
            <div class="form-group-enhanced">
                <label>Subtitle Text</label>
                <textarea class="form-control-enhanced" rows="3" oninput="updateSetting('${sec.id}', 'subtitle', this.value)">${s.subtitle || ''}</textarea>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group-enhanced">
                        <label>Primary CTA Text</label>
                        <input type="text" class="form-control-enhanced" value="${s.cta_text || ''}" oninput="updateSetting('${sec.id}', 'cta_text', this.value)">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group-enhanced">
                        <label>Primary CTA Link</label>
                        <input type="text" class="form-control-enhanced" value="${s.cta_url || ''}" oninput="updateSetting('${sec.id}', 'cta_url', this.value)">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group-enhanced">
                        <label>Secondary CTA Text</label>
                        <input type="text" class="form-control-enhanced" value="${s.secondary_cta_text || ''}" oninput="updateSetting('${sec.id}', 'secondary_cta_text', this.value)">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group-enhanced">
                        <label>Secondary CTA Link</label>
                        <input type="text" class="form-control-enhanced" value="${s.secondary_cta_url || ''}" oninput="updateSetting('${sec.id}', 'secondary_cta_url', this.value)">
                    </div>
                </div>
            </div>
            
            <div class="form-group-enhanced">
                <label>Hero Layout style</label>
                <select class="form-control-enhanced" onchange="updateSetting('${sec.id}', 'layout_style', this.value)">
                    <option value="split_right" ${s.layout_style === 'split_right' ? 'selected' : ''}>Split - Content Left, Image/Mockup Right</option>
                    <option value="split_left" ${s.layout_style === 'split_left' ? 'selected' : ''}>Split - Image/Mockup Left, Content Right</option>
                    <option value="centered" ${s.layout_style === 'centered' ? 'selected' : ''}>Centered Content (No Mockup Image)</option>
                </select>
            </div>

            <div class="form-group-enhanced">
                <label>Mockup/Hero Image</label>
                <div class="image-upload-wrapper">
                    ${s.image_url ? `<img src="${s.image_url}" class="image-preview-thumb">` : ''}
                    <label class="image-upload-btn-label">
                        <i class="fas fa-image mr-1"></i> Choose Mockup Image
                        <input type="file" style="display:none;" onchange="uploadImage(this, '${sec.id}', 'image_url')">
                    </label>
                </div>
            </div>
            
            <div class="form-group-enhanced">
                <label>Background Type</label>
                <select class="form-control-enhanced" onchange="updateSetting('${sec.id}', 'bg_type', this.value)">
                    <option value="gradient" ${s.bg_type === 'gradient' ? 'selected' : ''}>Brand Gradient</option>
                    <option value="color" ${s.bg_type === 'color' ? 'selected' : ''}>Solid Color</option>
                    <option value="image" ${s.bg_type === 'image' ? 'selected' : ''}>Background Image</option>
                </select>
            </div>
            <div class="form-group-enhanced">
                <label>Solid Background Color (e.g. #ffffff)</label>
                <input type="text" class="form-control-enhanced" value="${s.bg_color || ''}" oninput="updateSetting('${sec.id}', 'bg_color', this.value)">
            </div>
            <div class="form-group-enhanced">
                <label>Background Image URL (Uploaded via General Settings)</label>
                <div class="image-upload-wrapper">
                    ${s.bg_image ? `<img src="${s.bg_image}" class="image-preview-thumb">` : ''}
                    <label class="image-upload-btn-label">
                        <i class="fas fa-image mr-1"></i> Choose BG Image
                        <input type="file" style="display:none;" onchange="uploadImage(this, '${sec.id}', 'bg_image')">
                    </label>
                </div>
            </div>
        `;
    }
    else if (sec.type === 'features') {
        html += `
            <div class="form-group-enhanced">
                <label>Section Heading</label>
                <input type="text" class="form-control-enhanced" value="${s.title || ''}" oninput="updateSetting('${sec.id}', 'title', this.value)">
            </div>
            <div class="form-group-enhanced">
                <label>Section Subtitle</label>
                <input type="text" class="form-control-enhanced" value="${s.subtitle || ''}" oninput="updateSetting('${sec.id}', 'subtitle', this.value)">
            </div>
            <div class="form-group-enhanced">
                <label>Display Grid Columns</label>
                <select class="form-control-enhanced" onchange="updateSetting('${sec.id}', 'columns', parseInt(this.value))">
                    <option value="2" ${s.columns === 2 ? 'selected' : ''}>2 Columns</option>
                    <option value="3" ${s.columns === 3 ? 'selected' : ''}>3 Columns</option>
                    <option value="4" ${s.columns === 4 ? 'selected' : ''}>4 Columns</option>
                </select>
            </div>
            
            <label class="font-weight-bold d-block mb-2 text-dark" style="font-size:12px;">Feature Items</label>
            <div class="nested-items-list">
        `;
        
        const items = s.items || [];
        items.forEach((item, idx) => {
            html += `
                <div class="nested-item-box">
                    <div class="nested-item-header">
                        <span>FEATURE #${idx + 1}</span>
                        <button type="button" class="btn-delete-nested" onclick="deleteNestedItem('${sec.id}', 'items', ${idx})">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group-enhanced mb-1">
                                <label>FA Icon</label>
                                <input type="text" class="form-control-enhanced" value="${item.icon || 'fa-check'}" placeholder="fa-check" oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'icon', this.value)">
                            </div>
                        </div>
                        <div class="col-8">
                            <div class="form-group-enhanced mb-1">
                                <label>Title</label>
                                <input type="text" class="form-control-enhanced" value="${item.title || ''}" placeholder="Real-time Tracking" oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'title', this.value)">
                            </div>
                        </div>
                    </div>
                    <div class="form-group-enhanced mb-0">
                        <label>Description</label>
                        <input type="text" class="form-control-enhanced" value="${item.desc || ''}" placeholder="Explain the highlight features..." oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'desc', this.value)">
                    </div>
                </div>
            `;
        });
        
        html += `
            </div>
            <button type="button" class="btn-add-nested mt-2" onclick="addNestedItem('${sec.id}', 'items', {icon:'fa-bolt', title:'New Highlight', desc:'Describe this benefit here'})">
                <i class="fas fa-plus mr-1"></i> Add Feature Card
            </button>
        `;
    }
    else if (sec.type === 'split_content') {
        html += `
            <div class="form-group-enhanced">
                <label>Heading Title</label>
                <input type="text" class="form-control-enhanced" value="${s.title || ''}" oninput="updateSetting('${sec.id}', 'title', this.value)">
            </div>
            <div class="form-group-enhanced">
                <label>Subheading Text</label>
                <input type="text" class="form-control-enhanced" value="${s.subtitle || ''}" oninput="updateSetting('${sec.id}', 'subtitle', this.value)">
            </div>
            <div class="form-group-enhanced">
                <label>Description Summary</label>
                <textarea class="form-control-enhanced" rows="4" oninput="updateSetting('${sec.id}', 'description', this.value)">${s.description || ''}</textarea>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group-enhanced">
                        <label>Button Action Text</label>
                        <input type="text" class="form-control-enhanced" value="${s.cta_text || ''}" oninput="updateSetting('${sec.id}', 'cta_text', this.value)">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group-enhanced">
                        <label>Button Link URL</label>
                        <input type="text" class="form-control-enhanced" value="${s.cta_url || ''}" oninput="updateSetting('${sec.id}', 'cta_url', this.value)">
                    </div>
                </div>
            </div>
            <div class="form-group-enhanced">
                <label>Section Image</label>
                <div class="image-upload-wrapper">
                    ${s.image_url ? `<img src="${s.image_url}" class="image-preview-thumb">` : ''}
                    <label class="image-upload-btn-label">
                        <i class="fas fa-image mr-1"></i> Upload Image
                        <input type="file" style="display:none;" onchange="uploadImage(this, '${sec.id}', 'image_url')">
                    </label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group-enhanced">
                        <label>Image Alignment</label>
                        <select class="form-control-enhanced" onchange="updateSetting('${sec.id}', 'image_align', this.value)">
                            <option value="right" ${s.image_align === 'right' ? 'selected' : ''}>Right</option>
                            <option value="left" ${s.image_align === 'left' ? 'selected' : ''}>Left</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group-enhanced">
                        <label>Background Color</label>
                        <input type="text" class="form-control-enhanced" value="${s.bg_color || '#ffffff'}" placeholder="#ffffff" oninput="updateSetting('${sec.id}', 'bg_color', this.value)">
                    </div>
                </div>
            </div>
        `;
    }
    else if (sec.type === 'stats') {
        html += `
            <div class="form-group-enhanced">
                <label>Section Heading</label>
                <input type="text" class="form-control-enhanced" value="${s.title || ''}" oninput="updateSetting('${sec.id}', 'title', this.value)">
            </div>
            <div class="form-group-enhanced">
                <label>Background Color</label>
                <input type="text" class="form-control-enhanced" value="${s.bg_color || '#f8fafc'}" placeholder="#f8fafc" oninput="updateSetting('${sec.id}', 'bg_color', this.value)">
            </div>
            
            <label class="font-weight-bold d-block mb-2 text-dark" style="font-size:12px;">Stats Counters</label>
            <div class="nested-items-list">
        `;
        
        const items = s.items || [];
        items.forEach((item, idx) => {
            html += `
                <div class="nested-item-box">
                    <div class="nested-item-header">
                        <span>COUNTER STAT #${idx + 1}</span>
                        <button type="button" class="btn-delete-nested" onclick="deleteNestedItem('${sec.id}', 'items', ${idx})">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group-enhanced mb-1">
                                <label>Stat Number/Value</label>
                                <input type="text" class="form-control-enhanced" value="${item.number || ''}" placeholder="120K+" oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'number', this.value)">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group-enhanced mb-1">
                                <label>FA Icon</label>
                                <input type="text" class="form-control-enhanced" value="${item.icon || 'fa-users'}" placeholder="fa-users" oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'icon', this.value)">
                            </div>
                        </div>
                    </div>
                    <div class="form-group-enhanced mb-0">
                        <label>Label Title</label>
                        <input type="text" class="form-control-enhanced" value="${item.label || ''}" placeholder="Active Publishers" oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'label', this.value)">
                    </div>
                </div>
            `;
        });
        
        html += `
            </div>
            <button type="button" class="btn-add-nested mt-2" onclick="addNestedItem('${sec.id}', 'items', {icon:'fa-chart-pie', number:'100%', label:'New Stat Metrics'})">
                <i class="fas fa-plus mr-1"></i> Add Counter Node
            </button>
        `;
    }
    else if (sec.type === 'testimonials') {
        html += `
            <div class="form-group-enhanced">
                <label>Section Heading</label>
                <input type="text" class="form-control-enhanced" value="${s.title || ''}" oninput="updateSetting('${sec.id}', 'title', this.value)">
            </div>
            <div class="form-group-enhanced">
                <label>Section Subtitle</label>
                <input type="text" class="form-control-enhanced" value="${s.subtitle || ''}" oninput="updateSetting('${sec.id}', 'subtitle', this.value)">
            </div>
            
            <label class="font-weight-bold d-block mb-2 text-dark" style="font-size:12px;">Testimonial Reviews</label>
            <div class="nested-items-list">
        `;
        
        const items = s.items || [];
        items.forEach((item, idx) => {
            html += `
                <div class="nested-item-box">
                    <div class="nested-item-header">
                        <span>TESTIMONIAL #${idx + 1}</span>
                        <button type="button" class="btn-delete-nested" onclick="deleteNestedItem('${sec.id}', 'items', ${idx})">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4">
                            <div class="image-upload-wrapper p-2" style="border-width: 1px;">
                                ${item.avatar ? `<img src="${item.avatar}" class="image-preview-thumb" style="max-height:40px;">` : ''}
                                <label class="image-upload-btn-label" style="font-size:10px;">
                                    Upload
                                    <input type="file" style="display:none;" onchange="uploadImage(this, '${sec.id}', 'avatar', true, 'items', ${idx})">
                                </label>
                            </div>
                        </div>
                        <div class="col-8">
                            <div class="form-group-enhanced mb-1">
                                <label style="font-size:10px;">Author Name</label>
                                <input type="text" class="form-control-enhanced py-1 px-2" value="${item.name || ''}" placeholder="David Cooper" oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'name', this.value)">
                            </div>
                            <div class="form-group-enhanced mb-0">
                                <label style="font-size:10px;">Author Role/Company</label>
                                <input type="text" class="form-control-enhanced py-1 px-2" value="${item.role || ''}" placeholder="CEO, LeadMedia" oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'role', this.value)">
                            </div>
                        </div>
                    </div>
                    <div class="form-group-enhanced mb-0">
                        <label>Review Quote</label>
                        <textarea class="form-control-enhanced" rows="3" placeholder="Writing testimonial content..." oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'quote', this.value)">${item.quote || ''}</textarea>
                    </div>
                </div>
            `;
        });
        
        html += `
            </div>
            <button type="button" class="btn-add-nested mt-2" onclick="addNestedItem('${sec.id}', 'items', {avatar:'', name:'New Reviewer', role:'Publisher Partner', quote:'This service is spectacular. I am generating conversions with absolutely zero postback failure.'})">
                <i class="fas fa-plus mr-1"></i> Add Testimonial Card
            </button>
        `;
    }
    else if (sec.type === 'faq') {
        html += `
            <div class="form-group-enhanced">
                <label>Section Heading</label>
                <input type="text" class="form-control-enhanced" value="${s.title || ''}" oninput="updateSetting('${sec.id}', 'title', this.value)">
            </div>
            <div class="form-group-enhanced">
                <label>Section Subtitle</label>
                <input type="text" class="form-control-enhanced" value="${s.subtitle || ''}" oninput="updateSetting('${sec.id}', 'subtitle', this.value)">
            </div>
            
            <label class="font-weight-bold d-block mb-2 text-dark" style="font-size:12px;">FAQ Items</label>
            <div class="nested-items-list">
        `;
        
        const items = s.items || [];
        items.forEach((item, idx) => {
            html += `
                <div class="nested-item-box">
                    <div class="nested-item-header">
                        <span>ACCORDION ITEM #${idx + 1}</span>
                        <button type="button" class="btn-delete-nested" onclick="deleteNestedItem('${sec.id}', 'items', ${idx})">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </div>
                    <div class="form-group-enhanced mb-2">
                        <label>Question text</label>
                        <input type="text" class="form-control-enhanced" value="${item.question || ''}" placeholder="How do payments work?" oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'question', this.value)">
                    </div>
                    <div class="form-group-enhanced mb-0">
                        <label>Answer text</label>
                        <textarea class="form-control-enhanced" rows="3" placeholder="Provide platform answer..." oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'answer', this.value)">${item.answer || ''}</textarea>
                    </div>
                </div>
            `;
        });
        
        html += `
            </div>
            <button type="button" class="btn-add-nested mt-2" onclick="addNestedItem('${sec.id}', 'items', {question:'What is the conversion tracking setup?', answer:'We offer postback URL redirects, image pixels, and raw iframe tracking capabilities.'})">
                <i class="fas fa-plus mr-1"></i> Add FAQ Accordion
            </button>
        `;
    }
    else if (sec.type === 'cta_banner') {
        html += `
            <div class="form-group-enhanced">
                <label>CTA Highlight Heading</label>
                <input type="text" class="form-control-enhanced" value="${s.title || ''}" oninput="updateSetting('${sec.id}', 'title', this.value)">
            </div>
            <div class="form-group-enhanced">
                <label>Description Subtitle</label>
                <input type="text" class="form-control-enhanced" value="${s.subtitle || ''}" oninput="updateSetting('${sec.id}', 'subtitle', this.value)">
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group-enhanced">
                        <label>Button Text</label>
                        <input type="text" class="form-control-enhanced" value="${s.cta_text || ''}" oninput="updateSetting('${sec.id}', 'cta_text', this.value)">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group-enhanced">
                        <label>Button Link URL</label>
                        <input type="text" class="form-control-enhanced" value="${s.cta_url || ''}" oninput="updateSetting('${sec.id}', 'cta_url', this.value)">
                    </div>
                </div>
            </div>
            <div class="form-group-enhanced">
                <label>Banner Background Color</label>
                <input type="text" class="form-control-enhanced" value="${s.bg_color || '#0f172a'}" placeholder="#0f172a" oninput="updateSetting('${sec.id}', 'bg_color', this.value)">
            </div>
        `;
    }
    else if (sec.type === 'trust_badges') {
        html += `
            <div class="form-group-enhanced">
                <label>Section Heading</label>
                <input type="text" class="form-control-enhanced" value="${s.title || ''}" oninput="updateSetting('${sec.id}', 'title', this.value)">
            </div>
            
            <label class="font-weight-bold d-block mb-2 text-dark" style="font-size:12px;">Partner Logo Badges</label>
            <div class="nested-items-list">
        `;
        
        const items = s.items || [];
        items.forEach((item, idx) => {
            html += `
                <div class="nested-item-box">
                    <div class="nested-item-header">
                        <span>PARTNER LOGO #${idx + 1}</span>
                        <button type="button" class="btn-delete-nested" onclick="deleteNestedItem('${sec.id}', 'items', ${idx})">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </div>
                    <div class="form-group-enhanced mb-2">
                        <label>Logo Badge File</label>
                        <div class="image-upload-wrapper p-2" style="border-width: 1px;">
                            ${item.image_url ? `<img src="${item.image_url}" class="image-preview-thumb" style="max-height:40px;">` : ''}
                            <label class="image-upload-btn-label" style="font-size:10px;">
                                Upload Logo Image
                                <input type="file" style="display:none;" onchange="uploadImage(this, '${sec.id}', 'image_url', true, 'items', ${idx})">
                            </label>
                        </div>
                    </div>
                    <div class="form-group-enhanced mb-0">
                        <label>Partner Link URL (Optional)</label>
                        <input type="text" class="form-control-enhanced" value="${item.link || '#'}" placeholder="#" oninput="updateNestedSetting('${sec.id}', 'items', ${idx}, 'link', this.value)">
                    </div>
                </div>
            `;
        });
        
        html += `
            </div>
            <button type="button" class="btn-add-nested mt-2" onclick="addNestedItem('${sec.id}', 'items', {image_url:'', link:'#'})">
                <i class="fas fa-plus mr-1"></i> Add Partner Logo
            </button>
        `;
    }
    
    return html;
}
</script>

</body>
</html>
