<?php
/**
 * Campus Resource Sharing Platform
 * Authentication & Role-Based Authorization
 */

declare(strict_types=1);

/**
 * Check if the user is authenticated
 */
function is_logged_in(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Return current logged-in user array from session or fresh from database
 */
function current_user(bool $refresh = false): ?array {
    if (!is_logged_in()) {
        return null;
    }

    static $cachedUser = null;
    if ($cachedUser !== null && !$refresh) {
        return $cachedUser;
    }

    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, d.department_name, d.department_code 
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user && $user['account_status'] === 'active') {
            $cachedUser = $user;
            // Update session cache
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            return $user;
        } else {
            // User suspended or deleted
            logout_user();
            return null;
        }
    } catch (Exception $e) {
        error_log("Failed to fetch current user: " . $e->getMessage());
        return null;
    }
}

/**
 * Require authentication or redirect to login
 */
function require_auth(): void {
    if (!is_logged_in()) {
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $redirectUrl = APP_URL . '/login.php';
        if (!empty($currentUri)) {
            $redirectUrl .= '?redirect=' . urlencode($currentUri);
        }
        set_flash('info', 'Please sign in to access this page.');
        header("Location: {$redirectUrl}");
        exit;
    }

    // Refresh user status and check active account
    $user = current_user();
    if (!$user) {
        set_flash('error', 'Your session has expired or your account is inactive.');
        redirect('login.php');
    }
}

/**
 * Require specific role(s)
 */
function require_role(array|string $roles): void {
    require_auth();

    $roles = is_array($roles) ? $roles : [$roles];
    $userRole = $_SESSION['user_role'] ?? '';

    if (!in_array($userRole, $roles, true)) {
        http_response_code(403);
        // Show structured 403 page
        include_once BASE_PATH . '/includes/header.php';
        echo '<div class="container py-5 text-center">';
        echo '  <div class="row justify-content-center">';
        echo '    <div class="col-md-6">';
        echo '      <div class="card shadow-sm border-0 p-5 rounded-4">';
        echo '        <i class="bi bi-shield-lock text-danger" style="font-size: 4rem;"></i>';
        echo '        <h2 class="mt-3 fw-bold text-dark">Access Denied (403)</h2>';
        echo '        <p class="text-muted mt-2">You do not have permission to view or manage this administrative resource.</p>';
        echo '        <div class="mt-4"><a href="' . APP_URL . '/index.php" class="btn btn-primary px-4 rounded-pill">Return to Homepage</a></div>';
        echo '      </div>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
        include_once BASE_PATH . '/includes/footer.php';
        exit;
    }
}

/**
 * Log in a user and regenerate session ID securely
 */
function login_user(array $user): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['institution_id'] = $user['institution_id'];
    $_SESSION['department_id'] = $user['department_id'] ?? null;
    $_SESSION['last_activity'] = time();

    // Reset rate limiter on successful login
    reset_login_rate_limit($user['email']);

    // Log login action
    log_audit((int)$user['id'], 'USER_LOGIN', 'users', (int)$user['id'], 'User logged in successfully.');
}

/**
 * Log out user safely
 */
function logout_user(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        log_audit((int)$userId, 'USER_LOGOUT', 'users', (int)$userId, 'User logged out.');
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Rate Limiter for Login (Max 5 attempts in 15 minutes)
 */
function check_login_rate_limit(string $email): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = 'rate_login_' . md5(strtolower(trim($email)));
    $attempts = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time()];

    if (time() - $attempts['first_attempt'] > 900) {
        // Reset window after 15 minutes
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        return true;
    }

    return $attempts['count'] < 5;
}

function record_login_failure(string $email): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = 'rate_login_' . md5(strtolower(trim($email)));
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
    } else {
        $_SESSION[$key]['count']++;
    }
}

function reset_login_rate_limit(string $email): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $key = 'rate_login_' . md5(strtolower(trim($email)));
    unset($_SESSION[$key]);
}
