<?php
/**
 * Campus Resource Sharing Platform - Currently Lent Items
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/TransactionService.php';

require_auth();

$user = current_user();
$transactionService = new TransactionService();

// Handle return confirmation directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_return') {
    require_csrf_token();
    $transId = (int)($_POST['transaction_id'] ?? 0);
    $condition = $_POST['condition_on_return'] ?? 'good';
    $notes = trim($_POST['damage_notes'] ?? '');

    $res = $transactionService->recordReturn($transId, (int)$user['id'], $condition, $notes);
    if ($res['success']) {
        set_flash('success', 'Return confirmed! The resource availability has been restored.');
    } else {
        set_flash('error', $res['error']);
    }
    redirect('student/lent_items.php');
}

$lentItems = $transactionService->getLentItems((int)$user['id']);

$pageTitle = "Currently Lent Items";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Currently Lent Items</h2>
                <p class="text-muted mb-0">Track materials you have actively lent to other students and faculty members.</p>
            </div>
            <a href="<?= APP_URL ?>/student/my_resources.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-collection me-1"></i> My Listings
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <?php if (empty($lentItems)): ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                <i class="bi bi-box-arrow-up text-muted" style="font-size: 3.5rem;"></i>
                <h5 class="fw-bold text-dark mt-3">You have no items actively lent out</h5>
                <p class="text-muted small mx-auto" style="max-width: 480px;">
                    When other campus members borrow and collect your listed materials, they will appear here until returned.
                </p>
                <div>
                    <a href="<?= APP_URL ?>/student/lending_requests.php" class="btn btn-primary rounded-pill px-4">Check Pending Requests</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Borrower</th>
                                <th>Handover Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th class="text-end">Return Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lentItems as $item): 
                                $isOverdue = ($item['transaction_status'] === 'overdue' || time() > strtotime($item['due_at']));
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= get_resource_image_url($item['image_path'], 1) ?>" alt="<?= e($item['title']) ?>" class="rounded-3 border" style="width: 48px; height: 48px; object-fit: cover;">
                                            <div>
                                                <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $item['resource_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?= e($item['title']) ?>
                                                </a>
                                                <div class="text-xs text-muted"><?= e($item['category_name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($item['borrower_name']) ?></div>
                                        <div class="text-xs text-muted"><?= e($item['borrower_inst_id']) ?> &bull; <?= e($item['borrower_course'] ?? 'Student') ?></div>
                                    </td>
                                    <td><span class="small"><?= format_date($item['handover_at']) ?></span></td>
                                    <td>
                                        <span class="small fw-bold <?= $isOverdue ? 'text-danger' : 'text-primary' ?>">
                                            <?= format_date($item['due_at']) ?>
                                        </span>
                                    </td>
                                    <td><?= status_badge($item['transaction_status']) ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <?php if (!empty($item['conversation_id'])): ?>
                                                <a href="<?= APP_URL ?>/student/messages.php?request_id=<?= $item['request_id'] ?>" class="btn btn-sm btn-outline-primary" title="Message borrower">
                                                    <i class="bi bi-chat-dots"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#confirmReturnModal<?= $item['id'] ?>">
                                                <i class="bi bi-check2-circle me-1"></i>Confirm Return
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Return Modal -->
                                <div class="modal fade" id="confirmReturnModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Confirm Item Return</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="<?= APP_URL ?>/student/lent_items.php">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="confirm_return">
                                                <input type="hidden" name="transaction_id" value="<?= $item['id'] ?>">

                                                <div class="modal-body text-start">
                                                    <p class="small text-muted mb-3">Has <?= e($item['borrower_name']) ?> returned <strong><?= e($item['title']) ?></strong> to you?</p>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold text-secondary">Condition on Return</label>
                                                        <select class="form-select" name="condition_on_return">
                                                            <option value="good" selected>Good / Intact Condition</option>
                                                            <option value="like_new">Like New / Immaculate</option>
                                                            <option value="fair">Fair (Normal Wear)</option>
                                                            <option value="damaged">Damaged / Missing Components</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold text-secondary">Feedback / Damage Notes (Optional)</label>
                                                        <textarea class="form-control" name="damage_notes" rows="2" placeholder="Note any condition issues or confirmation comments..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">Confirm Return & Close</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
