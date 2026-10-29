<?php
/**
 * Campus Resource Sharing Platform
 * Borrowing & Resource Request Service (with Concurrency & Transactions)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/ResourceService.php';
require_once __DIR__ . '/NotificationService.php';

class RequestService {
    private PDO $db;
    private ResourceService $resourceService;
    private NotificationService $notifService;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->resourceService = new ResourceService();
        $this->notifService = new NotificationService();
    }

    /**
     * Submit a new borrowing / donation / exchange request
     */
    public function submitRequest(int $requesterId, array $data): array {
        check_and_update_overdue_transactions();

        $resourceId = (int)($data['resource_id'] ?? 0);
        $res = $this->resourceService->getById($resourceId);

        if (!$res) {
            return ['success' => false, 'error' => 'The requested resource does not exist.'];
        }

        if ($res['moderation_status'] !== 'approved') {
            return ['success' => false, 'error' => 'This resource is not currently approved for requests.'];
        }

        // Prevent owner from requesting their own item
        if ((int)$res['owner_id'] === $requesterId) {
            return ['success' => false, 'error' => 'You cannot submit a request for your own listed resource.'];
        }

        // Check availability
        if ($res['available_quantity'] < 1 || $res['availability_status'] === 'unavailable') {
            return ['success' => false, 'error' => 'This resource is currently not available for requests.'];
        }

        // Check for existing pending/active request by this user for this resource
        $checkStmt = $this->db->prepare("
            SELECT id FROM resource_requests
            WHERE resource_id = ? AND requester_id = ? AND status IN ('pending', 'approved', 'ready_for_collection', 'borrowed')
        ");
        $checkStmt->execute([$resourceId, $requesterId]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'error' => 'You already have an active or pending request for this resource.'];
        }

        // Check user active requests limit
        $maxRequests = (int)get_setting('max_active_requests_per_user', '3');
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM resource_requests
            WHERE requester_id = ? AND status IN ('pending', 'approved', 'ready_for_collection')
        ");
        $countStmt->execute([$requesterId]);
        if ((int)$countStmt->fetchColumn() >= $maxRequests) {
            return ['success' => false, 'error' => "You have reached the limit of {$maxRequests} active pending/approved requests."];
        }

        // Duration validation
        $startDate = !empty($data['requested_start_date']) ? $data['requested_start_date'] : date('Y-m-d');
        $duration = max(1, (int)($data['requested_duration_days'] ?? $res['borrowing_duration_days']));
        
        // Calculate proposed return date
        $returnDate = date('Y-m-d', strtotime("{$startDate} + {$duration} days"));
        $requestType = $res['sharing_method'];

        $stmt = $this->db->prepare("
            INSERT INTO resource_requests (
                resource_id, requester_id, request_type,
                requested_start_date, requested_duration_days, proposed_return_date,
                purpose, quantity_requested, status, created_at
            ) VALUES (
                ?, ?, ?,
                ?, ?, ?,
                ?, 1, 'pending', NOW()
            )
        ");
        $stmt->execute([
            $resourceId,
            $requesterId,
            $requestType,
            $startDate,
            $duration,
            $returnDate,
            trim($data['purpose'] ?? '')
        ]);

        $requestId = (int)$this->db->lastInsertId();

        // Create linked conversation for messaging
        $convStmt = $this->db->prepare("INSERT INTO conversations (resource_request_id, created_at) VALUES (?, NOW())");
        $convStmt->execute([$requestId]);

        // Notify the resource owner
        $requesterStmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
        $requesterStmt->execute([$requesterId]);
        $requesterName = $requesterStmt->fetchColumn() ?: 'A student';

        $this->notifService->create(
            (int)$res['owner_id'],
            'New Resource Request',
            "{$requesterName} submitted a {$requestType} request for '{$res['title']}'.",
            'request',
            $resourceId,
            $requestId
        );

        log_audit($requesterId, 'REQUEST_SUBMITTED', 'resource_requests', $requestId, "Submitted request for '{$res['title']}'");

        return ['success' => true, 'request_id' => $requestId];
    }

    /**
     * Approve a pending request (Using DB Transaction & Concurrency Lock)
     */
    public function approveRequest(int $requestId, int $userId, ?string $reason = null, ?string $altDate = null, bool $isAdmin = false): array {
        $this->db->beginTransaction();

        try {
            // Lock and fetch request
            $stmt = $this->db->prepare("
                SELECT req.*, r.owner_id, r.title, r.available_quantity, r.total_quantity, r.sharing_method
                FROM resource_requests req
                JOIN resources r ON req.resource_id = r.id
                WHERE req.id = ?
                FOR UPDATE
            ");
            $stmt->execute([$requestId]);
            $req = $stmt->fetch();

            if (!$req) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Request not found.'];
            }

            if (!$isAdmin && (int)$req['owner_id'] !== $userId) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Unauthorized: Only the resource owner can approve this request.'];
            }

            if ($req['status'] !== 'pending') {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Request cannot be approved because current status is '{$req['status']}'."];
            }

            // Verify inventory
            if ((int)$req['available_quantity'] < (int)$req['quantity_requested']) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Cannot approve: Available quantity of this resource is insufficient or already reserved.'];
            }

            // Update request status to approved
            $updReq = $this->db->prepare("
                UPDATE resource_requests SET
                    status = 'approved',
                    decision_reason = ?,
                    alternative_pickup_date = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updReq->execute([$reason, $altDate ?: null, $requestId]);

            // Sync resource availability
            $newAvailable = max(0, (int)$req['available_quantity'] - (int)$req['quantity_requested']);
            $newStatus = ($newAvailable > 0) ? 'available' : 'reserved';

            $updRes = $this->db->prepare("UPDATE resources SET available_quantity = ?, availability_status = ? WHERE id = ?");
            $updRes->execute([$newAvailable, $newStatus, $req['resource_id']]);

            // If donation, create or update donation record
            if ($req['sharing_method'] === 'donate') {
                $donStmt = $this->db->prepare("
                    INSERT INTO donations (resource_id, donor_id, recipient_id, request_id, status, created_at)
                    VALUES (?, ?, ?, ?, 'handover_scheduled', NOW())
                    ON DUPLICATE KEY UPDATE status = 'handover_scheduled'
                ");
                $donStmt->execute([$req['resource_id'], $req['owner_id'], $req['requester_id'], $requestId]);
            }

            $this->db->commit();

            // Send notification to requester
            $this->notifService->create(
                (int)$req['requester_id'],
                'Request Approved!',
                "Your request for '{$req['title']}' has been approved by the owner. Please coordinate collection.",
                'approval',
                (int)$req['resource_id'],
                $requestId
            );

            log_audit($userId, 'REQUEST_APPROVED', 'resource_requests', $requestId, "Approved request #{$requestId} for '{$req['title']}'");

            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Failed to approve request: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred during approval: ' . $e->getMessage()];
        }
    }

    /**
     * Reject a pending request
     */
    public function rejectRequest(int $requestId, int $userId, ?string $reason = null, bool $isAdmin = false): array {
        $stmt = $this->db->prepare("
            SELECT req.*, r.owner_id, r.title 
            FROM resource_requests req
            JOIN resources r ON req.resource_id = r.id
            WHERE req.id = ?
        ");
        $stmt->execute([$requestId]);
        $req = $stmt->fetch();

        if (!$req) {
            return ['success' => false, 'error' => 'Request not found.'];
        }

        if (!$isAdmin && (int)$req['owner_id'] !== $userId) {
            return ['success' => false, 'error' => 'Unauthorized: Only the owner can reject this request.'];
        }

        if ($req['status'] !== 'pending') {
            return ['success' => false, 'error' => 'Only pending requests can be rejected.'];
        }

        $upd = $this->db->prepare("UPDATE resource_requests SET status = 'rejected', decision_reason = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$reason ?: 'Declined by owner', $requestId]);

        // Notify requester
        $this->notifService->create(
            (int)$req['requester_id'],
            'Request Declined',
            "Your request for '{$req['title']}' was declined by the owner. Reason: " . ($reason ?: 'Not specified'),
            'rejection',
            (int)$req['resource_id'],
            $requestId
        );

        log_audit($userId, 'REQUEST_REJECTED', 'resource_requests', $requestId, "Rejected request #{$requestId}");
        return ['success' => true];
    }

    /**
     * Cancel a request by the requester
     */
    public function cancelRequest(int $requestId, int $userId, bool $isAdmin = false): array {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT req.*, r.owner_id, r.title 
                FROM resource_requests req
                JOIN resources r ON req.resource_id = r.id
                WHERE req.id = ?
                FOR UPDATE
            ");
            $stmt->execute([$requestId]);
            $req = $stmt->fetch();

            if (!$req) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Request not found.'];
            }

            if (!$isAdmin && (int)$req['requester_id'] !== $userId) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Unauthorized: You can only cancel your own requests.'];
            }

            if (!in_array($req['status'], ['pending', 'approved', 'ready_for_collection'])) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Cannot cancel a request that has already been handed over or completed.'];
            }

            $wasReserved = in_array($req['status'], ['approved', 'ready_for_collection']);

            $upd = $this->db->prepare("UPDATE resource_requests SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $upd->execute([$requestId]);

            $this->db->commit();

            // If it had reserved quantity, release it
            if ($wasReserved) {
                $this->resourceService->syncAvailability((int)$req['resource_id']);
            }

            // Notify owner
            $this->notifService->create(
                (int)$req['owner_id'],
                'Request Cancelled',
                "The requester has cancelled their request for '{$req['title']}'.",
                'request',
                (int)$req['resource_id'],
                $requestId
            );

            log_audit($userId, 'REQUEST_CANCELLED', 'resource_requests', $requestId, "Cancelled request #{$requestId}");
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get user's outgoing requests
     */
    public function getUserRequests(int $userId): array {
        check_and_update_overdue_transactions();

        $stmt = $this->db->prepare("
            SELECT req.*, r.title as resource_title, r.sharing_method, r.image_path, r.condition_status,
                   u.full_name as owner_name, u.role as owner_role,
                   c.category_name,
                   bt.id as transaction_id, bt.transaction_status, bt.due_at, bt.handover_at,
                   conv.id as conversation_id
            FROM resource_requests req
            JOIN resources r ON req.resource_id = r.id
            JOIN users u ON r.owner_id = u.id
            JOIN categories c ON r.category_id = c.id
            LEFT JOIN borrowing_transactions bt ON bt.request_id = req.id
            LEFT JOIN conversations conv ON conv.resource_request_id = req.id
            WHERE req.requester_id = ?
            ORDER BY req.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get incoming requests for resources owned by user
     */
    public function getIncomingRequests(int $ownerId): array {
        check_and_update_overdue_transactions();

        $stmt = $this->db->prepare("
            SELECT req.*, r.title as resource_title, r.sharing_method, r.available_quantity,
                   u.full_name as requester_name, u.role as requester_role, u.institution_id as requester_inst_id,
                   u.course as requester_course, u.semester as requester_semester,
                   bt.id as transaction_id, bt.transaction_status, bt.due_at,
                   conv.id as conversation_id
            FROM resource_requests req
            JOIN resources r ON req.resource_id = r.id
            JOIN users u ON req.requester_id = u.id
            LEFT JOIN borrowing_transactions bt ON bt.request_id = req.id
            LEFT JOIN conversations conv ON conv.resource_request_id = req.id
            WHERE r.owner_id = ?
            ORDER BY req.created_at DESC
        ");
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }
}
