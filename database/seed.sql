-- Campus Resource Sharing Platform
-- Seed Data for Testing and Live Demonstrations
-- All data included is verified sample institutional data.

-- 1. Departments
INSERT INTO `departments` (`id`, `department_name`, `department_code`, `status`) VALUES
(1, 'Computer Applications (BCA/MCA)', 'BCA', 'active'),
(2, 'Computer Science & Engineering', 'CSE', 'active'),
(3, 'Information Technology', 'IT', 'active'),
(4, 'Electronics & Communication', 'ECE', 'active'),
(5, 'Mechanical Engineering', 'ME', 'active'),
(6, 'Management & Commerce', 'BBA', 'active'),
(7, 'Basic Sciences & Humanities', 'BSH', 'active');

-- 2. System Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('institution_name', 'Apex Institute of Technology & Management'),
('institution_short_name', 'Apex Campus'),
('campus_contact_email', 'resources@apex.edu'),
('campus_help_desk', 'Academic Block A, Room 104'),
('default_borrow_days', '7'),
('max_active_requests_per_user', '3'),
('max_active_borrowed_items', '2'),
('overdue_grace_hours', '24'),
('allowed_email_domains', 'campus.edu,student.campus.edu,apex.edu'),
('require_listing_moderation', '0'),
('enable_in_app_messaging', '1'),
('terms_version', '1.0');

-- 3. Users
-- Passwords:
-- Admin: Admin@12345
-- Faculty: Faculty@12345
-- Students: Student@12345
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `institution_id`, `department_id`, `role`, `course`, `semester`, `designation`, `phone`, `account_status`) VALUES
(1, 'Platform Administrator', 'admin@campus.edu', '$2y$10$vTeJW6Fw8G9u0kK1nbZwrueaayVNxVMDSuFxgRWbXppJ1cc7Mel4e', 'ADM-2026-001', 1, 'admin', NULL, NULL, 'Chief Systems Administrator', '+91 9876543210', 'active'),
(2, 'Dr. Rajesh Sharma', 'prof.sharma@campus.edu', '$2y$10$2UTEdlXKgb7A8/q/QjdXrOreuN2hnT1o.zjkhYzK9lu8uuG/3izU2', 'FAC-BCA-01', 1, 'faculty', NULL, NULL, 'Associate Professor & HOD', '+91 9876543211', 'active'),
(3, 'Dr. Ananya Verma', 'dr.verma@campus.edu', '$2y$10$2UTEdlXKgb7A8/q/QjdXrOreuN2hnT1o.zjkhYzK9lu8uuG/3izU2', 'FAC-CSE-02', 2, 'faculty', NULL, NULL, 'Assistant Professor (CSE)', '+91 9876543212', 'active'),
(4, 'Rahul Sharma', 'rahul.sharma@student.campus.edu', '$2y$10$Rapu.hN9tLifZIamIqsyU.j1SzwhbmNPS7a3VKnBhbhy0Gzs2ki3a', 'BCA-2024-042', 1, 'student', 'Bachelor of Computer Applications', 5, NULL, '+91 9876543220', 'active'),
(5, 'Priya Patel', 'priya.patel@student.campus.edu', '$2y$10$Rapu.hN9tLifZIamIqsyU.j1SzwhbmNPS7a3VKnBhbhy0Gzs2ki3a', 'BCA-2024-089', 1, 'student', 'Bachelor of Computer Applications', 5, NULL, '+91 9876543221', 'active'),
(6, 'Amit Kumar', 'amit.kumar@student.campus.edu', '$2y$10$Rapu.hN9tLifZIamIqsyU.j1SzwhbmNPS7a3VKnBhbhy0Gzs2ki3a', 'CSE-2023-112', 2, 'student', 'B.Tech Computer Science', 7, NULL, '+91 9876543222', 'active'),
(7, 'Sneha Reddy', 'sneha.reddy@student.campus.edu', '$2y$10$Rapu.hN9tLifZIamIqsyU.j1SzwhbmNPS7a3VKnBhbhy0Gzs2ki3a', 'ECE-2024-015', 4, 'student', 'B.Tech Electronics & Comm', 3, NULL, '+91 9876543223', 'active');

