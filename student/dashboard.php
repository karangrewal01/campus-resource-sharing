<?php
/**
 * Campus Resource Sharing Platform - Student Dashboard
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/AnalyticsService.php';
require_once BASE_PATH . '/services/NotificationService.php';

require_auth();

$user = current_user();
$analyticsService = new AnalyticsService();
$notifService = new NotificationService();

$metrics = $analyticsService->getStudentMetrics((int)$user['id']);
$recentNotifications = $notifService->getUserNotifications((int)$user['id'], 5);

$pageTitle = "Student Dashboard";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <!-- Welcome Banner -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center pb-4 mb-4 border-bottom gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-1">Student Dashboard</h2>
                <p class="text-muted mb-0">Welcome back, <?= e($user['full_name']) ?>! Keep track of your borrowed, lent, and requested campus resources.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= APP_URL ?>/student/browse_resources.php" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
                    <i class="bi bi-search me-1"></i> Browse Catalog
                </a>
                <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-primary rounded-pill px-3 shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> List Resource
                </a>
            </div>
        </div>

        <?= display_flash_alerts() ?>

        <!-- Overdue Alert Banner if any -->
        <?php if ($metrics['overdue_count'] > 0): ?>
            <div class="alert alert-danger d-flex align-items-center rounded-4 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-octagon-fill fs-2 me-3 flex-shrink-0 animate-pulse text-danger"></i>
                <div>
                    <h6 class="fw-bold mb-1">Action Required: You have <?= $metrics['overdue_count'] ?> Overdue Resource(s)!</h6>
                    <p class="small mb-0">Please coordinate with the resource owner immediately and confirm return to restore your full borrowing privileges.</p>
                </div>
                <a href="<?= APP_URL ?>/student/borrowed_items.php" class="btn btn-danger btn-sm rounded-pill ms-auto px-3 flex-shrink-0">
                    View Overdue Items
                </a>
            </div>
        <?php endif; ?>

        <!-- Stat Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <a href="<?= APP_URL ?>/student/my_resources.php" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-collection-fill"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $metrics['total_listings'] ?></h3>
                            <span class="text-xs text-muted fw-semibold">My Listed Items</span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-sm-6">
                <a href="<?= APP_URL ?>/student/borrowed_items.php" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-icon bg-indigo-subtle text-indigo" style="background-color: #ede9fe; color: #6366f1;">
                            <i class="bi bi-box-arrow-in-down"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $metrics['currently_borrowed'] ?></h3>
                            <span class="text-xs text-muted fw-semibold">Currently Borrowed</span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-sm-6">
                <a href="<?= APP_URL ?>/student/lent_items.php" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-icon bg-teal-subtle text-teal" style="background-color: #ccfbf1; color: #0d9488;">
                            <i class="bi bi-box-arrow-up"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $metrics['currently_lent'] ?></h3>
                            <span class="text-xs text-muted fw-semibold">Currently Lent Out</span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-sm-6">
                <a href="<?= APP_URL ?>/student/lending_requests.php" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-subtle text-warning-emphasis">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $metrics['pending_approvals'] ?></h3>
                            <span class="text-xs text-muted fw-semibold">Pending Requests to Approve</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Upcoming Deadlines -->
            <div class="col-lg-7">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-alarm text-primary me-2"></i>Upcoming Return Deadlines</h5>
                        <a href="<?= APP_URL ?>/student/borrowed_items.php" class="text-xs text-primary text-decoration-none fw-semibold">View All</a>
                    </div>

                    <?php if (empty($metrics['upcoming_deadlines'])): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-check2-circle text-success fs-1"></i>
                            <p class="small mt-2 mb-0">No upcoming return deadlines in the next 3 days. You are all set!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>Resource</th>
                                        <th>Owner</th>
                                        <th>Due Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($metrics['upcoming_deadlines'] as $due): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?= e($due['title']) ?></td>
                                            <td class="small text-muted"><?= e($due['owner_name']) ?></td>
                                            <td>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                    <?= format_date($due['due_at']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= APP_URL ?>/student/messages.php?request_id=<?= $due['request_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    Chat Owner
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Notifications -->
            <div class="col-lg-5">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-bell text-primary me-2"></i>Recent Notifications</h5>
                        <a href="<?= APP_URL ?>/student/notifications.php" class="text-xs text-primary text-decoration-none fw-semibold">All Notifications</a>
                    </div>

                    <?php if (empty($recentNotifications)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-bell-slash fs-1"></i>
                            <p class="small mt-2 mb-0">No notifications yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($recentNotifications as $notif): ?>
                                <div class="p-2 rounded-3 border-bottom <?= empty($notif['read_at']) ? 'bg-primary-subtle' : 'bg-white' ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="mb-1 text-sm fw-semibold text-dark"><?= e($notif['title']) ?></h6>
                                        <small class="text-xs text-muted flex-shrink-0"><?= format_date($notif['created_at']) ?></small>
                                    </div>
                                    <p class="text-xs text-muted mb-0"><?= e($notif['message']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sharing Impact Summary -->
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-xs text-muted text-uppercase fw-bold">Completed Borrows</span>
                        <h4 class="fw-bold mb-0 text-success"><?= $metrics['completed_transactions'] ?></h4>
                    </div>
                    <i class="bi bi-check-circle-fill text-success fs-2"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-xs text-muted text-uppercase fw-bold">Free Donations Done</span>
                        <h4 class="fw-bold mb-0 text-primary"><?= $metrics['completed_donations'] ?></h4>
                    </div>
                    <i class="bi bi-gift-fill text-primary fs-2"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-xs text-muted text-uppercase fw-bold">Completed Exchanges</span>
                        <h4 class="fw-bold mb-0 text-warning-emphasis"><?= $metrics['completed_exchanges'] ?></h4>
                    </div>
                    <i class="bi bi-arrow-repeat text-warning fs-2"></i>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
