<?php
/**
 * Campus Resource Sharing Platform - User Registration
 */

require_once __DIR__ . '/config/app.php';

if (is_logged_in()) {
    redirect('index.php');
}

$pdo = Database::getConnection();

// Fetch departments for dropdown
$deptStmt = $pdo->query("SELECT id, department_name, department_code FROM departments WHERE status = 'active' ORDER BY department_name ASC");
$departments = $deptStmt->fetchAll();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $role = in_array($_POST['role'] ?? '', ['student', 'faculty']) ? $_POST['role'] : 'student';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $institutionId = strtoupper(trim($_POST['institution_id'] ?? ''));
    $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $phone = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $semester = !empty($_POST['semester']) ? (int)$_POST['semester'] : null;
    $designation = trim($_POST['designation'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validation checks
    if (empty($fullName)) {
        $errors[] = 'Full name is required.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid college email address is required.';
    } else {
        // Check allowed domains if configured
        $allowedDomainsRaw = get_setting('allowed_email_domains', '');
        if (!empty($allowedDomainsRaw)) {
            $allowedDomains = array_map('trim', explode(',', strtolower($allowedDomainsRaw)));
            $emailParts = explode('@', $email);
            $domain = end($emailParts);
            $isDomainAllowed = false;
            foreach ($allowedDomains as $allowed) {
                if ($domain === $allowed || str_ends_with($domain, '.' . $allowed)) {
                    $isDomainAllowed = true;
                    break;
                }
            }
            if (!$isDomainAllowed) {
                $errors[] = "Email domain '@{$domain}' is not authorized. Allowed domains: " . implode(', ', $allowedDomains);
            }
        }
    }

    if (empty($institutionId)) {
        $errors[] = 'Student Roll Number or College Employee ID is required.';
    }

    if (empty($departmentId)) {
        $errors[] = 'Please select your academic department.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Password and confirmation password do not match.';
    }

    // Uniqueness checks in database
    if (empty($errors)) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $errors[] = 'An account with this email address is already registered.';
        }

        $checkIdStmt = $pdo->prepare("SELECT id FROM users WHERE institution_id = ?");
        $checkIdStmt->execute([$institutionId]);
        if ($checkIdStmt->fetch()) {
            $errors[] = 'An account with this Student/Employee ID is already registered.';
        }
    }

    // Insert user if no errors
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $accountStatus = 'active';

            $insertStmt = $pdo->prepare("
                INSERT INTO users (
                    full_name, email, password_hash, institution_id, department_id,
                    role, course, semester, designation, phone, account_status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, NOW()
                )
            ");
            $insertStmt->execute([
                $fullName,
                $email,
                $passwordHash,
                $institutionId,
                $departmentId,
                $role,
                $role === 'student' ? $course : null,
                $role === 'student' ? $semester : null,
                $role === 'faculty' ? $designation : null,
                $phone ?: null,
                $accountStatus
            ]);

            $newUserId = (int)$pdo->lastInsertId();
            log_audit($newUserId, 'USER_REGISTERED', 'users', $newUserId, "New {$role} account registered: {$email}");

            set_flash('success', 'Registration successful! You can now sign in with your credentials.');
            redirect('login.php');
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'Failed to create account due to a server error. Please try again.';
        }
    }
}