-- 4. Categories
INSERT INTO `categories` (`id`, `category_name`, `slug`, `description`, `icon_class`, `status`) VALUES
(1, 'Textbooks & Reference Books', 'textbooks', 'Prescribed curriculum textbooks, reference handbooks, and competitive guides.', 'bi-book-half', 'active'),
(2, 'Academic Notes & Study Material', 'notes', 'Handwritten lecture notes, solved previous year papers, and lab manuals.', 'bi-journal-text', 'active'),
(3, 'Calculators & Stationery', 'stationery', 'Scientific calculators, drawing boards, mini-drafters, geometry kits.', 'bi-calculator', 'active'),
(4, 'Electronics & Accessories', 'electronics', 'Arduino boards, Raspberry Pi, sensors, breadboards, jumper wires, multimeter.', 'bi-cpu', 'active'),
(5, 'Laboratory Equipment', 'lab-equipment', 'Lab coats, safety goggles, dissection boxes, test tube racks, optical prisms.', 'bi-eyedropper', 'active'),
(6, 'Project & Engineering Tools', 'engineering-tools', 'Soldering irons, crimping tools, wire strippers, mechanical toolkits.', 'bi-tools', 'active'),
(7, 'Sports Equipment', 'sports', 'Badminton sets, table tennis paddles, footballs, volleyballs, chess boards.', 'bi-dribbble', 'active'),
(8, 'Art & Creative Supplies', 'art-supplies', 'Canvas boards, acrylic paint sets, easel stands, sculpting tools.', 'bi-palette', 'active'),
(9, 'Hostel & Daily-Use Items', 'hostel-items', 'Study lamps, extension cords, foldable study tables, room heaters.', 'bi-lamp', 'active'),
(10, 'Other Approved Resources', 'other', 'Miscellaneous academic, department, and campus sharing utilities.', 'bi-grid', 'active');

