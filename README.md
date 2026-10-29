# Campus Resource Sharing Platform

> A full-stack, responsive, and secure educational resource-sharing web application built for colleges and universities. Developed with **Core PHP 8.2**, **MySQL / MariaDB**, **HTML5**, **CSS3**, **Vanilla JavaScript**, and **Bootstrap 5**.

---

## 1. Project Overview

The **Campus Resource Sharing Platform** enables students and faculty members across academic departments to lend, borrow, exchange, and donate educational materials—including curriculum textbooks, lecture notes, scientific calculators, laboratory apparatus, microcontroller starter kits, and sports gear.

### Key Objectives
- **Financial Relief**: Reduces educational expenditure by encouraging peer-to-peer sharing.
- **Environmental Sustainability**: Prevents academic resource wastage through circular campus reuse.
- **Accountability & Trust**: Enforces institutional verification, automated overdue tracking, and damage condition ratings.
- **Academic Collaboration**: Facilitates structured communication and collection handovers between students and professors.

---

## 2. Technology Stack

- **Frontend**: HTML5, CSS3 (Modern Light Glassmorphism), Bootstrap 5.3, Bootstrap Icons 1.11, Chart.js 4.4, Vanilla JavaScript.
- **Backend**: Core PHP 8.2 (Compatible with PHP 8.0 - 8.3), PHP Sessions, PHP Data Objects (PDO) with prepared statements.
- **Database**: MySQL 5.7+ / MariaDB 10.4+ with InnoDB engine and ACID transaction locks.
- **Runtime Environment**: Windows with XAMPP (Apache 2.4 + MySQL 3306).
- **Restrictions Adherence**: Zero Node.js, zero React/Angular/Vue, zero Laravel or frameworks. Pure Core PHP and MySQL.

---

## 3. Demo Credentials (Ready for Viva & Evaluation)

| Role | Email Address | Password | Campus / Roll ID | Notes |
| :--- | :--- | :--- | :--- | :--- |
| **Platform Administrator** | `admin@campus.edu` | `Admin@12345` | `ADM-2026-001` | Full administrative controls, reports, settings |
| **Faculty Member** | `prof.sharma@campus.edu` | `Faculty@12345` | `FAC-BCA-01` | Associate Professor & HOD (BCA) |
| **Student (Lender)** | `rahul.sharma@student.campus.edu` | `Student@12345` | `BCA-2024-042` | BCA Semester 5 student with active listings |
| **Student (Borrower)** | `priya.patel@student.campus.edu` | `Student@12345` | `BCA-2024-089` | BCA Semester 5 student with active borrows |
| **Student (Final Year)** | `amit.kumar@student.campus.edu` | `Student@12345` | `CSE-2023-112` | B.Tech CSE Semester 7 student |

---

## 4. Local Installation and Setup on Windows (XAMPP)

