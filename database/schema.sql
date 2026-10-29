-- Campus Resource Sharing Platform
-- Database Schema Definition
-- Compatible with MySQL 5.7+ / MariaDB 10.3+

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `conversations`;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `exchanges`;
DROP TABLE IF EXISTS `donations`;
DROP TABLE IF EXISTS `borrowing_transactions`;
DROP TABLE IF EXISTS `resource_requests`;
DROP TABLE IF EXISTS `resources`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `settings`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Departments Table
CREATE TABLE `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `department_name` VARCHAR(100) NOT NULL,
    `department_code` VARCHAR(20) NOT NULL UNIQUE,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users Table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `institution_id` VARCHAR(50) NOT NULL UNIQUE,
    `department_id` INT NULL,
    `role` ENUM('student', 'faculty', 'admin') NOT NULL DEFAULT 'student',
    `course` VARCHAR(100) NULL,
    `semester` INT NULL,
    `designation` VARCHAR(100) NULL,
    `phone` VARCHAR(30) NULL,
    `profile_image` VARCHAR(255) NULL,
    `account_status` ENUM('active', 'suspended', 'pending') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`account_status`),
    CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Categories Table
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon_class` VARCHAR(50) DEFAULT 'bi-box-seam',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Resources Table
CREATE TABLE `resources` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `department_id` INT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `resource_type` ENUM('book', 'notes', 'calculator', 'stationery', 'electronics', 'lab_equipment', 'engineering_tool', 'sports', 'art_supplies', 'hostel_item', 'other') NOT NULL DEFAULT 'book',
    `sharing_method` ENUM('borrow', 'donate', 'exchange', 'temporary_use') NOT NULL DEFAULT 'borrow',
    `condition_status` ENUM('new', 'like_new', 'good', 'fair', 'poor') NOT NULL DEFAULT 'good',
    `total_quantity` INT NOT NULL DEFAULT 1,
    `available_quantity` INT NOT NULL DEFAULT 1,
    `borrowing_duration_days` INT NOT NULL DEFAULT 7,
    `exchange_requirements` TEXT NULL,
    `collection_instructions` TEXT NULL,
    `usage_instructions` TEXT NULL,
    `image_path` VARCHAR(255) NULL,
    `course_semester_tag` VARCHAR(100) NULL,
    `availability_status` ENUM('available', 'reserved', 'borrowed', 'unavailable', 'under_review') NOT NULL DEFAULT 'available',
    `moderation_status` ENUM('approved', 'pending_approval', 'rejected') NOT NULL DEFAULT 'approved',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_res_owner` (`owner_id`),
    INDEX `idx_res_category` (`category_id`),
    INDEX `idx_res_dept` (`department_id`),
    INDEX `idx_res_method` (`sharing_method`),
    INDEX `idx_res_status` (`availability_status`),
    INDEX `idx_res_moderation` (`moderation_status`),
    INDEX `idx_res_type` (`resource_type`),
    INDEX `idx_res_condition` (`condition_status`),
    CONSTRAINT `fk_resources_owner` FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_resources_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_resources_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Resource Requests Table
CREATE TABLE `resource_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `resource_id` INT NOT NULL,
    `requester_id` INT NOT NULL,
    `request_type` ENUM('borrow', 'donate', 'exchange', 'temporary_use') NOT NULL DEFAULT 'borrow',
    `requested_start_date` DATE NOT NULL,
    `requested_duration_days` INT NOT NULL DEFAULT 7,
    `proposed_return_date` DATE NOT NULL,
    `purpose` TEXT NULL,
    `quantity_requested` INT NOT NULL DEFAULT 1,
    `status` ENUM('pending', 'approved', 'rejected', 'cancelled', 'ready_for_collection', 'borrowed', 'return_pending', 'returned', 'overdue', 'disputed') NOT NULL DEFAULT 'pending',
    `decision_reason` TEXT NULL,
    `alternative_pickup_date` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_req_resource` (`resource_id`),
    INDEX `idx_req_requester` (`requester_id`),
    INDEX `idx_req_status` (`status`),
    CONSTRAINT `fk_requests_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_requests_requester` FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Borrowing Transactions Table
CREATE TABLE `borrowing_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT NOT NULL UNIQUE,
    `resource_id` INT NOT NULL,
    `owner_id` INT NOT NULL,
    `borrower_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `handover_at` DATETIME NOT NULL,
    `due_at` DATETIME NOT NULL,
    `returned_at` DATETIME NULL,
    `transaction_status` ENUM('active', 'returned', 'overdue', 'damaged', 'lost', 'disputed') NOT NULL DEFAULT 'active',
    `condition_on_return` VARCHAR(50) NULL,
    `damage_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_trans_res` (`resource_id`),
    INDEX `idx_trans_owner` (`owner_id`),
    INDEX `idx_trans_borrower` (`borrower_id`),
    INDEX `idx_trans_status` (`transaction_status`),
    INDEX `idx_trans_due` (`due_at`),
    CONSTRAINT `fk_trans_request` FOREIGN KEY (`request_id`) REFERENCES `resource_requests`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_trans_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_trans_owner` FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_trans_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Donations Table
