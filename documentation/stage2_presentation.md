# STAGE 2 — LWP REVIEW 02 PRESENTATION
## Supplier Delivery and Performance Monitoring System (SDPMS)

---

### SLIDE 1: TITLE SLIDE
- **Project Title:** Supplier Delivery and Performance Monitoring System (SDPMS)
- **Review Stage:** Stage 2 — Dynamic Full-Stack Implementation (LWP Review 02)
- **Student Name:** [STUDENT NAME]
- **Register Number:** [REGISTER NUMBER]
- **Degree / Branch:** B.E. Computer Science and Engineering
- **Department:** Department of Computer Science and Engineering
- **Institution:** [COLLEGE / INSTITUTION NAME]
- **Project Guide:** [FACULTY GUIDE NAME, Designation]
- **Academic Year:** 2025 – 2026

*Speaker Notes:*  
"Respected evaluators, today I present the completed Stage 2 of the Supplier Delivery and Performance Monitoring System. Building upon the Stage 1 prototype, this version establishes a secure, dynamic, multi-user web application powered by PHP 8, MySQL, and PDO prepared statements running on XAMPP."

---

### SLIDE 2: STAGE 1 RECAP & STAGE 2 TRANSITION
- **Recap of Stage 1:**
  - Demonstrated interactive frontend prototype with responsive design system and Chart.js dashboards.
  - Implemented client-side delivery delay math and reliability formulas.
  - Identified the necessity for centralized database persistence and server-side authentication.
- **Stage 2 Achievements:**
  - Built 6 normalized relational MySQL tables with foreign key constraints.
  - Migrated business logic to secure, reusable PHP 8 modules.
  - Implemented full session authentication, Bcrypt password hashing, and CSRF protection.
  - Connected all dashboard KPIs and vendor scores directly to live SQL queries.

---

### SLIDE 3: DYNAMIC APPLICATION ARCHITECTURE
- **3-Tier Enterprise Architecture:**
  - **Client Tier:** Semantic HTML5, Vanilla CSS3, Vanilla JS, Chart.js.
  - **Server Tier:** Apache 2.4 HTTP Server, PHP 8 runtime, PDO database layer.
  - **Data Tier:** MySQL 8 / MariaDB with InnoDB storage engine.
- **Backend Modular Design:**
  - `config.php`: Centralized environment constants and database configuration.
  - `database.php`: Singleton PDO connection manager with exception handling.
  - `auth.php`: Session control, role verification, and login/logout helpers.
  - `csrf.php`: Cryptographic token generation and form validation.
  - `functions.php`: Shared mathematical formulas, currency formatting, and audit logger.

---

### SLIDE 4: RELATIONAL DATABASE & ER SCHEMA
- **Normalized Relational Model (InnoDB Engine):**
  1. `users`: Stores procurement managers with Bcrypt hashes (`id`, `username`, `email`, `password_hash`, `role`).
  2. `suppliers`: Vendor master directory (`id PK`, `company_name`, `category`, `city`, `status`).
  3. `purchase_orders`: Requisitions (`id PK`, `supplier_id FK`, `order_date`, `expected_date`, `quantity`, `unit_price`, `total_amount`, `status`).
  4. `deliveries`: Physical receipts (`id PK`, `order_id FK,UK`, `expected_date`, `actual_date`, `received_qty`, `status`).
  5. `delivery_status_history`: Immutable status transitions (`delivery_id FK`, `previous_status`, `new_status`, `timestamp`).
  6. `audit_logs`: Security audit log (`user_id FK`, `action`, `table_name`, `record_id`, `details`, `timestamp`).
- **Referential Integrity:** `ON UPDATE CASCADE` and `ON DELETE RESTRICT` prevent orphaned records.

---

### SLIDE 5: SECURITY IMPLEMENTATION & OWASP COMPLIANCE
- **1. PDO Prepared Statements:**
  - Complete elimination of SQL Injection across every query through parameterized binding (`$stmt->execute([':id' => $id])`).
- **2. CSRF Token Defense:**
  - Every state-changing POST form embeds a cryptographically random 32-byte session token validated by `require_csrf_token()`.
- **3. Password Security:**
  - Standard `password_hash($pass, PASSWORD_BCRYPT)` and verification via `password_verify()`. No plaintext storage.
- **4. Session Fixation Mitigation:**
  - Session IDs are regenerated via `session_regenerate_id(true)` immediately upon successful login.
- **5. Output Escaping:**
  - All rendered data passes through `sanitize_output()` (`htmlspecialchars`) to thwart Cross-Site Scripting (XSS).

---

