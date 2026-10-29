<?php
/**
 * Campus Resource Sharing Platform - Shared Footer Component
 */
?>
<footer class="bg-white border-top mt-auto py-5 text-secondary">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-mortarboard-fill text-primary fs-3"></i>
                    <span class="fs-5 fw-bold text-dark"><?= e(APP_NAME) ?></span>
                </div>
                <p class="text-muted small mb-3">
                    A centralized, trusted peer-to-peer sharing network built for students and faculty of <?= e(INSTITUTION_NAME) ?>. Reduce educational expenses, minimize wastage, and build a collaborative campus community.
                </p>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-recycle me-1"></i>Zero Wastage</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-shield-check me-1"></i>Verified Campus Accounts</span>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h6 class="text-uppercase text-dark fw-bold text-xs mb-3">Quick Navigation</h6>
                <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                    <li><a href="<?= APP_URL ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
                    <li><a href="<?= APP_URL ?>/student/browse_resources.php" class="text-decoration-none text-muted">Browse Resources</a></li>
                    <li><a href="<?= APP_URL ?>/student/donations.php" class="text-decoration-none text-muted">Free Donations</a></li>
                    <li><a href="<?= APP_URL ?>/student/exchanges.php" class="text-decoration-none text-muted">Item Exchanges</a></li>
                    <li><a href="<?= APP_URL ?>/login.php" class="text-decoration-none text-muted">Sign In</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-uppercase text-dark fw-bold text-xs mb-3">Sharing Categories</h6>
                <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                    <li><a href="<?= APP_URL ?>/student/browse_resources.php?category_id=1" class="text-decoration-none text-muted">Textbooks & References</a></li>
                    <li><a href="<?= APP_URL ?>/student/browse_resources.php?category_id=2" class="text-decoration-none text-muted">Lecture Notes & Papers</a></li>
                    <li><a href="<?= APP_URL ?>/student/browse_resources.php?category_id=3" class="text-decoration-none text-muted">Scientific Calculators</a></li>
                    <li><a href="<?= APP_URL ?>/student/browse_resources.php?category_id=4" class="text-decoration-none text-muted">Electronics & Sensors</a></li>
                    <li><a href="<?= APP_URL ?>/student/browse_resources.php?category_id=5" class="text-decoration-none text-muted">Lab Coats & Equipment</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-uppercase text-dark fw-bold text-xs mb-3">Campus Help Desk</h6>
                <ul class="list-unstyled small mb-0 d-flex flex-column gap-2 text-muted">
                    <li><i class="bi bi-geo-alt me-2 text-primary"></i><?= e(get_setting('campus_help_desk', 'Academic Block A, Room 104')) ?></li>
                    <li><i class="bi bi-envelope me-2 text-primary"></i><?= e(get_setting('campus_contact_email', 'resources@apex.edu')) ?></li>
                    <li><i class="bi bi-clock me-2 text-primary"></i>Mon - Fri, 9:00 AM - 5:00 PM</li>
                    <li class="mt-2 text-xs text-muted">A BCA Major Project Demonstration by Final Year Students.</li>
                </ul>
            </div>
        </div>

        <hr class="my-4 text-muted opacity-25">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
            <p class="mb-0">&copy; <?= date('Y') ?> <?= e(INSTITUTION_NAME) ?>. All rights reserved.</p>
            <div class="d-flex gap-3 mt-2 mt-md-0">
                <span class="text-muted">Core PHP 8.2 &bull; MySQL &bull; Bootstrap 5</span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

<!-- Chart.js for Dashboards -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Main Client Script -->
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
