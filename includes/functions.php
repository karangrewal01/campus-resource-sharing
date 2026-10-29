<?php
/**
 * Campus Resource Sharing Platform
 * Global Helper Functions & Utilities
 */

declare(strict_types=1);

/**
 * Escape HTML output to prevent XSS
 */
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Set a session flash message
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_messages'][$type][] = $message;
}

/**
 * Check if a flash message type exists
 */
function has_flash(?string $type = null): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if ($type === null) {
        return !empty($_SESSION['flash_messages']);
    }
    return !empty($_SESSION['flash_messages'][$type]);
}

/**
 * Retrieve and clear flash messages
 */
function get_flash(?string $type = null): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if ($type === null) {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    $messages = $_SESSION['flash_messages'][$type] ?? [];
    unset($_SESSION['flash_messages'][$type]);
    return $messages;
}

/**
 * Render Bootstrap alert boxes for flash messages
 */
function display_flash_alerts(): string {
    $all = get_flash();
    if (empty($all)) {
        return '';
    }

    $html = '';
    $classMap = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info'
    ];
    $iconMap = [
        'success' => 'bi-check-circle-fill',
        'error'   => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info'    => 'bi-info-circle-fill'
    ];

    foreach ($all as $type => $messages) {
        $alertClass = $classMap[$type] ?? 'alert-secondary';
        $iconClass = $iconMap[$type] ?? 'bi-bell-fill';
        foreach ($messages as $msg) {
            $html .= '<div class="alert ' . $alertClass . ' alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">';
            $html .= '<i class="bi ' . $iconClass . ' me-2 flex-shrink-0 fs-5"></i>';
            $html .= '<div>' . e($msg) . '</div>';
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            $html .= '</div>';
        }
    }

    return $html;
}

/**
 * Safe redirect to a URL or relative path
 */
function redirect(string $path): void {
    if (strpos($path, 'http://') !== 0 && strpos($path, 'https://') !== 0) {
        $url = rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
    } else {
        $url = $path;
    }
    header("Location: {$url}");
    exit;
}

/**
 * Send JSON response and exit
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Format date for friendly display
 */
function format_date(?string $datetime, string $format = 'd M Y'): string {
    if (empty($datetime)) {
        return 'N/A';
    }
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

/**
 * Format datetime for friendly display
 */
function format_datetime(?string $datetime): string {
    return format_date($datetime, 'd M Y, h:i A');
}

/**
 * Render visual badge for sharing methods
 */
function method_badge(string $method): string {
    switch ($method) {
        case 'borrow':
            return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-arrow-left-right me-1"></i>Borrow / Lend</span>';
        case 'donate':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-gift me-1"></i>Donation (Free)</span>';
        case 'exchange':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-arrow-repeat me-1"></i>Exchange</span>';
        case 'temporary_use':
            return '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-clock-history me-1"></i>Short-Term Use</span>';
        default:
            return '<span class="badge bg-light text-dark border">' . e(ucfirst($method)) . '</span>';
    }
}

/**
 * Render visual badge for condition
 */
function condition_badge(string $condition): string {
    switch ($condition) {
        case 'new':
            return '<span class="badge bg-success"><i class="bi bi-stars me-1"></i>Brand New</span>';
        case 'like_new':
            return '<span class="badge bg-teal text-white" style="background-color: #0d9488;"><i class="bi bi-gem me-1"></i>Like New</span>';
        case 'good':
            return '<span class="badge bg-primary"><i class="bi bi-hand-thumbs-up me-1"></i>Good</span>';
        case 'fair':
            return '<span class="badge bg-warning text-dark"><i class="bi bi-check me-1"></i>Fair / Usable</span>';
        case 'poor':
            return '<span class="badge bg-secondary"><i class="bi bi-tools me-1"></i>Worn / For Parts</span>';
        default:
            return '<span class="badge bg-light text-dark">' . e(ucfirst($condition)) . '</span>';
    }
}

/**
 * Render visual badge for request & transaction statuses
 */
function status_badge(string $status): string {
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>';
        case 'approved':
            return '<span class="badge bg-info text-dark"><i class="bi bi-check2-circle me-1"></i>Approved</span>';
        case 'ready_for_collection':
            return '<span class="badge bg-primary text-white"><i class="bi bi-box-arrow-up-right me-1"></i>Ready for Pickup</span>';
        case 'borrowed':
        case 'active':
            return '<span class="badge bg-indigo text-white" style="background-color: #4f46e5;"><i class="bi bi-arrow-left-right me-1"></i>Active / Borrowed</span>';
        case 'return_pending':
            return '<span class="badge bg-purple text-white" style="background-color: #9333ea;"><i class="bi bi-arrow-return-left me-1"></i>Return In Progress</span>';
        case 'returned':
        case 'completed':
            return '<span class="badge bg-success"><i class="bi bi-check-all me-1"></i>Completed / Returned</span>';
        case 'overdue':
            return '<span class="badge bg-danger animate-pulse"><i class="bi bi-alarm-fill me-1"></i>Overdue</span>';
        case 'rejected':
            return '<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Rejected</span>';
        case 'cancelled':
            return '<span class="badge bg-light text-muted border"><i class="bi bi-slash-circle me-1"></i>Cancelled</span>';
        case 'disputed':
            return '<span class="badge bg-danger"><i class="bi bi-shield-exclamation me-1"></i>Disputed</span>';
        case 'available':
            return '<span class="badge bg-success"><i class="bi bi-check2 me-1"></i>Available</span>';
        case 'reserved':
            return '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Reserved</span>';
        case 'unavailable':
            return '<span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i>Unavailable</span>';
        case 'under_review':
            return '<span class="badge bg-dark"><i class="bi bi-eye me-1"></i>Under Review</span>';
        default:
            return '<span class="badge bg-light text-dark border">' . e(ucfirst($status)) . '</span>';
    }
}

