<?php
/**
 * Campus Resource Sharing Platform - Free Donations Module
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/DonationService.php';

$donationService = new DonationService();
$currentUser = is_logged_in() ? current_user() : null;

// Handle donation completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_donation') {
    require_auth();
    require_csrf_token();
    $donationId = (int)($_POST['donation_id'] ?? 0);
    $res = $donationService->completeDonation($donationId, (int)$currentUser['id'], ($currentUser['role'] === 'admin'));

    if ($res['success']) {
        set_flash('success', 'Donation handover completed! Thank you for supporting the campus community.');
    } else {
        set_flash('error', $res['error']);
    }
    redirect('student/donations.php');
}

$availableDonations = $donationService->getAvailableDonations();
$myDonations = $currentUser ? $donationService->getUserDonations((int)$currentUser['id']) : [];

$pageTitle = "Free Campus Donations";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="bg-success-subtle py-4 border-bottom">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-success text-white mb-2"><i class="bi bi-gift-fill me-1"></i>Zero Cost &bull; Free Transfer</span>
                <h2 class="fw-bold text-dark mb-1">Campus Resource Donations</h2>
                <p class="text-muted small mb-0">Give away academic textbooks, handwritten notes, and lab coats permanently to junior students.</p>
            </div>
            <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-success rounded-pill px-4 shadow-sm align-self-start align-self-md-auto">
                <i class="bi bi-gift me-1"></i> Donate an Item
            </a>
        </div>
    </div>
</div>

<div class="container py-4">
    <?= display_flash_alerts() ?>

    <ul class="nav nav-pills mb-4" id="donationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4" id="browse-tab" data-bs-toggle="pill" data-bs-target="#browse-donations" type="button" role="tab">
                <i class="bi bi-grid me-1"></i> Available Free Items (<?= count($availableDonations) ?>)
            </button>
        </li>
        <?php if ($currentUser): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 ms-2" id="history-tab" data-bs-toggle="pill" data-bs-target="#my-donations" type="button" role="tab">
                <i class="bi bi-clock-history me-1"></i> My Donation Activity (<?= count($myDonations) ?>)
            </button>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content" id="donationTabContent">
        <!-- Tab 1: Available Donations -->
        <div class="tab-pane fade show active" id="browse-donations" role="tabpanel">
            <?php if (empty($availableDonations)): ?>
                <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                    <i class="bi bi-gift text-muted" style="font-size: 3.5rem;"></i>
                    <h5 class="fw-bold text-dark mt-3">No active free donations right now</h5>
                    <p class="text-muted small mx-auto" style="max-width: 480px;">
                        Finished with last semester's handwritten notes, reference guides, or lab manuals? Donate them to junior students!
                    </p>
                    <div>
                        <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-success rounded-pill px-4">Donate an Item</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($availableDonations as $item): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="resource-card shadow-sm">
                                <div class="resource-card-img-wrap">
                                    <img src="<?= get_resource_image_url($item['image_path'], (int)$item['category_id']) ?>" alt="<?= e($item['title']) ?>">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-success text-white"><i class="bi bi-gift me-1"></i>Free</span>
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
                                    <p class="text-muted text-xs mb-3 text-truncate-2" style="min-height: 34px;">
                                        <?= e(substr($item['description'], 0, 95)) ?>...
                                    </p>

                                    <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                                        <div class="text-xs text-muted">
                                            <span class="d-block fw-semibold text-dark text-truncate" style="max-width: 130px;"><?= e($item['donor_name']) ?></span>
                                            <span><?= e($item['department_name'] ?? 'Campus') ?></span>
                                        </div>
                                        <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-success rounded-pill px-3">
                                            Request Free
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 2: My Donation Activity -->
        <?php if ($currentUser): ?>
        <div class="tab-pane fade" id="my-donations" role="tabpanel">
            <?php if (empty($myDonations)): ?>
                <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                    <i class="bi bi-gift text-muted" style="font-size: 3.5rem;"></i>
                    <h5 class="fw-bold text-dark mt-3">No donation records yet</h5>
                    <p class="text-muted small">Items you donate or receive through donations will appear here.</p>
                </div>
            <?php else: ?>
                <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Resource</th>
                                    <th>Donor</th>
                                    <th>Recipient</th>
                                    <th>Status</th>
                                    <th>Handover Notes</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myDonations as $don): 
                                    $isDonor = ((int)$don['donor_id'] === (int)$currentUser['id']);
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($don['title']) ?></div>
                                            <span class="text-xs text-muted"><?= e($don['category_name']) ?></span>
                                        </td>
                                        <td><?= e($don['donor_name']) ?> <?= $isDonor ? '<span class="badge bg-light text-dark border">You</span>' : '' ?></td>
                                        <td><?= e($don['recipient_name']) ?> <?= !$isDonor ? '<span class="badge bg-light text-dark border">You</span>' : '' ?></td>
                                        <td><?= status_badge($don['status']) ?></td>
                                        <td><span class="small text-muted"><?= e($don['notes'] ?? 'None') ?></span></td>
                                        <td class="text-end">
                                            <?php if ($isDonor && $don['status'] !== 'completed'): ?>
                                                <form method="POST" action="<?= APP_URL ?>/student/donations.php" data-confirm="Confirm that you have completed physical handover of this donated item?">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="complete_donation">
                                                    <input type="hidden" name="donation_id" value="<?= $don['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">
                                                        <i class="bi bi-check2-circle me-1"></i>Confirm Handover
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-light text-success border"><i class="bi bi-check-all me-1"></i>Completed</span>
                                            <?php endif; ?>
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
