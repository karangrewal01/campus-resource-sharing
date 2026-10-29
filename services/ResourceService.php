<?php
/**
 * Campus Resource Sharing Platform
 * Resource Management Service
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class ResourceService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get paginated resources with multiple search/filter criteria
     */
    public function getResources(array $filters = [], int $limit = 12, int $offset = 0): array {
        check_and_update_overdue_transactions();

        $sql = "
            SELECT r.*, 
                   c.category_name, c.slug as category_slug, c.icon_class,
                   d.department_name, d.department_code,
                   u.full_name as owner_name, u.role as owner_role, u.institution_id as owner_inst_id
            FROM resources r
            JOIN categories c ON r.category_id = c.id
            LEFT JOIN departments d ON r.department_id = d.id
            JOIN users u ON r.owner_id = u.id
            WHERE r.moderation_status = 'approved'
        ";

        $params = [];

        // Search query (title & description)
        if (!empty($filters['search'])) {
            $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.course_semester_tag LIKE ?)";
            $term = '%' . trim($filters['search']) . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $sql .= " AND r.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        // Sharing Method filter
        if (!empty($filters['sharing_method'])) {
            $sql .= " AND r.sharing_method = ?";
            $params[] = $filters['sharing_method'];
        }

        // Resource Type filter
        if (!empty($filters['resource_type'])) {
            $sql .= " AND r.resource_type = ?";
            $params[] = $filters['resource_type'];
        }

        // Condition status
        if (!empty($filters['condition_status'])) {
            $sql .= " AND r.condition_status = ?";
            $params[] = $filters['condition_status'];
        }

        // Department filter
        if (!empty($filters['department_id'])) {
            $sql .= " AND r.department_id = ?";
            $params[] = (int)$filters['department_id'];
        }

        // Availability filter
        if (!empty($filters['availability_status'])) {
            $sql .= " AND r.availability_status = ?";
            $params[] = $filters['availability_status'];
        } elseif (isset($filters['only_available']) && $filters['only_available']) {
            $sql .= " AND r.availability_status = 'available' AND r.available_quantity > 0";
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'oldest':
                $sql .= " ORDER BY r.created_at ASC";
                break;
            case 'title_asc':
                $sql .= " ORDER BY r.title ASC";
                break;
            case 'title_desc':
                $sql .= " ORDER BY r.title DESC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY r.created_at DESC";
                break;
        }

        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count resources for pagination
     */
    public function countResources(array $filters = []): int {
        $sql = "
            SELECT COUNT(*)
            FROM resources r
            JOIN categories c ON r.category_id = c.id
            JOIN users u ON r.owner_id = u.id
            WHERE r.moderation_status = 'approved'
        ";

        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.course_semester_tag LIKE ?)";
            $term = '%' . trim($filters['search']) . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND r.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['sharing_method'])) {
            $sql .= " AND r.sharing_method = ?";
            $params[] = $filters['sharing_method'];
        }

        if (!empty($filters['resource_type'])) {
            $sql .= " AND r.resource_type = ?";
            $params[] = $filters['resource_type'];
        }

        if (!empty($filters['condition_status'])) {
            $sql .= " AND r.condition_status = ?";
            $params[] = $filters['condition_status'];
        }

        if (!empty($filters['department_id'])) {
            $sql .= " AND r.department_id = ?";
            $params[] = (int)$filters['department_id'];
        }

        if (!empty($filters['availability_status'])) {
            $sql .= " AND r.availability_status = ?";
            $params[] = $filters['availability_status'];
        } elseif (isset($filters['only_available']) && $filters['only_available']) {
            $sql .= " AND r.availability_status = 'available' AND r.available_quantity > 0";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get single resource with full details
     */
    public function getById(int $id): ?array {
        check_and_update_overdue_transactions();

        $stmt = $this->db->prepare("
            SELECT r.*, 
                   c.category_name, c.slug as category_slug, c.icon_class,
                   d.department_name, d.department_code,
                   u.full_name as owner_name, u.role as owner_role, u.institution_id as owner_inst_id,
                   u.course as owner_course, u.semester as owner_semester, u.designation as owner_designation
            FROM resources r
            JOIN categories c ON r.category_id = c.id
            LEFT JOIN departments d ON r.department_id = d.id
            JOIN users u ON r.owner_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Get resources owned by a user
     */
    public function getByOwner(int $ownerId): array {
        check_and_update_overdue_transactions();

        $stmt = $this->db->prepare("
            SELECT r.*, c.category_name, c.icon_class,
                   (SELECT COUNT(*) FROM resource_requests req WHERE req.resource_id = r.id AND req.status = 'pending') as pending_requests_count,
                   (SELECT COUNT(*) FROM borrowing_transactions bt WHERE bt.resource_id = r.id AND bt.transaction_status = 'active') as active_borrows_count
            FROM resources r
            JOIN categories c ON r.category_id = c.id
            WHERE r.owner_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    /**
     * Create a new resource listing
     */
    public function create(array $data): int {
        $moderationRequired = get_setting('require_listing_moderation', '0') === '1';
        $moderationStatus = $moderationRequired ? 'pending_approval' : 'approved';

        $stmt = $this->db->prepare("
            INSERT INTO resources (
                owner_id, category_id, department_id, title, description,
                resource_type, sharing_method, condition_status,
                total_quantity, available_quantity, borrowing_duration_days,
                exchange_requirements, collection_instructions, usage_instructions,
                image_path, course_semester_tag, availability_status, moderation_status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, 'available', ?, NOW()
            )
        ");

        $totalQty = max(1, (int)($data['total_quantity'] ?? 1));
        $stmt->execute([
            $data['owner_id'],
            $data['category_id'],
            $data['department_id'] ?: null,
            trim($data['title']),
            trim($data['description']),
            $data['resource_type'],
            $data['sharing_method'],
            $data['condition_status'],
            $totalQty,
            $totalQty,
            (int)($data['borrowing_duration_days'] ?? 7),
            $data['exchange_requirements'] ?? null,
            $data['collection_instructions'] ?? null,
            $data['usage_instructions'] ?? null,
            $data['image_path'] ?? null,
            $data['course_semester_tag'] ?? null,
            $moderationStatus
        ]);

        $resId = (int)$this->db->lastInsertId();
        log_audit((int)$data['owner_id'], 'RESOURCE_CREATED', 'resources', $resId, "Created listing '{$data['title']}'");
        return $resId;
    }

    /**
     * Update an existing listing
     */
    public function update(int $id, int $userId, array $data, bool $isAdmin = false): bool {
        $existing = $this->getById($id);
        if (!$existing) {
            return false;
        }

        // Ownership verification
        if (!$isAdmin && (int)$existing['owner_id'] !== $userId) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE resources SET
                category_id = ?,
                department_id = ?,
                title = ?,
                description = ?,
                resource_type = ?,
                sharing_method = ?,
                condition_status = ?,
                total_quantity = ?,
                borrowing_duration_days = ?,
                exchange_requirements = ?,
                collection_instructions = ?,
                usage_instructions = ?,
                image_path = COALESCE(?, image_path),
                course_semester_tag = ?,
                availability_status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $totalQty = max(1, (int)($data['total_quantity'] ?? $existing['total_quantity']));
        $stmt->execute([
            $data['category_id'] ?? $existing['category_id'],
            $data['department_id'] ?? $existing['department_id'],
            trim($data['title'] ?? $existing['title']),
            trim($data['description'] ?? $existing['description']),
            $data['resource_type'] ?? $existing['resource_type'],
            $data['sharing_method'] ?? $existing['sharing_method'],
            $data['condition_status'] ?? $existing['condition_status'],
            $totalQty,
            (int)($data['borrowing_duration_days'] ?? $existing['borrowing_duration_days']),
            $data['exchange_requirements'] ?? $existing['exchange_requirements'],
            $data['collection_instructions'] ?? $existing['collection_instructions'],
            $data['usage_instructions'] ?? $existing['usage_instructions'],
            $data['image_path'] ?? null,
            $data['course_semester_tag'] ?? $existing['course_semester_tag'],
            $data['availability_status'] ?? $existing['availability_status'],
            $id
        ]);

        // Recalculate available quantity
        $this->syncAvailability($id);

        log_audit($userId, 'RESOURCE_UPDATED', 'resources', $id, "Updated listing details for #{$id}");
        return true;
    }

    /**
     * Safe deletion of resource: prevents deletion if active transactions exist
     */
    public function delete(int $id, int $userId, bool $isAdmin = false): array {
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Resource listing not found.'];
        }

        if (!$isAdmin && (int)$existing['owner_id'] !== $userId) {
            return ['success' => false, 'error' => 'Unauthorized: You can only delete your own listings.'];
        }

        // Check for active transactions
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM borrowing_transactions 
            WHERE resource_id = ? AND transaction_status IN ('active', 'overdue', 'disputed')
        ");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return ['success' => false, 'error' => 'Cannot delete this resource because there is an active or overdue borrowing transaction associated with it.'];
        }

        // Check for active/pending requests
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM resource_requests 
            WHERE resource_id = ? AND status IN ('pending', 'approved', 'ready_for_collection', 'borrowed')
        ");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return ['success' => false, 'error' => 'Cannot delete listing with pending or active requests. Please resolve or cancel them first.'];
        }

        // Check for historical requests/transactions referencing this resource
        // If history exists, archive/mark unavailable to preserve institutional audit integrity
        $stmtHist = $this->db->prepare("SELECT COUNT(*) FROM resource_requests WHERE resource_id = ?");
        $stmtHist->execute([$id]);
        if ((int)$stmtHist->fetchColumn() > 0) {
            $this->db->prepare("UPDATE resources SET availability_status = 'unavailable', updated_at = NOW() WHERE id = ?")->execute([$id]);
            log_audit($userId, 'RESOURCE_ARCHIVED', 'resources', $id, "Archived resource listing '{$existing['title']}' to preserve transaction history");
            return ['success' => true, 'archived' => true, 'message' => 'Resource has historical transactions and was safely archived as unavailable.'];
        }

        // Safe delete when no foreign keys reference it
        try {
            $del = $this->db->prepare("DELETE FROM resources WHERE id = ?");
            $del->execute([$id]);
            log_audit($userId, 'RESOURCE_DELETED', 'resources', $id, "Deleted resource listing '{$existing['title']}'");
            return ['success' => true, 'archived' => false];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Database constraint prevented deletion: ' . $e->getMessage()];
        }
    }

    /**
     * Recalculate available_quantity and status based on active transactions/reservations
     */
    public function syncAvailability(int $resourceId): void {
        $stmt = $this->db->prepare("SELECT total_quantity, availability_status FROM resources WHERE id = ?");
        $stmt->execute([$resourceId]);
        $res = $stmt->fetch();
        if (!$res) return;

        // Count active borrowed units
        $stmt2 = $this->db->prepare("
            SELECT COALESCE(SUM(quantity), 0) FROM borrowing_transactions
            WHERE resource_id = ? AND transaction_status IN ('active', 'overdue')
        ");
        $stmt2->execute([$resourceId]);
        $activeBorrowed = (int)$stmt2->fetchColumn();

        // Count approved reservations not yet handed over
        $stmt3 = $this->db->prepare("
            SELECT COALESCE(SUM(quantity_requested), 0) FROM resource_requests
            WHERE resource_id = ? AND status IN ('approved', 'ready_for_collection')
        ");
        $stmt3->execute([$resourceId]);
        $reserved = (int)$stmt3->fetchColumn();

        $availableQty = max(0, (int)$res['total_quantity'] - ($activeBorrowed + $reserved));
        
        $newStatus = $res['availability_status'];
        if ($res['availability_status'] !== 'unavailable' && $res['availability_status'] !== 'under_review') {
            if ($availableQty > 0) {
                $newStatus = 'available';
            } elseif ($activeBorrowed > 0) {
                $newStatus = 'borrowed';
            } elseif ($reserved > 0) {
                $newStatus = 'reserved';
            } else {
                $newStatus = 'unavailable';
            }
        }

        $upd = $this->db->prepare("UPDATE resources SET available_quantity = ?, availability_status = ? WHERE id = ?");
        $upd->execute([$availableQty, $newStatus, $resourceId]);
    }

    public function getAllCategories(): array {
        $stmt = $this->db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY category_name ASC");
        return $stmt->fetchAll();
    }

    public function getAllDepartments(): array {
        $stmt = $this->db->query("SELECT * FROM departments WHERE status = 'active' ORDER BY department_name ASC");
        return $stmt->fetchAll();
    }
}
