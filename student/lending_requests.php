<?php
/**
 * Campus Resource Sharing Platform - Incoming Requests & Handover Management
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/RequestService.php';
require_once BASE_PATH . '/services/TransactionService.php';

require_auth();

$user = current_user();
$requestService = new RequestService();
$transactionService = new TransactionService();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';
    $reqId = (int)($_POST['request_id'] ?? 0);
    $transId = (int)($_POST['transaction_id'] ?? 0);

    if ($action === 'approve') {
        $reason = trim($_POST['decision_reason'] ?? '');
        $altDate = !empty($_POST['alternative_pickup_date']) ? $_POST['alternative_pickup_date'] : null;
        $res = $requestService->approveRequest($reqId, (int)$user['id'], $reason, $altDate);
        if ($res['success']) {
            set_flash('success', 'Request approved! Please coordinate collection with the student.');
        } else {
            set_flash('error', $res['error']);
        }
    } elseif ($action === 'reject') {
        $reason = trim($_POST['decision_reason'] ?? '');
        $res = $requestService->rejectRequest($reqId, (int)$user['id'], $reason);
        if ($res['success']) {
            set_flash('info', 'Request declined.');
        } else {
            set_flash('error', $res['error']);
        }
    } elseif ($action === 'confirm_handover') {
        $res = $transactionService->recordHandover($reqId, (int)$user['id']);
        if ($res['success']) {
            set_flash('success', 'Physical handover confirmed! Borrowing period has officially begun.');
        } else {
            set_flash('error', $res['error']);
        }
    } elseif ($action === 'confirm_return') {
        $condition = $_POST['condition_on_return'] ?? 'good';
        $damageNotes = trim($_POST['damage_notes'] ?? '');
        $res = $transactionService->recordReturn($transId, (int)$user['id'], $condition, $damageNotes);
        if ($res['success']) {
            set_flash('success', 'Resource return confirmed! Item is now back in your inventory.');
        } else {
            set_flash('error', $res['error']);
        }
    }

    redirect('student/lending_requests.php');
}

$incomingRequests = $requestService->getIncomingRequests((int)$user['id']);

$pageTitle = "Incoming Requests for My Items";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Incoming Requests for My Listings</h2>
                <p class="text-muted mb-0">Review borrow requests, coordinate item handovers, and confirm returns.</p>
            </div>
            <a href="<?= APP_URL ?>/student/my_resources.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-collection me-1"></i> My Listings
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <?php if (empty($incomingRequests)): ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                <i class="bi bi-inbox text-muted" style="font-size: 3.5rem;"></i>
                <h5 class="fw-bold text-dark mt-3">No incoming requests right now</h5>
                <p class="text-muted small mx-auto" style="max-width: 480px;">
                    When other campus members request to borrow, donate, or exchange your items, they will appear here.
                </p>
            </div>
        <?php else: ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Requester</th>
                                <th>Resource</th>
                                <th>Dates & Duration</th>
                                <th>Purpose / Note</th>
                                <th>Status</th>
                                <th class="text-end">Manage Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incomingRequests as $req): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($req['requester_name']) ?></div>
                                        <div class="text-xs text-muted"><?= e($req['requester_inst_id']) ?> &bull; <?= e($req['requester_course'] ?? 'Student') ?></div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark d-block"><?= e($req['resource_title']) ?></span>
                                        <span class="text-xs"><?= method_badge($req['sharing_method']) ?></span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <strong>Pickup:</strong> <?= format_date($req['requested_start_date']) ?><br>
                                            <strong>Duration:</strong> <?= $req['requested_duration_days'] ?> day(s)<br>
                                            <strong>Due:</strong> <?= format_date($req['proposed_return_date']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small text-muted d-inline-block text-truncate" style="max-width: 220px;" title="<?= e($req['purpose']) ?>">
                                            <?= e($req['purpose'] ?: 'None specified') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= status_badge($req['status']) ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end align-items-center gap-1">
                                            <!-- Chat button -->
                                            <?php if (!empty($req['conversation_id'])): ?>
                                                <a href="<?= APP_URL ?>/student/messages.php?request_id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-primary" title="Chat with Requester">
                                                    <i class="bi bi-chat-dots"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Pending Actions: Approve or Reject -->
                                            <?php if ($req['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#approveModal<?= $req['id'] ?>">
                                                    <i class="bi bi-check-lg me-1"></i>Approve
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $req['id'] ?>">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            <?php elseif (in_array($req['status'], ['approved', 'ready_for_collection'])): ?>
                                                <!-- Approved: Handover Confirmation -->
                                                <form method="POST" action="<?= APP_URL ?>/student/lending_requests.php" data-confirm="Confirm physical handover of '<?= e($req['resource_title']) ?>' to <?= e($req['requester_name']) ?>?">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="confirm_handover">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i>Confirm Handover
                                                    </button>
                                                </form>
                                            <?php elseif (in_array($req['status'], ['borrowed', 'overdue']) && !empty($req['transaction_id'])): ?>
                                                <!-- Borrowed / Overdue: Confirm Return -->
                                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#returnModal<?= $req['transaction_id'] ?>">
                                                    <i class="bi bi-arrow-return-left me-1"></i>Confirm Return
                                                </button>
                                            <?php else: ?>
                                                <span class="text-xs text-muted">Closed</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Approve Modal -->
                                <?php if ($req['status'] === 'pending'): ?>
                                    <div class="modal fade" id="approveModal<?= $req['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Approve Request</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="<?= APP_URL ?>/student/lending_requests.php">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">

                                                    <div class="modal-body text-start">
                                                        <p class="small text-muted mb-3">Approve <?= e($req['requester_name']) ?>'s request for <strong><?= e($req['resource_title']) ?></strong>.</p>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Pickup / Collection Note (Optional)</label>
                                                            <input type="text" class="form-control" name="decision_reason" placeholder="e.g. Meet outside CS Lab Block B at 3 PM">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Proposed Alternative Pickup Date (Optional)</label>
                                                            <input type="date" class="form-control" name="alternative_pickup_date" min="<?= date('Y-m-d') ?>">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">Confirm Approval</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal<?= $req['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold text-danger">Decline Request</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="<?= APP_URL ?>/student/lending_requests.php">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">

                                                    <div class="modal-body text-start">
                                                        <p class="small text-muted mb-3">Please specify why you are declining <?= e($req['requester_name']) ?>'s request.</p>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Reason for Declining</label>
                                                            <textarea class="form-control" name="decision_reason" rows="3" placeholder="e.g. Item currently needed for my own practical exam..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">Decline Request</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Return Modal -->
                                <?php if (!empty($req['transaction_id'])): ?>
                                    <div class="modal fade" id="returnModal<?= $req['transaction_id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Confirm Item Return</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="<?= APP_URL ?>/student/lending_requests.php">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="confirm_return">
                                                    <input type="hidden" name="transaction_id" value="<?= $req['transaction_id'] ?>">

                                                    <div class="modal-body text-start">
                                                        <p class="small text-muted mb-3">Confirming return for <strong><?= e($req['resource_title']) ?></strong> returned by <?= e($req['requester_name']) ?>.</p>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Returned Condition</label>
                                                            <select class="form-select" name="condition_on_return">
                                                                <option value="good" selected>Good / Expected Condition</option>
                                                                <option value="like_new">Like New / Excellent</option>
                                                                <option value="fair">Fair / Minor Signs of Use</option>
                                                                <option value="damaged">Damaged / Missing Parts</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Notes or Damage Details (if any)</label>
                                                            <textarea class="form-control" name="damage_notes" rows="2" placeholder="e.g. Returned with all cables and intact cover."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">Confirm Return</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
