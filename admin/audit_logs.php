<?php
/**
 * Campus Resource Sharing Platform - System Audit Logs
 */

require_once dirname(__DIR__) . '/config/app.php';

require_role('admin');

$pdo = Database::getConnection();

$search = trim($_GET['search'] ?? '');
$sql = "
    SELECT a.*, u.full_name as actor_name, u.role as actor_role
    FROM audit_logs a
    LEFT JOIN users u ON a.actor_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (a.action LIKE ? OR a.details LIKE ? OR u.full_name LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY a.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = "System Audit Logs";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">System Security & Audit Trail</h2>
                <p class="text-muted mb-0">Immutable records of administrative updates, registrations, resource transactions, and authentication events.</p>
            </div>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <div class="card border-0 rounded-4 shadow-sm bg-white p-3 mb-4">
            <form method="GET" action="<?= APP_URL ?>/admin/audit_logs.php" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" placeholder="Search by action, details, or actor name..." value="<?= e($search) ?>">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary rounded-pill">Search Logs</button>
                </div>
            </form>
        </div>

        <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Actor</th>
                            <th>Action</th>
                            <th>Entity Type</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No audit log records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td><span class="small text-muted"><?= format_datetime($l['created_at']) ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($l['actor_name'] ?? 'System / Anonymous') ?></div>
                                        <?php if (!empty($l['actor_role'])): ?>
                                            <span class="text-xs text-muted text-uppercase"><?= e($l['actor_role']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= e($l['action']) ?></code></td>
                                    <td><span class="badge bg-light text-dark border text-xs"><?= e($l['entity_type']) ?> #<?= $l['entity_id'] ?? '-' ?></span></td>
                                    <td><span class="small text-secondary"><?= e($l['details'] ?? '') ?></span></td>
                                    <td><span class="text-xs text-muted"><?= e($l['ip_address'] ?? '127.0.0.1') ?></span></td>
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
