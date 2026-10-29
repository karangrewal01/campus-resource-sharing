<?php
/**
 * Campus Resource Sharing Platform - User Authentication (Login)
 */

require_once __DIR__ . '/config/app.php';

// If already logged in, redirect to respective dashboard
if (is_logged_in()) {
    $role = $_SESSION['user_role'] ?? 'student';
    if ($role === 'admin') redirect('admin/dashboard.php');
    if ($role === 'faculty') redirect('faculty/dashboard.php');
    redirect('student/dashboard.php');
}

$error = '';
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = trim($_POST['redirect'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your college email and password.';
    } elseif (!check_login_rate_limit($email)) {
        $error = 'Too many failed login attempts. For security reasons, please wait 15 minutes before trying again.';
    } else {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, d.department_name, d.department_code 
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['account_status'] !== 'active') {
                $error = 'Your campus account is currently suspended or pending verification. Please contact the administrator.';
            } else {
                // Successful authentication
                login_user($user);

                set_flash('success', "Welcome back, {$user['full_name']}!");

                if (!empty($redirect) && strpos($redirect, 'http') === false) {
                    redirect(ltrim($redirect, '/'));
                } else {
                    if ($user['role'] === 'admin') {
                        redirect('admin/dashboard.php');
                    } elseif ($user['role'] === 'faculty') {
                        redirect('faculty/dashboard.php');
                    } else {
                        redirect('student/dashboard.php');
                    }
                }
            }
        } else {
            record_login_failure($email);
            $error = 'Invalid email address or password. Please verify your credentials.';
        }
    }
}

$pageTitle = "Sign In - Campus Resource Sharing";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="container py-5 my-auto">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card border-0 shadow-lg rounded-4 p-4 p-sm-5 bg-white">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mb-3" style="width: 58px; height: 58px;">
                        <i class="bi bi-shield-lock-fill fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Sign In</h3>
                    <p class="text-muted small">Access your campus resource sharing dashboard</p>
                </div>

                <?= display_flash_alerts() ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center rounded-3 mb-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5 flex-shrink-0"></i>
                        <div class="small"><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/login.php" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-secondary">College Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="e.g. rahul.sharma@student.campus.edu" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label small fw-semibold text-secondary mb-0">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-semibold shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Account
                        </button>
                    </div>

                    <div class="text-center">
                        <p class="small text-muted mb-0">Don't have a campus account? <a href="<?= APP_URL ?>/register.php" class="text-primary fw-semibold text-decoration-none">Register here</a></p>
                    </div>
                </form>

                <!-- Sample Demo Credentials Box for College Evaluators & Viva -->
                <div class="mt-4 pt-3 border-top">
                    <span class="text-xs text-uppercase fw-bold text-muted d-block mb-2 text-center">Quick Demo Credentials (BCA Viva)</span>
                    <div class="bg-light p-2 rounded-3 text-xs d-flex flex-column gap-1">
                        <div><strong>Admin:</strong> <code>admin@campus.edu</code> &bull; <code>Admin@12345</code></div>
                        <div><strong>Faculty:</strong> <code>prof.sharma@campus.edu</code> &bull; <code>Faculty@12345</code></div>
                        <div><strong>Student:</strong> <code>rahul.sharma@student.campus.edu</code> &bull; <code>Student@12345</code></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
