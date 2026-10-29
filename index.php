<?php
/**
 * Campus Resource Sharing Platform - Public Landing Page
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/services/ResourceService.php';

$resourceService = new ResourceService();
$pdo = Database::getConnection();

// Fetch live statistics
$totalResources = (int)$pdo->query("SELECT COUNT(*) FROM resources WHERE moderation_status = 'approved' AND availability_status = 'available'")->fetchColumn();
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE account_status = 'active'")->fetchColumn();
$totalTransactions = (int)$pdo->query("SELECT COUNT(*) FROM borrowing_transactions")->fetchColumn();
$totalDonations = (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'completed'")->fetchColumn();

// Fetch recently added approved resources
$recentResources = $resourceService->getResources([], 6, 0);

// Fetch categories with active resource counts
$catStmt = $pdo->query("
    SELECT c.*, COUNT(r.id) as active_count
    FROM categories c
    LEFT JOIN resources r ON c.id = r.category_id AND r.moderation_status = 'approved' AND r.availability_status = 'available'
    WHERE c.status = 'active'
    GROUP BY c.id
    ORDER BY active_count DESC, c.category_name ASC
");
$categories = $catStmt->fetchAll();

// Fetch public announcements
$announcements = $pdo->query("
    SELECT a.*, u.full_name as author_name
    FROM announcements a
    JOIN users u ON a.created_by = u.id
    WHERE a.status = 'published' AND a.audience IN ('all', 'students')
    ORDER BY a.published_at DESC
    LIMIT 3
")->fetchAll();

$pageTitle = "Home - Campus Resource Sharing Platform";
require_once BASE_PATH . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-gradient py-5 py-lg-6 shadow-sm">
    <div class="container py-4 position-relative" style="z-index: 2;">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-white text-primary fw-semibold px-3 py-2 rounded-pill mb-3 shadow-sm">
                    <i class="bi bi-patch-check-fill me-1"></i> Official Campus Utility &bull; <?= e(INSTITUTION_NAME) ?>
                </span>
                <h1 class="display-4 fw-bold lh-sm mb-3">
                    Borrow, Lend & Share Academic Resources Seamlessly
                </h1>
                <p class="lead opacity-90 mb-4 fs-5" style="max-width: 620px;">
                    Stop overspending on semester textbooks, calculators, and lab equipment. Discover materials shared by peers and faculty right inside your campus.
                </p>

                <!-- Search Box inside Hero -->
                <div class="card p-2 rounded-4 shadow border-0 mb-4 bg-white">
                    <form action="<?= APP_URL ?>/student/browse_resources.php" method="GET" class="d-flex flex-column flex-sm-row gap-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-search fs-5"></i></span>
                            <input type="text" name="search" class="form-control border-0 shadow-none ps-0" placeholder="Search textbooks, notes, Arduino, calculators..." aria-label="Search resources">
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold flex-shrink-0">
                            Find Items
                        </button>
                    </form>
                </div>

                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= APP_URL ?>/student/browse_resources.php" class="btn btn-light rounded-pill px-4 py-2 fw-semibold shadow-sm text-primary">
                        <i class="bi bi-grid-fill me-2"></i>Browse Catalog
                    </a>
                    <?php if (!is_logged_in()): ?>
                        <a href="<?= APP_URL ?>/register.php" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">
                            <i class="bi bi-person-plus me-2"></i>Create Free Account
                        </a>
                    <?php else: ?>
                        <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">
                            <i class="bi bi-plus-circle me-2"></i>List an Unused Item
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5 text-center">
                <div class="glass-panel p-4 rounded-4 shadow-lg text-dark text-start">
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
                        <span class="fw-bold text-uppercase text-xs tracking-wider text-muted">Campus Sharing Impact</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-circle-fill text-xs me-1"></i>Live Data</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <h3 class="fw-bold text-primary mb-1"><?= $totalResources ?></h3>
                                <p class="text-xs text-muted mb-0 fw-medium">Available Resources</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <h3 class="fw-bold text-indigo mb-1" style="color: #4f46e5;"><?= $totalUsers ?></h3>
                                <p class="text-xs text-muted mb-0 fw-medium">Verified Campus Members</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <h3 class="fw-bold text-success mb-1"><?= $totalTransactions ?></h3>
                                <p class="text-xs text-muted mb-0 fw-medium">Completed Borrows</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <h3 class="fw-bold text-warning-emphasis mb-1"><?= $totalDonations ?></h3>
                                <p class="text-xs text-muted mb-0 fw-medium">Items Donated Free</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-shield-check text-primary fs-3"></i>
                            <div>
                                <h6 class="mb-0 fw-semibold text-sm">Accountability & Security</h6>
                                <small class="text-muted text-xs">All exchanges are verified with college IDs & department records.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Campus Announcements (if any) -->
<?php if (!empty($announcements)): ?>
<section class="py-4 bg-primary-subtle border-bottom">
    <div class="container">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-megaphone-fill text-primary"></i>
            <span class="fw-bold text-uppercase text-xs text-primary">Campus Announcements</span>
        </div>
        <div class="row g-3">
            <?php foreach ($announcements as $ann): ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-semibold text-dark mb-0 text-sm"><?= e($ann['title']) ?></h6>
                            <small class="text-muted text-xs flex-shrink-0 ms-2"><?= format_date($ann['published_at']) ?></small>
                        </div>
                        <p class="text-muted text-xs mb-0"><?= e(substr($ann['content'], 0, 110)) ?>...</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Resource Categories Grid -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-primary fw-bold text-uppercase text-xs tracking-wider">Explore by Category</span>
            <h2 class="fw-bold text-dark mt-1">What are you looking for today?</h2>
            <p class="text-muted mx-auto" style="max-width: 580px;">Everything from prescribed semester textbooks to scientific calculators, lab coats, and electronic prototyping components.</p>
        </div>

        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <a href="<?= APP_URL ?>/student/browse_resources.php?category_id=<?= $cat['id'] ?>" class="card h-100 p-3 rounded-4 border text-decoration-none text-dark stat-card">
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi <?= e($cat['icon_class'] ?? 'bi-box-seam') ?>"></i>
                        </div>
                        <div>
                            <h6 class="fw-semibold mb-1"><?= e($cat['category_name']) ?></h6>
                            <span class="text-xs text-muted"><?= $cat['active_count'] ?> Available Items</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Recently Added Items -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-2">
            <div>
                <span class="text-primary fw-bold text-uppercase text-xs tracking-wider">Fresh Listings</span>
                <h2 class="fw-bold text-dark mt-1">Recently Added Resources</h2>
                <p class="text-muted mb-0">Discover educational materials listed by students and professors this week.</p>
            </div>
            <a href="<?= APP_URL ?>/student/browse_resources.php" class="btn btn-outline-primary rounded-pill px-4">
                View All Catalog <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if (empty($recentResources)): ?>
            <div class="card p-5 text-center border-0 rounded-4 shadow-sm bg-white">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-dark fw-bold">No resources listed yet</h5>
                <p class="text-muted">Be the first student or faculty member to list an educational resource!</p>
                <div>
                    <a href="<?= APP_URL ?>/student/add_resource.php" class="btn btn-primary rounded-pill px-4">List a Resource</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($recentResources as $res): ?>
                    <div class="col-xl-4 col-md-6">
                        <div class="resource-card shadow-sm">
                            <div class="resource-card-img-wrap">
                                <img src="<?= get_resource_image_url($res['image_path'], (int)$res['category_id']) ?>" alt="<?= e($res['title']) ?>" loading="lazy">
                                <div class="position-absolute top-0 end-0 m-2">
                                    <?= method_badge($res['sharing_method']) ?>
                                </div>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1 text-xs text-muted">
                                    <span><i class="bi bi-tag me-1"></i><?= e($res['category_name']) ?></span>
                                    <span><?= condition_badge($res['condition_status']) ?></span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($res['title']) ?>">
                                    <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $res['id'] ?>" class="text-decoration-none text-dark">
                                        <?= e($res['title']) ?>
                                    </a>
                                </h6>
                                <p class="text-muted text-xs mb-3 text-truncate-2" style="min-height: 34px;">
                                    <?= e(substr($res['description'], 0, 100)) ?>...
                                </p>

                                <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-xs fw-bold" style="width: 28px; height: 28px;">
                                            <?= strtoupper(substr($res['owner_name'], 0, 1)) ?>
                                        </div>
                                        <span class="text-xs text-muted text-truncate" style="max-width: 130px;"><?= e($res['owner_name']) ?></span>
                                    </div>
                                    <a href="<?= APP_URL ?>/student/resource_details.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="text-primary fw-bold text-uppercase text-xs tracking-wider">Step-by-Step Guide</span>
            <h2 class="fw-bold text-dark mt-1">How Campus Resource Sharing Works</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">A transparent, circular lifecycle that ensures safety, accountability, and prompt returns.</p>
        </div>

        <div class="row g-4 text-center">
            <div class="col-md-4 col-sm-6">
                <div class="p-4 rounded-4 bg-light h-100 border border-light-subtle">
                    <div class="stat-icon bg-primary text-white mx-auto mb-3 shadow-sm">1</div>
                    <h5 class="fw-bold mb-2">Register with College ID</h5>
                    <p class="text-muted small mb-0">Sign up using your college email address and student/faculty ID for campus verification.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="p-4 rounded-4 bg-light h-100 border border-light-subtle">
                    <div class="stat-icon bg-primary text-white mx-auto mb-3 shadow-sm">2</div>
                    <h5 class="fw-bold mb-2">Discover Materials</h5>
                    <p class="text-muted small mb-0">Search textbooks, notes, lab equipment, or project tools by course, semester, or department.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="p-4 rounded-4 bg-light h-100 border border-light-subtle">
                    <div class="stat-icon bg-primary text-white mx-auto mb-3 shadow-sm">3</div>
                    <h5 class="fw-bold mb-2">Submit a Request</h5>
                    <p class="text-muted small mb-0">Specify the needed borrowing duration, pickup date, and purpose of request directly on the listing.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="p-4 rounded-4 bg-light h-100 border border-light-subtle">
                    <div class="stat-icon bg-primary text-white mx-auto mb-3 shadow-sm">4</div>
                    <h5 class="fw-bold mb-2">Owner Approval</h5>
                    <p class="text-muted small mb-0">The listing owner reviews the request and coordinates handover instructions through in-app chat.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="p-4 rounded-4 bg-light h-100 border border-light-subtle">
                    <div class="stat-icon bg-primary text-white mx-auto mb-3 shadow-sm">5</div>
                    <h5 class="fw-bold mb-2">Campus Handover</h5>
                    <p class="text-muted small mb-0">Meet at an agreed safe campus spot (Library, Department corridor, or Cafeteria) and confirm handover.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="p-4 rounded-4 bg-light h-100 border border-light-subtle">
                    <div class="stat-icon bg-primary text-white mx-auto mb-3 shadow-sm">6</div>
                    <h5 class="fw-bold mb-2">Timely Return or Donation</h5>
                    <p class="text-muted small mb-0">Return the resource on or before the due date, or keep permanently if it was a free donation.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Frequently Asked Questions -->
<section class="py-5 bg-light">
    <div class="container py-2" style="max-width: 820px;">
        <div class="text-center mb-5">
            <span class="text-primary fw-bold text-uppercase text-xs tracking-wider">Help & Answers</span>
            <h2 class="fw-bold text-dark mt-1">Frequently Asked Questions</h2>
        </div>

        <div class="accordion shadow-sm rounded-4 overflow-hidden" id="faqAccordion">
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header">
                    <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        Who is eligible to participate in Campus Resource Sharing?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted small">
                        All currently enrolled students and active faculty members across all departments with a valid institution ID are eligible. Account registration requires institutional details to maintain safety and accountability.
                    </div>
                </div>
            </div>
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        Is there any cost or deposit involved in borrowing?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted small">
                        No. The Campus Resource Sharing Platform operates as an educational and sustainability utility. All borrowing, donations, and exchanges are free of charge between students and teachers.
                    </div>
                </div>
            </div>
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        What happens if an item is not returned on time?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted small">
                        Items that pass their return deadline are automatically flagged as <strong>Overdue</strong>. Borrowers receive automated alerts, and their account borrowing limits are restricted until the item is confirmed returned by the owner. Repeated offenses are reported to the platform administrator.
                    </div>
                </div>
            </div>
            <div class="accordion-item border-0">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                        How are item exchanges coordinated?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted small">
                        When an item is listed for exchange, other students can submit an exchange proposal specifying what material or tool they offer in return. Once both parties agree and meet on campus, both confirm the exchange completion.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container py-3">
        <h2 class="fw-bold mb-3">Join Your Campus Sharing Network Today</h2>
        <p class="lead opacity-90 mx-auto mb-4" style="max-width: 600px;">
            Have unused books, lab coats, or electronics sitting in your room? Give them a second life and help a junior or classmate.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?= APP_URL ?>/register.php" class="btn btn-light rounded-pill px-4 py-2 fw-semibold text-primary">Get Started Now</a>
            <a href="<?= APP_URL ?>/student/browse_resources.php" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">Browse Listings</a>
        </div>
    </div>
</section>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
