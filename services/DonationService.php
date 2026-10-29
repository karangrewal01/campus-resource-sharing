<?php
/**
 * Campus Resource Sharing Platform
 * Donation Management Service
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/ResourceService.php';

class DonationService {
    private PDO $db;
    private NotificationService $notifService;
    private ResourceService $resourceService;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->notifService = new NotificationService();
        $this->resourceService = new ResourceService();
    }

    /**
     * Get all active donation listings available across campus
     */
    public function getAvailableDonations(): array {
        $stmt = $this->db->prepare("
            SELECT r.*, c.category_name, c.icon_class, d.department_name,
                   u.full_name as donor_name, u.role as donor_role
            FROM resources r
            JOIN categories c ON r.category_id = c.id
            LEFT JOIN departments d ON r.department_id = d.id
            JOIN users u ON r.owner_id = u.id
            WHERE r.sharing_method = 'donate' 
              AND r.moderation_status = 'approved'
              AND r.availability_status = 'available'
            ORDER BY r.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Mark a donation completed after handover
     */
    public function completeDonation(int $donationId, int $userId, bool $isAdmin = false): array {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT don.*, r.title, r.owner_id
                FROM donations don
                JOIN resources r ON don.resource_id = r.id
                WHERE don.id = ?
                FOR UPDATE
            ");
            $stmt->execute([$donationId]);
            $don = $stmt->fetch();

            if (!$don) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Donation record not found.'];
            }

            if (!$isAdmin && (int)$don['donor_id'] !== $userId) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Unauthorized: Only the donating owner can confirm completion.'];
            }

            if ($don['status'] === 'completed') {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Donation has already been completed.'];
            }

            $updDon = $this->db->prepare("
                UPDATE donations SET
                    status = 'completed',
                    handover_at = COALESCE(handover_at, NOW()),
                    completion_at = NOW()
                WHERE id = ?
            ");
            $updDon->execute([$donationId]);

            // Update resource status to unavailable (permanently transferred)
            $updRes = $this->db->prepare("
                UPDATE resources SET
                    available_quantity = 0,
                    availability_status = 'unavailable',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updRes->execute([$don['resource_id']]);

            // Update request
            $updReq = $this->db->prepare("UPDATE resource_requests SET status = 'returned', updated_at = NOW() WHERE id = ?");
            $updReq->execute([$don['request_id']]);

            $this->db->commit();

            // Notify recipient
            $this->notifService->create(
                (int)$don['recipient_id'],
                'Donation Handover Completed',
                "Your donation collection for '{$don['title']}' has been completed. Thank you for keeping campus resources circular!",
                'donation',
                (int)$don['resource_id'],
                (int)$don['request_id']
            );

            log_audit($userId, 'DONATION_COMPLETED', 'donations', $donationId, "Completed donation of '{$don['title']}' to User #{$don['recipient_id']}");

            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get donation history for a user (items donated and items received)
     */
    public function getUserDonations(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT don.*, r.title, r.image_path,
                   donor.full_name as donor_name, recipient.full_name as recipient_name,
                   c.category_name
            FROM donations don
            JOIN resources r ON don.resource_id = r.id
            JOIN users donor ON don.donor_id = donor.id
            JOIN users recipient ON don.recipient_id = recipient.id
            JOIN categories c ON r.category_id = c.id
            WHERE don.donor_id = ? OR don.recipient_id = ?
            ORDER BY don.created_at DESC
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }
}