CREATE TABLE `donations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `resource_id` INT NOT NULL,
    `donor_id` INT NOT NULL,
    `recipient_id` INT NOT NULL,
    `request_id` INT NOT NULL UNIQUE,
    `handover_at` DATETIME NULL,
    `completion_at` DATETIME NULL,
    `status` ENUM('pending', 'handover_scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_don_resource` (`resource_id`),
    INDEX `idx_don_donor` (`donor_id`),
    INDEX `idx_don_recipient` (`recipient_id`),
    INDEX `idx_don_status` (`status`),
    CONSTRAINT `fk_don_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_don_donor` FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_don_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_don_request` FOREIGN KEY (`request_id`) REFERENCES `resource_requests`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Exchanges Table
CREATE TABLE `exchanges` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `proposer_id` INT NOT NULL,
    `listing_resource_id` INT NOT NULL,
    `offered_resource_id` INT NULL,
    `offered_item_description` TEXT NULL,
    `proposed_terms` TEXT NULL,
    `request_id` INT NULL,
    `status` ENUM('proposed', 'accepted', 'rejected', 'handover_pending', 'completed', 'cancelled') NOT NULL DEFAULT 'proposed',
    `agreed_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_exc_proposer` (`proposer_id`),
    INDEX `idx_exc_listing` (`listing_resource_id`),
    INDEX `idx_exc_status` (`status`),
    CONSTRAINT `fk_exc_proposer` FOREIGN KEY (`proposer_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_exc_listing` FOREIGN KEY (`listing_resource_id`) REFERENCES `resources`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_exc_offered` FOREIGN KEY (`offered_resource_id`) REFERENCES `resources`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Conversations Table (Linked to Request)
CREATE TABLE `conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `resource_request_id` INT NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_conv_request` FOREIGN KEY (`resource_request_id`) REFERENCES `resource_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Messages Table
CREATE TABLE `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `message_body` TEXT NOT NULL,
    `read_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_msg_conv` (`conversation_id`),
    INDEX `idx_msg_sender` (`sender_id`),
    CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Notifications Table
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `notification_type` VARCHAR(50) NOT NULL DEFAULT 'system',
    `related_resource_id` INT NULL,
    `related_request_id` INT NULL,
    `read_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_notif_user` (`user_id`),
    INDEX `idx_notif_read` (`read_at`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Reports Table
CREATE TABLE `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT NOT NULL,
    `resource_id` INT NOT NULL,
    `report_reason` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('pending', 'investigating', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending',
    `resolved_by` INT NULL,
    `resolution_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` DATETIME NULL,
    INDEX `idx_rep_status` (`status`),
    INDEX `idx_rep_resource` (`resource_id`),
    CONSTRAINT `fk_rep_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rep_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rep_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Announcements Table
CREATE TABLE `announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT NOT NULL,
    `created_by` INT NOT NULL,
    `audience` ENUM('all', 'students', 'faculty') NOT NULL DEFAULT 'all',
    `published_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'published',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ann_status` (`status`),
    INDEX `idx_ann_audience` (`audience`),
    CONSTRAINT `fk_ann_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Settings Table
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Audit Logs Table
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `actor_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_actor` (`actor_id`),
    INDEX `idx_audit_action` (`action`),
    CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
