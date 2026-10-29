<?php
/**
 * Campus Resource Sharing Platform - Item Exchanges Module
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/ExchangeService.php';

$exchangeService = new ExchangeService();
$currentUser = is_logged_in() ? current_user() : null;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_auth();
    require_csrf_token();
    $action = $_POST['action'] ?? '';
    $exchangeId = (int)($_POST['exchange_id'] ?? 0);

    if ($action === 'submit_proposal') {
        $listingId = (int)($_POST['listing_resource_id'] ?? 0);
        $offeredDesc = trim($_POST['offered_item_description'] ?? '');
        $terms = trim($_POST['proposed_terms'] ?? '');

        if (empty($offeredDesc)) {
            set_flash('error', 'Please describe the item you are offering in exchange.');
        } else {
            $res = $exchangeService->submitProposal((int)$currentUser['id'], $listingId, null, $offeredDesc, $terms);
            if ($res['success']) {
                set_flash('success', 'Exchange proposal submitted to the listing owner!');
            } else {
                set_flash('error', $res['error']);
            }
        }
    } elseif ($action === 'accept_proposal') {
        $res = $exchangeService->acceptProposal($exchangeId, (int)$currentUser['id'], ($currentUser['role'] === 'admin'));
        if ($res['success']) {
            set_flash('success', 'Exchange proposal accepted! Please coordinate physical item exchange.');
        } else {
            set_flash('error', $res['error']);
        }
    } elseif ($action === 'reject_proposal') {
        $res = $exchangeService->rejectProposal($exchangeId, (int)$currentUser['id'], ($currentUser['role'] === 'admin'));
        if ($res['success']) {
            set_flash('info', 'Exchange proposal declined.');
        } else {
            set_flash('error', $res['error']);
        }
    } elseif ($action === 'complete_exchange') {
        $res = $exchangeService->completeExchange($exchangeId, (int)$currentUser['id'], ($currentUser['role'] === 'admin'));
        if ($res['success']) {
            set_flash('success', 'Exchange completed! Thank you for participating in circular campus sharing.');
        } else {
            set_flash('error', $res['error']);
        }
    }

    redirect('student/exchanges.php');
}

$availableExchanges = $exchangeService->getAvailableExchanges();
$myExchanges = $currentUser ? $exchangeService->getUserExchanges((int)$currentUser['id']) : [];

$pageTitle = "Item Exchanges - Campus Resource Sharing";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="bg-warning-subtle py-4 border-bottom">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-warning text-dark mb-2"><i class="bi bi-arrow-repeat me-1"></i>Mutual Campus Barter</span>
                <h2 class="fw-bold text-dark mb-1">Campus Item Exchanges</h2>
                <p class="text-muted small mb-0">Trade academic equipment, microcontroller kits, and textbooks directly with other students.</p>
            </div>
            <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-warning rounded-pill px-4 shadow-sm align-self-start align-self-md-auto fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> List an Item for Exchange
            </a>
        </div>
    </div>
</div>

<div class="container py-4">
    <?= display_flash_alerts() ?>

    <ul class="nav nav-pills mb-4" id="exchangeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4" id="browse-tab" data-bs-toggle="pill" data-bs-target="#browse-exchanges" type="button" role="tab">
                <i class="bi bi-grid me-1"></i> Available Exchange Listings (<?= count($availableExchanges) ?>)
            </button>
        </li>
        <?php if ($currentUser): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 ms-2" id="history-tab" data-bs-toggle="pill" data-bs-target="#my-exchanges" type="button" role="tab">
                <i class="bi bi-clock-history me-1"></i> My Proposals & Trades (<?= count($myExchanges) ?>)
            </button>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content" id="exchangeTabContent">
        <!-- Tab 1: Available Exchange Listings -->
        <div class="tab-pane fade show active" id="browse-exchanges" role="tabpanel">
            <?php if (empty($availableExchanges)): ?>
                <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                    <i class="bi bi-arrow-repeat text-muted" style="font-size: 3.5rem;"></i>
                    <h5 class="fw-bold text-dark mt-3">No active exchange listings</h5>
                    <p class="text-muted small mx-auto" style="max-width: 480px;">
                        Have engineering kits, calculators, or reference materials you want to trade for something else?
                    </p>
                    <div>
                        <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-warning rounded-pill px-4 fw-semibold">List an Item for Exchange</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($availableExchanges as $item): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="resource-card shadow-sm">
                                <div class="resource-card-img-wrap">
                                    <img src="<?= get_resource_image_url($item['image_path'], (int)$item['category_id']) ?>" alt="<?= e($item['title']) ?>">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-warning text-dark"><i class="bi bi-arrow-repeat me-1"></i>Exchange</span>
                                    </div>
                                </div>
                                <div class="p-3 d-flex flex-column flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1 text-xs text-muted">
                                        <span><i class="bi bi-tag me-1"></i><?= e($item['category_name']) ?></span>
                                        <span><?= condition_badge($item['condition_status']) ?></span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($item['title']) ?>">
                                        <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $item['id'] ?>" class="text-decoration-none text-dark">
                                            <?= e($item['title']) ?>
                                        </a>
                                    </h6>
                                    
                                    <?php if (!empty($item['exchange_requirements'])): ?>
                                        <div class="p-2 rounded-2 bg-light text-xs text-dark my-2">
                                            <strong>Wants in return:</strong> <?= e($item['exchange_requirements']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                                        <div class="text-xs text-muted">
                                            <span class="d-block fw-semibold text-dark text-truncate" style="max-width: 130px;"><?= e($item['owner_name']) ?></span>
                                            <span><?= e($item['department_name'] ?? 'Campus') ?></span>
                                        </div>
                                        <?php if ($currentUser && (int)$item['owner_id'] === (int)$currentUser['id']): ?>
                                            <span class="badge bg-light text-secondary border">Your Listing</span>
                                        <?php elseif ($currentUser): ?>
                                            <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#proposeModal<?= $item['id'] ?>">
                                                Propose Trade
                                            </button>
                                        <?php else: ?>
                                            <a href="<?= APP_URL ?>/login.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">Sign in</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Propose Modal -->
                        <?php if ($currentUser && (int)$item['owner_id'] !== (int)$currentUser['id']): ?>
                            <div class="modal fade" id="proposeModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Propose Item Exchange</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="<?= APP_URL ?>/student/exchanges.php">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="submit_proposal">
                                            <input type="hidden" name="listing_resource_id" value="<?= $item['id'] ?>">

                                            <div class="modal-body text-start">
                                                <p class="small text-muted mb-2">You are offering an exchange for <strong><?= e($item['title']) ?></strong>.</p>
                                                <?php if (!empty($item['exchange_requirements'])): ?>
                                                    <div class="alert alert-warning py-2 px-3 small rounded-3 mb-3">
                                                        <strong>Owner seeks:</strong> <?= e($item['exchange_requirements']) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-secondary">What item are you offering in return? <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" name="offered_item_description" rows="3" placeholder="Describe the item, make/model, edition, condition, and included components..." required></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-secondary">Proposed Handover Terms (Optional)</label>
                                                    <input type="text" class="form-control" name="proposed_terms" placeholder="e.g. 1-to-1 direct exchange, meet at library cafeteria">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold">Submit Trade Proposal</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 2: My Proposals & Trades -->
        <?php if ($currentUser): ?>
        <div class="tab-pane fade" id="my-exchanges" role="tabpanel">
            <?php if (empty($myExchanges)): ?>
                <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                    <i class="bi bi-arrow-repeat text-muted" style="font-size: 3.5rem;"></i>
                    <h5 class="fw-bold text-dark mt-3">No exchange activity yet</h5>
                    <p class="text-muted small">Your exchange proposals and proposals received on your listings will appear here.</p>
                </div>
            <?php else: ?>
                <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Listed Item</th>
                                    <th>Item Offered in Return</th>
                                    <th>Parties</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myExchanges as $exc): 
                                    $isOwner = ((int)$exc['listing_owner_id'] === (int)$currentUser['id']);
                                ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark d-block"><?= e($exc['listing_title']) ?></span>
                                            <small class="text-xs text-muted">Owner: <?= e($exc['owner_name']) ?></small>
                                        </td>
                                        <td>
                                            <span class="small fw-semibold text-dark d-block"><?= e($exc['offered_item_description']) ?></span>
                                            <?php if (!empty($exc['proposed_terms'])): ?>
                                                <small class="text-xs text-muted">Terms: <?= e($exc['proposed_terms']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="text-xs">
                                                <strong>From:</strong> <?= e($exc['proposer_name']) ?> <?= !$isOwner ? '(You)' : '' ?><br>
                                                <strong>To:</strong> <?= e($exc['owner_name']) ?> <?= $isOwner ? '(You)' : '' ?>
                                            </div>
                                        </td>
                                        <td><?= status_badge($exc['status']) ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <!-- Owner action on pending proposal -->
                                                <?php if ($isOwner && $exc['status'] === 'proposed'): ?>
                                                    <form method="POST" action="<?= APP_URL ?>/student/exchanges.php" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="accept_proposal">
                                                        <input type="hidden" name="exchange_id" value="<?= $exc['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">Accept</button>
                                                    </form>
                                                    <form method="POST" action="<?= APP_URL ?>/student/exchanges.php" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="reject_proposal">
                                                        <input type="hidden" name="exchange_id" value="<?= $exc['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2">Decline</button>
                                                    </form>
                                                <?php elseif ($exc['status'] === 'accepted'): ?>
                                                    <!-- Both can complete -->
                                                    <form method="POST" action="<?= APP_URL ?>/student/exchanges.php" class="d-inline" data-confirm="Confirm that you have completed physical exchange of both items?">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="complete_exchange">
                                                        <input type="hidden" name="exchange_id" value="<?= $exc['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">
                                                            <i class="bi bi-check-all me-1"></i>Confirm Completed
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-xs text-muted">Finished</span>
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
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
