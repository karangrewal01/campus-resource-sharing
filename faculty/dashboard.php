<?php
/**
 * Campus Resource Sharing Platform - Faculty Dashboard
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/AnalyticsService.php';
require_once BASE_PATH . '/services/ResourceService.php';
require_once BASE_PATH . '/services/RequestService.php';

require_role(['faculty', 'admin']);

$user = current_user();
$analyticsService = new AnalyticsService();
$resourceService = new ResourceService();
$requestService = new RequestService();

$facultyMetrics = $analyticsService->getFacultyMetrics((int)$user['id'], (int)($user['department_id'] ?? 0));
$pendingRequests = $requestService->getIncomingRequests((int)$user['id']);
$myResources = $resourceService->getByOwner((int)$user['id']);

$pageTitle = "Faculty Dashboard";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center pb-4 mb-4 border-bottom gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-1">Faculty Academic Dashboard</h2>
                <p class="text-muted mb-0">Welcome, <?= e($user['full_name']) ?>! Coordinate reference textbooks, lab apparatus, and student academic requests.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= APP_URL ?>/faculty/announcements.php" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
                    <i class="bi bi-megaphone me-1"></i> Post Department Notice
                </a>
                <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-primary rounded-pill px-3 shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Share Academic Material
                </a>
            </div>
        </div>

        <?= display_flash_alerts() ?>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-dark"><?= $facultyMetrics['my_resources'] ?></h3>
                        <span class="text-xs text-muted fw-semibold">Resources I've Shared</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-dark"><?= $facultyMetrics['active_borrows'] ?></h3>
                        <span class="text-xs text-muted fw-semibold">Active Student Borrows</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon bg-info-subtle text-info-emphasis">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-dark"><?= $facultyMetrics['dept_resources'] ?></h3>
                        <span class="text-xs text-muted fw-semibold">Total Department Materials</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon bg-warning-subtle text-warning-emphasis">
                        <i class="bi bi-inbox-fill"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-dark"><?= $facultyMetrics['pending_requests'] ?></h3>
                        <span class="text-xs text-muted fw-semibold">Pending Student Requests</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Student Requests Table -->
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-inbox text-primary me-2"></i>Pending Student Borrowing Requests</h5>
                <a href="<?= APP_URL ?>/faculty/requests.php" class="text-xs text-primary fw-semibold text-decoration-none">Manage All Requests</a>
            </div>

            <?php if (empty($pendingRequests)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-check-circle text-success fs-1"></i>
                    <p class="small mt-2 mb-0">No pending student requests. All borrowing approvals are up to date!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Requested Material</th>
                                <th>Requested Duration</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRequests as $req): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($req['requester_name']) ?></div>
                                        <div class="text-xs text-muted"><?= e($req['requester_inst_id']) ?> &bull; <?= e($req['requester_course'] ?? 'Student') ?></div>
                                    </td>
                                    <td class="fw-bold text-dark"><?= e($req['resource_title']) ?></td>
                                    <td><span class="small"><?= $req['requested_duration_days'] ?> day(s) from <?= format_date($req['requested_start_date']) ?></span></td>
                                    <td><span class="small text-muted text-truncate d-inline-block" style="max-width: 200px;"><?= e($req['purpose'] ?: 'Exam preparation') ?></span></td>
                                    <td><?= status_badge($req['status']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= APP_URL ?>/student/lending_requests.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            Process Request
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
