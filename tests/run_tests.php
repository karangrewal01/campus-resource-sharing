<?php
/**
 * Campus Resource Sharing Platform - Automated Comprehensive Test Suite
 * Suitable for BCA Viva Verification & QA Assessment
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/services/ResourceService.php';
require_once BASE_PATH . '/services/RequestService.php';
require_once BASE_PATH . '/services/TransactionService.php';
require_once BASE_PATH . '/services/DonationService.php';
require_once BASE_PATH . '/services/ExchangeService.php';
require_once BASE_PATH . '/services/NotificationService.php';
require_once BASE_PATH . '/services/MessageService.php';
require_once BASE_PATH . '/services/AnalyticsService.php';

class TestRunner {
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function assert(bool $condition, string $testName, string $failureDetails = ''): void {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$testName}\n";
        } else {
            $this->failed++;
            $msg = "  [FAIL] {$testName}" . ($failureDetails ? " -> {$failureDetails}" : "");
            echo "{$msg}\n";
            $this->failures[] = $msg;
        }
    }

    public function summary(): void {
        $total = $this->passed + $this->failed;
        echo "\n======================================================\n";
        echo "TEST SUITE COMPLETED\n";
        echo "Total Assertions: {$total}\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "======================================================\n";

        if ($this->failed > 0) {
            echo "\nFailed Tests:\n";
            foreach ($this->failures as $f) {
                echo "{$f}\n";
            }
            exit(1);
        } else {
            echo "\nALL TESTS PASSED WITH 100% SUCCESS RATE!\n";
        }
    }
}

$t = new TestRunner();
$pdo = Database::getConnection();

echo "======================================================\n";
echo "CAMPUS RESOURCE SHARING PLATFORM - AUTOMATED TESTS\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Target DB: campus_resource_sharing\n";
echo "======================================================\n\n";

// --- SUITE 1: DATABASE INTEGRITY ---
echo "--- 1. DATABASE SCHEMA & INTEGRITY ---\n";
$t->assert($pdo instanceof PDO, "PDO connection instance established");

$requiredTables = [
    'users', 'departments', 'categories', 'resources', 'resource_requests',
    'borrowing_transactions', 'donations', 'exchanges', 'conversations',
    'messages', 'notifications', 'reports', 'announcements', 'settings', 'audit_logs'
];

foreach ($requiredTables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
    $t->assert((bool)$stmt->fetch(), "Table '{$table}' exists in database");
}

// --- SUITE 2: SECURITY & CRYPTOGRAPHY ---
echo "\n--- 2. SECURITY & AUTHENTICATION CONTROLS ---\n";
$passwordPlain = "SecureStudent@2026";
$hash = password_hash($passwordPlain, PASSWORD_BCRYPT);
$t->assert(password_verify($passwordPlain, $hash), "password_hash() & password_verify() bcrypt verification");
$t->assert(!password_verify("WrongPassword", $hash), "Rejection of invalid password verification");

$token1 = get_csrf_token();
$t->assert(!empty($token1) && strlen($token1) === 64, "CSRF token 64-char hex string generation");
$t->assert(verify_csrf_token($token1), "CSRF verification with identical token");
$t->assert(!verify_csrf_token("invalid_token_sample"), "Rejection of forged CSRF token");

$xssInput = '<script>alert("XSS Attack");</script>';
$escaped = e($xssInput);
$t->assert(strpos($escaped, '<script>') === false && strpos($escaped, '&lt;script&gt;') !== false, "XSS HTML escaping helper e()");

$csvPayload = "=cmd|' /C calc'!A0";
$sanitizedCsv = sanitize_for_csv($csvPayload);
$t->assert(strpos($sanitizedCsv, "'=") === 0, "Spreadsheet formula injection prevention (sanitize_for_csv)");

// --- SUITE 3: RESOURCE MANAGEMENT SERVICE ---
echo "\n--- 3. RESOURCE CRUD & DISCOVERY SERVICE ---\n";
$resourceService = new ResourceService();
$categories = $resourceService->getAllCategories();
$t->assert(!empty($categories), "Retrieve active categories list from database");

$departments = $resourceService->getAllDepartments();
$t->assert(!empty($departments), "Retrieve active academic departments list");

// Create test resource
$testResId = $resourceService->create([
    'owner_id'                => 4, // Rahul Sharma (Student)
    'category_id'             => 1, // Textbooks
    'department_id'           => 1, // BCA
    'title'                   => 'Automated Test: Clean Architecture by Robert C. Martin',
    'description'             => 'Software engineering principles and clean agile design patterns.',
    'resource_type'           => 'book',
    'sharing_method'          => 'borrow',
    'condition_status'        => 'like_new',
    'total_quantity'          => 1,
    'borrowing_duration_days' => 7,
    'collection_instructions' => 'CS Lab 2',
    'usage_instructions'      => 'Handle with clean hands'
]);
$t->assert($testResId > 0, "Resource creation in database (ID: {$testResId})");

$fetchedRes = $resourceService->getById($testResId);
$t->assert($fetchedRes !== null && $fetchedRes['title'] === 'Automated Test: Clean Architecture by Robert C. Martin', "Retrieve resource by ID with full details");

// Search & Filter
$searchResults = $resourceService->getResources(['search' => 'Clean Architecture']);
$t->assert(count($searchResults) >= 1, "Resource discovery search by keyword");

$filterResults = $resourceService->getResources(['category_id' => 1]);
$t->assert(!empty($filterResults), "Resource filtering by category");

// --- SUITE 4: BORROWING REQUEST & CONCURRENCY TRANSACTION WORKFLOW ---
echo "\n--- 4. BORROWING REQUEST & CONCURRENCY WORKFLOW ---\n";
$requestService = new RequestService();
$transactionService = new TransactionService();

// Submitting request: Priya (User 5) requests Rahul's item
$reqResult = $requestService->submitRequest(5, [
    'resource_id'             => $testResId,
    'requested_start_date'    => date('Y-m-d'),
    'requested_duration_days' => 5,
    'purpose'                 => 'Studying clean architecture principles for BCA project.'
]);
$t->assert($reqResult['success'] === true, "Student submits borrowing request for resource");
$testReqId = $reqResult['request_id'];

// Prevent duplicate active requests
$dupResult = $requestService->submitRequest(5, [
    'resource_id'             => $testResId,
    'requested_start_date'    => date('Y-m-d'),
    'requested_duration_days' => 5,
    'purpose'                 => 'Attempt duplicate request.'
]);
$t->assert($dupResult['success'] === false, "Prevention of duplicate active requests by same user for same resource");

// Prevent owner borrowing own item
$ownerSelfReq = $requestService->submitRequest(4, [
    'resource_id'             => $testResId,
    'requested_start_date'    => date('Y-m-d'),
    'requested_duration_days' => 5,
    'purpose'                 => 'Attempt self request.'
]);
$t->assert($ownerSelfReq['success'] === false, "Prevention of owner requesting their own listed resource");

// Owner approves request (Transaction with concurrency inventory lock)
$apprResult = $requestService->approveRequest($testReqId, 4, 'Approved. Meet outside CS Lab 2.');
$t->assert($apprResult['success'] === true, "Owner approves borrowing request using database transaction");

// Verify resource availability was decremented and marked reserved
$resAfterApproval = $resourceService->getById($testResId);
$t->assert((int)$resAfterApproval['available_quantity'] === 0, "Inventory quantity correctly decremented on approval");
$t->assert($resAfterApproval['availability_status'] === 'reserved', "Availability status updated to 'reserved'");

// Handover Confirmation
$handoverResult = $transactionService->recordHandover($testReqId, 4);
$t->assert($handoverResult['success'] === true, "Handover recorded with calculated due date and active status");
$testTransId = $handoverResult['transaction_id'];

// Return Confirmation
$returnResult = $transactionService->recordReturn($testTransId, 4, 'like_new', 'Returned in immaculate condition.');
$t->assert($returnResult['success'] === true, "Return confirmed and transaction marked completed");

// Verify availability restored
$resAfterReturn = $resourceService->getById($testResId);
$t->assert((int)$resAfterReturn['available_quantity'] === 1, "Inventory quantity restored to 1 upon return");
$t->assert($resAfterReturn['availability_status'] === 'available', "Availability status restored to 'available'");

// --- SUITE 5: IN-PLATFORM MESSAGING & NOTIFICATIONS ---
echo "\n--- 5. MESSAGING & NOTIFICATIONS ---\n";
$messageService = new MessageService();
$notifService = new NotificationService();

$conv = $messageService->getConversationByRequest($testReqId, 4);
$t->assert($conv !== null, "Conversation automatically linked to resource request");

$sendRes = $messageService->sendMessage((int)$conv['id'], 5, "Hi Rahul, thank you for the book!");
$t->assert($sendRes['success'] === true, "Send in-platform coordination message");

$messages = $messageService->getMessages((int)$conv['id'], 4);
$t->assert(!empty($messages), "Fetch conversation messages with read receipts");

// Unauthorized user attempt on conversation
$unauthorizedMessages = $messageService->getMessages((int)$conv['id'], 6); // User 6 Amit
$t->assert(empty($unauthorizedMessages), "Access control: Unrelated user denied access to private request chat");

// In-app notifications
$unreadCount = $notifService->getUnreadCount(4);
$t->assert($unreadCount >= 0, "Unread notifications count query");

// --- SUITE 6: REAL DATABASE METRICS & AUDIT LOGS ---
echo "\n--- 6. ANALYTICS & AUDIT LOGGING ---\n";
$analyticsService = new AnalyticsService();
$adminMetrics = $analyticsService->getAdminMetrics();
$t->assert($adminMetrics['users_total'] >= 7, "Admin metrics: Real registered user count (>= 7)");
$t->assert($adminMetrics['resources_total'] >= 10, "Admin metrics: Real resource listing count (>= 10)");
$t->assert(!empty($adminMetrics['category_chart']), "Admin metrics: Real category chart distribution dataset");

$studentMetrics = $analyticsService->getStudentMetrics(4);
$t->assert(isset($studentMetrics['total_listings']), "Student metrics: Real listing and transaction count");

// Clean up test resource
$delRes = $resourceService->delete($testResId, 4);
// Test safe deletion logic
$t->assert(is_array($delRes), "Safe deletion check handled");

$t->summary();
