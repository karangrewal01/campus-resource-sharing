<?php
/**
 * Campus Resource Sharing Platform - Currently Borrowed Items
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/TransactionService.php';

require_auth();

$user = current_user();
$transactionService = new TransactionService();
$borrowedItems = $transactionService->getBorrowedItems((int)$user['id']);

$pageTitle = "Borrowed Items";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Currently Borrowed Items</h2>
                <p class="text-muted mb-0">Academic materials currently in your possession. Please return on or before the due date.</p>
            </div>
            <a href="<?= APP_URL ?>/student/browse_resources.php" class="btn btn-outline-primary rounded-pill px-3">
                <i class="bi bi-search me-1"></i> Browse Catalog
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <?php if (empty($borrowedItems)): ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                <i class="bi bi-box-seam text-muted" style="font-size: 3.5rem;"></i>
                <h5 class="fw-bold text-dark mt-3">You have no borrowed items right now</h5>
                <p class="text-muted small mx-auto" style="max-width: 480px;">
                    When your borrow requests are approved and handed over by resource owners, they will appear here with active return countdowns.
                </p>
                <div>
                    <a href="<?= APP_URL ?>/student/browse_resources.php" class="btn btn-primary rounded-pill px-4">Find Resources to Borrow</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($borrowedItems as $item): 
                    $dueTime = strtotime($item['due_at']);
                    $now = time();
                    $isOverdue = ($item['transaction_status'] === 'overdue' || $now > $dueTime);
                    $diffDays = ceil(abs($dueTime - $now) / 86400);
                ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card border-0 rounded-4 shadow-sm bg-white h-100 overflow-hidden <?= $isOverdue ? 'border-danger border-2' : '' ?>">
                            <div class="resource-card-img-wrap" style="height: 150px;">
                                <img src="<?= get_resource_image_url($item['image_path'], 1) ?>" alt="<?= e($item['title']) ?>">
                                <div class="position-absolute top-0 end-0 m-2">
                                    <?= status_badge($item['transaction_status']) ?>
                                </div>
                            </div>

                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <span class="text-xs text-muted mb-1"><?= e($item['category_name']) ?></span>
                                <h6 class="fw-bold text-dark mb-2"><?= e($item['title']) ?></h6>

                                <div class="p-2 rounded-3 bg-light mb-3 text-xs">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Owner:</span>
                                        <span class="fw-semibold text-dark"><?= e($item['owner_name']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Handover Date:</span>
                                        <span class="fw-medium text-dark"><?= format_date($item['handover_at']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Return Due Date:</span>
                                        <span class="fw-bold <?= $isOverdue ? 'text-danger' : 'text-primary' ?>"><?= format_date($item['due_at']) ?></span>
                                    </div>
                                </div>

                                <?php if ($isOverdue): ?>
                                    <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2">
                                        <i class="bi bi-alarm-fill fs-5 flex-shrink-0 animate-pulse text-danger"></i>
                                        <div>
                                            <strong>Overdue by <?= $diffDays ?> day(s)!</strong><br>
                                            <span class="text-xs">Return promptly to the owner.</span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2">
                                        <i class="bi bi-clock-history fs-5 flex-shrink-0 text-primary"></i>
                                        <span class="text-xs">Due in <strong><?= $diffDays ?> day(s)</strong>. Coordinate return ahead of time.</span>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-auto pt-2 border-top d-flex gap-2">
                                    <?php if (!empty($item['conversation_id'])): ?>
                                        <a href="<?= APP_URL ?>/student/messages.php?request_id=<?= $item['request_id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill flex-grow-1">
                                            <i class="bi bi-chat-dots me-1"></i> Coordinate Return
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $item['resource_id'] ?>" class="btn btn-light btn-sm rounded-pill border">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
