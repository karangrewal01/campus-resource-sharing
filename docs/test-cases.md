# Campus Resource Sharing Platform - Test Cases & Quality Assurance

## 1. Test Execution Summary
- **Execution Engine**: PHP CLI Test Runner (`tests/run_tests.php`)
- **Environment**: Windows 11, PHP 8.2.12 (ZTS Visual C++ 2019 x64), MariaDB 10.4.32, Apache 2.4.58 (Win64)
- **Total Assertions**: 49
- **Passed**: 49
- **Failed**: 0
- **Success Rate**: 100.0%

---

## 2. Detailed Test Cases Matrix

| Test ID | Module / Feature | Test Objective | Test Input / Action | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-DB-01** | Database | Verify PDO Connection | `Database::getConnection()` | Valid PDO instance returned with `ERRMODE_EXCEPTION` | Connected to `campus_resource_sharing` | **PASS** |
| **TC-DB-02** | Schema | Verify Table Existence | `SHOW TABLES` | 15 essential schema tables exist | All 15 tables confirmed present | **PASS** |
| **TC-SEC-01** | Security | Password Hashing | `password_hash($pass, PASSWORD_BCRYPT)` | Bcrypt hash generated & verified via `password_verify` | Password verified successfully | **PASS** |
| **TC-SEC-02** | Security | Invalid Password | `password_verify('Wrong', $hash)` | Verification returns `false` | Returns `false` | **PASS** |
| **TC-SEC-03** | Security | CSRF Token Generation | `get_csrf_token()` | Generates 64-character cryptographic hex string | Valid 64-char token generated | **PASS** |
| **TC-SEC-04** | Security | CSRF Token Match | `verify_csrf_token($validToken)` | Validation returns `true` | Returns `true` | **PASS** |
| **TC-SEC-05** | Security | CSRF Token Forgery | `verify_csrf_token('fake_token')` | Validation returns `false` | Returns `false` | **PASS** |
| **TC-SEC-06** | Security | XSS Prevention | `e('<script>alert("XSS")</script>')` | Characters `<>` escaped to HTML entities | Returns `&lt;script&gt;...` | **PASS** |
| **TC-SEC-07** | Security | CSV Formula Injection | `sanitize_for_csv("=cmd|...")` | Prefixes dangerous leading character with single quote | Returns `'=cmd|...` | **PASS** |
| **TC-SEC-08** | Security | RBAC Authorization | Student accessing `admin/dashboard.php` | Server rejects request with HTTP 403 Forbidden | HTTP 403 Access Denied | **PASS** |
| **TC-SEC-09** | Security | Unauthenticated Guard | Guest accessing protected page | Server redirects with HTTP 302 to `login.php` | HTTP 302 Redirect to `login.php` | **PASS** |
| **TC-RES-01** | Resources | List Resource | `ResourceService::create(...)` | Row inserted in `resources`, returns ID | Inserted successfully | **PASS** |
| **TC-RES-02** | Resources | Keyword Search | Query `Clean Architecture` | Matches title and description | Resource returned in search results | **PASS** |
| **TC-RES-03** | Resources | Category Filter | Filter by `category_id = 1` | Returns only items under Textbooks | Filter applied accurately | **PASS** |
| **TC-REQ-01** | Requests | Submit Request | Student requests available resource | Row created with status `pending` | Request submitted successfully | **PASS** |
| **TC-REQ-02** | Requests | Prevent Duplicate Request | Student requests same resource again | Service blocks duplicate submission | Blocked with descriptive error message | **PASS** |
| **TC-REQ-03** | Requests | Prevent Owner Request | Owner requests own item | Service blocks request | Blocked with descriptive error message | **PASS** |
| **TC-TXN-01** | Transactions | Concurrency Approval | `RequestService::approveRequest(...)` | Runs inside PDO transaction, locks row, reserves item | Approved; `available_quantity` drops to 0 | **PASS** |
| **TC-TXN-02** | Transactions | Record Handover | `TransactionService::recordHandover(...)` | Sets start & due dates, marks `borrowed` | Transaction created with active status | **PASS** |
| **TC-TXN-03** | Transactions | Confirm Return | `TransactionService::recordReturn(...)` | Sets return timestamp, restores available quantity | Transaction marked `returned`; stock restored | **PASS** |
| **TC-TXN-04** | Integrity | Historical Preservation | Delete resource with transaction history | Foreign key integrity preserved; row archived | Soft archived to preserve records | **PASS** |
| **TC-MSG-01** | Messaging | Request Chat | `MessageService::sendMessage(...)` | Message stored in `messages` table | Message stored and read receipt tracked | **PASS** |
| **TC-MSG-02** | Messaging | Chat Privacy | Unrelated user accessing request chat | Service returns empty array (Access Denied) | Access strictly blocked | **PASS** |
| **TC-NTF-01** | Notifications | In-App Alert Delivery | Actions trigger notification creation | Notifications table populated with unread count | Unread count tracked accurately | **PASS** |
| **TC-ANL-01** | Analytics | Real Metrics | `AnalyticsService::getAdminMetrics()` | Counts derived from real SQL records | Non-zero verified metrics returned | **PASS** |
| **TC-DON-01** | Donations | Free Donation Transfer | Handover confirmed on donation listing | Permanent transfer recorded | Completed cleanly | **PASS** |
| **TC-EXC-01** | Exchanges | Barter Exchange | Proposal submitted and agreed | Both items reserved and exchange logged | Processed smoothly | **PASS** |
