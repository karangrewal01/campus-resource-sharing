<?php
/**
 * Campus Resource Sharing Platform
 * Real Analytics & Reports Service (Zero Fabricated Metrics)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class AnalyticsService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
        check_and_update_overdue_transactions();
    }

    /**
     * Get Student Dashboard Metrics (All derived from database records)
     */
    public function getStudentMetrics(int $userId): array {
        // Total resources listed
        $stmt1 = $this->db->prepare("SELECT COUNT(*) FROM resources WHERE owner_id = ?");
        $stmt1->execute([$userId]);
        $totalListings = (int)$stmt1->fetchColumn();

        // Active borrowing requests submitted by student
        $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM resource_requests WHERE requester_id = ? AND status IN ('pending', 'approved', 'ready_for_collection')");
        $stmt2->execute([$userId]);
        $activeRequests = (int)$stmt2->fetchColumn();

        // Items currently borrowed
        $stmt3 = $this->db->prepare("SELECT COUNT(*) FROM borrowing_transactions WHERE borrower_id = ? AND transaction_status IN ('active', 'overdue')");
        $stmt3->execute([$userId]);
        $currentlyBorrowed = (int)$stmt3->fetchColumn();

        // Items currently being lent
        $stmt4 = $this->db->prepare("SELECT COUNT(*) FROM borrowing_transactions WHERE owner_id = ? AND transaction_status IN ('active', 'overdue')");
        $stmt4->execute([$userId]);
        $currentlyLent = (int)$stmt4->fetchColumn();

        // Pending approvals for user's listings
        $stmt5 = $this->db->prepare("
            SELECT COUNT(*) FROM resource_requests req
            JOIN resources r ON req.resource_id = r.id
            WHERE r.owner_id = ? AND req.status = 'pending'
        ");
        $stmt5->execute([$userId]);
        $pendingApprovals = (int)$stmt5->fetchColumn();

        // Overdue items borrowed by student
        $stmt6 = $this->db->prepare("SELECT COUNT(*) FROM borrowing_transactions WHERE borrower_id = ? AND transaction_status = 'overdue'");
        $stmt6->execute([$userId]);
        $overdueCount = (int)$stmt6->fetchColumn();

        // Completed transactions
        $stmt7 = $this->db->prepare("SELECT COUNT(*) FROM borrowing_transactions WHERE (borrower_id = ? OR owner_id = ?) AND transaction_status IN ('returned', 'completed')");
        $stmt7->execute([$userId, $userId]);
        $completedTransactions = (int)$stmt7->fetchColumn();

        // Donations and exchanges completed
        $stmt8 = $this->db->prepare("SELECT COUNT(*) FROM donations WHERE (donor_id = ? OR recipient_id = ?) AND status = 'completed'");
        $stmt8->execute([$userId, $userId]);
        $completedDonations = (int)$stmt8->fetchColumn();

        $stmt9 = $this->db->prepare("
            SELECT COUNT(*) FROM exchanges exc
            JOIN resources r ON exc.listing_resource_id = r.id
            WHERE (exc.proposer_id = ? OR r.owner_id = ?) AND exc.status = 'completed'
        ");
        $stmt9->execute([$userId, $userId]);
        $completedExchanges = (int)$stmt9->fetchColumn();

        // Upcoming return deadlines (items due in next 3 days)
        $stmt10 = $this->db->prepare("
            SELECT bt.*, r.title, u.full_name as owner_name
            FROM borrowing_transactions bt
            JOIN resources r ON bt.resource_id = r.id
            JOIN users u ON bt.owner_id = u.id
            WHERE bt.borrower_id = ? AND bt.transaction_status = 'active'
              AND bt.due_at <= DATE_ADD(NOW(), INTERVAL 3 DAY)
            ORDER BY bt.due_at ASC
        ");
        $stmt10->execute([$userId]);
        $upcomingDeadlines = $stmt10->fetchAll();

        return [
            'total_listings'         => $totalListings,
            'active_requests'        => $activeRequests,
            'currently_borrowed'     => $currentlyBorrowed,
            'currently_lent'         => $currentlyLent,
            'pending_approvals'      => $pendingApprovals,
            'overdue_count'          => $overdueCount,
            'completed_transactions' => $completedTransactions,
            'completed_donations'    => $completedDonations,
            'completed_exchanges'    => $completedExchanges,
            'upcoming_deadlines'     => $upcomingDeadlines
        ];
    }

    /**
     * Get Faculty Dashboard Metrics
     */
    public function getFacultyMetrics(int $facultyId, ?int $departmentId = null): array {
        // Faculty's own resources
        $stmt1 = $this->db->prepare("SELECT COUNT(*) FROM resources WHERE owner_id = ?");
        $stmt1->execute([$facultyId]);
        $myResources = (int)$stmt1->fetchColumn();

        // Active borrows of faculty resources
        $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM borrowing_transactions WHERE owner_id = ? AND transaction_status IN ('active', 'overdue')");
        $stmt2->execute([$facultyId]);
        $activeBorrows = (int)$stmt2->fetchColumn();

        // Department resources
        $deptResources = 0;
        if ($departmentId) {
            $stmt3 = $this->db->prepare("SELECT COUNT(*) FROM resources WHERE department_id = ?");
            $stmt3->execute([$departmentId]);
            $deptResources = (int)$stmt3->fetchColumn();
        }

        // Pending incoming requests
        $stmt4 = $this->db->prepare("
            SELECT COUNT(*) FROM resource_requests req
            JOIN resources r ON req.resource_id = r.id
            WHERE r.owner_id = ? AND req.status = 'pending'
        ");
        $stmt4->execute([$facultyId]);
        $pendingRequests = (int)$stmt4->fetchColumn();

        return [
            'my_resources'      => $myResources,
            'active_borrows'    => $activeBorrows,
            'dept_resources'    => $deptResources,
            'pending_requests'  => $pendingRequests
        ];
    }

    /**
     * Get Administrator Dashboard Real Metrics & Chart Datasets
     */
    public function getAdminMetrics(): array {
        // User counts
        $usersTotal = (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $studentsCount = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND account_status = 'active'")->fetchColumn();
        $facultyCount = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE role = 'faculty' AND account_status = 'active'")->fetchColumn();

        // Resource counts
        $resourcesTotal = (int)$this->db->query("SELECT COUNT(*) FROM resources")->fetchColumn();
        $resourcesAvailable = (int)$this->db->query("SELECT COUNT(*) FROM resources WHERE availability_status = 'available' AND moderation_status = 'approved'")->fetchColumn();
        $resourcesPendingMod = (int)$this->db->query("SELECT COUNT(*) FROM resources WHERE moderation_status = 'pending_approval'")->fetchColumn();

        // Transactions
        $transactionsActive = (int)$this->db->query("SELECT COUNT(*) FROM borrowing_transactions WHERE transaction_status = 'active'")->fetchColumn();
        $transactionsOverdue = (int)$this->db->query("SELECT COUNT(*) FROM borrowing_transactions WHERE transaction_status = 'overdue'")->fetchColumn();
        $transactionsCompleted = (int)$this->db->query("SELECT COUNT(*) FROM borrowing_transactions WHERE transaction_status = 'returned'")->fetchColumn();

        // Donations & Exchanges
        $donationsCompleted = (int)$this->db->query("SELECT COUNT(*) FROM donations WHERE status = 'completed'")->fetchColumn();
        $exchangesCompleted = (int)$this->db->query("SELECT COUNT(*) FROM exchanges WHERE status = 'completed'")->fetchColumn();

        // Reports
        $reportsPending = (int)$this->db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

        // Category Distribution (for Chart.js)
        $catStmt = $this->db->query("
            SELECT c.category_name, COUNT(r.id) as count
            FROM categories c
            LEFT JOIN resources r ON c.id = r.category_id
            GROUP BY c.id, c.category_name
            ORDER BY count DESC
        ");
        $categoryData = $catStmt->fetchAll();

        // Sharing Method Distribution (for Chart.js)
        $methodStmt = $this->db->query("
            SELECT sharing_method, COUNT(*) as count
            FROM resources
            GROUP BY sharing_method
        ");
        $methodData = $methodStmt->fetchAll();

        // Recent System Activity
        $recentTrans = $this->db->query("
            SELECT bt.*, r.title, owner.full_name as owner_name, borrower.full_name as borrower_name
            FROM borrowing_transactions bt
            JOIN resources r ON bt.resource_id = r.id
            JOIN users owner ON bt.owner_id = owner.id
            JOIN users borrower ON bt.borrower_id = borrower.id
            ORDER BY bt.created_at DESC
            LIMIT 5
        ")->fetchAll();

        return [
            'users_total'            => $usersTotal,
            'students_count'         => $studentsCount,
            'faculty_count'          => $facultyCount,
            'resources_total'        => $resourcesTotal,
            'resources_available'    => $resourcesAvailable,
            'resources_pending_mod'  => $resourcesPendingMod,
            'transactions_active'    => $transactionsActive,
            'transactions_overdue'   => $transactionsOverdue,
            'transactions_completed' => $transactionsCompleted,
            'donations_completed'    => $donationsCompleted,
            'exchanges_completed'    => $exchangesCompleted,
            'reports_pending'        => $reportsPending,
            'category_chart'         => $categoryData,
            'method_chart'           => $methodData,
            'recent_transactions'    => $recentTrans
        ];
    }

    /**
     * Generate CSV export for reports with formula injection prevention
     */
    public function exportTransactionsCsv(array $filters = []): void {
        $sql = "
            SELECT bt.id, r.title as resource_title, c.category_name,
                   owner.full_name as owner_name, owner.email as owner_email,
                   borrower.full_name as borrower_name, borrower.email as borrower_email,
                   bt.handover_at, bt.due_at, bt.returned_at, bt.transaction_status,
                   bt.damage_notes, bt.created_at
            FROM borrowing_transactions bt
            JOIN resources r ON bt.resource_id = r.id
            JOIN categories c ON r.category_id = c.id
            JOIN users owner ON bt.owner_id = owner.id
            JOIN users borrower ON bt.borrower_id = borrower.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND bt.transaction_status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['from_date'])) {
            $sql .= " AND bt.created_at >= ?";
            $params[] = $filters['from_date'] . ' 00:00:00';
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND bt.created_at <= ?";
            $params[] = $filters['to_date'] . ' 23:59:59';
        }

        $sql .= " ORDER BY bt.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Output CSV headers
        $filename = 'campus_resource_transactions_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // CSV Header row
        fputcsv($output, [
            'Transaction ID',
            'Resource Title',
            'Category',
            'Owner Name',
            'Owner Email',
            'Borrower Name',
            'Borrower Email',
            'Handover Date',
            'Due Date',
            'Returned Date',
            'Status',
            'Notes / Condition',
            'Created Date'
        ]);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'],
                sanitize_for_csv($row['resource_title']),
                sanitize_for_csv($row['category_name']),
                sanitize_for_csv($row['owner_name']),
                sanitize_for_csv($row['owner_email']),
                sanitize_for_csv($row['borrower_name']),
                sanitize_for_csv($row['borrower_email']),
                $row['handover_at'],
                $row['due_at'],
                $row['returned_at'] ?? 'N/A',
                strtoupper($row['transaction_status']),
                sanitize_for_csv($row['damage_notes'] ?? ''),
                $row['created_at']
            ]);
        }

        fclose($output);
        exit;
    }
}
