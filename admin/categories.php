<?php
/**
 * Campus Resource Sharing Platform - Category Management
 */

require_once dirname(__DIR__) . '/config/app.php';

require_role('admin');

$currentUser = current_user();
$pdo = Database::getConnection();

// Handle Creation & Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_category') {
        $name = trim($_POST['category_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon_class'] ?? 'bi-box-seam');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name, slug, description, icon_class, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
            try {
                $stmt->execute([$name, $slug, $desc, $icon]);
                log_audit($currentUser['id'], 'CATEGORY_CREATED', 'categories', (int)$pdo->lastInsertId(), "Created category '{$name}'");
                set_flash('success', "Category '{$name}' created successfully.");
            } catch (Exception $e) {
                set_flash('error', 'A category with this name or slug already exists.');
            }
        }
    } elseif ($action === 'toggle_status') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $newStatus = ($_POST['status'] === 'inactive') ? 'inactive' : 'active';
        $pdo->prepare("UPDATE categories SET status = ? WHERE id = ?")->execute([$newStatus, $catId]);
        log_audit($currentUser['id'], 'CATEGORY_STATUS_CHANGED', 'categories', $catId, "Category status changed to {$newStatus}");
        set_flash('success', "Category status updated.");
    }

    redirect('admin/categories.php');
}

$categories = $pdo->query("
    SELECT c.*, COUNT(r.id) as resource_count
    FROM categories c
    LEFT JOIN resources r ON c.id = r.category_id
    GROUP BY c.id
    ORDER BY c.category_name ASC
")->fetchAll();

$pageTitle = "Manage Categories";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Resource Categories</h2>
                <p class="text-muted mb-0">Organize and manage catalog classifications for campus sharing materials.</p>
            </div>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <div class="row g-4">
            <!-- Add Category Form -->
            <div class="col-lg-4">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-plus-circle text-primary me-2"></i>Add New Category</h5>
                    <form method="POST" action="<?= APP_URL ?>/admin/categories.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create_category">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="category_name" placeholder="e.g. Graphic Tablets & Stylus" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Bootstrap Icon Class</label>
                            <input type="text" class="form-control" name="icon_class" value="bi-box-seam" placeholder="e.g. bi-book, bi-cpu, bi-tools">
                            <small class="text-muted text-xs">Reference: <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons library</a></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Brief explanation of items belonging to this category..."></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary rounded-pill py-2 fw-semibold shadow-sm">
                                Create Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Categories Table -->
            <div class="col-lg-8">
                <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Icon</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th class="text-end">Manage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($cat['category_name']) ?></div>
                                            <small class="text-xs text-muted"><?= e($cat['description'] ?: 'No description') ?></small>
                                        </td>
                                        <td>
                                            <span class="fs-4 text-primary"><i class="bi <?= e($cat['icon_class']) ?>"></i></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= $cat['resource_count'] ?></span>
                                        </td>
                                        <td>
                                            <?php if ($cat['status'] === 'active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="<?= APP_URL ?>/admin/categories.php" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                <input type="hidden" name="status" value="<?= ($cat['status'] === 'active') ? 'inactive' : 'active' ?>">
                                                <button type="submit" class="btn btn-sm <?= ($cat['status'] === 'active') ? 'btn-outline-secondary' : 'btn-outline-success' ?> rounded-pill px-3">
                                                    <?= ($cat['status'] === 'active') ? 'Disable' : 'Enable' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
