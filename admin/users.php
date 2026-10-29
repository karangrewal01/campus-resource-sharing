<?php
/**
 * Campus Resource Sharing Platform - User Account Management
 */

require_once dirname(__DIR__) . '/config/app.php';

require_role('admin');

$currentUser = current_user();
$pdo = Database::getConnection();

// Handle Actions (Activate / Suspend)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';
    $targetUserId = (int)($_POST['user_id'] ?? 0);

    if ($targetUserId === (int)$currentUser['id']) {
        set_flash('error', 'You cannot alter your own administrative account status.');
    } elseif ($action === 'toggle_status') {
        $newStatus = ($_POST['status'] === 'suspended') ? 'suspended' : 'active';
        $upd = $pdo->prepare("UPDATE users SET account_status = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$newStatus, $targetUserId]);
        log_audit($currentUser['id'], 'USER_STATUS_CHANGED', 'users', $targetUserId, "Account status set to {$newStatus}");
        set_flash('success', "User account status updated to '{$newStatus}'.");
    } elseif ($action === 'change_role') {
        $newRole = in_array($_POST['role'] ?? '', ['student', 'faculty', 'admin']) ? $_POST['role'] : 'student';
        $upd = $pdo->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$newRole, $targetUserId]);
        log_audit($currentUser['id'], 'USER_ROLE_CHANGED', 'users', $targetUserId, "Role changed to {$newRole}");
        set_flash('success', "User role updated to '{$newRole}'.");
    }

    redirect('admin/users.php');
}

// Filters
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$deptFilter = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;

$sql = "
    SELECT u.*, d.department_name, d.department_code,
           (SELECT COUNT(*) FROM resources r WHERE r.owner_id = u.id) as listings_count,
           (SELECT COUNT(*) FROM borrowing_transactions bt WHERE bt.borrower_id = u.id AND bt.transaction_status = 'active') as active_borrows
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.institution_id LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($roleFilter)) {
    $sql .= " AND u.role = ?";
    $params[] = $roleFilter;
}

if (!empty($statusFilter)) {
    $sql .= " AND u.account_status = ?";
    $params[] = $statusFilter;
}

if (!empty($deptFilter)) {
    $sql .= " AND u.department_id = ?";
    $params[] = $deptFilter;
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$deptList = $pdo->query("SELECT * FROM departments WHERE status = 'active' ORDER BY department_name ASC")->fetchAll();

$pageTitle = "Manage Campus Users";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">User Account Management</h2>
                <p class="text-muted mb-0">Review student and faculty accounts, verify institutional IDs, and manage platform permissions.</p>
            </div>
            <span class="badge bg-primary text-white rounded-pill px-3 py-2 fs-6">
                Total Users: <?= count($users) ?>
            </span>
        </div>

        <?= display_flash_alerts() ?>

        <!-- Search and Filter Bar -->
        <div class="card border-0 rounded-4 shadow-sm bg-white p-3 mb-4">
            <form method="GET" action="<?= APP_URL ?>/admin/users.php" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, roll number..." value="<?= e($search) ?>">
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="student" <?= ($roleFilter === 'student') ? 'selected' : '' ?>>Student</option>
                        <option value="faculty" <?= ($roleFilter === 'faculty') ? 'selected' : '' ?>>Faculty</option>
                        <option value="admin" <?= ($roleFilter === 'admin') ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="department_id" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach ($deptList as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($deptFilter === (int)$d['id']) ? 'selected' : '' ?>>
                                <?= e($d['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?= ($statusFilter === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= ($statusFilter === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary rounded-pill">Filter</button>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User Details</th>
                            <th>ID & Role</th>
                            <th>Department</th>
                            <th>Active Listings</th>
                            <th>Borrows</th>
                            <th>Status</th>
                            <th class="text-end">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($u['full_name']) ?></div>
                                    <div class="text-xs text-muted"><?= e($u['email']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($u['institution_id']) ?></span>
                                    <span class="badge <?= ($u['role'] === 'admin') ? 'bg-danger' : (($u['role'] === 'faculty') ? 'bg-indigo' : 'bg-primary') ?> text-white ms-1" style="<?= ($u['role'] === 'faculty') ? 'background-color: #6366f1;' : '' ?>">
                                        <?= strtoupper($u['role']) ?>
                                    </span>
                                </td>
                                <td><span class="small"><?= e($u['department_code'] ?? 'None') ?></span></td>
                                <td><?= $u['listings_count'] ?></td>
                                <td><?= $u['active_borrows'] ?></td>
                                <td>
                                    <?php if ($u['account_status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ((int)$u['id'] !== (int)$currentUser['id']): ?>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($u['account_status'] === 'active'): ?>
                                                <form method="POST" action="<?= APP_URL ?>/admin/users.php" class="d-inline" data-confirm="Suspend account for <?= e($u['full_name']) ?>?">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="status" value="suspended">
                                                    <button type="submit" class="btn btn-outline-danger" title="Suspend user">
                                                        Suspend
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="<?= APP_URL ?>/admin/users.php" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" class="btn btn-outline-success" title="Activate user">
                                                        Activate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-muted">You</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