### Step 1: Install & Launch XAMPP
1. Download and install [XAMPP for Windows](https://www.apachefriends.org/) (PHP 8.2 recommended).
2. Open the **XAMPP Control Panel**.
3. Click **Start** on both **Apache** and **MySQL**. Verify that both modules display green indicators.

### Step 2: Deploy Project to `htdocs`
Place the `campus-resource-sharing` folder directly into your XAMPP web root directory:
```
C:\xampp\htdocs\campus-resource-sharing
```
*(Alternatively, on Windows NTFS, you can create a Directory Junction pointing to the project location: `mklink /J "C:\xampp\htdocs\campus-resource-sharing" "C:\path\to\campus-resource-sharing"`).*

### Step 3: Create & Import the Database
1. Open your web browser and navigate to **phpMyAdmin**: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
2. Click **New** on the left sidebar to create a database named:
   ```sql
   campus_resource_sharing
   ```
   Select collation: `utf8mb4_unicode_ci` and click **Create**.
3. Select the `campus_resource_sharing` database and navigate to the **Import** tab.
4. Import the following two SQL files in sequence:
   - First: `database/schema.sql` (Creates all 15 relational tables, indexes, and constraints).
   - Second: `database/seed.sql` (Populates sample departments, accounts, listings, transactions, and announcements).

*Alternatively, from the command prompt or PowerShell:*
```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u root -e "CREATE DATABASE IF NOT EXISTS campus_resource_sharing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
& 'C:\xampp\mysql\bin\mysql.exe' -u root campus_resource_sharing -e "source database/schema.sql"
& 'C:\xampp\mysql\bin\mysql.exe' -u root campus_resource_sharing -e "source database/seed.sql"
```

### Step 4: Verify Database Connection Configuration
Review `config/database.php`. By default, XAMPP uses:
```php
Host: 127.0.0.1
Port: 3306
Database: campus_resource_sharing
User: root
Password: (empty string)
```
If your MySQL root user has a password, set it in `config/database.php` or create a `.env` file from `.env.example`.

### Step 5: Launch the Application
Open any modern web browser (Chrome, Edge, Firefox) and navigate to:
```
http://localhost/campus-resource-sharing/
```

---

## 5. Automated Test Suite Execution

The application comes with an automated PHP test runner covering database integrity, security, password hashing, CSRF verification, resource discovery, borrowing workflows, concurrency locks, messaging, and audit logging.

Run the test suite using PHP CLI:
```powershell
& 'C:\xampp\php\php.exe' tests/run_tests.php
```

Expected output:
```
Total Assertions: 49
Passed: 49
Failed: 0
ALL TESTS PASSED WITH 100% SUCCESS RATE!
```

---

## 6. Functional Workflows

### A. Student Borrowing Workflow
1. Browse or search resources by category, condition, or department via `student/browse_resources.php`.
2. Open resource details and submit a request with intended pickup date and duration.
3. The owner reviews incoming requests under `student/lending_requests.php` and clicks **Approve**.
4. Both parties coordinate physical handover using the integrated chat (`student/messages.php`).
5. Upon physical exchange, the owner clicks **Confirm Handover**, transitioning the transaction to **Active** and locking in the calculated return deadline.
6. When returned, the owner clicks **Confirm Return**, inspects the condition, and restores available inventory.

### B. Free Donation Workflow
1. Users list surplus textbooks or equipment for permanent giveaway under `student/add_resource.php` (Sharing Method: Free Donation).
2. Junior students browse available donations via `student/donations.php`.
3. Upon collection, the owner confirms handover, completing the donation and archiving the transaction.

### C. Barter / Item Exchange Workflow
1. An owner lists an item (e.g. an 8051 Microcontroller Kit) stating desired items in return.
2. Another student submits an exchange proposal with their offered item description via `student/exchanges.php`.
3. The owner reviews and accepts the proposal.
4. After campus trade, both confirm completion.

### D. Faculty & Administrator Portals
- **Faculty Dashboard** (`faculty/dashboard.php`): Share curriculum materials, review departmental student requests, and broadcast academic notices.
- **Admin Console** (`admin/dashboard.php`): Monitor real-time campus metrics, inspect category distribution charts, moderate listings, resolve student reports, suspend violating users, review audit trails, and export clean CSV reports.

---

## 7. Troubleshooting & Common Issues

| Issue | Root Cause | Solution |
| :--- | :--- | :--- |
| **Apache Port 80 Conflict** | Port 80 occupied by IIS, Skype, or World Wide Web Publishing Service | Open XAMPP -> Config -> `httpd.conf` and change `Listen 80` to `Listen 8080`, then access `http://localhost:8080/campus-resource-sharing/` |
| **MySQL Won't Start (Port 3306 in use)** | Existing MySQL instance running as Windows Service | Stop the standalone MySQL service via `services.msc` or change MySQL port in XAMPP to `3307` |
| **Access Denied (403) on Admin Pages** | Logged-in account does not possess `admin` privileges | Log out and sign in using the administrator credentials (`admin@campus.edu` / `Admin@12345`) |
| **Image Uploads Exceeding Limit** | PHP default post/upload limits | The application enforces 5MB. In `php.ini`, ensure `upload_max_filesize = 10M` and `post_max_size = 12M` |

---

## 8. Viva Examination Preparation Guide

For students presenting this project during a BCA / B.Tech viva:

1. **Problem Statement**:
   Many students purchase expensive textbooks, scientific calculators, and lab tools for a single semester. Once exams conclude, these valuable items remain idle. Campus Resource Sharing establishes a localized, trusted circular sharing network that slashes student educational expenses.

2. **Why Core PHP without Frameworks?**:
   Core PHP demonstrates an in-depth understanding of fundamental web mechanics—including session lifecycles, HTTP headers, PDO prepared statements, raw SQL normalization, input escaping, and manual cryptographic token verification—without relying on framework abstraction.

3. **Concurrency & Race Conditions**:
   When approving a borrow request for a single-copy resource, simultaneous requests could cause a double-booking. We mitigate this using InnoDB database transactions (`$pdo->beginTransaction()`) combined with `FOR UPDATE` row-level locks and availability quantity verification before committing.

4. **Security Controls**:
   - Authentication: Bcrypt hashing (`password_hash()`, `password_verify()`).
   - Session Security: Session regeneration upon login to prevent session fixation.
   - CSRF Defense: Cryptographic tokens verified via `hash_equals()`.
   - Injection Prevention: 100% PDO prepared statements for SQL; `htmlspecialchars()` for XSS; leading-character prefixing for CSV formula injection.
   - File Uploads: Verified server-side via `finfo` MIME inspection, size caps, randomized naming, and `.htaccess` script execution denial.

---

## 9. License & Institutional Attribution
Built as an educational open campus utility for **Apex Institute of Technology & Management**.
Licensed under the MIT License.
