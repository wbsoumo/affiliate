<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<style>
    /* Premium light themed sidebar style to match screenshot */
    .main-sidebar.sidebar-light-primary {
        background-color: #ffffff;
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
</style>

<!-- Sidebar -->
<aside class="main-sidebar sidebar-light-primary elevation-1">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link pl-3 d-flex align-items-center justify-content-between" style="text-decoration: none;">
        <span class="brand-text d-flex align-items-center">
            <i class="fas fa-check-double text-success mr-2" style="font-size: 1.1rem;"></i>
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

                <!-- Offers Menu -->
                <li class="nav-item has-treeview <?= in_array($currentPage, ['campaigns.php', 'offers.php', 'offer_edit.php', 'offer_details.php', 'create_campaign.php', 'create_offer.php', 'campaign_access.php']) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= in_array($currentPage, ['campaigns.php', 'offers.php', 'offer_edit.php', 'offer_details.php', 'create_campaign.php', 'create_offer.php', 'campaign_access.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-gift"></i>
                        <p>
                            Offers
                            <i class="right fas fa-angle-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="campaigns.php" class="nav-link <?= in_array($currentPage, ['campaigns.php', 'offers.php', 'offer_edit.php', 'offer_details.php']) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Manage Offers</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="create_campaign.php" class="nav-link <?= in_array($currentPage, ['create_campaign.php', 'create_offer.php']) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Create Offer</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="campaign_access.php" class="nav-link <?= ($currentPage == 'campaign_access.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Campaign Access</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Reports Menu -->
                <li class="nav-item has-treeview <?= in_array($currentPage, ['reports_campaigns.php', 'reports_affiliates.php', 'reports_advertisers.php']) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= in_array($currentPage, ['reports_campaigns.php', 'reports_affiliates.php', 'reports_advertisers.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>
                            Reports / Logs
                            <i class="right fas fa-angle-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="reports_campaigns.php" class="nav-link <?= ($currentPage == 'reports_campaigns.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Campaign Report</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports_affiliates.php" class="nav-link <?= ($currentPage == 'reports_affiliates.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Affiliate Report</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports_advertisers.php" class="nav-link <?= ($currentPage == 'reports_advertisers.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Advertiser Report</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Affiliates Menu -->
                <li class="nav-item has-treeview <?= in_array($currentPage, ['publishers.php', 'pending_kyc.php']) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= in_array($currentPage, ['publishers.php', 'pending_kyc.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user-friends"></i>
                        <p>
                            Affiliates
                            <i class="right fas fa-angle-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="publishers.php" class="nav-link <?= ($currentPage == 'publishers.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Manage Publishers</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="pending_kyc.php" class="nav-link <?= ($currentPage == 'pending_kyc.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Pending KYC</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Advertisers Menu -->
                <li class="nav-item has-treeview <?= in_array($currentPage, ['advertisers.php', 'advertiser_edit.php']) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= in_array($currentPage, ['advertisers.php', 'advertiser_edit.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-briefcase"></i>
                        <p>
                            Advertisers
                            <i class="right fas fa-angle-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="advertisers.php" class="nav-link <?= ($currentPage == 'advertisers.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Manage Advertisers</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Fraud Detection Menu -->
                <li class="nav-item has-treeview <?= in_array($currentPage, ['fraud_dashboard.php']) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= in_array($currentPage, ['fraud_dashboard.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-shield-alt"></i>
                        <p>
                            Fraud Detection
                            <i class="right fas fa-angle-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="fraud_dashboard.php" class="nav-link <?= ($currentPage == 'fraud_dashboard.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Fraud Signals</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Integration Menu -->
                <li class="nav-item has-treeview <?= in_array($currentPage, ['publisher_postbacks.php']) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= in_array($currentPage, ['publisher_postbacks.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-plug"></i>
                        <p>
                            Integration
                            <i class="right fas fa-angle-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="publisher_postbacks.php" class="nav-link <?= ($currentPage == 'publisher_postbacks.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Publisher Postbacks</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Tools Menu -->
                <li class="nav-item has-treeview <?= in_array($currentPage, ['homepage_editor.php']) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= in_array($currentPage, ['homepage_editor.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-wrench"></i>
                        <p>
                            Tools
                            <i class="right fas fa-angle-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="homepage_editor.php" class="nav-link <?= ($currentPage == 'homepage_editor.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Website Editor</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Account Menu -->
                <li class="nav-item has-treeview <?= in_array($currentPage, ['profile.php', 'account_managers.php', 'users.php']) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= in_array($currentPage, ['profile.php', 'account_managers.php', 'users.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user"></i>
                        <p>
                            Account
                            <i class="right fas fa-angle-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="profile.php" class="nav-link <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>My Profile</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="account_managers.php" class="nav-link <?= ($currentPage == 'account_managers.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Account Managers</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="users.php" class="nav-link <?= ($currentPage == 'users.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Manage Users</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Billing Link -->
                <li class="nav-item">
                    <a href="settings.php?tab=billing" class="nav-link">
                        <i class="nav-icon fas fa-file-invoice-dollar"></i>
                        <p>Billing</p>
                    </a>
                </li>
                
                <li class="nav-header">System</li>

                <li class="nav-item">
                    <a href="https://support.taskbazi.xyz" target="_blank" class="nav-link">
                        <i class="nav-icon fas fa-question-circle"></i>
                        <p>Support</p>
                    </a>
                </li>

                <!-- Settings Menu -->
                <li class="nav-item has-treeview <?= in_array($currentPage, ['settings.php']) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= in_array($currentPage, ['settings.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>
                            Settings
                            <i class="right fas fa-angle-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="settings.php" class="nav-link <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>System Settings</p>
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
        </nav>
    </div>

    <!-- Sidebar Footer Profile Widget -->
    <div class="sidebar-footer-profile">
        <div class="user-info">
            <div class="avatar-circle">
                <?= strtoupper(substr($adminName, 0, 1)) ?>
            </div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($adminName) ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
        <a href="../logout.php" class="logout-btn" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</aside>

<!-- Pure Vanilla Javascript Collapsible Menu Handler (No jQuery required during execution, preventing loading sync issues) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Highlight current path
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

    // 2. Click toggle implementation
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
