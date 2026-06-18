<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$advertiserName = $_SESSION['user_name'] ?? 'Advertiser';
?>
<style>
    /* Premium light themed sidebar style to match screenshot */
    .main-sidebar.sidebar-light-primary {
        background-color: #ffffff !important;
        border-right: 1px solid #e2e8f0;
    }
    .main-sidebar .brand-link {
        background-color: #ffffff !important;
        border-bottom: 1px solid #f1f5f9 !important;
        color: #0f172a !important;
        font-weight: 700 !important;
        height: 57px;
    }
    .sidebar-light-primary .nav-sidebar > .nav-item > .nav-link.active {
        background-color: #eff6ff !important;
        color: #2563eb !important;
        font-weight: 600;
        border-radius: 6px;
    }
    .sidebar-light-primary .nav-sidebar .nav-treeview > .nav-item > .nav-link.active {
        background-color: #f8fafc !important;
        color: #2563eb !important;
        font-weight: 600;
    }
    .sidebar-light-primary .nav-link {
        color: #475569 !important;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    .sidebar-light-primary .nav-link:hover {
        background-color: #f1f5f9;
        color: #0f172a !important;
    }
    .sidebar-light-primary .nav-header {
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8 !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 12px 16px 4px 16px;
    }
    .sidebar-footer-profile {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 15px;
        background-color: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        z-index: 1030;
    }
    .sidebar-footer-profile .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .sidebar-footer-profile .avatar-circle {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        font-weight: 700;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
    .sidebar-footer-profile .user-meta {
        line-height: 1.2;
    }
    .sidebar-footer-profile .user-name {
        font-weight: 600;
        font-size: 0.85rem;
        color: #1e293b;
    }
    .sidebar-footer-profile .user-role {
        font-size: 0.75rem;
        color: #64748b;
    }
    .sidebar-footer-profile .logout-btn {
        color: #94a3b8;
        transition: color 0.2s;
        font-size: 1.1rem;
    }
    .sidebar-footer-profile .logout-btn:hover {
        color: #ef4444;
    }
    .main-sidebar .sidebar {
        padding-bottom: 75px !important; /* space for footer profile */
    }
    
    /* Chevron Rotation transitions for collapsible treeview */
    .nav-item.has-treeview > .nav-link .right {
        transition: transform 0.2s ease;
    }
    .nav-item.has-treeview.menu-open > .nav-link .right {
        transform: rotate(90deg);
    }
    .nav-treeview {
        display: none;
    }
    .nav-item.menu-open > .nav-treeview {
        display: block;
    }

    /* Premium layout overrides for all pages including this sidebar */
    body {
        font-family: 'Inter', sans-serif !important;
        background-color: #f8fafc !important;
    }
    .content-wrapper {
        background-color: #f8fafc !important;
    }
    .card, .card-dashboard {
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
    }
    .card-header {
        background-color: #ffffff !important;
        border-bottom: 1px solid #f1f5f9 !important;
    }
    .table thead th, .table-dashboard thead th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-weight: 600 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        font-size: 0.8rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
    }
</style>

