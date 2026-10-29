<?php
/**
 * Campus Resource Sharing Platform - Resource Moderation & Reports Management
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/ResourceService.php';

require_role('admin');

$currentUser = current_user();
$pdo = Database::getConnection();
$resourceService = new ResourceService();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';
    $resId = (int)($_POST['resource_id'] ?? 0);
    $reportId = (int)($_POST['report_id'] ?? 0);

    if ($action === 'set_moderation') {
        $status = in_array($_POST['moderation_status'] ?? '', ['approved', 'rejected', 'pending_approval']) ? $_POST['moderation_status'] : 'approved';
        $pdo->prepare("UPDATE resources SET moderation_status = ?, updated_at = NOW() WHERE id = ?")->execute([$status, $resId]);
        log_audit($currentUser['id'], 'RESOURCE_MODERATION', 'resources', $resId, "Moderation status changed to {$status}");
        set_flash('success', "Resource moderation updated to '{$status}'.");
    } elseif ($action === 'take_down') {
        $pdo->prepare("UPDATE resources SET availability_status = 'under_review', moderation_status = 'rejected', updated_at = NOW() WHERE id = ?")->execute([$resId]);
        log_audit($currentUser['id'], 'RESOURCE_TAKEDOWN', 'resources', $resId, "Listing taken down by administrator");
        set_flash('info', 'Listing taken down from the public catalog.');
    } elseif ($action === 'resolve_report') {
        $notes = trim($_POST['resolution_notes'] ?? 'Reviewed and resolved by administrator.');
        $repStatus = $_POST['report_status'] ?? 'resolved';
        $pdo->prepare("UPDATE reports SET status = ?, resolved_by = ?, resolution_notes = ?, resolved_at = NOW() WHERE id = ?")->execute([$repStatus, $currentUser['id'], $notes, $reportId]);
        log_audit($currentUser['id'], 'REPORT_RESOLVED', 'reports', $reportId, "Report marked as {$repStatus}");
        set_flash('success', 'Report status successfully updated.');
    }

    redirect('admin/resources.php');
}

// Fetch all resources for moderation
$resources = $pdo->query("
    SELECT r.*, c.category_name, u.full_name as owner_name, u.institution_id as owner_inst_id
    FROM resources r
    JOIN categories c ON r.category_id = c.id
    JOIN users u ON r.owner_id = u.id
    ORDER BY r.created_at DESC
")->fetchAll();

// Fetch reports
$reports = $pdo->query("
    SELECT rep.*, r.title as resource_title, r.owner_id as resource_owner_id,
           reporter.full_name as reporter_name, resolver.full_name as resolver_name
    FROM reports rep
    JOIN resources r ON rep.resource_id = r.id
    JOIN users reporter ON rep.reporter_id = reporter.id
    LEFT JOIN users resolver ON rep.resolved_by = resolver.id
    ORDER BY rep.created_at DESC
")->fetchAll();

$activeTab = $_GET['tab'] ?? 'resources';

$pageTitle = "Resource Moderation & Reports";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h2 class="fw-bold text-dark mb-1">Catalog Moderation & Reports</h2>
                <p class="text-muted mb-0">Review listed academic items, take down inappropriate submissions, and resolve student complaints.</p>
            </div>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>

        <?= display_flash_alerts() ?>

        <ul class="nav nav-pills mb-4" id="modTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link <?= ($activeTab === 'resources') ? 'active' : '' ?> rounded-pill px-4" id="resources-tab" data-bs-toggle="pill" data-bs-target="#tab-resources" type="button" role="tab">
                    <i class="bi bi-box-seam me-1"></i> All Catalog Resources (<?= count($resources) ?>)
                </button>
            </li>
            <li class="nav-item ms-2">
                <button class="nav-link <?= ($activeTab === 'reports') ? 'active' : '' ?> rounded-pill px-4" id="reports-tab" data-bs-toggle="pill" data-bs-target="#tab-reports" type="button" role="tab">
                    <i class="bi bi-flag-fill me-1"></i> Flagged Reports (<?= count($reports) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="modTabContent">
            <!-- Tab 1: All Resources -->
            <div class="tab-pane fade <?= ($activeTab === 'resources') ? 'show active' : '' ?>" id="tab-resources" role="tabpanel">
                <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Resource Title</th>
                                    <th>Owner</th>
                                    <th>Category & Method</th>
                                    <th>Stock</th>
                                    <th>Moderation Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resources as $res): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $res['id'] ?>" class="fw-bold text-dark text-decoration-none" target="_blank">
                                                <?= e($res['title']) ?> <i class="bi bi-box-arrow-up-right text-xs text-muted"></i>
                                            </a>
                                            <div class="text-xs text-muted">Condition: <?= e($res['condition_status']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($res['owner_name']) ?></div>
                                            <div class="text-xs text-muted"><?= e($res['owner_inst_id']) ?></div>
                                        </td>
                                        <td>
                                            <span class="text-xs d-block"><?= e($res['category_name']) ?></span>
                                            <?= method_badge($res['sharing_method']) ?>
                                        </td>
                                        <td><?= $res['available_quantity'] ?> / <?= $res['total_quantity'] ?></td>
                                        <td>
                                            <?php if ($res['moderation_status'] === 'approved'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Approved</span>
                                            <?php elseif ($res['moderation_status'] === 'pending_approval'): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Pending Review</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Rejected / Taken Down</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($res['moderation_status'] !== 'approved'): ?>
                                                    <form method="POST" action="<?= APP_URL ?>/admin/resources.php" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="set_moderation">
                                                        <input type="hidden" name="resource_id" value="<?= $res['id'] ?>">
                                                        <input type="hidden" name="moderation_status" value="approved">
                                                        <button type="submit" class="btn btn-outline-success" title="Approve listing">Approve</button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($res['moderation_status'] === 'approved'): ?>
                                                    <form method="POST" action="<?= APP_URL ?>/admin/resources.php" class="d-inline" data-confirm="Take down '<?= e($res['title']) ?>' from public display?">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="take_down">
                                                        <input type="hidden" name="resource_id" value="<?= $res['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Take down listing">Take Down</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Flagged Reports -->
            <div class="tab-pane fade <?= ($activeTab === 'reports') ? 'show active' : '' ?>" id="tab-reports" role="tabpanel">
                <?php if (empty($reports)): ?>
                    <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center my-4">
                        <i class="bi bi-shield-check text-success fs-1"></i>
                        <h5 class="fw-bold text-dark mt-3">No flagged reports</h5>
                        <p class="text-muted small">All campus resource listings are compliant with guidelines.</p>
                    </div>
                <?php else: ?>
                    <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-custom align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Reported Resource</th>
                                        <th>Reporter</th>
                                        <th>Violation Reason</th>
                                        <th>Details</th>
                                        <th>Status</th>
                                        <th class="text-end">Resolution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $rep): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $rep['resource_id'] ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                                                    <?= e($rep['resource_title']) ?> <i class="bi bi-box-arrow-up-right text-xs"></i>
                                                </a>
                                            </td>
                                            <td><span class="small"><?= e($rep['reporter_name']) ?></span></td>
                                            <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><?= e($rep['report_reason']) ?></span></td>
                                            <td><span class="small text-muted d-inline-block text-truncate" style="max-width: 200px;" title="<?= e($rep['description']) ?>"><?= e($rep['description']) ?></span></td>
                                            <td><?= status_badge($rep['status']) ?></td>
                                            <td class="text-end">
                                                <?php if ($rep['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#resolveModal<?= $rep['id'] ?>">
                                                        Resolve
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-xs text-muted">Resolved by <?= e($rep['resolver_name'] ?? 'Admin') ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Resolve Modal -->
                                        <div class="modal fade" id="resolveModal<?= $rep['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Resolve Flagged Report</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST" action="<?= APP_URL ?>/admin/resources.php">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="resolve_report">
                                                        <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">

                                                        <div class="modal-body text-start">
                                                            <p class="small text-muted mb-3">Report for: <strong><?= e($rep['resource_title']) ?></strong></p>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold text-secondary">Decision Status</label>
                                                                <select class="form-select" name="report_status">
                                                                    <option value="resolved">Resolved (Violation addressed)</option>
                                                                    <option value="dismissed">Dismissed (No violation found)</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold text-secondary">Resolution Notes</label>
                                                                <textarea class="form-control" name="resolution_notes" rows="3" placeholder="Action taken or justification..." required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Submit Resolution</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
