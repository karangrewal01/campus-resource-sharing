<?php
/**
 * Campus Resource Sharing Platform - Administrator Dashboard
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/AnalyticsService.php';

require_role('admin');

$analyticsService = new AnalyticsService();
$adminMetrics = $analyticsService->getAdminMetrics();

$pageTitle = "Administrator Dashboard";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center pb-4 mb-4 border-bottom gap-3">
            <div>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1"><i class="bi bi-shield-lock-fill me-1"></i>Administrator Console</span>
                <h2 class="fw-bold text-dark mb-1">Campus Management & Analytics</h2>
                <p class="text-muted mb-0">System metrics, moderation alerts, user accounts, and real-time campus sharing trends.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= APP_URL ?>/admin/reports.php" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Reports
                </a>
                <a href="<?= APP_URL ?>/admin/settings.php" class="btn btn-primary rounded-pill px-3 shadow-sm">
                    <i class="bi bi-sliders me-1"></i> Campus Policies
                </a>
            </div>
        </div>

        <?= display_flash_alerts() ?>

        <!-- Pending Moderation / Flagged Alert if any -->
        <?php if ($adminMetrics['reports_pending'] > 0): ?>
            <div class="alert alert-warning d-flex align-items-center rounded-4 shadow-sm mb-4" role="alert">
                <i class="bi bi-flag-fill fs-3 me-3 text-warning-emphasis flex-shrink-0"></i>
                <div>
                    <h6 class="fw-bold mb-1"><?= $adminMetrics['reports_pending'] ?> Resource Report(s) Pending Review</h6>
                    <p class="small mb-0">Campus members have flagged one or more listings for administrative inspection.</p>
                </div>
                <a href="<?= APP_URL ?>/admin/resources.php?tab=reports" class="btn btn-warning btn-sm rounded-pill ms-auto px-3 flex-shrink-0">
                    Inspect Reports
                </a>
            </div>
        <?php endif; ?>

        <!-- Stat Cards Row 1: Users & Resources -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <a href="<?= APP_URL ?>/admin/users.php" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $adminMetrics['users_total'] ?></h3>
                            <span class="text-xs text-muted fw-semibold"><?= $adminMetrics['students_count'] ?> Students &bull; <?= $adminMetrics['faculty_count'] ?> Faculty</span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-sm-6">
                <a href="<?= APP_URL ?>/admin/resources.php" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $adminMetrics['resources_total'] ?></h3>
                            <span class="text-xs text-muted fw-semibold"><?= $adminMetrics['resources_available'] ?> Available for Sharing</span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-sm-6">
                <a href="<?= APP_URL ?>/admin/transactions.php" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-icon bg-indigo-subtle text-indigo" style="background-color: #ede9fe; color: #4f46e5;">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $adminMetrics['transactions_active'] ?></h3>
                            <span class="text-xs text-muted fw-semibold">Active Borrows Ongoing</span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-sm-6">
                <a href="<?= APP_URL ?>/admin/transactions.php?status=overdue" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger-subtle text-danger">
                            <i class="bi bi-alarm-fill <?= ($adminMetrics['transactions_overdue'] > 0) ? 'animate-pulse' : '' ?>"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 <?= ($adminMetrics['transactions_overdue'] > 0) ? 'text-danger' : 'text-dark' ?>">
                                <?= $adminMetrics['transactions_overdue'] ?>
                            </h3>
                            <span class="text-xs text-muted fw-semibold">Overdue Items Alert</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Category Distribution Chart -->
            <div class="col-lg-6">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-pie-chart text-primary me-2"></i>Resource Category Distribution</h5>
                    <div style="position: relative; height: 280px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Sharing Method Distribution Chart -->
            <div class="col-lg-6">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-steps text-primary me-2"></i>Sharing Methods Breakdown</h5>
                    <div style="position: relative; height: 280px;">
                        <canvas id="methodChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Metrics & Recent Transactions Table -->
        <div class="row g-4">
            <!-- Summary Highlights -->
            <div class="col-lg-4">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-trophy text-primary me-2"></i>Campus Impact Summary</h5>
                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-xs text-muted text-uppercase fw-bold">Completed Borrows</span>
                                <h4 class="fw-bold mb-0 text-success"><?= $adminMetrics['transactions_completed'] ?></h4>
                            </div>
                            <i class="bi bi-check2-circle text-success fs-2"></i>
                        </div>

                        <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-xs text-muted text-uppercase fw-bold">Free Donations Completed</span>
                                <h4 class="fw-bold mb-0 text-primary"><?= $adminMetrics['donations_completed'] ?></h4>
                            </div>
                            <i class="bi bi-gift-fill text-primary fs-2"></i>
                        </div>

                        <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-xs text-muted text-uppercase fw-bold">Item Exchanges Completed</span>
                                <h4 class="fw-bold mb-0 text-warning-emphasis"><?= $adminMetrics['exchanges_completed'] ?></h4>
                            </div>
                            <i class="bi bi-arrow-repeat text-warning fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent System Activity -->
            <div class="col-lg-8">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-activity text-primary me-2"></i>Recent Borrowing Handover Activity</h5>
                        <a href="<?= APP_URL ?>/admin/transactions.php" class="text-xs text-primary fw-semibold text-decoration-none">View All Transactions</a>
                    </div>

                    <?php if (empty($adminMetrics['recent_transactions'])): ?>
                        <div class="text-center py-4 text-muted">
                            <p class="small mb-0">No recent transactions recorded in the system.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-custom align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Resource</th>
                                        <th>Lender</th>
                                        <th>Borrower</th>
                                        <th>Handover Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($adminMetrics['recent_transactions'] as $t): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?= e($t['title']) ?></td>
                                            <td class="small text-muted"><?= e($t['owner_name']) ?></td>
                                            <td class="small text-muted"><?= e($t['borrower_name']) ?></td>
                                            <td class="small"><?= format_date($t['handover_at']) ?></td>
                                            <td><?= status_badge($t['transaction_status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Chart.js Initialization Script with Real Database Datasets -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Category Chart
    const catLabels = <?= json_encode(array_column($adminMetrics['category_chart'], 'category_name')) ?>;
    const catData = <?= json_encode(array_map('intval', array_column($adminMetrics['category_chart'], 'count'))) ?>;
    const catColors = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#0284c7', '#ea580c', '#16a34a', '#db2777', '#4b5563', '#4f46e5'];

    const ctxCat = document.getElementById('categoryChart');
    if (ctxCat) {
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catData,
                    backgroundColor: catColors.slice(0, catLabels.length),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 12, font: { size: 11 } }
                    }
                }
            }
        });
    }

    // 2. Sharing Method Chart
    const methodLabels = <?= json_encode(array_map(function($m) { return ucwords(str_replace('_', ' ', $m['sharing_method'])); }, $adminMetrics['method_chart'])) ?>;
    const methodData = <?= json_encode(array_map(function($m) { return (int)$m['count']; }, $adminMetrics['method_chart'])) ?>;

    const ctxMethod = document.getElementById('methodChart');
    if (ctxMethod) {
        new Chart(ctxMethod, {
            type: 'bar',
            data: {
                labels: methodLabels,
                datasets: [{
                    label: 'Resources Listed',
                    data: methodData,
                    backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#06b6d4'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
