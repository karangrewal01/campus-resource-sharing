<?php
/**
 * Campus Resource Sharing Platform - Reports & Analytics Export
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/AnalyticsService.php';

require_role('admin');

$analyticsService = new AnalyticsService();
$pdo = Database::getConnection();

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $filters = [
        'status'    => trim($_GET['status'] ?? ''),
        'from_date' => trim($_GET['from_date'] ?? ''),
        'to_date'   => trim($_GET['to_date'] ?? '')
    ];
    $analyticsService->exportTransactionsCsv($filters);
    exit;
}

$statusFilter = trim($_GET['status'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$sql = "
    SELECT bt.*, r.title as resource_title, c.category_name,
           owner.full_name as owner_name, borrower.full_name as borrower_name
    FROM borrowing_transactions bt
    JOIN resources r ON bt.resource_id = r.id
    JOIN categories c ON r.category_id = c.id
    JOIN users owner ON bt.owner_id = owner.id
    JOIN users borrower ON bt.borrower_id = borrower.id
    WHERE 1=1
";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND bt.transaction_status = ?";
    $params[] = $statusFilter;
}
if (!empty($fromDate)) {
    $sql .= " AND bt.created_at >= ?";
    $params[] = $fromDate . ' 00:00:00';
}
if (!empty($toDate)) {
    $sql .= " AND bt.created_at <= ?";
    $params[] = $toDate . ' 23:59:59';
}

$sql .= " ORDER BY bt.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reportRows = $stmt->fetchAll();

// Calculate actual metrics
$totalCount = count($reportRows);
$returnedCount = 0;
$overdueCount = 0;
foreach ($reportRows as $r) {
    if ($r['transaction_status'] === 'returned') $returnedCount++;
    if ($r['transaction_status'] === 'overdue') $overdueCount++;
}
$completionRate = ($totalCount > 0) ? round(($returnedCount / $totalCount) * 100, 1) : 0;

$pageTitle = "Audit Reports & Analytics";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Campus Sharing Reports & Analytics</h2>
                <p class="text-muted mb-0">Generate verified institutional utilization reports, export audit CSVs, and examine transaction efficiency.</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-printer me-1"></i> Print View
                </button>
                <a href="?<?= http_build_query(array_merge($_GET, ['action' => 'export_csv'])) ?>" class="btn btn-success rounded-pill px-4 shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Clean CSV
                </a>
            </div>
        </div>

        <?= display_flash_alerts() ?>

        <!-- Filter Form -->
        <div class="card border-0 rounded-4 shadow-sm bg-white p-3 mb-4">
            <form method="GET" action="<?= APP_URL ?>/admin/reports.php" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-xs fw-bold text-secondary text-uppercase">Status Filter</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?= ($statusFilter === 'active') ? 'selected' : '' ?>>Active Borrows</option>
                        <option value="returned" <?= ($statusFilter === 'returned') ? 'selected' : '' ?>>Returned / Completed</option>
                        <option value="overdue" <?= ($statusFilter === 'overdue') ? 'selected' : '' ?>>Overdue</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs fw-bold text-secondary text-uppercase">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?= e($fromDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs fw-bold text-secondary text-uppercase">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?= e($toDate) ?>">
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary rounded-pill">Generate Report</button>
                </div>
            </form>
        </div>

        <!-- Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                    <span class="text-xs text-muted text-uppercase fw-bold">Report Volume</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $totalCount ?> Transactions</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                    <span class="text-xs text-muted text-uppercase fw-bold">Returned Completion Rate</span>
                    <h3 class="fw-bold mb-0 text-success"><?= $completionRate ?>%</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                    <span class="text-xs text-muted text-uppercase fw-bold">Overdue Transactions</span>
                    <h3 class="fw-bold mb-0 text-danger"><?= $overdueCount ?></h3>
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>TXN ID</th>
                            <th>Resource Title</th>
                            <th>Category</th>
                            <th>Lender</th>
                            <th>Borrower</th>
                            <th>Handover Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportRows)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No records match the selected report criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reportRows as $r): ?>
                                <tr>
                                    <td><code>#<?= $r['id'] ?></code></td>
                                    <td class="fw-bold text-dark"><?= e($r['resource_title']) ?></td>
                                    <td><?= e($r['category_name']) ?></td>
                                    <td><?= e($r['owner_name']) ?></td>
                                    <td><?= e($r['borrower_name']) ?></td>
                                    <td><?= format_date($r['handover_at']) ?></td>
                                    <td><?= format_date($r['due_at']) ?></td>
                                    <td><?= status_badge($r['transaction_status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
