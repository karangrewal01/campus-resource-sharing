<?php
/**
 * Campus Resource Sharing Platform
 * In-Platform Messaging & Handover Coordination Service
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class MessageService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get or create conversation for a resource request
     */
    public function getConversationByRequest(int $requestId, int $userId, bool $isAdmin = false): ?array {
        $stmt = $this->db->prepare("
            SELECT conv.*, req.status as request_status, req.requester_id,
                   r.id as resource_id, r.title as resource_title, r.owner_id,
                   req_user.full_name as requester_name,
                   owner_user.full_name as owner_name
            FROM conversations conv
            JOIN resource_requests req ON conv.resource_request_id = req.id
            JOIN resources r ON req.resource_id = r.id
            JOIN users req_user ON req.requester_id = req_user.id
            JOIN users owner_user ON r.owner_id = owner_user.id
            WHERE conv.resource_request_id = ?
        ");
        $stmt->execute([$requestId]);
        $conv = $stmt->fetch();

        if (!$conv) {
            // Check if request exists, if so create conversation
            $checkReq = $this->db->prepare("
                SELECT req.id, req.requester_id, r.owner_id, r.title, r.id as res_id
                FROM resource_requests req
                JOIN resources r ON req.resource_id = r.id
                WHERE req.id = ?
            ");
            $checkReq->execute([$requestId]);
            $req = $checkReq->fetch();
            if (!$req) return null;

            if (!$isAdmin && (int)$req['requester_id'] !== $userId && (int)$req['owner_id'] !== $userId) {
                return null;
            }

            $ins = $this->db->prepare("INSERT INTO conversations (resource_request_id, created_at) VALUES (?, NOW())");
            $ins->execute([$requestId]);
            return $this->getConversationByRequest($requestId, $userId, $isAdmin);
        }

        // Authorization check: must be requester, owner, or admin
        if (!$isAdmin && (int)$conv['requester_id'] !== $userId && (int)$conv['owner_id'] !== $userId) {
            return null;
        }

        return $conv;
    }

    /**
     * Get messages in a conversation
     */
    public function getMessages(int $conversationId, int $userId, bool $isAdmin = false): array {
        // Verify access to conversation
        $convStmt = $this->db->prepare("
            SELECT conv.*, req.requester_id, r.owner_id
            FROM conversations conv
            JOIN resource_requests req ON conv.resource_request_id = req.id
            JOIN resources r ON req.resource_id = r.id
            WHERE conv.id = ?
        ");
        $convStmt->execute([$conversationId]);
        $conv = $convStmt->fetch();

        if (!$conv) return [];
        if (!$isAdmin && (int)$conv['requester_id'] !== $userId && (int)$conv['owner_id'] !== $userId) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT m.*, u.full_name as sender_name, u.role as sender_role
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$conversationId]);
        $messages = $stmt->fetchAll();

        // Mark incoming messages as read
        $markStmt = $this->db->prepare("
            UPDATE messages 
            SET read_at = NOW() 
            WHERE conversation_id = ? AND sender_id != ? AND read_at IS NULL
        ");
        $markStmt->execute([$conversationId, $userId]);

        return $messages;
    }

    /**
     * Send message in a conversation
     */
    public function sendMessage(int $conversationId, int $senderId, string $body, bool $isAdmin = false): array {
        $trimmed = trim($body);
        if ($trimmed === '') {
            return ['success' => false, 'error' => 'Message content cannot be empty.'];
        }

        // Verify participant
        $convStmt = $this->db->prepare("
            SELECT conv.*, req.requester_id, req.id as request_id, r.owner_id, r.title as resource_title
            FROM conversations conv
            JOIN resource_requests req ON conv.resource_request_id = req.id
            JOIN resources r ON req.resource_id = r.id
            WHERE conv.id = ?
        ");
        $convStmt->execute([$conversationId]);
        $conv = $convStmt->fetch();

        if (!$conv) {
            return ['success' => false, 'error' => 'Conversation not found.'];
        }

        if (!$isAdmin && (int)$conv['requester_id'] !== $senderId && (int)$conv['owner_id'] !== $senderId) {
            return ['success' => false, 'error' => 'Unauthorized: You are not a participant in this conversation.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO messages (conversation_id, sender_id, message_body, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$conversationId, $senderId, $trimmed]);
        $msgId = (int)$this->db->lastInsertId();

        // Determine recipient
        $recipientId = ((int)$conv['requester_id'] === $senderId) ? (int)$conv['owner_id'] : (int)$conv['requester_id'];

        // Get sender name
        $senderStmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
        $senderStmt->execute([$senderId]);
        $senderName = $senderStmt->fetchColumn() ?: 'A user';

        // Notify recipient
        $notif = new NotificationService();
        $notif->create(
            $recipientId,
            'New Message',
            "{$senderName} sent a message regarding '{$conv['resource_title']}'.",
            'message',
            null,
            (int)$conv['request_id']
        );

        return ['success' => true, 'message_id' => $msgId];
    }
}
