<?php
/**
 * Campus Resource Sharing Platform - Top Navigation Bar
 */
$currentUser = is_logged_in() ? current_user() : null;
$unreadNotifs = 0;
if ($currentUser) {
    require_once BASE_PATH . '/services/NotificationService.php';
    $notifService = new NotificationService();
    $unreadNotifs = $notifService->getUnreadCount((int)$currentUser['id']);
}
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top py-2">
    <div class="container">
        <a class="navbar-brand fs-4" href="<?= APP_URL ?>/index.php">
            <i class="bi bi-mortarboard-fill text-primary"></i>
            <span>Campus Share</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active fw-semibold' : '' ?>" href="<?= APP_URL ?>/index.php">
                        <i class="bi bi-house-door me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'browse_resources.php') ? 'active fw-semibold' : '' ?>" href="<?= APP_URL ?>/student/browse_resources.php">
                        <i class="bi bi-search me-1"></i>Browse Resources
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'donations.php') ? 'active fw-semibold' : '' ?>" href="<?= APP_URL ?>/student/donations.php">
                        <i class="bi bi-gift me-1"></i>Free Donations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'exchanges.php') ? 'active fw-semibold' : '' ?>" href="<?= APP_URL ?>/student/exchanges.php">
                        <i class="bi bi-arrow-repeat me-1"></i>Exchanges
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <?php if ($currentUser): ?>
                    <!-- Notification Bell -->
                    <a href="<?= APP_URL ?>/student/notifications.php" class="btn btn-light position-relative rounded-circle border shadow-sm p-2" title="Notifications">
                        <i class="bi bi-bell text-secondary fs-5"></i>
                        <?php if ($unreadNotifs > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $unreadNotifs ?>
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- Add Resource Quick Action -->
                    <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-outline-primary rounded-pill px-3 py-1 text-sm d-none d-sm-inline-flex align-items-center gap-1">
                        <i class="bi bi-plus-circle"></i>
                        <span>List Resource</span>
                    </a>

                    <!-- User Dropdown Menu -->
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle border rounded-pill d-flex align-items-center gap-2 py-1 px-3 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5 text-primary"></i>
                            <span class="fw-medium text-dark"><?= e(explode(' ', $currentUser['full_name'])[0]) ?></span>
                            <span class="badge bg-secondary-subtle text-secondary border text-xs"><?= strtoupper($currentUser['role']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2 p-2" style="min-width: 220px;">
                            <li><h6 class="dropdown-header text-uppercase text-xs"><?= e($currentUser['full_name']) ?></h6></li>
                            <?php if ($currentUser['role'] === 'admin'): ?>
                                <li><a class="dropdown-item py-2 rounded-2" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Console</a></li>
                            <?php elseif ($currentUser['role'] === 'faculty'): ?>
                                <li><a class="dropdown-item py-2 rounded-2" href="<?= APP_URL ?>/faculty/dashboard.php"><i class="bi bi-briefcase me-2 text-primary"></i>Faculty Dashboard</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item py-2 rounded-2" href="<?= APP_URL ?>/student/dashboard.php"><i class="bi bi-speedometer2 me-2 text-primary"></i>Student Dashboard</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item py-2 rounded-2" href="<?= APP_URL ?>/student/my_resources.php"><i class="bi bi-collection me-2 text-primary"></i>My Listings</a></li>
                            <li><a class="dropdown-item py-2 rounded-2" href="<?= APP_URL ?>/student/my_requests.php"><i class="bi bi-clock-history me-2 text-primary"></i>My Requests</a></li>
                            <li><a class="dropdown-item py-2 rounded-2" href="<?= APP_URL ?>/student/borrowed_items.php"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Borrowed Items</a></li>
                            <li><a class="dropdown-item py-2 rounded-2" href="<?= APP_URL ?>/student/profile.php"><i class="bi bi-person-gear me-2 text-primary"></i>Profile & Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 rounded-2 text-danger" href="<?= APP_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/login.php" class="btn btn-outline-primary rounded-pill px-4">Sign In</a>
                    <a href="<?= APP_URL ?>/register.php" class="btn btn-primary rounded-pill px-4">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