### SLIDE 6: CRUD OPERATIONS & BUSINESS CONSTRAINTS
- **Supplier Module (`suppliers/`):**
  - Add supplier with auto-suggested ID (`SUP-XXXX`), duplicate ID prevention, and email validation.
  - Update contact info while locking primary keys.
  - **Safety Constraint:** If a supplier has linked purchase orders, direct deletion is blocked and the vendor is safely deactivated (`status = Inactive`) to preserve historical audit logs.
- **Purchase Order Module (`purchase-orders/`):**
  - Auto-generated PO code (`PO-YYYY-XXX`).
  - Strict validation: Positive quantities, non-negative unit price, and `expected_date >= order_date`.
  - Atomic transaction: Inserts PO and automatically creates synchronized delivery consignment in `deliveries`.

---

### SLIDE 7: DELIVERY TRACKING & STATUS SYNCHRONIZATION
- **Delivery Consignment Lifecycle (`deliveries/`):**
  - Warehouse receipt updates: Records actual dock arrival date and received quantity.
  - **Constraint Checks:**
    - Received quantity cannot be negative.
    - Received quantity cannot exceed contracted ordered count.
    - Cannot mark "Delivered" if received count is 0.
    - If `received_qty < ordered_qty`, order is automatically classified as `Partially Delivered`.
- **Status Synchronization:**
  - Updating a delivery status immediately updates the linked purchase order status (`Completed`, `Partially Delivered`, `Delayed`, `Cancelled`).
  - Every update logs an immutable entry in `delivery_status_history`.

---

### SLIDE 8: DYNAMIC SUPPLIER PERFORMANCE EVALUATION
- **Live SQL Aggregation:** Metrics are calculated directly from saved MySQL records.
- **Mathematical Formulations:**
  - **On-Time Compliance Rate:**
    $$\text{On-Time \%} = \frac{\text{On-Time Completed Deliveries}}{\text{Total Eligible Completed Deliveries}} \times 100$$
  - **Average Delay Days:**
    $$\text{Avg Delay} = \frac{\sum \text{Positive Late Days}}{\text{Eligible Completed Deliveries}}$$
  - **Quantity Fulfilment Rate:**
    $$\text{Quantity \%} = \frac{\text{Total Accepted Quantity}}{\text{Total Contracted Quantity}} \times 100$$
  - **Composite Reliability Score (70/30 Weighting):**
    $$\text{Score} = (0.70 \times \text{On-Time \%}) + (0.30 \times \min(100, \text{Quantity \%}))$$
- **Benchmark Bar Chart:** Dynamically compares vendor on-time compliance and scorecards side by side using Chart.js.

---

### SLIDE 9: REPORTING, PRINT LAYOUT & CSV STREAMING
- **Reporting Parameters (`reports/`):**
  - Filters by Report Type, Supplier, Order/Delivery Status, and Custom Date Ranges.
  - Real-time summary KPI cards computing total records, cumulative order valuation, and delayed counts.
- **Print Optimization:**
  - Custom `@media print` CSS cleanly strips navigation bars, search inputs, and action buttons for clean A4 printing.
- **Safe CSV Exporter (`export_csv.php`):**
  - Directly streams output with `Content-Type: text/csv` and UTF-8 Byte Order Mark (BOM).
  - RFC 4180 quotation escaping prevents formula injection and format truncation.

---

### SLIDE 10: TESTING RESULTS & VERIFICATION MATRIX
- **Summary of Verification (15/15 Passed):**
  - **Authentication:** Valid logins accepted; invalid attempts rejected.
  - **Constraint Enforcement:** Duplicate supplier and PO IDs blocked; deletion of linked suppliers safely diverted to deactivation.
  - **Business Arithmetic:** PO valuations, delay days, and performance percentages matched manual calculations with zero division errors.
  - **Transactions:** Rollback verified upon partial database failure.
  - **Security Audits:** CSRF attacks blocked with 403 Forbidden; SQL injection payloads rendered harmless by PDO binding.

---

### SLIDE 11: CONCLUSION & FUTURE SCOPE
- **Project Deliverables Completed:**
  - Both Stage 1 (Static Prototype) and Stage 2 (Dynamic PHP/MySQL Web Application) fully operational.
  - Complete normalized SQL schema and realistic demonstration seed scripts provided.
  - Comprehensive documentation, viva questions, and setup guides prepared.
- **Future Enhancements:**
  - Multi-item line requisition support (`purchase_order_items`).
  - Barcode / QR code scanning at factory warehouse docks.
  - Automated SMTP email notifications for overdue deliveries.

*Speaker Notes:*  
"Thank you, respected examiners. I now welcome your evaluation and am ready to demonstrate the live running dynamic PHP application on local Apache/MySQL."
