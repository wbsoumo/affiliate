<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link text-center">
        <span class="brand-text font-weight-light" style="font-size: 1.5rem;">
            <i class="fas fa-crown mr-2"></i>
            <strong>Admin</strong>
        </span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-header">CAMPAIGNS</li>
                <li class="nav-item">
                    <a href="campaigns.php" class="nav-link <?= in_array($currentPage, ['campaigns.php', 'offers.php', 'offer_edit.php', 'offer_details.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-bullhorn"></i>
                        <p>
                            Manage Campaigns
                            <?php if (isset($summary['pending_offers']) && $summary['pending_offers'] > 0): ?>
                            <span class="badge badge-warning right"><?php echo $summary['pending_offers']; ?></span>
                            <?php elseif (isset($pendingOffersCount) && $pendingOffersCount > 0): ?>
                            <span class="badge badge-warning right"><?php echo $pendingOffersCount; ?></span>
                            <?php endif; ?>
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="create_campaign.php" class="nav-link <?= in_array($currentPage, ['create_campaign.php', 'create_offer.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-plus"></i>
                        <p>Create Campaign</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="campaign_access.php" class="nav-link <?= ($currentPage == 'campaign_access.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-key"></i>
                        <p>Campaign Access</p>
                    </a>
                </li>

                <li class="nav-header">REPORTS</li>
                <li class="nav-item">
                    <a href="reports_campaigns.php" class="nav-link <?= ($currentPage == 'reports_campaigns.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Campaign Report</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports_affiliates.php" class="nav-link <?= ($currentPage == 'reports_affiliates.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Affiliate Report</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports_advertisers.php" class="nav-link <?= ($currentPage == 'reports_advertisers.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-building"></i>
                        <p>Advertiser Report</p>
                    </a>
                </li>

                <li class="nav-header">PUBLISHERS</li>
                <li class="nav-item">
                    <a href="publishers.php" class="nav-link <?= ($currentPage == 'publishers.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user-friends"></i>
                        <p>Manage Publishers</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="publisher_postbacks.php" class="nav-link <?= ($currentPage == 'publisher_postbacks.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-link"></i>
                        <p>Publisher Postbacks</p>
                    </a>
                </li>

                <li class="nav-header">ADVERTISERS</li>
                <li class="nav-item">
                    <a href="advertisers.php" class="nav-link <?= in_array($currentPage, ['advertisers.php', 'advertiser_edit.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-briefcase"></i>
                        <p>Manage Advertisers</p>
                    </a>
                </li>

                <li class="nav-header">ACCOUNT</li>
                <li class="nav-item">
                    <a href="account_managers.php" class="nav-link <?= ($currentPage == 'account_managers.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user-tie"></i>
                        <p>Account Managers</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="homepage_editor.php" class="nav-link <?= ($currentPage == 'homepage_editor.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-file-signature"></i>
                        <p>Website Editor</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Settings</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
