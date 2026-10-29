<?php
/**
 * Campus Resource Sharing Platform - Faculty Shared Resources
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/ResourceService.php';

require_role(['faculty', 'admin']);

$user = current_user();
$resourceService = new ResourceService();
$myResources = $resourceService->getByOwner((int)$user['id']);

$pageTitle = "Faculty Shared Materials";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Faculty Academic Resources</h2>
                <p class="text-muted mb-0">Textbooks, reference handbooks, and laboratory components you have made available for student borrowing.</p>
            </div>
            <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Academic Material
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <?php if (empty($myResources)): ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                <i class="bi bi-mortarboard text-muted" style="font-size: 3.5rem;"></i>
                <h5 class="fw-bold text-dark mt-3">You haven't listed any academic resources</h5>
                <p class="text-muted small mx-auto" style="max-width: 480px;">
                    Make syllabus reference textbooks, past question bank solutions, or laboratory apparatus available to your department students.
                </p>
                <div>
                    <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-primary rounded-pill px-4">Share Academic Material</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Resource</th>
                                <th>Category</th>
                                <th>Sharing Method</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myResources as $res): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= get_resource_image_url($res['image_path'], (int)$res['category_id']) ?>" alt="<?= e($res['title']) ?>" class="rounded-3 border" style="width: 48px; height: 48px; object-fit: cover;">
                                            <div>
                                                <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $res['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?= e($res['title']) ?>
                                                </a>
                                                <div class="text-xs text-muted">Course Tag: <?= e($res['course_semester_tag'] ?: 'All Semesters') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="text-xs"><?= e($res['category_name']) ?></span></td>
                                    <td><?= method_badge($res['sharing_method']) ?></td>
                                    <td><?= $res['available_quantity'] ?> / <?= $res['total_quantity'] ?></td>
                                    <td><?= status_badge($res['availability_status']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= APP_URL ?>/student/edit_resource.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </a>
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
