<?php
/**
 * Campus Resource Sharing Platform - Resource Discovery & Marketplace
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/ResourceService.php';

$resourceService = new ResourceService();

// Parse filter parameters from GET
$filters = [
    'search'              => trim($_GET['search'] ?? ''),
    'category_id'         => !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null,
    'sharing_method'      => !empty($_GET['sharing_method']) ? trim($_GET['sharing_method']) : null,
    'resource_type'       => !empty($_GET['resource_type']) ? trim($_GET['resource_type']) : null,
    'condition_status'    => !empty($_GET['condition_status']) ? trim($_GET['condition_status']) : null,
    'department_id'       => !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null,
    'availability_status' => !empty($_GET['availability_status']) ? trim($_GET['availability_status']) : null,
    'only_available'      => isset($_GET['only_available']) ? (bool)$_GET['only_available'] : false,
    'sort'                => trim($_GET['sort'] ?? 'newest')
];

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;

$totalCount = $resourceService->countResources($filters);
$resources = $resourceService->getResources($filters, $limit, $offset);
$totalPages = ceil($totalCount / $limit);

$categories = $resourceService->getAllCategories();
$departments = $resourceService->getAllDepartments();

$pageTitle = "Browse Campus Resources";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="bg-primary-subtle py-4 border-bottom">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-1">Campus Resource Catalog</h2>
                <p class="text-muted small mb-0">Discover and borrow textbooks, lab manuals, tools, and equipment across all academic departments.</p>
            </div>
            <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-primary rounded-pill px-4 shadow-sm align-self-start align-self-md-auto">
                <i class="bi bi-plus-circle me-1"></i> List New Resource
            </a>
        </div>
    </div>
</div>

<div class="container py-4">
    <?= display_flash_alerts() ?>

    <div class="row g-4">
        <!-- Sidebar Filters Column -->
        <div class="col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm bg-white p-3 p-sm-4 sticky-top" style="top: 80px; z-index: 10;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-funnel text-primary me-2"></i>Filters</h5>
                    <a href="<?= APP_URL ?>/student/browse_resources.php" class="text-xs text-muted text-decoration-none">Reset All</a>
                </div>

                <form method="GET" action="<?= APP_URL ?>/student/browse_resources.php">
                    <!-- Search input -->
                    <div class="mb-3">
                        <label class="form-label text-xs fw-bold text-secondary text-uppercase">Keyword Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Title, topic, course..." value="<?= e($filters['search']) ?>">
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="mb-3">
                        <label class="form-label text-xs fw-bold text-secondary text-uppercase">Category</label>
                        <select name="category_id" class="form-select text-sm">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sharing Method Filter -->
                    <div class="mb-3">
                        <label class="form-label text-xs fw-bold text-secondary text-uppercase">Sharing Method</label>
                        <select name="sharing_method" class="form-select text-sm">
                            <option value="">All Methods</option>
                            <option value="borrow" <?= ($filters['sharing_method'] === 'borrow') ? 'selected' : '' ?>>Borrow / Lend</option>
                            <option value="donate" <?= ($filters['sharing_method'] === 'donate') ? 'selected' : '' ?>>Free Donation</option>
                            <option value="exchange" <?= ($filters['sharing_method'] === 'exchange') ? 'selected' : '' ?>>Item Exchange</option>
                            <option value="temporary_use" <?= ($filters['sharing_method'] === 'temporary_use') ? 'selected' : '' ?>>Short-Term Use</option>
                        </select>
                    </div>

                    <!-- Department Filter -->
                    <div class="mb-3">
                        <label class="form-label text-xs fw-bold text-secondary text-uppercase">Department</label>
                        <select name="department_id" class="form-select text-sm">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= ($filters['department_id'] === (int)$dept['id']) ? 'selected' : '' ?>>
                                    <?= e($dept['department_name']) ?> (<?= e($dept['department_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Condition Status Filter -->
                    <div class="mb-3">
                        <label class="form-label text-xs fw-bold text-secondary text-uppercase">Item Condition</label>
                        <select name="condition_status" class="form-select text-sm">
                            <option value="">Any Condition</option>
                            <option value="new" <?= ($filters['condition_status'] === 'new') ? 'selected' : '' ?>>Brand New</option>
                            <option value="like_new" <?= ($filters['condition_status'] === 'like_new') ? 'selected' : '' ?>>Like New</option>
                            <option value="good" <?= ($filters['condition_status'] === 'good') ? 'selected' : '' ?>>Good</option>
                            <option value="fair" <?= ($filters['condition_status'] === 'fair') ? 'selected' : '' ?>>Fair / Usable</option>
                        </select>
                    </div>

                    <!-- Availability Status -->
                    <div class="mb-3">
                        <label class="form-label text-xs fw-bold text-secondary text-uppercase">Availability</label>
                        <select name="availability_status" class="form-select text-sm">
                            <option value="">All Statuses</option>
                            <option value="available" <?= ($filters['availability_status'] === 'available') ? 'selected' : '' ?>>Available Now</option>
                            <option value="reserved" <?= ($filters['availability_status'] === 'reserved') ? 'selected' : '' ?>>Reserved</option>
                            <option value="borrowed" <?= ($filters['availability_status'] === 'borrowed') ? 'selected' : '' ?>>Currently Borrowed</option>
                        </select>
                    </div>

                    <!-- Sort by -->
                    <div class="mb-4">
                        <label class="form-label text-xs fw-bold text-secondary text-uppercase">Sort By</label>
                        <select name="sort" class="form-select text-sm">
                            <option value="newest" <?= ($filters['sort'] === 'newest') ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= ($filters['sort'] === 'oldest') ? 'selected' : '' ?>>Oldest First</option>
                            <option value="title_asc" <?= ($filters['sort'] === 'title_asc') ? 'selected' : '' ?>>Title (A to Z)</option>
                            <option value="title_desc" <?= ($filters['sort'] === 'title_desc') ? 'selected' : '' ?>>Title (Z to A)</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill fw-semibold">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resources Results Column -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small">
                    Showing <strong><?= count($resources) ?></strong> of <strong><?= $totalCount ?></strong> resources
                    <?php if (!empty($filters['search'])): ?>
                        for "<strong><?= e($filters['search']) ?></strong>"
                    <?php endif; ?>
                </span>
            </div>

            <?php if (empty($resources)): ?>
                <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                    <i class="bi bi-search text-muted" style="font-size: 3.5rem;"></i>
                    <h5 class="fw-bold text-dark mt-3">No matching campus resources found</h5>
                    <p class="text-muted small mx-auto" style="max-width: 480px;">
                        Try broadening your search keywords or resetting category filters to see more educational materials.
                    </p>
                    <div>
                        <a href="<?= APP_URL ?>/student/browse_resources.php" class="btn btn-outline-primary rounded-pill px-4">Reset All Filters</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($resources as $res): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="resource-card shadow-sm">
                                <div class="resource-card-img-wrap">
                                    <img src="<?= get_resource_image_url($res['image_path'], (int)$res['category_id']) ?>" alt="<?= e($res['title']) ?>" loading="lazy">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <?= method_badge($res['sharing_method']) ?>
                                    </div>
                                    <div class="position-absolute bottom-0 start-0 m-2">
                                        <?= status_badge($res['availability_status']) ?>
                                    </div>
                                </div>
                                <div class="p-3 d-flex flex-column flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1 text-xs text-muted">
                                        <span class="text-truncate" style="max-width: 140px;" title="<?= e($res['category_name']) ?>">
                                            <i class="bi bi-tag me-1"></i><?= e($res['category_name']) ?>
                                        </span>
                                        <span><?= condition_badge($res['condition_status']) ?></span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($res['title']) ?>">
                                        <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $res['id'] ?>" class="text-decoration-none text-dark">
                                            <?= e($res['title']) ?>
                                        </a>
                                    </h6>
                                    <p class="text-muted text-xs mb-3 text-truncate-2" style="min-height: 34px;">
                                        <?= e(substr($res['description'], 0, 95)) ?>...
                                    </p>

                                    <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                                        <div class="text-xs text-muted">
                                            <span class="d-block fw-semibold text-dark text-truncate" style="max-width: 120px;"><?= e($res['owner_name']) ?></span>
                                            <span><?= e($res['department_code'] ?? 'Campus') ?></span>
                                        </div>
                                        <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5" aria-label="Catalog pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link rounded-pill px-3 me-2" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                    <a class="page-link rounded-circle mx-1 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link rounded-pill px-3 ms-2" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