<!-- Sidebar -->
<aside class="main-sidebar sidebar-light-primary elevation-1">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link pl-3 d-flex align-items-center justify-content-between" style="text-decoration: none;">
        <span class="brand-text d-flex align-items-center">
            <i class="fas fa-briefcase text-primary mr-2" style="font-size: 1.1rem;"></i>
            <span style="font-size: 1.15rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">Taskbazi</span>
        </span>
        <button class="btn btn-link p-0 text-muted d-lg-none" data-widget="pushmenu" style="font-size: 1.1rem; line-height: 1;">
            <i class="fas fa-times"></i>
        </button>
    </a>

    <!-- Sidebar Menu -->
    <div class="sidebar">
        <nav class="mt-3">
            <ul class="nav nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                
                <li class="nav-header">Navigation</li>
                
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-header">Campaigns</li>

                <li class="nav-item">
                    <a href="campaigns.php" class="nav-link <?= ($currentPage == 'campaigns.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-bullhorn"></i>
                        <p>Manage Campaigns</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="offers.php" class="nav-link <?= ($currentPage == 'offers.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-gift"></i>
                        <p>All Offers</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="create_offer.php" class="nav-link <?= ($currentPage == 'create_offer.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        <p>Create New Offer</p>
                    </a>
                </li>

                <li class="nav-header">Reports & Analytics</li>

                <li class="nav-item">
                    <a href="reports_campaigns.php" class="nav-link <?= ($currentPage == 'reports_campaigns.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Campaign Reports</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="reports_conversions.php" class="nav-link <?= ($currentPage == 'reports_conversions.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-exchange-alt"></i>
                        <p>Conversion Reports</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="reports_affiliates.php" class="nav-link <?= ($currentPage == 'reports_affiliates.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Affiliate Reports</p>
                    </a>
                </li>

                <li class="nav-header">Tools</li>

                <li class="nav-item">
                    <a href="ip_whitelist.php" class="nav-link <?= ($currentPage == 'ip_whitelist.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tower-broadcast"></i>
                        <p>IP Whitelist</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="postback.php" class="nav-link <?= ($currentPage == 'postback.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-code"></i>
                        <p>Postback Manager</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="api.php" class="nav-link <?= ($currentPage == 'api.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-plug"></i>
                        <p>API Integration</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="optimization.php" class="nav-link <?= ($currentPage == 'optimization.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-rocket"></i>
                        <p>Optimization Tools</p>
                    </a>
                </li>

                <li class="nav-header">Account</li>

                <li class="nav-item">
                    <a href="profile.php" class="nav-link <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user"></i>
                        <p>Profile</p>
                    </a>
                </li>

            </ul>
        </nav>
    </div>

    <!-- Sidebar Footer Profile Widget -->
    <div class="sidebar-footer-profile">
        <div class="user-info">
            <div class="avatar-circle">
                <?= strtoupper(substr($advertiserName, 0, 1)) ?>
            </div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($advertiserName) ?></div>
                <div class="user-role">Advertiser</div>
            </div>
        </div>
        <a href="../logout.php" class="logout-btn" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</aside>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var currentUrl = window.location.pathname.split('/').pop();
    if (currentUrl === '') currentUrl = 'dashboard.php';
    
    var navLinks = document.querySelectorAll('.nav-sidebar a.nav-link');
    navLinks.forEach(function(link) {
        var href = link.getAttribute('href');
        if (href === currentUrl) {
            link.classList.add('active');
            
            var parentItem = link.closest('.has-treeview');
            while (parentItem) {
                parentItem.classList.add('menu-open');
                var parentLink = parentItem.querySelector('> a.nav-link');
                if (parentLink) parentLink.classList.add('active');
                
                var treeview = parentItem.querySelector('> .nav-treeview');
                if (treeview) treeview.style.display = 'block';
                
                parentItem = parentItem.parentElement.closest('.has-treeview');
            }
        }
    });

    var parentLinks = document.querySelectorAll('.nav-item.has-treeview > a.nav-link');
    parentLinks.forEach(function(pLink) {
        pLink.addEventListener('click', function(e) {
            e.preventDefault();
            var parent = this.parentElement;
            var treeview = parent.querySelector('.nav-treeview');
            if (!treeview) return;
            
            if (parent.classList.contains('menu-open')) {
                parent.classList.remove('menu-open');
                treeview.style.display = 'none';
            } else {
                var accordion = document.querySelector('.nav-sidebar').getAttribute('data-accordion');
                if (accordion !== 'false') {
                    var openMenus = document.querySelectorAll('.nav-item.has-treeview.menu-open');
                    openMenus.forEach(function(openMenu) {
                        if (openMenu !== parent) {
                            openMenu.classList.remove('menu-open');
                            var openTree = openMenu.querySelector('.nav-treeview');
                            if (openTree) openTree.style.display = 'none';
                        }
                    });
                }
                parent.classList.add('menu-open');
                treeview.style.display = 'block';
            }
        });
    });
});
</script>
