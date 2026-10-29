<?php
/**
 * Campus Resource Sharing Platform - Edit Existing Resource
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/ResourceService.php';

require_auth();

$user = current_user();
$resourceService = new ResourceService();
$resId = (int)($_GET['id'] ?? 0);

$resource = $resourceService->getById($resId);

if (!$resource) {
    set_flash('error', 'Resource not found.');
    redirect('student/my_resources.php');
}

$isAdmin = ($user['role'] === 'admin');
if (!$isAdmin && (int)$resource['owner_id'] !== (int)$user['id']) {
    set_flash('error', 'Unauthorized: You can only edit your own listings.');
    redirect('student/my_resources.php');
}

$categories = $resourceService->getAllCategories();
$departments = $resourceService->getAllDepartments();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $resourceType = $_POST['resource_type'] ?? 'book';
    $sharingMethod = $_POST['sharing_method'] ?? 'borrow';
    $conditionStatus = $_POST['condition_status'] ?? 'good';
    $availabilityStatus = $_POST['availability_status'] ?? 'available';
    $durationDays = max(1, (int)($_POST['borrowing_duration_days'] ?? 7));
    $exchangeRequirements = trim($_POST['exchange_requirements'] ?? '');
    $collectionInstructions = trim($_POST['collection_instructions'] ?? '');
    $usageInstructions = trim($_POST['usage_instructions'] ?? '');
    $courseSemesterTag = trim($_POST['course_semester_tag'] ?? '');

    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if (empty($categoryId)) $errors[] = 'Category is required.';

    $imagePath = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadRes = handle_image_upload($_FILES['image'], 'resources');
        if ($uploadRes['success']) {
            $imagePath = $uploadRes['path'];
        } else {
            $errors[] = $uploadRes['error'];
        }
    }

    if (empty($errors)) {
        $updated = $resourceService->update($resId, (int)$user['id'], [
            'category_id'             => $categoryId,
            'department_id'           => $departmentId,
            'title'                   => $title,
            'description'             => $description,
            'resource_type'           => $resourceType,
            'sharing_method'          => $sharingMethod,
            'condition_status'        => $conditionStatus,
            'availability_status'     => $availabilityStatus,
            'borrowing_duration_days' => $durationDays,
            'exchange_requirements'   => $exchangeRequirements,
            'collection_instructions' => $collectionInstructions,
            'usage_instructions'      => $usageInstructions,
            'image_path'              => $imagePath,
            'course_semester_tag'     => $courseSemesterTag
        ], $isAdmin);

        if ($updated) {
            set_flash('success', "Listing '{$title}' has been successfully updated!");
            redirect('student/my_resources.php');
        } else {
            $errors[] = 'Failed to update resource listing.';
        }
    }
}

$pageTitle = "Edit Resource - " . $resource['title'];
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Edit Listing: <?= e($resource['title']) ?></h2>
                <p class="text-muted mb-0">Update item description, availability status, or handover details.</p>
            </div>
            <a href="<?= APP_URL ?>/student/my_resources.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to My Listings
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-4 shadow-sm mb-4">
                <ul class="mb-0 small ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card border-0 rounded-4 shadow-sm bg-white p-4 p-md-5">
            <form method="POST" action="<?= APP_URL ?>/student/edit_resource.php?id=<?= $resource['id'] ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>

                <div class="row g-4">
                    <div class="col-md-8">
                        <label for="title" class="form-label small fw-semibold text-secondary">Resource Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="title" name="title" value="<?= e($_POST['title'] ?? $resource['title']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="category_id" class="form-label small fw-semibold text-secondary">Category <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" id="category_id" name="category_id" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ((int)($resource['category_id']) === (int)$cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label for="description" class="form-label small fw-semibold text-secondary">Detailed Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?= e($_POST['description'] ?? $resource['description']) ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label for="sharing_method" class="form-label small fw-semibold text-secondary">Sharing Method</label>
                        <select class="form-select" id="sharing_method" name="sharing_method">
                            <option value="borrow" <?= ($resource['sharing_method'] === 'borrow') ? 'selected' : '' ?>>Borrow / Lend</option>
                            <option value="donate" <?= ($resource['sharing_method'] === 'donate') ? 'selected' : '' ?>>Free Donation</option>
                            <option value="exchange" <?= ($resource['sharing_method'] === 'exchange') ? 'selected' : '' ?>>Item Exchange</option>
                            <option value="temporary_use" <?= ($resource['sharing_method'] === 'temporary_use') ? 'selected' : '' ?>>Short-Term Use</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="resource_type" class="form-label small fw-semibold text-secondary">Resource Type</label>
                        <select class="form-select" id="resource_type" name="resource_type">
                            <?php 
                            $types = ['book', 'notes', 'calculator', 'stationery', 'electronics', 'lab_equipment', 'engineering_tool', 'sports', 'art_supplies', 'hostel_item', 'other'];
                            foreach ($types as $t):
                            ?>
                                <option value="<?= $t ?>" <?= ($resource['resource_type'] === $t) ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="condition_status" class="form-label small fw-semibold text-secondary">Condition</label>
                        <select class="form-select" id="condition_status" name="condition_status">
                            <option value="new" <?= ($resource['condition_status'] === 'new') ? 'selected' : '' ?>>Brand New</option>
                            <option value="like_new" <?= ($resource['condition_status'] === 'like_new') ? 'selected' : '' ?>>Like New</option>
                            <option value="good" <?= ($resource['condition_status'] === 'good') ? 'selected' : '' ?>>Good</option>
                            <option value="fair" <?= ($resource['condition_status'] === 'fair') ? 'selected' : '' ?>>Fair</option>
                            <option value="poor" <?= ($resource['condition_status'] === 'poor') ? 'selected' : '' ?>>Poor</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="availability_status" class="form-label small fw-semibold text-secondary">Availability Status</label>
                        <select class="form-select" id="availability_status" name="availability_status">
                            <option value="available" <?= ($resource['availability_status'] === 'available') ? 'selected' : '' ?>>Available for Requests</option>
                            <option value="unavailable" <?= ($resource['availability_status'] === 'unavailable') ? 'selected' : '' ?>>Temporarily Unavailable / Hidden</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="borrowing_duration_days" class="form-label small fw-semibold text-secondary">Borrowing Duration (Days)</label>
                        <input type="number" class="form-control" id="borrowing_duration_days" name="borrowing_duration_days" value="<?= $resource['borrowing_duration_days'] ?>" min="1" max="60">
                    </div>

                    <div class="col-md-4">
                        <label for="course_semester_tag" class="form-label small fw-semibold text-secondary">Course / Semester Tag</label>
                        <input type="text" class="form-control" id="course_semester_tag" name="course_semester_tag" value="<?= e($resource['course_semester_tag'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="collection_instructions" class="form-label small fw-semibold text-secondary">Collection / Handover Instructions</label>
                        <input type="text" class="form-control" id="collection_instructions" name="collection_instructions" value="<?= e($resource['collection_instructions'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="usage_instructions" class="form-label small fw-semibold text-secondary">Special Usage Instructions</label>
                        <input type="text" class="form-control" id="usage_instructions" name="usage_instructions" value="<?= e($resource['usage_instructions'] ?? '') ?>">
                    </div>

                    <div class="col-md-12">
                        <label for="image" class="form-label small fw-semibold text-secondary">Replace Image (Optional)</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                        <?php if (!empty($resource['image_path'])): ?>
                            <div class="mt-2 text-xs text-muted">Current image: <code><?= e($resource['image_path']) ?></code></div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= APP_URL ?>/student/my_resources.php" class="btn btn-light rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-semibold shadow-sm">
                        <i class="bi bi-check2 me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
