<?php
/**
 * Campus Resource Sharing Platform
 * Cross-Site Request Forgery (CSRF) Protection
 */

declare(strict_types=1);

/**
 * Generate or retrieve the current session CSRF token
 */
function get_csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden HTML input containing the CSRF token
 */
function csrf_field(): string {
    $token = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate a submitted CSRF token
 */
function verify_csrf_token(?string $token = null): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate CSRF token or terminate request with 403 Forbidden
 */
function require_csrf_token(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) {
            http_response_code(403);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid or expired security token (CSRF). Please refresh and try again.']);
                exit;
            }
            die("Security verification failed (Invalid CSRF Token). Please go back, refresh the page, and try again.");
        }
    }
}
