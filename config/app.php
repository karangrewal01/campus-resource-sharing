<?php
/**
 * Campus Resource Sharing Platform
 * Application Configuration & Initialization
 */

declare(strict_types=1);

// Prevent error exposure in production, display in local dev
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// Define Base Paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads');

// Dynamically detect base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Detect root directory for campus-resource-sharing
if (strpos($scriptDir, '/campus-resource-sharing') !== false) {
    $baseSub = substr($scriptDir, 0, strpos($scriptDir, '/campus-resource-sharing') + strlen('/campus-resource-sharing'));
} else {
    $baseSub = '/campus-resource-sharing';
}

define('APP_URL', rtrim($protocol . $host . $baseSub, '/'));
define('UPLOADS_URL', APP_URL . '/uploads');

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    // Configure session cookie parameters
    $cookieParams = [
        'lifetime' => 0, // Until browser closes
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}

// Require Database & Common Files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/csrf.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/auth.php';

// Helper to get system settings from the database
function get_setting(string $key, string $default = ''): string {
    static $settingsCache = null;
    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch()) {
                $settingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            error_log("Failed to load settings: " . $e->getMessage());
        }
    }
    return $settingsCache[$key] ?? $default;
}

define('APP_NAME', get_setting('institution_short_name', 'Campus Resource Sharing'));
define('INSTITUTION_NAME', get_setting('institution_name', 'Apex Institute of Technology & Management'));
