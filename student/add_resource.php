<?php
/**
 * Campus Resource Sharing Platform - List a New Resource
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/ResourceService.php';

require_auth();

$user = current_user();
$resourceService = new ResourceService();

$categories = $resourceService->getAllCategories();
$departments = $resourceService->getAllDepartments();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : $user['department_id'];
    $resourceType = $_POST['resource_type'] ?? 'book';
    $sharingMethod = $_POST['sharing_method'] ?? 'borrow';
    $conditionStatus = $_POST['condition_status'] ?? 'good';
    $totalQuantity = max(1, (int)($_POST['total_quantity'] ?? 1));
    $durationDays = max(1, (int)($_POST['borrowing_duration_days'] ?? 7));
    $exchangeRequirements = trim($_POST['exchange_requirements'] ?? '');
    $collectionInstructions = trim($_POST['collection_instructions'] ?? '');
    $usageInstructions = trim($_POST['usage_instructions'] ?? '');
    $courseSemesterTag = trim($_POST['course_semester_tag'] ?? '');

    // Validation
    if (empty($title)) {
        $errors[] = 'Resource title is required.';
    }
    if (empty($description)) {
        $errors[] = 'Resource description is required.';
    }
    if (empty($categoryId)) {
        $errors[] = 'Please select a valid category.';
    }

    // Handle optional image upload
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
        $resId = $resourceService->create([
            'owner_id'                => (int)$user['id'],
            'category_id'             => $categoryId,
            'department_id'           => $departmentId,
            'title'                   => $title,
            'description'             => $description,
            'resource_type'           => $resourceType,
            'sharing_method'          => $sharingMethod,
            'condition_status'        => $conditionStatus,
            'total_quantity'          => $totalQuantity,
            'borrowing_duration_days' => $durationDays,
            'exchange_requirements'   => $exchangeRequirements,
            'collection_instructions' => $collectionInstructions,
            'usage_instructions'      => $usageInstructions,
            'image_path'              => $imagePath,
            'course_semester_tag'     => $courseSemesterTag
        ]);

        set_flash('success', "Resource '{$title}' has been successfully listed in the campus catalog!");
        redirect('student/my_resources.php');
    }
}

$pageTitle = "List New Resource";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">List a New Campus Resource</h2>
                <p class="text-muted mb-0">Share unused textbooks, calculators, stationery, and lab tools with fellow students.</p>
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
            <form method="POST" action="<?= APP_URL ?>/student/add_resource.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>

                <div class="row g-4">
                    <div class="col-md-8">
                        <label for="title" class="form-label small fw-semibold text-secondary">Resource Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="title" name="title" value="<?= e($_POST['title'] ?? '') ?>" placeholder="e.g. Operating Systems Concepts (Silberschatz) or Casio fx-991EX" required>
                    </div>

                    <div class="col-md-4">
                        <label for="category_id" class="form-label small fw-semibold text-secondary">Category <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" id="category_id" name="category_id" required>
                            <option value="">Select Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && (int)$_POST['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label for="description" class="form-label small fw-semibold text-secondary">Detailed Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Mention edition, authors, syllabus coverage, condition details, accessories included..." required><?= e($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label for="sharing_method" class="form-label small fw-semibold text-secondary">Sharing Method</label>
                        <select class="form-select" id="sharing_method" name="sharing_method" onchange="toggleMethodFields()">
                            <option value="borrow" <?= (!isset($_POST['sharing_method']) || $_POST['sharing_method'] === 'borrow') ? 'selected' : '' ?>>Borrow / Lend (Temporary)</option>
                            <option value="donate" <?= (isset($_POST['sharing_method']) && $_POST['sharing_method'] === 'donate') ? 'selected' : '' ?>>Free Donation (Permanent)</option>
                            <option value="exchange" <?= (isset($_POST['sharing_method']) && $_POST['sharing_method'] === 'exchange') ? 'selected' : '' ?>>Item Exchange (Barter)</option>
                            <option value="temporary_use" <?= (isset($_POST['sharing_method']) && $_POST['sharing_method'] === 'temporary_use') ? 'selected' : '' ?>>Short-Term Campus Use</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="resource_type" class="form-label small fw-semibold text-secondary">Resource Type</label>
                        <select class="form-select" id="resource_type" name="resource_type">
                            <option value="book">Textbook / Reference Book</option>
                            <option value="notes">Academic Notes / Study Material</option>
                            <option value="calculator">Scientific Calculator</option>
                            <option value="stationery">Stationery & Drawing Kit</option>
                            <option value="electronics">Electronics & Components</option>
                            <option value="lab_equipment">Laboratory Equipment</option>
                            <option value="engineering_tool">Engineering & Hardware Tools</option>
                            <option value="sports">Sports Equipment</option>
                            <option value="art_supplies">Art & Creative Supplies</option>
                            <option value="hostel_item">Hostel & Daily-Use Item</option>
                            <option value="other">Other Campus Resource</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="condition_status" class="form-label small fw-semibold text-secondary">Condition</label>
                        <select class="form-select" id="condition_status" name="condition_status">
                            <option value="new">Brand New</option>
                            <option value="like_new">Like New (Mint)</option>
                            <option value="good" selected>Good (Minor wear, fully functional)</option>
                            <option value="fair">Fair (Usable with pencil marks / wear)</option>
                            <option value="poor">Worn / Functional for parts</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="department_id" class="form-label small fw-semibold text-secondary">Target Department</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="">Open to All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= ((int)($user['department_id'] ?? 0) === (int)$dept['id']) ? 'selected' : '' ?>>
                                    <?= e($dept['department_name']) ?> (<?= e($dept['department_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="course_semester_tag" class="form-label small fw-semibold text-secondary">Course / Semester Tag</label>
                        <input type="text" class="form-control" id="course_semester_tag" name="course_semester_tag" value="<?= e($_POST['course_semester_tag'] ?? '') ?>" placeholder="e.g. BCA Sem 4 / CSE Sem 6">
                    </div>

                    <div class="col-md-2" id="wrap_duration">
                        <label for="borrowing_duration_days" class="form-label small fw-semibold text-secondary">Borrow Days</label>
                        <input type="number" class="form-control" id="borrowing_duration_days" name="borrowing_duration_days" value="<?= e($_POST['borrowing_duration_days'] ?? '7') ?>" min="1" max="60">
                    </div>

                    <div class="col-md-2">
                        <label for="total_quantity" class="form-label small fw-semibold text-secondary">Total Quantity</label>
                        <input type="number" class="form-control" id="total_quantity" name="total_quantity" value="1" min="1" max="100">
                    </div>

                    <!-- Exchange Requirement Box -->
                    <div class="col-md-12 d-none" id="wrap_exchange">
                        <div class="p-3 bg-warning-subtle rounded-3 border border-warning-subtle">
                            <label for="exchange_requirements" class="form-label small fw-bold text-warning-emphasis">What items are you willing to accept in exchange?</label>
                            <input type="text" class="form-control" id="exchange_requirements" name="exchange_requirements" placeholder="e.g. Willing to exchange for Python Book, Raspberry Pi, or Scientific Calculator">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="collection_instructions" class="form-label small fw-semibold text-secondary">Handover / Pickup Location Instructions</label>
                        <input type="text" class="form-control" id="collection_instructions" name="collection_instructions" value="<?= e($_POST['collection_instructions'] ?? '') ?>" placeholder="e.g. Central Library 1st floor reading hall or Cafeteria">
                    </div>

                    <div class="col-md-6">
                        <label for="usage_instructions" class="form-label small fw-semibold text-secondary">Special Usage or Return Instructions</label>
                        <input type="text" class="form-control" id="usage_instructions" name="usage_instructions" value="<?= e($_POST['usage_instructions'] ?? '') ?>" placeholder="e.g. Handle optical prisms carefully, do not highlight with pen">
                    </div>

                    <!-- Image Upload with Live Preview -->
                    <div class="col-md-12">
                        <label for="image" class="form-label small fw-semibold text-secondary">Resource Photograph (Optional, Max 5MB)</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp" data-preview="imgPreview">
                        <div class="form-text text-xs">Supported formats: JPG, PNG, WebP. If no photo is uploaded, a clean category icon placeholder is used.</div>
                        <div class="mt-3">
                            <img id="imgPreview" src="" alt="Image preview" class="rounded-3 border shadow-sm d-none" style="max-height: 180px; max-width: 260px; object-fit: cover;">
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= APP_URL ?>/student/my_resources.php" class="btn btn-light rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Publish Resource Listing
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function toggleMethodFields() {
    const method = document.getElementById('sharing_method').value;
    const wrapExchange = document.getElementById('wrap_exchange');
    const wrapDuration = document.getElementById('wrap_duration');

    if (method === 'exchange') {
        wrapExchange.classList.remove('d-none');
    } else {
        wrapExchange.classList.add('d-none');
    }

    if (method === 'donate') {
        wrapDuration.style.opacity = '0.5';
    } else {
        wrapDuration.style.opacity = '1';
    }
}
document.addEventListener('DOMContentLoaded', toggleMethodFields);
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
