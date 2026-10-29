<?php
/**
 * Campus Resource Sharing Platform - Faculty Announcements
 */

require_once dirname(__DIR__) . '/config/app.php';

require_role(['faculty', 'admin']);

$user = current_user();
$pdo = Database::getConnection();

// Handle creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_announcement') {
    require_csrf_token();
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $audience = in_array($_POST['audience'] ?? '', ['all', 'students', 'faculty']) ? $_POST['audience'] : 'all';

    if (empty($title) || empty($content)) {
        set_flash('error', 'Please provide both title and announcement details.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, created_by, audience, published_at, status, created_at) VALUES (?, ?, ?, ?, NOW(), 'published', NOW())");
        $stmt->execute([$title, $content, $user['id'], $audience]);
        log_audit($user['id'], 'ANNOUNCEMENT_POSTED', 'announcements', (int)$pdo->lastInsertId(), "Posted announcement '{$title}'");
        set_flash('success', 'Announcement published successfully on the campus bulletin!');
        redirect('faculty/announcements.php');
    }
}

$announcements = $pdo->prepare("
    SELECT a.*, u.full_name as author_name
    FROM announcements a
    JOIN users u ON a.created_by = u.id
    WHERE a.created_by = ? OR ? = 'admin'
    ORDER BY a.created_at DESC
");
$announcements->execute([$user['id'], $user['role']]);
$myAnnouncements = $announcements->fetchAll();

$pageTitle = "Post Campus Announcement";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Campus Announcements & Notices</h2>
                <p class="text-muted mb-0">Publish return reminders, donation drives, and academic resource notices to students.</p>
            </div>
            <a href="<?= APP_URL ?>/faculty/dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <div class="row g-4">
            <!-- Form to Post -->
            <div class="col-lg-5">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-pencil-square text-primary me-2"></i>Publish Notice</h5>
                    <form method="POST" action="<?= APP_URL ?>/faculty/announcements.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create_announcement">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Announcement Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" placeholder="e.g. Pre-Exam Book Return Notice" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Target Audience</label>
                            <select class="form-select" name="audience">
                                <option value="all">All Campus Members</option>
                                <option value="students" selected>Students Only</option>
                                <option value="faculty">Faculty Members Only</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Notice Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="content" rows="4" placeholder="Detail the notice, dates, locations, or requirements..." required></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary rounded-pill py-2 fw-semibold shadow-sm">
                                <i class="bi bi-send-fill me-1"></i> Post Announcement
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List of notices -->
            <div class="col-lg-7">
                <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-megaphone text-primary me-2"></i>Published Notices</h5>
                    <?php if (empty($myAnnouncements)): ?>
                        <div class="text-center py-4 text-muted">
                            <p class="small mb-0">No announcements posted yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($myAnnouncements as $ann): ?>
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold text-dark mb-0"><?= e($ann['title']) ?></h6>
                                        <span class="badge bg-primary-subtle text-primary text-xs text-uppercase"><?= e($ann['audience']) ?></span>
                                    </div>
                                    <p class="small text-muted mb-2"><?= nl2br(e($ann['content'])) ?></p>
                                    <small class="text-xs text-muted">Published on <?= format_datetime($ann['published_at']) ?> by <?= e($ann['author_name']) ?></small>
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
