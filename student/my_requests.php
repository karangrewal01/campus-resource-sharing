<?php
/**
 * Campus Resource Sharing Platform - Outgoing Borrow Requests
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/RequestService.php';

require_auth();

$user = current_user();
$requestService = new RequestService();

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_request') {
    require_csrf_token();
    $reqId = (int)($_POST['request_id'] ?? 0);
    $res = $requestService->cancelRequest($reqId, (int)$user['id']);

    if ($res['success']) {
        set_flash('info', 'Your request has been cancelled.');
    } else {
        set_flash('error', $res['error']);
    }
    redirect('student/my_requests.php');
}

$myRequests = $requestService->getUserRequests((int)$user['id']);

$pageTitle = "My Outgoing Requests";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">My Outgoing Requests</h2>
                <p class="text-muted mb-0">Track status of items you have requested to borrow, receive, or exchange.</p>
            </div>
            <a href="<?= APP_URL ?>/student/browse_resources.php" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-search me-1"></i> Browse More Items
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <?php if (empty($myRequests)): ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                <i class="bi bi-send text-muted" style="font-size: 3.5rem;"></i>
                <h5 class="fw-bold text-dark mt-3">You haven't requested any resources yet</h5>
                <p class="text-muted small mx-auto" style="max-width: 480px;">
                    Find textbooks, notes, lab equipment, or project tools available on campus and request them easily.
                </p>
                <div>
                    <a href="<?= APP_URL ?>/student/browse_resources.php" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-search me-1"></i> Explore Catalog
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Requested Item</th>
                                <th>Owner</th>
                                <th>Requested Dates</th>
                                <th>My Purpose</th>
                                <th>Status / Owner Decision</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myRequests as $req): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= get_resource_image_url($req['image_path'], 1) ?>" alt="<?= e($req['resource_title']) ?>" class="rounded-3 border" style="width: 48px; height: 48px; object-fit: cover;">
                                            <div>
                                                <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $req['resource_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?= e($req['resource_title']) ?>
                                                </a>
                                                <div class="text-xs text-muted"><?= method_badge($req['sharing_method']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark d-block"><?= e($req['owner_name']) ?></span>
                                        <span class="text-xs text-muted text-uppercase"><?= e($req['owner_role']) ?></span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <strong>Pickup:</strong> <?= format_date($req['requested_start_date']) ?><br>
                                            <strong>Duration:</strong> <?= $req['requested_duration_days'] ?> days<br>
                                            <strong>Due:</strong> <?= format_date($req['proposed_return_date']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small text-muted d-inline-block text-truncate" style="max-width: 200px;" title="<?= e($req['purpose']) ?>">
                                            <?= e($req['purpose'] ?: 'None') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <div><?= status_badge($req['status']) ?></div>
                                            <?php if (!empty($req['decision_reason'])): ?>
                                                <small class="text-xs text-muted fst-italic">"<?= e($req['decision_reason']) ?>"</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end align-items-center gap-1">
                                            <?php if (!empty($req['conversation_id'])): ?>
                                                <a href="<?= APP_URL ?>/student/messages.php?request_id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-primary" title="Open conversation">
                                                    <i class="bi bi-chat-dots me-1"></i>Chat
                                                </a>
                                            <?php endif; ?>

                                            <?php if (in_array($req['status'], ['pending', 'approved', 'ready_for_collection'])): ?>
                                                <form method="POST" action="<?= APP_URL ?>/student/my_requests.php" class="d-inline" data-confirm="Are you sure you want to cancel this request?">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="cancel_request">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel request">
                                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
