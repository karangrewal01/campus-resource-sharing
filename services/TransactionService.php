<?php
/**
 * Campus Resource Sharing Platform
 * Transaction Service: Handover, Return, Overdue & Condition Management
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/ResourceService.php';
require_once __DIR__ . '/NotificationService.php';

class TransactionService {
    private PDO $db;
    private ResourceService $resourceService;
    private NotificationService $notifService;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->resourceService = new ResourceService();
        $this->notifService = new NotificationService();
    }

    /**
     * Confirm physical handover and initiate borrowing duration
     */
    public function recordHandover(int $requestId, int $userId, bool $isAdmin = false): array {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT req.*, r.owner_id, r.title, r.sharing_method
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
                return ['success' => false, 'error' => 'Unauthorized: Only the resource owner can confirm handover.'];
            }

            if (!in_array($req['status'], ['approved', 'ready_for_collection'])) {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Handover cannot be confirmed because request status is '{$req['status']}'."];
            }

            // Calculate due timestamp
            $durationDays = max(1, (int)$req['requested_duration_days']);
            $handoverAt = date('Y-m-d H:i:s');
            $dueAt = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));

            // Insert borrowing transaction
            $insTrans = $this->db->prepare("
                INSERT INTO borrowing_transactions (
                    request_id, resource_id, owner_id, borrower_id, quantity,
                    handover_at, due_at, transaction_status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, 'active', NOW()
                )
            ");
            $insTrans->execute([
                $requestId,
                $req['resource_id'],
                $req['owner_id'],
                $req['requester_id'],
                $req['quantity_requested'],
                $handoverAt,
                $dueAt
            ]);
            $transId = (int)$this->db->lastInsertId();

            // Update request status
            $updReq = $this->db->prepare("UPDATE resource_requests SET status = 'borrowed', updated_at = NOW() WHERE id = ?");
            $updReq->execute([$requestId]);

            $this->db->commit();

            // Sync resource availability
            $this->resourceService->syncAvailability((int)$req['resource_id']);

            // Notify borrower
            $this->notifService->create(
                (int)$req['requester_id'],
                'Handover Confirmed',
                "Handover for '{$req['title']}' has been confirmed by the owner. Please return by " . format_date($dueAt) . ".",
                'handover',
                (int)$req['resource_id'],
                $requestId
            );

            log_audit($userId, 'TRANSACTION_HANDOVER', 'borrowing_transactions', $transId, "Handover recorded for '{$req['title']}' to User #{$req['requester_id']}");

            return ['success' => true, 'transaction_id' => $transId];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Failed to record handover: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Confirm return of the borrowed resource
     */
    public function recordReturn(int $transactionId, int $userId, ?string $condition = null, ?string $damageNotes = null, bool $isAdmin = false): array {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT bt.*, r.title, r.owner_id as res_owner_id, req.status as req_status
                FROM borrowing_transactions bt
                JOIN resources r ON bt.resource_id = r.id
                JOIN resource_requests req ON bt.request_id = req.id
                WHERE bt.id = ?
                FOR UPDATE
            ");
            $stmt->execute([$transactionId]);
            $trans = $stmt->fetch();

            if (!$trans) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Transaction not found.'];
            }

            if (!$isAdmin && (int)$trans['owner_id'] !== $userId) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Unauthorized: Only the resource owner can confirm the return.'];
            }

            if (!in_array($trans['transaction_status'], ['active', 'overdue'])) {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Transaction is already closed with status '{$trans['transaction_status']}'."];
            }

            $finalStatus = !empty($damageNotes) ? 'damaged' : 'returned';

            // Update transaction
            $updTrans = $this->db->prepare("
                UPDATE borrowing_transactions SET
                    returned_at = NOW(),
                    transaction_status = ?,
                    condition_on_return = ?,
                    damage_notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updTrans->execute([$finalStatus, $condition ?: null, $damageNotes ?: null, $transactionId]);

            // Update request
            $updReq = $this->db->prepare("UPDATE resource_requests SET status = 'returned', updated_at = NOW() WHERE id = ?");
            $updReq->execute([$trans['request_id']]);

            $this->db->commit();

            // Restore resource availability
            $this->resourceService->syncAvailability((int)$trans['resource_id']);

            // Notify borrower
            $this->notifService->create(
                (int)$trans['borrower_id'],
                'Return Completed',
                "Your return for '{$trans['title']}' has been officially confirmed by the owner. Thank you for participating in campus sharing!",
                'return',
                (int)$trans['resource_id'],
                (int)$trans['request_id']
            );

            log_audit($userId, 'TRANSACTION_RETURNED', 'borrowing_transactions', $transactionId, "Return confirmed for '{$trans['title']}'");

            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Failed to record return: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get active items currently borrowed by user
     */
    public function getBorrowedItems(int $borrowerId): array {
        check_and_update_overdue_transactions();

        $stmt = $this->db->prepare("
            SELECT bt.*, r.title, r.image_path, r.condition_status, r.sharing_method,
                   u.full_name as owner_name, u.role as owner_role,
                   c.category_name,
                   req.purpose, req.requested_start_date,
                   conv.id as conversation_id
            FROM borrowing_transactions bt
            JOIN resources r ON bt.resource_id = r.id
            JOIN users u ON bt.owner_id = u.id
            JOIN categories c ON r.category_id = c.id
            JOIN resource_requests req ON bt.request_id = req.id
            LEFT JOIN conversations conv ON conv.resource_request_id = req.id
            WHERE bt.borrower_id = ? AND bt.transaction_status IN ('active', 'overdue')
            ORDER BY bt.due_at ASC
        ");
        $stmt->execute([$borrowerId]);
        return $stmt->fetchAll();
    }

    /**
     * Get items currently lent by user to others
     */
    public function getLentItems(int $ownerId): array {
        check_and_update_overdue_transactions();

        $stmt = $this->db->prepare("
            SELECT bt.*, r.title, r.image_path, r.condition_status,
                   u.full_name as borrower_name, u.institution_id as borrower_inst_id,
                   u.course as borrower_course, u.semester as borrower_semester,
                   c.category_name,
                   req.purpose,
                   conv.id as conversation_id
            FROM borrowing_transactions bt
            JOIN resources r ON bt.resource_id = r.id
            JOIN users u ON bt.borrower_id = u.id
            JOIN categories c ON r.category_id = c.id
            JOIN resource_requests req ON bt.request_id = req.id
            LEFT JOIN conversations conv ON conv.resource_request_id = req.id
            WHERE bt.owner_id = ? AND bt.transaction_status IN ('active', 'overdue')
            ORDER BY bt.due_at ASC
        ");
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    /**
     * Get complete transaction history for a user (as borrower or lender)
     */
    public function getUserTransactionHistory(int $userId): array {
        check_and_update_overdue_transactions();

        $stmt = $this->db->prepare("
            SELECT bt.*, r.title, r.image_path,
                   owner.full_name as owner_name, borrower.full_name as borrower_name,
                   c.category_name
            FROM borrowing_transactions bt
            JOIN resources r ON bt.resource_id = r.id
            JOIN users owner ON bt.owner_id = owner.id
            JOIN users borrower ON bt.borrower_id = borrower.id
            JOIN categories c ON r.category_id = c.id
            WHERE bt.owner_id = ? OR bt.borrower_id = ?
            ORDER BY bt.created_at DESC
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }
}
