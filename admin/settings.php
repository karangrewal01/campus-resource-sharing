<?php
/**
 * Campus Resource Sharing Platform - Institutional Policies & Settings
 */

require_once dirname(__DIR__) . '/config/app.php';

require_role('admin');

$currentUser = current_user();
$pdo = Database::getConnection();

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $settingsToUpdate = [
        'institution_name'             => trim($_POST['institution_name'] ?? ''),
        'institution_short_name'       => trim($_POST['institution_short_name'] ?? ''),
        'campus_contact_email'         => trim($_POST['campus_contact_email'] ?? ''),
        'campus_help_desk'             => trim($_POST['campus_help_desk'] ?? ''),
        'default_borrow_days'          => (string)max(1, (int)($_POST['default_borrow_days'] ?? 7)),
        'max_active_requests_per_user' => (string)max(1, (int)($_POST['max_active_requests_per_user'] ?? 3)),
        'max_active_borrowed_items'    => (string)max(1, (int)($_POST['max_active_borrowed_items'] ?? 2)),
        'allowed_email_domains'        => trim($_POST['allowed_email_domains'] ?? ''),
        'require_listing_moderation'   => isset($_POST['require_listing_moderation']) ? '1' : '0'
    ];

    $updStmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
    foreach ($settingsToUpdate as $key => $val) {
        $updStmt->execute([$key, $val]);
    }

    log_audit($currentUser['id'], 'SETTINGS_UPDATED', 'settings', null, 'Institutional campus policies updated.');
    set_flash('success', 'Campus policies and system settings saved successfully.');
    redirect('admin/settings.php');
}

$pageTitle = "Campus Policies & Settings";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Campus Sharing Policies & Settings</h2>
                <p class="text-muted mb-0">Configure institution rules, borrowing duration caps, email domains, and moderation requirements.</p>
            </div>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <div class="card border-0 rounded-4 shadow-sm bg-white p-4 p-md-5">
            <form method="POST" action="<?= APP_URL ?>/admin/settings.php">
                <?= csrf_field() ?>

                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-building text-primary me-2"></i>Institution Profile</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold text-secondary">Institution Full Name</label>
                        <input type="text" class="form-control" name="institution_name" value="<?= e(get_setting('institution_name')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Campus Short Brand</label>
                        <input type="text" class="form-control" name="institution_short_name" value="<?= e(get_setting('institution_short_name')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Help Desk Location / Room</label>
                        <input type="text" class="form-control" name="campus_help_desk" value="<?= e(get_setting('campus_help_desk')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Contact Email</label>
                        <input type="email" class="form-control" name="campus_contact_email" value="<?= e(get_setting('campus_contact_email')) ?>" required>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Borrowing & Circulation Limits</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Default Borrowing Duration (Days)</label>
                        <input type="number" class="form-control" name="default_borrow_days" value="<?= e(get_setting('default_borrow_days', '7')) ?>" min="1" max="60" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Max Active Requests Per Student</label>
                        <input type="number" class="form-control" name="max_active_requests_per_user" value="<?= e(get_setting('max_active_requests_per_user', '3')) ?>" min="1" max="10" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Max Concurrent Borrowed Items</label>
                        <input type="number" class="form-control" name="max_active_borrowed_items" value="<?= e(get_setting('max_active_borrowed_items', '2')) ?>" min="1" max="10" required>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock text-primary me-2"></i>Security & Verification Controls</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold text-secondary">Allowed Registration Email Domains (Comma-separated)</label>
                        <input type="text" class="form-control" name="allowed_email_domains" value="<?= e(get_setting('allowed_email_domains')) ?>" placeholder="e.g. campus.edu, student.campus.edu, apex.edu">
                        <div class="form-text text-xs">Leave empty to allow registration from any valid email domain.</div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="require_listing_moderation" name="require_listing_moderation" value="1" <?= (get_setting('require_listing_moderation', '0') === '1') ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-semibold text-dark" for="require_listing_moderation">
                                Require Administrator Moderation Approval Before New Listings Become Publicly Visible
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Save Institutional Policies
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
