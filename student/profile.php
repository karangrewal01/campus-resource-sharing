<?php
/**
 * Campus Resource Sharing Platform - Student Profile & History
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/TransactionService.php';
require_once BASE_PATH . '/services/AnalyticsService.php';

require_auth();

$user = current_user();
$pdo = Database::getConnection();
$transactionService = new TransactionService();
$analyticsService = new AnalyticsService();

$errors = [];
$success = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    require_csrf_token();
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $semester = !empty($_POST['semester']) ? (int)$_POST['semester'] : null;

    if (empty($fullName)) {
        $errors[] = 'Full name cannot be blank.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, course = ?, semester = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$fullName, $phone ?: null, $course ?: null, $semester ?: null, $user['id']]);
        log_audit($user['id'], 'PROFILE_UPDATED', 'users', $user['id'], 'Updated user profile information.');
        set_flash('success', 'Profile updated successfully.');
        redirect('student/profile.php');
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    require_csrf_token();
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    // Verify current password
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $currentHash = $stmt->fetchColumn();

    if (!password_verify($currentPass, $currentHash)) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($newPass) < 6) {
        $errors[] = 'New password must be at least 6 characters long.';
    } elseif ($newPass !== $confirmPass) {
        $errors[] = 'New password and confirmation password do not match.';
    } else {
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$newHash, $user['id']]);
        log_audit($user['id'], 'PASSWORD_CHANGED', 'users', $user['id'], 'User changed password.');
        set_flash('success', 'Password successfully updated.');
        redirect('student/profile.php');
    }
}

$history = $transactionService->getUserTransactionHistory((int)$user['id']);
$metrics = $analyticsService->getStudentMetrics((int)$user['id']);

$pageTitle = "My Profile & History";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">My Campus Profile & Sharing History</h2>
                <p class="text-muted mb-0">View account details, update settings, and review your historical transactions.</p>
            </div>
            <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3 rounded-pill">
                <i class="bi bi-shield-check me-1"></i> Verified <?= ucfirst(e($user['role'])) ?>
            </span>
        </div>

        <?= display_flash_alerts() ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-4 shadow-sm mb-4">
                <ul class="mb-0 small ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Profile Info & Edit Form -->
            <div class="col-lg-6">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-gear text-primary me-2"></i>Account Details</h5>
                    
                    <form method="POST" action="<?= APP_URL ?>/student/profile.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Full Name</label>
                            <input type="text" class="form-control" name="full_name" value="<?= e($user['full_name']) ?>" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">College Email</label>
                                <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly disabled>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">Institution ID</label>
                                <input type="text" class="form-control bg-light" value="<?= e($user['institution_id']) ?>" readonly disabled>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">Department</label>
                                <input type="text" class="form-control bg-light" value="<?= e($user['department_name'] ?? 'Not specified') ?>" readonly disabled>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">Mobile Number</label>
                                <input type="tel" class="form-control" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="+91 98765 43210">
                            </div>
                        </div>

                        <?php if ($user['role'] === 'student'): ?>
                            <div class="row g-2 mb-3">
                                <div class="col-sm-8">
                                    <label class="form-label small fw-semibold text-secondary">Course / Program</label>
                                    <input type="text" class="form-control" name="course" value="<?= e($user['course'] ?? '') ?>">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label small fw-semibold text-secondary">Semester</label>
                                    <select class="form-select" name="semester">
                                        <option value="">Select...</option>
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <option value="<?= $i ?>" <?= ((int)($user['semester'] ?? 0) === $i) ? 'selected' : '' ?>>Sem <?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <small class="text-xs text-muted">Member since <?= format_date($user['created_at']) ?></small>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                                Save Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Change Card -->
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock text-primary me-2"></i>Change Password</h5>
                    <form method="POST" action="<?= APP_URL ?>/student/profile.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">New Password</label>
                                <input type="password" class="form-control" name="new_password" minlength="6" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-outline-primary rounded-pill px-4 fw-semibold">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sharing History & Stats Column -->
            <div class="col-lg-6">
                <!-- Lifetime Metrics -->
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-line text-primary me-2"></i>Sharing Statistics</h5>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-3">
                                <h4 class="fw-bold text-primary mb-0"><?= $metrics['total_listings'] ?></h4>
                                <span class="text-xs text-muted">Listed</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-3">
                                <h4 class="fw-bold text-success mb-0"><?= $metrics['completed_transactions'] ?></h4>
                                <span class="text-xs text-muted">Borrows</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-3">
                                <h4 class="fw-bold text-warning-emphasis mb-0"><?= $metrics['completed_donations'] + $metrics['completed_exchanges'] ?></h4>
                                <span class="text-xs text-muted">Trades/Donated</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction History Table -->
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-primary me-2"></i>Recent Borrowing Transactions</h5>
                    
                    <?php if (empty($history)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-journal-x fs-1"></i>
                            <p class="small mt-2 mb-0">No past transactions recorded.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-custom align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Resource</th>
                                        <th>Role</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $h): 
                                        $amOwner = ((int)$h['owner_id'] === (int)$user['id']);
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="fw-semibold text-dark text-truncate d-block" style="max-width: 150px;" title="<?= e($h['title']) ?>">
                                                    <?= e($h['title']) ?>
                                                </span>
                                                <span class="text-xs text-muted"><?= e($h['category_name']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border text-xs">
                                                    <?= $amOwner ? 'Lender' : 'Borrower' ?>
                                                </span>
                                            </td>
                                            <td><span class="small"><?= format_date($h['due_at']) ?></span></td>
                                            <td><?= status_badge($h['transaction_status']) ?></td>
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

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
