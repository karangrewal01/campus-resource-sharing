<?php
/**
 * Campus Resource Sharing Platform
 * In-App Notification Service
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class NotificationService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create and store a new in-app notification
     */
    public function create(int $userId, string $title, string $message, string $type = 'system', ?int $resourceId = null, ?int $requestId = null): int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (
                    user_id, title, message, notification_type,
                    related_resource_id, related_request_id, created_at
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, NOW()
                )
            ");
            $stmt->execute([
                $userId,
                trim($title),
                trim($message),
                $type,
                $resourceId ?: null,
                $requestId ?: null
            ]);
            return (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Failed to insert notification: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get notifications for a user
     */
    public function getUserNotifications(int $userId, int $limit = 40): array {
        $stmt = $this->db->prepare("
            SELECT n.*, r.title as resource_title
            FROM notifications n
            LEFT JOIN resources r ON n.related_resource_id = r.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount(int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool {
        $stmt = $this->db->prepare("UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): bool {
        $stmt = $this->db->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL");
        return $stmt->execute([$userId]);
    }
}
