<?php
/**
 * Super Admin - SaaS Plan Management (Dynamic Version)
 * PHP 7.1+
 */

define('APP_INIT', true);
define('SUPER_ADMIN_CONTEXT', true);

require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/config/database.php';

require_role('super_admin');

$adminName = $_SESSION['super_auth']['name'] ?? 'Super Admin';
$error = null;
$successMessage = null;

// Handle Plan update POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_plan') {
    $planId = (int)$_POST['plan_id'];
    $price = trim($_POST['price'] ?? '');
    $offersLimit = trim($_POST['offers_limit'] ?? '');
    $publishersLimit = trim($_POST['publishers_limit'] ?? '');
    $advertisersLimit = trim($_POST['advertisers_limit'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color = trim($_POST['color'] ?? '#60a5fa');

    if ($price === '' || $offersLimit === '' || $publishersLimit === '' || $advertisersLimit === '') {
        $error = 'All fields except description are required.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE saas_plans 
                SET price = :price, 
                    offers_limit = :offers_limit, 
                    publishers_limit = :publishers_limit, 
                    advertisers_limit = :advertisers_limit, 
                    description = :description, 
                    color = :color 
                WHERE id = :id
            ");
            $stmt->execute([
                'price' => $price,
                'offers_limit' => $offersLimit,
                'publishers_limit' => $publishersLimit,
                'advertisers_limit' => $advertisersLimit,
                'description' => $description,
                'color' => $color,
                'id' => $planId
            ]);
            $successMessage = 'Plan updated successfully!';
        } catch (PDOException $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}

// Count tenants per plan
$plansDistribution = $pdo->query("
    SELECT plan_name, COUNT(*) as count 
    FROM tenants 
    WHERE status != 'deleted' 
    GROUP BY plan_name
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Retrieve SaaS plans dynamically from database
$dbPlans = $pdo->query("SELECT * FROM saas_plans ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$plans = [];
foreach ($dbPlans as $dp) {
    $plans[] = [
        'id' => (int)$dp['id'],
        'name' => $dp['name'],
        'price' => $dp['price'],
        'offers_limit' => $dp['offers_limit'],
        'publishers_limit' => $dp['publishers_limit'],
        'advertisers_limit' => $dp['advertisers_limit'],
        'description' => $dp['description'],
        'color' => $dp['color'],
        'count' => $plansDistribution[$dp['name']] ?? 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SaaS Pricing Plans · Taskbazi Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
        }
        /* Side Navigation */
        .sidebar {
            width: 260px;
            background-color: #1e293b;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: column;
            padding: 24px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
            font-weight: 700;
            font-size: 20px;
            color: #c084fc;
        }
        .brand i { font-size: 24px; }
        .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .nav-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .nav-item.active a, .nav-item a:hover {
            background-color: #334155;
            color: #f8fafc;
        }
        .nav-item a i { width: 20px; }
        .sidebar-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #f8fafc;
            margin-bottom: 16px;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a855f7, #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        /* Content */
        .content { flex: 1; padding: 40px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h1 { font-size: 28px; font-weight: 700; }
        .header p { color: #94a3b8; margin-top: 4px; }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 32px;
            margin-top: 24px;
        }
        .plan-card {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: relative;
            overflow: hidden;
        }
        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .plan-name { font-size: 20px; font-weight: 700; }
        .plan-price { font-size: 28px; font-weight: 800; }
        .plan-desc { font-size: 14px; color: #94a3b8; line-height: 1.5; }
        
        .plan-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 20px;
            margin-top: 10px;
        }
        .plan-features li { display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .plan-features li i { color: #34d399; }
        
        .plan-distribution {
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            font-weight: 600;
            margin-top: auto;
        }

        /* Action Buttons */
        .edit-btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: background 0.2s;
            width: 100%;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .edit-btn:hover {
            background-color: #2563eb;
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            width: 90%;
            max-width: 480px;
            padding: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .modal-header h2 {
            font-size: 20px;
            font-weight: 700;
        }
        .close-btn {
            font-size: 28px;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close-btn:hover {
            color: #f1f5f9;
        }
        .form-group-modal {
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group-modal label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .form-group-modal input, .form-group-modal textarea {
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            color: white;
            padding: 12px 16px;
            font-size: 14px;
            width: 100%;
        }
        .form-group-modal input:focus, .form-group-modal textarea:focus {
            outline: none;
            border-color: #a855f7;
        }
        .save-btn {
            background: linear-gradient(145deg, #a855f7, #7e22ce);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 4px 14px rgba(168, 85, 247, 0.3);
            transition: all 0.2s;
        }
        .save-btn:hover {
            background: linear-gradient(145deg, #7e22ce, #6b21a8);
            box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
        }
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand">
            <i class="fas fa-chart-network"></i>
            <span>Taskbazi Control</span>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php">
                    <i class="fas fa-grid-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="tenants.php">
                    <i class="fas fa-users-gear"></i> Tenants / Networks
                </a>
            </li>
            <li class="nav-item">
                <a href="domains.php">
                    <i class="fas fa-globe"></i> Domain Router
                </a>
            </li>
            <li class="nav-item active">
                <a href="plans.php">
                    <i class="fas fa-cubes"></i> SaaS Plans
                </a>
            </li>
            <li class="nav-item">
                <a href="platform_settings.php">
                    <i class="fas fa-sliders"></i> Platform Settings
                </a>
            </li>
            <li class="nav-item">
                <a href="data_browser.php">
                    <i class="fas fa-database"></i> Database Browser
                </a>
            </li>
            <li class="nav-item">
                <a href="audit_logs.php">
                    <i class="fas fa-receipt"></i> System Audit Logs
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="avatar">
                    <?=strtoupper(substr($adminName, 0, 1))?>
                </div>
                <div>
                    <h4 style="font-size: 14px; font-weight: 600;"><?=$adminName?></h4>
                    <span style="font-size: 12px; color: #94a3b8;">Super Admin</span>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content">
        <div class="header">
            <div>
                <h1>SaaS Subscription Tiers</h1>
                <p>Manage subscription packages, pricing limits, and review tenant distributions.</p>
            </div>
        </div>

        <?php if ($successMessage): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle mr-2"></i> <?=$successMessage?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle mr-2"></i> <?=$error?>
            </div>
        <?php endif; ?>

        <div class="plans-grid">
            <?php foreach ($plans as $p): ?>
                <div class="plan-card" style="border-top: 4px solid <?=$p['color']?>">
                    <div class="plan-header">
                        <span class="plan-name" style="color: <?=$p['color']?>"><?=$p['name']?></span>
                        <span class="plan-price"><?=$p['price']?></span>
                    </div>
                    <p class="plan-desc"><?=$p['description']?></p>
                    
                    <ul class="plan-features">
                        <li><i class="fas fa-check-circle"></i> Max Offers: <strong><?=$p['offers_limit']?></strong></li>
                        <li><i class="fas fa-check-circle"></i> Max Publishers: <strong><?=$p['publishers_limit']?></strong></li>
                        <li><i class="fas fa-check-circle"></i> Max Advertisers: <strong><?=$p['advertisers_limit']?></strong></li>
                        <li><i class="fas fa-check-circle"></i> Full API & Reports access</li>
                    </ul>
                    
                    <div class="plan-distribution" style="margin-bottom: 10px;">
                        <i class="fas fa-network-wired" style="color: <?=$p['color']?>; margin-right: 8px;"></i>
                        <span><?=$p['count']?> Active Portals</span>
                    </div>

                    <button class="edit-btn" onclick='openEditModal(<?=json_encode($p)?>)'>
                        <i class="fas fa-pen-to-square"></i> Edit Plan
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Plan: <span id="modalPlanName"></span></h2>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="edit_plan">
                <input type="hidden" name="plan_id" id="edit_plan_id">
                
                <div class="form-group-modal">
                    <label for="edit_price">Price (e.g. $99/mo)</label>
                    <input type="text" id="edit_price" name="price" required>
                </div>
                <div class="form-group-modal">
                    <label for="edit_offers">Max Offers Limit</label>
                    <input type="text" id="edit_offers" name="offers_limit" required>
                </div>
                <div class="form-group-modal">
                    <label for="edit_publishers">Max Publishers Limit</label>
                    <input type="text" id="edit_publishers" name="publishers_limit" required>
                </div>
                <div class="form-group-modal">
                    <label for="edit_advertisers">Max Advertisers Limit</label>
                    <input type="text" id="edit_advertisers" name="advertisers_limit" required>
                </div>
                <div class="form-group-modal">
                    <label for="edit_color">Color (Hex/CSS)</label>
                    <input type="text" id="edit_color" name="color" required>
                </div>
                <div class="form-group-modal">
                    <label for="edit_desc">Description</label>
                    <textarea id="edit_desc" name="description" rows="3"></textarea>
                </div>
                
                <button type="submit" class="save-btn">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('editModal');
        
        function openEditModal(plan) {
            document.getElementById('modalPlanName').innerText = plan.name;
            document.getElementById('edit_plan_id').value = plan.id;
            document.getElementById('edit_price').value = plan.price;
            document.getElementById('edit_offers').value = plan.offers_limit;
            document.getElementById('edit_publishers').value = plan.publishers_limit;
            document.getElementById('edit_advertisers').value = plan.advertisers_limit;
            document.getElementById('edit_color').value = plan.color;
            document.getElementById('edit_desc').value = plan.description;
            
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Close if click outside modal content
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