/**
 * Category-based fallback placeholder icons / SVGs
 */
function get_resource_image_url(?string $imagePath, int $categoryId = 1): string {
    if (!empty($imagePath)) {
        $fullFsPath = BASE_PATH . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $imagePath), DIRECTORY_SEPARATOR);
        if (file_exists($fullFsPath)) {
            return APP_URL . '/' . ltrim($imagePath, '/');
        }
    }

    // Default neutral placeholder based on category ID
    return APP_URL . '/assets/images/placeholder-cat-' . $categoryId . '.svg';
}

/**
 * Handle secure image upload with MIME validation
 */
function handle_image_upload(array $file, string $subfolder = 'resources'): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file parameter.'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'error' => 'No file uploaded.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'error' => 'Exceeded file size limit (Maximum 5MB).'];
        default:
            return ['success' => false, 'error' => 'Unknown upload error occurred.'];
    }

    $maxBytes = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'error' => 'File size exceeds maximum permitted 5MB limit.'];
    }

    // MIME verification using Fileinfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp'
    ];

    $ext = array_search($mime, $allowedMimes, true);
    if ($ext === false) {
        return ['success' => false, 'error' => 'Invalid image format. Only JPG, PNG, and WebP images are permitted.'];
    }

    // Target directory
    $targetDir = UPLOADS_PATH . DIRECTORY_SEPARATOR . $subfolder;
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create upload destination directory.'];
        }
    }

    // Randomized unique filename
    $filename = sprintf('%s_%s.%s', bin2hex(random_bytes(10)), time(), $ext);
    $destination = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file.'];
    }

    $relativePath = 'uploads/' . $subfolder . '/' . $filename;
    return ['success' => true, 'path' => $relativePath];
}

/**
 * Log action to audit_logs table
 */
function log_audit(?int $actorId, string $action, string $entityType, ?int $entityId = null, ?string $details = null): void {
    try {
        $pdo = Database::getConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (actor_id, action, entity_type, entity_id, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$actorId, $action, $entityType, $entityId, $details, $ip]);
    } catch (Exception $e) {
        error_log("Failed to write audit log: " . $e->getMessage());
    }
}

/**
 * Server-side Overdue Auto-check:
 * Updates active borrowing transactions whose due_at has passed to 'overdue',
 * and updates corresponding requests and creates notifications.
 */
function check_and_update_overdue_transactions(): void {
    static $hasRunThisRequest = false;
    if ($hasRunThisRequest) {
        return;
    }
    $hasRunThisRequest = true;

    try {
        $pdo = Database::getConnection();
        
        // Find transactions that are overdue
        $sql = "
            SELECT bt.id as trans_id, bt.request_id, bt.resource_id, bt.borrower_id, bt.due_at, r.title
            FROM borrowing_transactions bt
            JOIN resources r ON bt.resource_id = r.id
            WHERE bt.transaction_status = 'active'
              AND bt.due_at < NOW()
        ";
        $stmt = $pdo->query($sql);
        $overdues = $stmt->fetchAll();

        if (empty($overdues)) {
            return;
        }

        $updateTrans = $pdo->prepare("UPDATE borrowing_transactions SET transaction_status = 'overdue' WHERE id = ?");
        $updateReq = $pdo->prepare("UPDATE resource_requests SET status = 'overdue' WHERE id = ?");
        $insertNotif = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, notification_type, related_resource_id, related_request_id, created_at)
            VALUES (?, 'Item Overdue Alert', ?, 'overdue', ?, ?, NOW())
        ");

        foreach ($overdues as $row) {
            $updateTrans->execute([$row['trans_id']]);
            $updateReq->execute([$row['request_id']]);

            // Notify borrower
            $msg = "Your borrowed resource '" . $row['title'] . "' was due on " . format_date($row['due_at']) . " and is now marked OVERDUE. Please return it immediately.";
            $insertNotif->execute([$row['borrower_id'], $msg, $row['resource_id'], $row['request_id']]);
        }
    } catch (Exception $e) {
        error_log("Overdue check error: " . $e->getMessage());
    }
}

/**
 * Sanitize strings for CSV to prevent formula injection attacks
 */
function sanitize_for_csv(?string $str): string {
    $str = (string)($str ?? '');
    // If the string begins with =, +, -, @, \t, or \r, prefix with a single quote
    if (preg_match('/^[=\+\-@\t\r]/', $str)) {
        return "'" . $str;
    }
    return $str;
}