$pageTitle = "Register - Campus Resource Sharing";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="container py-5 my-3">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card border-0 shadow-lg rounded-4 p-4 p-sm-5 bg-white">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mb-3" style="width: 58px; height: 58px;">
                        <i class="bi bi-person-plus-fill fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Join Campus Sharing</h3>
                    <p class="text-muted small">Create your student or faculty account at <?= e(INSTITUTION_NAME) ?></p>
                </div>

                <?= display_flash_alerts() ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger rounded-3 mb-4 shadow-sm">
                        <ul class="mb-0 small ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/register.php" class="needs-validation" novalidate id="regForm">
                    <?= csrf_field() ?>

                    <!-- Account Type Selector -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary d-block">Account Type</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="role_student" value="student" <?= (!isset($_POST['role']) || $_POST['role'] === 'student') ? 'checked' : '' ?> onchange="toggleRoleFields()">
                                <label class="btn btn-outline-primary w-100 py-2 rounded-3 text-center" for="role_student">
                                    <i class="bi bi-mortarboard me-1"></i> Student
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="role_faculty" value="faculty" <?= (isset($_POST['role']) && $_POST['role'] === 'faculty') ? 'checked' : '' ?> onchange="toggleRoleFields()">
                                <label class="btn btn-outline-primary w-100 py-2 rounded-3 text-center" for="role_faculty">
                                    <i class="bi bi-briefcase me-1"></i> Faculty Member
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label small fw-semibold text-secondary">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" placeholder="e.g. Rahul Sharma" required>
                        </div>

                        <div class="col-md-6">
                            <label for="institution_id" class="form-label small fw-semibold text-secondary" id="label_inst_id">Roll Number / Student ID</label>
                            <input type="text" class="form-control" id="institution_id" name="institution_id" value="<?= e($_POST['institution_id'] ?? '') ?>" placeholder="e.g. BCA-2024-042" required>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-semibold text-secondary">College Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="username@student.campus.edu" required>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label small fw-semibold text-secondary">Mobile Number (Optional)</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="+91 98765 43210">
                        </div>

                        <div class="col-md-12">
                            <label for="department_id" class="form-label small fw-semibold text-secondary">Academic Department</label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">Select Department...</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>" <?= (isset($_POST['department_id']) && (int)$_POST['department_id'] === (int)$dept['id']) ? 'selected' : '' ?>>
                                        <?= e($dept['department_name']) ?> (<?= e($dept['department_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Student Specific Fields -->
                        <div class="col-md-8 student-field">
                            <label for="course" class="form-label small fw-semibold text-secondary">Course / Program</label>
                            <input type="text" class="form-control" id="course" name="course" value="<?= e($_POST['course'] ?? '') ?>" placeholder="e.g. Bachelor of Computer Applications (BCA)">
                        </div>

                        <div class="col-md-4 student-field">
                            <label for="semester" class="form-label small fw-semibold text-secondary">Semester</label>
                            <select class="form-select" id="semester" name="semester">
                                <option value="">Select...</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= (isset($_POST['semester']) && (int)$_POST['semester'] === $i) ? 'selected' : '' ?>>Sem <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Faculty Specific Field -->
                        <div class="col-md-12 faculty-field d-none">
                            <label for="designation" class="form-label small fw-semibold text-secondary">Faculty Designation</label>
                            <input type="text" class="form-control" id="designation" name="designation" value="<?= e($_POST['designation'] ?? '') ?>" placeholder="e.g. Associate Professor / Head of Department">
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label small fw-semibold text-secondary">Password (Min 6 chars)</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirm" class="form-label small fw-semibold text-secondary">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="6" required>
                        </div>
                    </div>

                    <div class="form-check mt-3 mb-4">
                        <input class="form-check-input" type="checkbox" id="terms" required checked>
                        <label class="form-check-label small text-muted" for="terms">
                            I agree to the campus resource sharing guidelines, responsible care of borrowed items, and honor due dates.
                        </label>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-semibold shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Complete Registration
                        </button>
                    </div>

                    <div class="text-center">
                        <p class="small text-muted mb-0">Already registered? <a href="<?= APP_URL ?>/login.php" class="text-primary fw-semibold text-decoration-none">Sign In</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRoleFields() {
    const isStudent = document.getElementById('role_student').checked;
    const studentFields = document.querySelectorAll('.student-field');
    const facultyFields = document.querySelectorAll('.faculty-field');
    const instIdLabel = document.getElementById('label_inst_id');

    if (isStudent) {
        studentFields.forEach(el => el.classList.remove('d-none'));
        facultyFields.forEach(el => el.classList.add('d-none'));
        instIdLabel.innerText = 'Roll Number / Student ID';
    } else {
        studentFields.forEach(el => el.classList.add('d-none'));
        facultyFields.forEach(el => el.classList.remove('d-none'));
        instIdLabel.innerText = 'Employee / Faculty ID';
    }
}
document.addEventListener('DOMContentLoaded', toggleRoleFields);
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
