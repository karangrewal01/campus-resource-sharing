<?php
/**
 * Campus Resource Sharing Platform - All Borrowing Transactions
 */

require_once dirname(__DIR__) . '/config/app.php';

require_role('admin');

$currentUser = current_user();
$pdo = Database::getConnection();
check_and_update_overdue_transactions();

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT bt.*, r.title as resource_title, c.category_name,
           owner.full_name as owner_name, owner.institution_id as owner_inst_id,
           borrower.full_name as borrower_name, borrower.institution_id as borrower_inst_id
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

if (!empty($search)) {
    $sql .= " AND (r.title LIKE ? OR owner.full_name LIKE ? OR borrower.full_name LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY bt.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$pageTitle = "All Campus Transactions";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Campus Sharing Transactions</h2>
                <p class="text-muted mb-0">Complete historical and active borrowing records, return timestamps, and overdue accountability.</p>
            </div>
            <a href="<?= APP_URL ?>/admin/reports.php" class="btn btn-outline-primary rounded-pill px-3">
                <i class="bi bi-download me-1"></i> Export CSV Report
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <!-- Search and Filter Bar -->
        <div class="card border-0 rounded-4 shadow-sm bg-white p-3 mb-4">
            <form method="GET" action="<?= APP_URL ?>/admin/transactions.php" class="row g-2">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search by resource, lender, or borrower..." value="<?= e($search) ?>">
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Transaction Statuses</option>
                        <option value="active" <?= ($statusFilter === 'active') ? 'selected' : '' ?>>Active (Currently Borrowed)</option>
                        <option value="overdue" <?= ($statusFilter === 'overdue') ? 'selected' : '' ?>>Overdue</option>
                        <option value="returned" <?= ($statusFilter === 'returned') ? 'selected' : '' ?>>Returned (Completed)</option>
                        <option value="damaged" <?= ($statusFilter === 'damaged') ? 'selected' : '' ?>>Damaged on Return</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary rounded-pill">Filter</button>
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Resource</th>
                            <th>Lender</th>
                            <th>Borrower</th>
                            <th>Handover Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No transactions found matching your criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td><code>#TXN-<?= str_pad((string)$t['id'], 4, '0', STR_PAD_LEFT) ?></code></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $t['resource_id'] ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                                            <?= e($t['resource_title']) ?>
                                        </a>
                                        <div class="text-xs text-muted"><?= e($t['category_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($t['owner_name']) ?></div>
                                        <div class="text-xs text-muted"><?= e($t['owner_inst_id']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($t['borrower_name']) ?></div>
                                        <div class="text-xs text-muted"><?= e($t['borrower_inst_id']) ?></div>
                                    </td>
                                    <td><span class="small"><?= format_date($t['handover_at']) ?></span></td>
                                    <td>
                                        <span class="small fw-semibold <?= ($t['transaction_status'] === 'overdue') ? 'text-danger' : 'text-dark' ?>">
                                            <?= format_date($t['due_at']) ?>
                                        </span>
                                    </td>
                                    <td><?= status_badge($t['transaction_status']) ?></td>
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