-- 5. Resources
INSERT INTO `resources` (`id`, `owner_id`, `category_id`, `department_id`, `title`, `description`, `resource_type`, `sharing_method`, `condition_status`, `total_quantity`, `available_quantity`, `borrowing_duration_days`, `exchange_requirements`, `collection_instructions`, `usage_instructions`, `image_path`, `course_semester_tag`, `availability_status`, `moderation_status`, `created_at`) VALUES
(1, 2, 1, 1, 'Database System Concepts (7th Edition) - Silberschatz & Korth', 'Standard reference textbook for Database Management Systems. Hardcover edition with minimal pencil annotations. Ideal for BCA Sem 3/4 or CSE Sem 4 DBMS course.', 'book', 'borrow', 'good', 1, 1, 14, NULL, 'Collect from Faculty Cabin F-12 during department office hours (2:00 PM - 4:00 PM).', 'Handle with care. Do not write with ink or highlight pages.', NULL, 'BCA Sem 4 / CSE Sem 4', 'available', 'approved', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(2, 4, 1, 1, 'Core Java: An Integrated Approach by R. Nageswara Rao', 'Comprehensive guide covering Core Java, Collections, Multithreading, and JDBC. Clean condition with index tabs for quick revision.', 'book', 'borrow', 'like_new', 1, 0, 7, NULL, 'Meet outside Central Library ground floor entrance after 4:30 PM.', 'Please return promptly before semester lab exams.', NULL, 'BCA Sem 3', 'borrowed', 'approved', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(3, 4, 2, 1, 'Computer Networks & Security - BCA 5th Sem Complete Handwritten Notes', 'Neatly organized handwritten lecture notes with color diagrams for OSI 7 layers, TCP/IP, IP addressing, routing algorithms, and cryptography.', 'notes', 'donate', 'good', 1, 0, 0, NULL, 'Collect outside CS Department corridor or near Central Cafeteria.', 'Free for junior students. Keep it or pass it on to the next batch.', NULL, 'BCA Sem 5', 'reserved', 'approved', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(4, 5, 3, 1, 'Casio fx-991EX ClassWiz Scientific Calculator', 'High-resolution display scientific calculator with matrix, vector calculations, and equation solver. Fully functional with fresh batteries.', 'calculator', 'borrow', 'like_new', 1, 1, 7, NULL, 'Library reading hall between 1:00 PM and 2:00 PM.', 'Keep in protective hard slide case when not in use.', NULL, 'All Semesters', 'available', 'approved', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(5, 6, 4, 2, 'Arduino Uno R3 Starter Kit with Sensor Bundle', 'Original ATmega328P Arduino Uno board with ultrasonic sensor, PIR motion sensor, servo motor, 16x2 LCD display, breadboard, and jumper cables.', 'electronics', 'borrow', 'good', 1, 0, 10, NULL, 'CSE IoT Research Lab (Block B, Room 204).', 'Handle static-sensitive components carefully on anti-static mat.', NULL, 'CSE / ECE Final Year', 'borrowed', 'approved', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(6, 7, 4, 4, 'Digital Multimeter with Test Probes (Mastech MAS830L)', 'Backlit LCD digital multimeter capable of measuring AC/DC voltage, DC current, resistance, diode check, and continuity buzzer. 9V battery included.', 'electronics', 'borrow', 'like_new', 1, 1, 5, NULL, 'ECE Hardware Lab 1 (Block C).', 'Verify probe orientation before high current measurements.', NULL, 'ECE Sem 3', 'available', 'approved', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(7, 6, 6, 2, '8051 Microcontroller Development Board & ISP Programmer', 'AT89S52 development board with on-board LEDs, 7-segment displays, and USB ISP programmer cable.', 'engineering_tool', 'exchange', 'good', 1, 1, 0, 'Willing to exchange for Raspberry Pi Pico W or ESP32 NodeMCU board for wireless IoT project.', 'Meet near Academic Block B cafeteria entrance on weekdays.', 'Includes CD with Keil UVision examples and flash utility.', NULL, 'CSE / IT Sem 6', 'available', 'approved', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(8, 5, 7, 1, 'Yonex Nanoray Badminton Racket Pair with Shuttlecock Tube', 'Pair of lightweight graphite badminton rackets with freshly strung synthetic gut. Tube contains 3 Mavis 350 nylon shuttles.', 'sports', 'temporary_use', 'good', 1, 1, 3, NULL, 'Sports Complex reception counter between 5:00 PM and 6:30 PM.', 'Return in the protective zipped twin bag. Avoid playing on asphalt courts.', NULL, 'Open to all', 'available', 'approved', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(9, 2, 5, 7, 'Chemistry Laboratory Glassware Set & Cotton Lab Coat (Size L)', 'Set of 250ml conical flasks, beakers, graduated pipettes, and clean white cotton lab coat.', 'lab_equipment', 'donate', 'good', 1, 1, 0, NULL, 'Department Office F-10, Block A.', 'Completely free for incoming first-year students who need lab gear.', NULL, '1st Year All Branches', 'available', 'approved', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(10, 3, 1, 2, 'Introduction to Algorithms (CLRS 3rd Edition)', 'The benchmark algorithms textbook by Cormen, Leiserson, Rivest, and Stein. Excellent reference for GATE and campus placements.', 'book', 'borrow', 'good', 2, 2, 14, NULL, 'CSE Faculty Staff Room 108.', 'Treat spine with care; avoid dog-earing pages.', NULL, 'CSE / IT Sem 5', 'available', 'approved', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 6. Resource Requests
INSERT INTO `resource_requests` (`id`, `resource_id`, `requester_id`, `request_type`, `requested_start_date`, `requested_duration_days`, `proposed_return_date`, `purpose`, `quantity_requested`, `status`, `decision_reason`, `created_at`) VALUES
-- Active borrow: Priya borrowed Rahul's Core Java book
(1, 2, 5, 'borrow', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 7, DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 5 DAY), INTERVAL 7 DAY), 'Preparing for BCA 3rd Sem Java OOP practicals and end-term viva examination.', 1, 'borrowed', 'Approved for practical exam preparation.', DATE_SUB(NOW(), INTERVAL 6 DAY)),
-- Overdue borrow: Amit borrowed IoT Arduino kit from Sneha
(2, 5, 4, 'borrow', DATE_SUB(CURDATE(), INTERVAL 12 DAY), 7, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Working on Smart Agriculture Prototype for Inter-College Hackathon.', 1, 'borrowed', 'Approved. Please bring it back within 7 days.', DATE_SUB(NOW(), INTERVAL 13 DAY)),
-- Donation request: Sneha requested handwritten notes from Rahul
(3, 3, 7, 'donate', CURDATE(), 0, CURDATE(), 'I am taking Computer Networks next semester and need reference notes.', 1, 'approved', 'Glad to share these notes with a junior student.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
-- Pending request: Amit requested Korth DBMS book from Prof. Sharma
(4, 1, 6, 'borrow', CURDATE(), 10, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Need for preparing Relational Algebra and Normalization assignments.', 1, 'pending', NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
-- Returned transaction request: Priya previously borrowed Scientific Calculator from Rahul
(5, 4, 7, 'borrow', DATE_SUB(CURDATE(), INTERVAL 20 DAY), 5, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'Used for Mathematics 1 end-term exam calculations.', 1, 'returned', 'Approved for exam period.', DATE_SUB(NOW(), INTERVAL 21 DAY));

-- 7. Borrowing Transactions
INSERT INTO `borrowing_transactions` (`id`, `request_id`, `resource_id`, `owner_id`, `borrower_id`, `quantity`, `handover_at`, `due_at`, `returned_at`, `transaction_status`, `condition_on_return`, `damage_notes`, `created_at`) VALUES
-- Active transaction: Priya borrowed Core Java book from Rahul (due in 2 days)
(1, 1, 2, 4, 5, 1, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 5 DAY), INTERVAL 7 DAY), NULL, 'active', NULL, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY)),
-- Overdue transaction: Rahul borrowed Arduino Kit from Amit (due 5 days ago!)
(2, 2, 5, 6, 4, 1, DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), NULL, 'overdue', NULL, NULL, DATE_SUB(NOW(), INTERVAL 12 DAY)),
-- Returned transaction: Sneha borrowed and returned Casio calculator to Priya
(3, 5, 4, 5, 7, 1, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 16 DAY), 'returned', 'like_new', 'Returned in immaculate condition with protective sleeve.', DATE_SUB(NOW(), INTERVAL 20 DAY));

-- 8. Donations
INSERT INTO `donations` (`id`, `resource_id`, `donor_id`, `recipient_id`, `request_id`, `handover_at`, `completion_at`, `status`, `notes`, `created_at`) VALUES
(1, 3, 4, 7, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL, 'handover_scheduled', 'Handover scheduled at Central Cafeteria.', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 9. Exchanges
INSERT INTO `exchanges` (`id`, `proposer_id`, `listing_resource_id`, `offered_resource_id`, `offered_item_description`, `proposed_terms`, `request_id`, `status`, `agreed_at`, `completed_at`, `created_at`) VALUES
(1, 4, 7, NULL, 'Raspberry Pi Pico H with pre-soldered headers, micro-USB cable, and starter sensor pack.', 'One-to-one exchange for the 8051 dev kit.', NULL, 'proposed', NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 10. Conversations & Messages
INSERT INTO `conversations` (`id`, `resource_request_id`, `created_at`) VALUES
(1, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 2, DATE_SUB(NOW(), INTERVAL 12 DAY));

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `message_body`, `read_at`, `created_at`) VALUES
(1, 1, 5, 'Hi Rahul, I will collect the Core Java book near the central library at 4:30 PM as mentioned.', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 1, 4, 'Sounds good Priya! I have kept the book in my bag. See you there.', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 2, 6, 'Hi Rahul, just checking in. The Arduino Kit return deadline passed a few days ago. Are you done with the hackathon project?', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 2, 4, 'Extremely sorry Amit! Our team was presenting today. I will bring it to IoT Lab tomorrow morning 10 AM.', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 11. Notifications
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `notification_type`, `related_resource_id`, `related_request_id`, `read_at`, `created_at`) VALUES
(1, 2, 'New Borrow Request', 'Amit Kumar requested to borrow "Database System Concepts (7th Edition)".', 'request', 1, 4, NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(2, 4, 'Item Overdue Alert', 'The borrowing period for "Arduino Uno R3 Starter Kit" expired 5 days ago. Please return it to Amit Kumar immediately.', 'overdue', 5, 2, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 5, 'Upcoming Return Deadline', 'Your borrowed item "Core Java: An Integrated Approach" is due for return in 2 days.', 'return', 2, 1, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 7, 'Donation Request Approved', 'Rahul Sharma approved your donation request for "Computer Networks Handwritten Notes".', 'donation', 3, 3, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 6, 'New Exchange Proposal', 'Rahul Sharma submitted an exchange proposal for your "8051 Microcontroller Development Board".', 'exchange', 7, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 12. Announcements
INSERT INTO `announcements` (`id`, `title`, `content`, `created_by`, `audience`, `published_at`, `status`, `created_at`) VALUES
(1, 'Welcome to Campus Resource Sharing Platform', 'Welcome students and faculty members! You can now easily share, borrow, exchange, and donate academic textbooks, project tools, calculators, and lab equipment across all departments.', 1, 'all', DATE_SUB(NOW(), INTERVAL 20 DAY), 'published', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(2, 'Pre-Exam Book Return Notice', 'All students holding borrowed textbooks and calculators are requested to check their dashboard due dates and coordinate timely returns before mid-semester examinations.', 2, 'students', DATE_SUB(NOW(), INTERVAL 3 DAY), 'published', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'Faculty Lab Equipment Donation Drive', 'Faculty members are encouraged to list reusable surplus lab components, manuals, and reference materials for junior students.', 1, 'faculty', DATE_SUB(NOW(), INTERVAL 5 DAY), 'published', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- 13. Reports for Moderation
INSERT INTO `reports` (`id`, `reporter_id`, `resource_id`, `report_reason`, `description`, `status`, `created_at`) VALUES
(1, 7, 7, 'Inaccurate Specifications', 'Listing description does not specify if the programmer cable supports Windows 11 64-bit drivers.', 'pending', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 14. Audit Logs
INSERT INTO `audit_logs` (`actor_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 'SYSTEM_INITIALIZATION', 'system', 1, 'Database seeded with default departments, categories, and initial administration policies.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(2, 'RESOURCE_CREATED', 'resource', 1, 'Prof. Rajesh Sharma listed "Database System Concepts (7th Edition)".', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(4, 'RESOURCE_CREATED', 'resource', 2, 'Rahul Sharma listed "Core Java: An Integrated Approach".', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(5, 'REQUEST_CREATED', 'request', 1, 'Priya Patel requested "Core Java: An Integrated Approach".', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(4, 'REQUEST_APPROVED', 'request', 1, 'Rahul Sharma approved borrowing request #1 for Priya Patel.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 'TRANSACTION_HANDOVER', 'transaction', 1, 'Handover confirmed for transaction #1 between Rahul Sharma and Priya Patel.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 5 DAY));
