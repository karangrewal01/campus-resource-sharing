<?php
/**
 * Campus Resource Sharing Platform - In-App Notifications
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/NotificationService.php';

require_auth();

$user = current_user();
$notifService = new NotificationService();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_all_read') {
        $notifService->markAllAsRead((int)$user['id']);
        set_flash('success', 'All notifications marked as read.');
    } elseif ($action === 'mark_read') {
        $notifId = (int)($_POST['notification_id'] ?? 0);
        $notifService->markAsRead($notifId, (int)$user['id']);
    }

    redirect('student/notifications.php');
}

$notifications = $notifService->getUserNotifications((int)$user['id'], 50);

$pageTitle = "Notifications";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Campus Notifications</h2>
                <p class="text-muted mb-0">Stay informed about borrowing approvals, return deadlines, and handover notices.</p>
            </div>
            <?php if (!empty($notifications)): ?>
                <form method="POST" action="<?= APP_URL ?>/student/notifications.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="bi bi-check2-all me-1"></i> Mark All as Read
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?= display_flash_alerts() ?>

        <?php if (empty($notifications)): ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                <i class="bi bi-bell-slash text-muted" style="font-size: 3.5rem;"></i>
                <h5 class="fw-bold text-dark mt-3">No notifications right now</h5>
                <p class="text-muted small">You will receive notifications when requests are submitted, approved, or due for return.</p>
            </div>
        <?php else: ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden p-2">
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $n): 
                        $isUnread = empty($n['read_at']);
                    ?>
                        <div class="list-group-item p-3 border-bottom d-flex align-items-start justify-content-between gap-3 <?= $isUnread ? 'bg-primary-subtle rounded-3 my-1' : '' ?>">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-circle p-2 mt-1 <?= $isUnread ? 'bg-primary text-white' : 'bg-light text-muted' ?> d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    <?php if ($n['notification_type'] === 'overdue'): ?>
                                        <i class="bi bi-alarm text-danger"></i>
                                    <?php elseif ($n['notification_type'] === 'approval'): ?>
                                        <i class="bi bi-check2-circle"></i>
                                    <?php elseif ($n['notification_type'] === 'exchange'): ?>
                                        <i class="bi bi-arrow-repeat"></i>
                                    <?php else: ?>
                                        <i class="bi bi-bell"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark text-sm"><?= e($n['title']) ?></h6>
                                    <p class="mb-1 text-secondary small"><?= e($n['message']) ?></p>
                                    <small class="text-xs text-muted"><?= format_datetime($n['created_at']) ?></small>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <?php if ($n['related_request_id']): ?>
                                    <a href="<?= APP_URL ?>/student/my_requests.php" class="btn btn-sm btn-light border rounded-pill px-2 text-xs">
                                        View Request
                                    </a>
                                <?php elseif ($n['related_resource_id']): ?>
                                    <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $n['related_resource_id'] ?>" class="btn btn-sm btn-light border rounded-pill px-2 text-xs">
                                        View Resource
                                    </a>
                                <?php endif; ?>

                                <?php if ($isUnread): ?>
                                    <form method="POST" action="<?= APP_URL ?>/student/notifications.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                                        <button type="submit" class="btn btn-sm text-muted p-1" title="Mark as read">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
