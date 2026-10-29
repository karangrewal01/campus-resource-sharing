<?php
/**
 * Campus Resource Sharing Platform - Administrator Announcements
 */

require_once dirname(__DIR__) . '/config/app.php';

require_role('admin');

$currentUser = current_user();
$pdo = Database::getConnection();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_announcement') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $audience = in_array($_POST['audience'] ?? '', ['all', 'students', 'faculty']) ? $_POST['audience'] : 'all';

        if (!empty($title) && !empty($content)) {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, created_by, audience, published_at, status, created_at) VALUES (?, ?, ?, ?, NOW(), 'published', NOW())");
            $stmt->execute([$title, $content, $currentUser['id'], $audience]);
            log_audit($currentUser['id'], 'ANNOUNCEMENT_POSTED', 'announcements', (int)$pdo->lastInsertId(), "Admin posted: {$title}");
            set_flash('success', 'Announcement published campus-wide.');
        }
    } elseif ($action === 'delete_announcement') {
        $annId = (int)($_POST['announcement_id'] ?? 0);
        $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$annId]);
        log_audit($currentUser['id'], 'ANNOUNCEMENT_DELETED', 'announcements', $annId, 'Admin removed announcement.');
        set_flash('info', 'Announcement deleted.');
    }

    redirect('admin/announcements.php');
}

$announcements = $pdo->query("
    SELECT a.*, u.full_name as author_name, u.role as author_role
    FROM announcements a
    JOIN users u ON a.created_by = u.id
    ORDER BY a.created_at DESC
")->fetchAll();

$pageTitle = "Campus Announcements";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Campus Bulletins & Announcements</h2>
                <p class="text-muted mb-0">Publish notices visible across student and faculty portals.</p>
            </div>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-megaphone text-primary me-2"></i>Publish Notice</h5>
                    <form method="POST" action="<?= APP_URL ?>/admin/announcements.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create_announcement">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Target Audience</label>
                            <select class="form-select" name="audience">
                                <option value="all">Entire Campus (Students & Faculty)</option>
                                <option value="students">Students Only</option>
                                <option value="faculty">Faculty Members Only</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Content Details <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="content" rows="4" required></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary rounded-pill py-2 fw-semibold shadow-sm">
                                Post Announcement
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-card-text text-primary me-2"></i>Active Announcements</h5>
                    <?php if (empty($announcements)): ?>
                        <div class="text-center py-4 text-muted">No announcements posted.</div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($announcements as $ann): ?>
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold text-dark mb-0"><?= e($ann['title']) ?></h6>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary-subtle text-primary text-xs text-uppercase"><?= e($ann['audience']) ?></span>
                                            <form method="POST" action="<?= APP_URL ?>/admin/announcements.php" class="d-inline" data-confirm="Delete this announcement?">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_announcement">
                                                <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">
                                                <button type="submit" class="btn btn-sm text-danger p-0 border-0"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-2"><?= nl2br(e($ann['content'])) ?></p>
                                    <small class="text-xs text-muted"><?= format_datetime($ann['published_at']) ?> by <?= e($ann['author_name']) ?> (<?= e($ann['author_role']) ?>)</small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
