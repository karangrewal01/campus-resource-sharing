<?php
/**
 * Campus Resource Sharing Platform - In-Platform Messaging & Coordination
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/MessageService.php';
require_once BASE_PATH . '/services/RequestService.php';

require_auth();

$user = current_user();
$messageService = new MessageService();
$requestId = (int)($_GET['request_id'] ?? 0);

if (!$requestId) {
    set_flash('error', 'No conversation selected.');
    redirect('student/my_requests.php');
}

$isAdmin = ($user['role'] === 'admin');
$conv = $messageService->getConversationByRequest($requestId, (int)$user['id'], $isAdmin);

if (!$conv) {
    set_flash('error', 'Conversation not found or access denied.');
    redirect('student/my_requests.php');
}

// Handle message send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    require_csrf_token();
    $messageBody = trim($_POST['message_body'] ?? '');

    $result = $messageService->sendMessage((int)$conv['id'], (int)$user['id'], $messageBody, $isAdmin);
    if (!$result['success']) {
        set_flash('error', $result['error']);
    }
    redirect("student/messages.php?request_id={$requestId}");
}

$messages = $messageService->getMessages((int)$conv['id'], (int)$user['id'], $isAdmin);

// Determine counterpart name
$isRequester = ((int)$conv['requester_id'] === (int)$user['id']);
$otherPartyName = $isRequester ? $conv['owner_name'] : $conv['requester_name'];
$otherPartyRole = $isRequester ? 'Owner' : 'Requester';

$pageTitle = "Coordination Chat - " . $conv['resource_title'];
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
            <div>
                <a href="<?= $isRequester ? APP_URL . '/student/my_requests.php' : APP_URL . '/student/lending_requests.php' ?>" class="btn btn-sm btn-outline-secondary rounded-pill mb-2">
                    <i class="bi bi-arrow-left me-1"></i> Back to Requests
                </a>
                <h3 class="fw-bold text-dark mb-0">Handover Coordination Chat</h3>
                <small class="text-muted">Direct messaging between you and <?= e($otherPartyName) ?> (<?= $otherPartyRole ?>)</small>
            </div>
            <div class="text-end">
                <span class="text-xs text-muted d-block">Request Status</span>
                <?= status_badge($conv['request_status']) ?>
            </div>
        </div>

        <?= display_flash_alerts() ?>

        <!-- Resource Context Banner -->
        <div class="card p-3 rounded-4 border-0 shadow-sm bg-white mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary-subtle text-primary p-2 fs-4">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <span class="text-xs text-muted text-uppercase fw-bold">Resource in Discussion</span>
                        <h6 class="mb-0 fw-bold text-dark"><?= e($conv['resource_title']) ?></h6>
                    </div>
                </div>
                <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $conv['resource_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    View Resource Details
                </a>
            </div>
        </div>

        <!-- Chat Container -->
        <div class="card border-0 rounded-4 shadow-sm bg-white p-3 p-md-4">
            <div class="chat-box mb-3" style="min-height: 320px; max-height: 480px; overflow-y: auto;">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat-dots fs-1"></i>
                        <p class="small mt-2 mb-0">No messages yet. Send a greeting to coordinate campus pickup location, time, or return details!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): 
                        $isMine = ((int)$msg['sender_id'] === (int)$user['id']);
                    ?>
                        <div class="d-flex flex-column mb-3 <?= $isMine ? 'align-items-end' : 'align-items-start' ?>">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="text-xs fw-semibold text-secondary"><?= e($msg['sender_name']) ?></span>
                                <span class="text-xs text-muted"><?= format_datetime($msg['created_at']) ?></span>
                            </div>
                            <div class="chat-bubble <?= $isMine ? 'chat-bubble-mine' : 'chat-bubble-theirs' ?> shadow-sm">
                                <?= nl2br(e($msg['message_body'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Message Input Form -->
            <form method="POST" action="<?= APP_URL ?>/student/messages.php?request_id=<?= $requestId ?>" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="send_message">
                <input type="text" name="message_body" class="form-control rounded-pill px-3" placeholder="Type a message to coordinate campus handover..." required autocomplete="off">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm flex-shrink-0">
                    <i class="bi bi-send-fill me-1"></i> Send
                </button>
            </form>
        </div>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
