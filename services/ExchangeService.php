<?php
/**
 * Campus Resource Sharing Platform
 * Exchange Proposals & Management Service
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/ResourceService.php';

class ExchangeService {
    private PDO $db;
    private NotificationService $notifService;
    private ResourceService $resourceService;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->notifService = new NotificationService();
        $this->resourceService = new ResourceService();
    }

    /**
     * Get available exchange listings
     */
    public function getAvailableExchanges(): array {
        $stmt = $this->db->prepare("
            SELECT r.*, c.category_name, c.icon_class, d.department_name,
                   u.full_name as owner_name, u.role as owner_role
            FROM resources r
            JOIN categories c ON r.category_id = c.id
            LEFT JOIN departments d ON r.department_id = d.id
            JOIN users u ON r.owner_id = u.id
            WHERE r.sharing_method = 'exchange' 
              AND r.moderation_status = 'approved'
              AND r.availability_status = 'available'
            ORDER BY r.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Submit an exchange proposal
     */
    public function submitProposal(int $proposerId, int $listingResourceId, ?int $offeredResourceId, string $offeredDesc, ?string $terms): array {
        $res = $this->resourceService->getById($listingResourceId);
        if (!$res) {
            return ['success' => false, 'error' => 'Resource listing not found.'];
        }

        if ((int)$res['owner_id'] === $proposerId) {
            return ['success' => false, 'error' => 'You cannot propose an exchange for your own listed item.'];
        }

        if ($res['availability_status'] !== 'available') {
            return ['success' => false, 'error' => 'This item is not currently available for exchange.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO exchanges (
                proposer_id, listing_resource_id, offered_resource_id,
                offered_item_description, proposed_terms, status, created_at
            ) VALUES (
                ?, ?, ?,
                ?, ?, 'proposed', NOW()
            )
        ");
        $stmt->execute([
            $proposerId,
            $listingResourceId,
            $offeredResourceId ?: null,
            trim($offeredDesc),
            trim($terms ?? '')
        ]);

        $exchangeId = (int)$this->db->lastInsertId();

        // Notify listing owner
        $proposerStmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
        $proposerStmt->execute([$proposerId]);
        $name = $proposerStmt->fetchColumn() ?: 'A student';

        $this->notifService->create(
            (int)$res['owner_id'],
            'New Exchange Proposal',
            "{$name} submitted an exchange proposal for your listing '{$res['title']}'.",
            'exchange',
            $listingResourceId
        );

        log_audit($proposerId, 'EXCHANGE_PROPOSED', 'exchanges', $exchangeId, "Exchange proposal submitted for '{$res['title']}'");

        return ['success' => true, 'exchange_id' => $exchangeId];
    }

    /**
     * Accept an exchange proposal
     */
    public function acceptProposal(int $exchangeId, int $userId, bool $isAdmin = false): array {
        $stmt = $this->db->prepare("
            SELECT exc.*, r.owner_id as listing_owner_id, r.title as listing_title
            FROM exchanges exc
            JOIN resources r ON exc.listing_resource_id = r.id
            WHERE exc.id = ?
        ");
        $stmt->execute([$exchangeId]);
        $exc = $stmt->fetch();

        if (!$exc) {
            return ['success' => false, 'error' => 'Exchange proposal not found.'];
        }

        if (!$isAdmin && (int)$exc['listing_owner_id'] !== $userId) {
            return ['success' => false, 'error' => 'Unauthorized: Only the listing owner can accept this proposal.'];
        }

        if ($exc['status'] !== 'proposed') {
            return ['success' => false, 'error' => "Proposal cannot be accepted because its status is '{$exc['status']}'."];
        }

        $upd = $this->db->prepare("
            UPDATE exchanges SET
                status = 'accepted',
                agreed_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([$exchangeId]);

        // Reserve the listed resource
        $this->db->prepare("UPDATE resources SET availability_status = 'reserved' WHERE id = ?")->execute([$exc['listing_resource_id']]);

        // Notify proposer
        $this->notifService->create(
            (int)$exc['proposer_id'],
            'Exchange Proposal Accepted!',
            "Your exchange proposal for '{$exc['listing_title']}' has been accepted! Please coordinate the item exchange.",
            'exchange',
            (int)$exc['listing_resource_id']
        );

        log_audit($userId, 'EXCHANGE_ACCEPTED', 'exchanges', $exchangeId, "Accepted exchange proposal #{$exchangeId}");

        return ['success' => true];
    }

    /**
     * Reject an exchange proposal
     */
    public function rejectProposal(int $exchangeId, int $userId, bool $isAdmin = false): array {
        $stmt = $this->db->prepare("
            SELECT exc.*, r.owner_id as listing_owner_id, r.title as listing_title
            FROM exchanges exc
            JOIN resources r ON exc.listing_resource_id = r.id
            WHERE exc.id = ?
        ");
        $stmt->execute([$exchangeId]);
        $exc = $stmt->fetch();

        if (!$exc) {
            return ['success' => false, 'error' => 'Proposal not found.'];
        }

        if (!$isAdmin && (int)$exc['listing_owner_id'] !== $userId) {
            return ['success' => false, 'error' => 'Unauthorized.'];
        }

        $this->db->prepare("UPDATE exchanges SET status = 'rejected', updated_at = NOW() WHERE id = ?")->execute([$exchangeId]);

        // Notify proposer
        $this->notifService->create(
            (int)$exc['proposer_id'],
            'Exchange Proposal Declined',
            "Your exchange proposal for '{$exc['listing_title']}' was declined.",
            'exchange',
            (int)$exc['listing_resource_id']
        );

        log_audit($userId, 'EXCHANGE_REJECTED', 'exchanges', $exchangeId, "Rejected exchange #{$exchangeId}");
        return ['success' => true];
    }

    /**
     * Complete an exchange after both parties trade items
     */
    public function completeExchange(int $exchangeId, int $userId, bool $isAdmin = false): array {
        $stmt = $this->db->prepare("
            SELECT exc.*, r.owner_id as listing_owner_id, r.title as listing_title
            FROM exchanges exc
            JOIN resources r ON exc.listing_resource_id = r.id
            WHERE exc.id = ?
        ");
        $stmt->execute([$exchangeId]);
        $exc = $stmt->fetch();

        if (!$exc) {
            return ['success' => false, 'error' => 'Exchange not found.'];
        }

        if (!$isAdmin && (int)$exc['listing_owner_id'] !== $userId && (int)$exc['proposer_id'] !== $userId) {
            return ['success' => false, 'error' => 'Unauthorized: Only participating parties can complete this exchange.'];
        }

        $upd = $this->db->prepare("UPDATE exchanges SET status = 'completed', completed_at = NOW(), updated_at = NOW() WHERE id = ?");
        $upd->execute([$exchangeId]);

        // Mark listing resource as completed/unavailable
        $this->db->prepare("UPDATE resources SET available_quantity = 0, availability_status = 'unavailable' WHERE id = ?")->execute([$exc['listing_resource_id']]);

        log_audit($userId, 'EXCHANGE_COMPLETED', 'exchanges', $exchangeId, "Completed exchange #{$exchangeId}");

        return ['success' => true];
    }

    /**
     * Get user exchanges (proposals made and proposals received)
     */
    public function getUserExchanges(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT exc.*, 
                   lr.title as listing_title, lr.image_path as listing_image, lr.owner_id as listing_owner_id,
                   owner.full_name as owner_name,
                   proposer.full_name as proposer_name
            FROM exchanges exc
            JOIN resources lr ON exc.listing_resource_id = lr.id
            JOIN users owner ON lr.owner_id = owner.id
            JOIN users proposer ON exc.proposer_id = proposer.id
            WHERE exc.proposer_id = ? OR lr.owner_id = ?
            ORDER BY exc.created_at DESC
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }
}
