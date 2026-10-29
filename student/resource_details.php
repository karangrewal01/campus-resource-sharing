<?php
/**
 * Campus Resource Sharing Platform - Dedicated Resource Details Page
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/ResourceService.php';
require_once BASE_PATH . '/services/RequestService.php';

$resId = (int)($_GET['id'] ?? 0);
$resourceService = new ResourceService();
$resource = $resourceService->getById($resId);

if (!$resource) {
    set_flash('error', 'The requested resource was not found or has been removed.');
    redirect('student/browse_resources.php');
}

$currentUser = is_logged_in() ? current_user() : null;
$isOwner = $currentUser && ((int)$currentUser['id'] === (int)$resource['owner_id']);
$isAdmin = $currentUser && ($currentUser['role'] === 'admin');

// Check if current user already has an active request for this resource
$existingRequest = null;
if ($currentUser && !$isOwner) {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT req.*, conv.id as conversation_id
        FROM resource_requests req
        LEFT JOIN conversations conv ON conv.resource_request_id = req.id
        WHERE req.resource_id = ? AND req.requester_id = ? AND req.status IN ('pending', 'approved', 'ready_for_collection', 'borrowed')
        ORDER BY req.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$resId, $currentUser['id']]);
    $existingRequest = $stmt->fetch();
}

// Handle request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_resource') {
    require_auth();
    require_csrf_token();

    $requestService = new RequestService();
    $result = $requestService->submitRequest((int)$currentUser['id'], [
        'resource_id'             => $resId,
        'requested_start_date'    => $_POST['requested_start_date'] ?? date('Y-m-d'),
        'requested_duration_days' => $_POST['requested_duration_days'] ?? $resource['borrowing_duration_days'],
        'purpose'                 => $_POST['purpose'] ?? ''
    ]);

    if ($result['success']) {
        set_flash('success', 'Your request has been submitted successfully to the resource owner!');
        redirect("student/resource_details.php?id={$resId}");
    } else {
        set_flash('error', $result['error']);
        redirect("student/resource_details.php?id={$resId}");
    }
}

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report_resource') {
    require_auth();
    require_csrf_token();

    $reason = trim($_POST['report_reason'] ?? '');
    $desc = trim($_POST['report_description'] ?? '');

    if (!empty($reason) && !empty($desc)) {
        $pdo = Database::getConnection();
        $repStmt = $pdo->prepare("INSERT INTO reports (reporter_id, resource_id, report_reason, description, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $repStmt->execute([$currentUser['id'], $resId, $reason, $desc]);
        log_audit($currentUser['id'], 'RESOURCE_REPORTED', 'reports', $resId, "Reported resource #{$resId}: {$reason}");
        set_flash('info', 'Thank you. Your report has been submitted to campus administrators for review.');
        redirect("student/resource_details.php?id={$resId}");
    } else {
        set_flash('error', 'Please provide a valid report reason and description.');
        redirect("student/resource_details.php?id={$resId}");
    }
}

$pageTitle = $resource['title'] . " - Resource Details";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/student/browse_resources.php" class="text-decoration-none">Resources</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/student/browse_resources.php?category_id=<?= $resource['category_id'] ?>" class="text-decoration-none"><?= e($resource['category_name']) ?></a></li>
            <li class="breadcrumb-item active text-truncate" style="max-width: 300px;"><?= e($resource['title']) ?></li>
        </ol>
    </nav>

    <?= display_flash_alerts() ?>

    <div class="row g-4">
        <!-- Resource Media & Left Column -->
        <div class="col-lg-5">
            <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden p-3 sticky-top" style="top: 80px;">
                <div class="rounded-3 overflow-hidden bg-light position-relative" style="max-height: 400px;">
                    <img src="<?= get_resource_image_url($resource['image_path'], (int)$resource['category_id']) ?>" alt="<?= e($resource['title']) ?>" class="w-100 h-auto object-fit-cover">
                    <div class="position-absolute top-0 end-0 m-3">
                        <?= method_badge($resource['sharing_method']) ?>
                    </div>
                </div>

                <!-- Verified Safety Badge -->
                <div class="mt-3 p-3 bg-light rounded-3 d-flex align-items-center gap-3">
                    <i class="bi bi-shield-check text-primary fs-3 flex-shrink-0"></i>
                    <div class="small">
                        <span class="fw-bold d-block text-dark">Verified Campus Listing</span>
                        <span class="text-muted text-xs">Listed by a verified member of <?= e(INSTITUTION_NAME) ?>.</span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <span class="text-xs text-muted">Listed on <?= format_date($resource['created_at']) ?></span>
                    <?php if (is_logged_in() && !$isOwner): ?>
                        <button type="button" class="btn btn-sm text-danger text-xs border-0 p-0" data-bs-toggle="modal" data-bs-target="#reportModal">
                            <i class="bi bi-flag me-1"></i>Report Listing
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Resource Details & Actions Right Column -->
        <div class="col-lg-7">
            <div class="card border-0 rounded-4 shadow-sm bg-white p-4 p-md-5 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-light text-secondary border"><i class="bi bi-tag me-1"></i><?= e($resource['category_name']) ?></span>
                    <?= condition_badge($resource['condition_status']) ?>
                    <?= status_badge($resource['availability_status']) ?>
                    <?php if (!empty($resource['course_semester_tag'])): ?>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><?= e($resource['course_semester_tag']) ?></span>
                    <?php endif; ?>
                </div>

                <h2 class="fw-bold text-dark mb-3"><?= e($resource['title']) ?></h2>

                <p class="text-secondary leading-relaxed mb-4" style="white-space: pre-line;">
                    <?= e($resource['description']) ?>
                </p>

                <!-- Key Specifications Grid -->
                <div class="row g-3 p-3 bg-light rounded-3 mb-4">
                    <div class="col-sm-6">
                        <small class="text-muted d-block text-xs text-uppercase fw-bold">Department</small>
                        <span class="fw-semibold text-dark"><?= e($resource['department_name'] ?? 'General Campus') ?> (<?= e($resource['department_code'] ?? 'ALL') ?>)</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block text-xs text-uppercase fw-bold">Availability / Stock</small>
                        <span class="fw-semibold text-dark"><?= $resource['available_quantity'] ?> of <?= $resource['total_quantity'] ?> unit(s) available</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block text-xs text-uppercase fw-bold">Borrowing Duration</small>
                        <span class="fw-semibold text-dark"><?= $resource['borrowing_duration_days'] ?> Days Max</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block text-xs text-uppercase fw-bold">Resource Type</small>
                        <span class="fw-semibold text-dark"><?= e(ucwords(str_replace('_', ' ', $resource['resource_type']))) ?></span>
                    </div>
                </div>

                <!-- Handover & Instructions -->
                <?php if (!empty($resource['collection_instructions'])): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-1"><i class="bi bi-geo-alt text-primary me-2"></i>Handover / Pickup Instructions</h6>
                        <p class="small text-muted mb-0 bg-light-subtle p-2 rounded-2 border"><?= e($resource['collection_instructions']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($resource['usage_instructions'])): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-1"><i class="bi bi-info-circle text-primary me-2"></i>Usage & Care Guidelines</h6>
                        <p class="small text-muted mb-0 bg-light-subtle p-2 rounded-2 border"><?= e($resource['usage_instructions']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($resource['sharing_method'] === 'exchange' && !empty($resource['exchange_requirements'])): ?>
                    <div class="mb-4 p-3 bg-warning-subtle rounded-3 border border-warning-subtle">
                        <h6 class="fw-bold text-warning-emphasis mb-1"><i class="bi bi-arrow-repeat me-2"></i>Owner's Desired Exchange Items</h6>
                        <p class="small text-dark mb-0"><?= e($resource['exchange_requirements']) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Owner Card -->
                <div class="p-3 border rounded-3 d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 48px; height: 48px;">
                            <?= strtoupper(substr($resource['owner_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <span class="text-xs text-muted text-uppercase fw-bold">Resource Owner</span>
                            <h6 class="mb-0 fw-bold text-dark"><?= e($resource['owner_name']) ?></h6>
                            <small class="text-muted text-xs"><?= strtoupper(e($resource['owner_role'])) ?> &bull; <?= e($resource['owner_course'] ?? $resource['owner_designation'] ?? 'Campus Member') ?></small>
                        </div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Verified</span>
                </div>

                <!-- Call to Action Buttons -->
                <div class="d-grid gap-2">
                    <?php if ($isOwner): ?>
                        <div class="alert alert-secondary text-center small mb-2">
                            <i class="bi bi-person-check me-1"></i> You are the owner of this listed resource.
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= APP_URL ?>/student/edit_resource.php?id=<?= $resource['id'] ?>" class="btn btn-outline-primary rounded-pill py-2 flex-grow-1 fw-semibold">
                                <i class="bi bi-pencil me-1"></i> Edit Listing
                            </a>
                            <a href="<?= APP_URL ?>/student/lending_requests.php" class="btn btn-primary rounded-pill py-2 flex-grow-1 fw-semibold">
                                <i class="bi bi-inbox me-1"></i> View Incoming Requests
                            </a>
                        </div>
                    <?php elseif (!is_logged_in()): ?>
                        <div class="alert alert-info text-center small mb-2">
                            Please sign in with your campus account to submit a request for this item.
                        </div>
                        <a href="<?= APP_URL ?>/login.php?redirect=student/resource_details.php?id=<?= $resource['id'] ?>" class="btn btn-primary rounded-pill py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Request Item
                        </a>
                    <?php elseif ($existingRequest): ?>
                        <div class="card p-3 bg-primary-subtle border-primary-subtle rounded-3 text-start">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-primary text-sm">Active Request Status:</span>
                                <?= status_badge($existingRequest['status']) ?>
                            </div>
                            <p class="text-xs text-muted mb-2">You requested this item on <?= format_date($existingRequest['created_at']) ?>.</p>
                            <div class="d-flex gap-2">
                                <a href="<?= APP_URL ?>/student/my_requests.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    View My Requests
                                </a>
                                <?php if (!empty($existingRequest['conversation_id'])): ?>
                                    <a href="<?= APP_URL ?>/student/messages.php?request_id=<?= $existingRequest['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                        <i class="bi bi-chat-dots me-1"></i> Chat with Owner
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($resource['available_quantity'] > 0 && $resource['availability_status'] === 'available'): ?>
                        <button type="button" class="btn btn-primary rounded-pill py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#requestModal">
                            <i class="bi bi-send me-1"></i> Request to <?= ($resource['sharing_method'] === 'donate') ? 'Receive Donation' : (($resource['sharing_method'] === 'exchange') ? 'Propose Exchange' : 'Borrow Resource') ?>
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary rounded-pill py-2 fw-semibold" disabled>
                            <i class="bi bi-slash-circle me-1"></i> Currently Unavailable / In Use
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Request Resource Modal -->
<?php if (is_logged_in() && !$isOwner && $resource['available_quantity'] > 0): ?>
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="requestModalLabel">Request Resource</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= APP_URL ?>/student/resource_details.php?id=<?= $resource['id'] ?>" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="request_resource">

                <div class="modal-body">
                    <p class="small text-muted">You are requesting <strong><?= e($resource['title']) ?></strong> from <?= e($resource['owner_name']) ?>.</p>

                    <div class="mb-3">
                        <label for="requested_start_date" class="form-label small fw-semibold text-secondary">Intended Collection Date</label>
                        <input type="date" class="form-control" id="requested_start_date" name="requested_start_date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <?php if ($resource['sharing_method'] !== 'donate'): ?>
                    <div class="mb-3">
                        <label for="requested_duration_days" class="form-label small fw-semibold text-secondary">Borrow Duration (Days)</label>
                        <input type="number" class="form-control" id="requested_duration_days" name="requested_duration_days" value="<?= $resource['borrowing_duration_days'] ?>" min="1" max="<?= $resource['borrowing_duration_days'] ?>" required>
                        <small class="text-muted text-xs">Maximum allowed for this item is <?= $resource['borrowing_duration_days'] ?> days.</small>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="purpose" class="form-label small fw-semibold text-secondary">Purpose of Request / Note to Owner</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="3" placeholder="e.g. Preparing for semester practical exams, need reference material..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Report Resource Modal -->
<?php if (is_logged_in() && !$isOwner): ?>
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="reportModalLabel"><i class="bi bi-flag-fill me-2"></i>Report Listing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= APP_URL ?>/student/resource_details.php?id=<?= $resource['id'] ?>" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="report_resource">

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="report_reason" class="form-label small fw-semibold text-secondary">Reason for Reporting</label>
                        <select class="form-select" id="report_reason" name="report_reason" required>
                            <option value="">Select reason...</option>
                            <option value="Inappropriate Content">Inappropriate Content</option>
                            <option value="Misleading Specifications">Misleading Specifications</option>
                            <option value="Damaged or Unusable Item">Damaged or Unusable Item</option>
                            <option value="Commercial Solicitation">Commercial Solicitation / Selling for Cash</option>
                            <option value="Other Policy Violation">Other Policy Violation</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="report_description" class="form-label small fw-semibold text-secondary">Detailed Description</label>
                        <textarea class="form-control" id="report_description" name="report_description" rows="3" placeholder="Provide specific details for campus administrators..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
