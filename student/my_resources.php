<?php
/**
 * Campus Resource Sharing Platform - Manage My Resource Listings
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/ResourceService.php';

require_auth();

$user = current_user();
$resourceService = new ResourceService();

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_resource') {
    require_csrf_token();
    $resId = (int)($_POST['resource_id'] ?? 0);
    $res = $resourceService->delete($resId, (int)$user['id'], ($user['role'] === 'admin'));

    if ($res['success']) {
        set_flash('success', 'Resource listing was successfully removed.');
    } else {
        set_flash('error', $res['error']);
    }
    redirect('student/my_resources.php');
}

$myResources = $resourceService->getByOwner((int)$user['id']);

$pageTitle = "My Listed Resources";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center pb-4 mb-4 border-bottom gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-1">My Resource Listings</h2>
                <p class="text-muted mb-0">Manage the materials you have contributed to the campus sharing community.</p>
            </div>
            <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add New Resource
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <?php if (empty($myResources)): ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                <i class="bi bi-collection text-muted" style="font-size: 3.5rem;"></i>
                <h5 class="fw-bold text-dark mt-3">You haven't listed any resources yet</h5>
                <p class="text-muted small mx-auto" style="max-width: 480px;">
                    Have textbooks, notes, calculators, or lab coats you aren't currently using? Share them with your classmates!
                </p>
                <div>
                    <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-plus-circle me-1"></i> List Your First Resource
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Method</th>
                                <th>Stock / Availability</th>
                                <th>Pending Requests</th>
                                <th>Active Borrows</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myResources as $res): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= get_resource_image_url($res['image_path'], (int)$res['category_id']) ?>" alt="<?= e($res['title']) ?>" class="rounded-3 border" style="width: 52px; height: 52px; object-fit: cover;">
                                            <div>
                                                <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $res['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?= e($res['title']) ?>
                                                </a>
                                                <div class="text-xs text-muted">Listed <?= format_date($res['created_at']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="text-xs"><?= e($res['category_name']) ?></span></td>
                                    <td><?= method_badge($res['sharing_method']) ?></td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <?= status_badge($res['availability_status']) ?>
                                            <span class="text-xs text-muted"><?= $res['available_quantity'] ?> / <?= $res['total_quantity'] ?> available</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($res['pending_requests_count'] > 0): ?>
                                            <a href="<?= APP_URL ?>/student/lending_requests.php" class="badge bg-warning text-dark text-decoration-none">
                                                <i class="bi bi-bell-fill me-1"></i><?= $res['pending_requests_count'] ?> Pending
                                            </a>
                                        <?php else: ?>
                                            <span class="text-xs text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($res['active_borrows_count'] > 0): ?>
                                            <span class="badge bg-indigo text-white" style="background-color: #6366f1;">
                                                <?= $res['active_borrows_count'] ?> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="text-xs text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $res['id'] ?>" class="btn btn-outline-secondary" title="View details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= APP_URL ?>/student/edit_resource.php?id=<?= $res['id'] ?>" class="btn btn-outline-primary" title="Edit resource">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="<?= APP_URL ?>/student/my_resources.php" class="d-inline" data-confirm="Are you sure you want to delete '<?= e($res['title']) ?>'?">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_resource">
                                                <input type="hidden" name="resource_id" value="<?= $res['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Delete listing">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
