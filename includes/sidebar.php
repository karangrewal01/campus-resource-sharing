<?php
/**
 * Campus Resource Sharing Platform - Contextual Dashboard Sidebar
 */
$user = current_user();
if (!$user) {
    return;
}

$role = $user['role'];
$currentScript = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="dashboard-sidebar">
    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom px-2">
        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 44px; height: 44px;">
            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
        </div>
        <div class="overflow-hidden">
            <h6 class="mb-0 text-truncate fw-semibold"><?= e($user['full_name']) ?></h6>
            <span class="badge bg-primary text-white text-xs text-uppercase"><?= e($user['role']) ?></span>
            <small class="d-block text-muted text-xs text-truncate"><?= e($user['institution_id']) ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <div class="text-xs text-uppercase text-muted fw-bold px-3 mb-2">Administration</div>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="nav-link <?= ($currentScript == 'dashboard.php' && $currentDir == 'admin') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Overview
            </a>
            <a href="<?= APP_URL ?>/admin/users.php" class="nav-link <?= ($currentScript == 'users.php') ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Manage Users
            </a>
            <a href="<?= APP_URL ?>/admin/resources.php" class="nav-link <?= ($currentScript == 'resources.php' && $currentDir == 'admin') ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> Moderation
            </a>
            <a href="<?= APP_URL ?>/admin/categories.php" class="nav-link <?= ($currentScript == 'categories.php') ? 'active' : '' ?>">
                <i class="bi bi-tags"></i> Categories
            </a>
            <a href="<?= APP_URL ?>/admin/transactions.php" class="nav-link <?= ($currentScript == 'transactions.php') ? 'active' : '' ?>">
                <i class="bi bi-arrow-left-right"></i> All Transactions
            </a>
            <a href="<?= APP_URL ?>/admin/reports.php" class="nav-link <?= ($currentScript == 'reports.php') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i> Reports & Export
            </a>
            <a href="<?= APP_URL ?>/admin/announcements.php" class="nav-link <?= ($currentScript == 'announcements.php' && $currentDir == 'admin') ? 'active' : '' ?>">
                <i class="bi bi-megaphone"></i> Announcements
            </a>
            <a href="<?= APP_URL ?>/admin/settings.php" class="nav-link <?= ($currentScript == 'settings.php') ? 'active' : '' ?>">
                <i class="bi bi-sliders"></i> Campus Policies
            </a>
            <a href="<?= APP_URL ?>/admin/audit_logs.php" class="nav-link <?= ($currentScript == 'audit_logs.php') ? 'active' : '' ?>">
                <i class="bi bi-shield-check"></i> Audit Logs
            </a>

            <div class="text-xs text-uppercase text-muted fw-bold px-3 mt-3 mb-2">Marketplace</div>
        <?php elseif ($role === 'faculty'): ?>
            <div class="text-xs text-uppercase text-muted fw-bold px-3 mb-2">Faculty Panel</div>
            <a href="<?= APP_URL ?>/faculty/dashboard.php" class="nav-link <?= ($currentScript == 'dashboard.php' && $currentDir == 'faculty') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Faculty Overview
            </a>
            <a href="<?= APP_URL ?>/faculty/resources.php" class="nav-link <?= ($currentScript == 'resources.php' && $currentDir == 'faculty') ? 'active' : '' ?>">
                <i class="bi bi-mortarboard"></i> Shared Resources
            </a>
            <a href="<?= APP_URL ?>/faculty/requests.php" class="nav-link <?= ($currentScript == 'requests.php' && $currentDir == 'faculty') ? 'active' : '' ?>">
                <i class="bi bi-inbox"></i> Student Requests
            </a>
            <a href="<?= APP_URL ?>/faculty/announcements.php" class="nav-link <?= ($currentScript == 'announcements.php' && $currentDir == 'faculty') ? 'active' : '' ?>">
                <i class="bi bi-megaphone"></i> Post Notice
            </a>

            <div class="text-xs text-uppercase text-muted fw-bold px-3 mt-3 mb-2">Campus Sharing</div>
        <?php else: ?>
            <div class="text-xs text-uppercase text-muted fw-bold px-3 mb-2">Student Portal</div>
            <a href="<?= APP_URL ?>/student/dashboard.php" class="nav-link <?= ($currentScript == 'dashboard.php' && $currentDir == 'student') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> My Dashboard
            </a>
        <?php endif; ?>

        <!-- Common Resource & Sharing Links -->
        <a href="<?= APP_URL ?>/student/browse_resources.php" class="nav-link <?= ($currentScript == 'browse_resources.php') ? 'active' : '' ?>">
            <i class="bi bi-search"></i> Browse Catalog
        </a>
        <a href="<?= APP_URL ?>/student/add_resource.php" class="nav-link <?= ($currentScript == 'add_resource.php') ? 'active' : '' ?>">
            <i class="bi bi-plus-circle"></i> List New Resource
        </a>
        <a href="<?= APP_URL ?>/student/my_resources.php" class="nav-link <?= ($currentScript == 'my_resources.php') ? 'active' : '' ?>">
            <i class="bi bi-collection"></i> My Listings
        </a>
        <a href="<?= APP_URL ?>/student/lending_requests.php" class="nav-link <?= ($currentScript == 'lending_requests.php') ? 'active' : '' ?>">
            <i class="bi bi-inbox-fill"></i> Incoming Requests
        </a>
        <a href="<?= APP_URL ?>/student/my_requests.php" class="nav-link <?= ($currentScript == 'my_requests.php') ? 'active' : '' ?>">
            <i class="bi bi-send-fill"></i> Outgoing Requests
        </a>
        <a href="<?= APP_URL ?>/student/borrowed_items.php" class="nav-link <?= ($currentScript == 'borrowed_items.php') ? 'active' : '' ?>">
            <i class="bi bi-box-arrow-in-down"></i> Borrowed Items
        </a>
        <a href="<?= APP_URL ?>/student/lent_items.php" class="nav-link <?= ($currentScript == 'lent_items.php') ? 'active' : '' ?>">
            <i class="bi bi-box-arrow-up"></i> Lent Items
        </a>
        <a href="<?= APP_URL ?>/student/donations.php" class="nav-link <?= ($currentScript == 'donations.php') ? 'active' : '' ?>">
            <i class="bi bi-gift"></i> Donations
        </a>
        <a href="<?= APP_URL ?>/student/exchanges.php" class="nav-link <?= ($currentScript == 'exchanges.php') ? 'active' : '' ?>">
            <i class="bi bi-arrow-repeat"></i> Exchanges
        </a>

        <div class="text-xs text-uppercase text-muted fw-bold px-3 mt-3 mb-2">Account</div>
        <a href="<?= APP_URL ?>/student/notifications.php" class="nav-link <?= ($currentScript == 'notifications.php') ? 'active' : '' ?>">
            <i class="bi bi-bell"></i> Notifications
        </a>
        <a href="<?= APP_URL ?>/student/profile.php" class="nav-link <?= ($currentScript == 'profile.php') ? 'active' : '' ?>">
            <i class="bi bi-person-gear"></i> Profile & Password
        </a>
        <a href="<?= APP_URL ?>/logout.php" class="nav-link text-danger mt-2">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </nav>
</aside>
