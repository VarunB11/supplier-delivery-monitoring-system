# VIVA-VOCE EXAMINATION PREPARATION GUIDE
## Supplier Delivery and Performance Monitoring System (SDPMS)

---

## Part 1: Quick 2–3 Minute Viva Project Explanation (Elevator Pitch)

> **Student Speech:**  
> "Good morning, respected examiners. My project is the **Supplier Delivery and Performance Monitoring System (SDPMS)**, developed for manufacturing businesses that procure raw materials and components from multiple suppliers.
>
> In manufacturing industries, delayed or incomplete supplier deliveries cause severe disruptions to assembly lines and lead to substantial financial losses. Most small and medium businesses still track their purchase orders and delivery schedules through manual paper registers or fragmented spreadsheets. This leads to human error, overlooked delivery delays, and subjective vendor evaluations during contract renewals.
>
> To solve this, I designed and developed a centralized, responsive web application implemented in two distinct stages:
> - **Stage 1 (Static Prototype):** Engineered using HTML5, CSS3, Vanilla JavaScript, and Chart.js, providing an interactive, client-side simulation of the enterprise procurement dashboard, purchase order calculations, delay tracking, and visual charts.
> - **Stage 2 (Dynamic Application):** Developed using PHP 8, MySQL/MariaDB, and Apache through XAMPP, with PDO prepared statements, session security, CSRF protection, and relational database integrity.
>
> The system implements four key mathematical formulas:
> 1. Exact **Delivery Delay Days**, distinguishing between completed late deliveries and overdue active shipments.
> 2. **On-Time Delivery Compliance Rate**, calculated as on-time completed deliveries divided by total eligible completed deliveries.
> 3. **Average Delivery Delay in Days**, measuring the severity of late arrivals.
> 4. A **Weighted Composite Reliability Score (out of 100)**, combining 70% on-time delivery rate with 30% quantity fulfillment to rate suppliers objectively as *Excellent*, *Good*, *Average*, or *Needs Improvement*.
>
> The application also features real-time KPI cards, interactive Chart.js trend charts, multi-parameter report filters, print-friendly stylesheets, and RFC 4180 compliant CSV exports. All SQL queries use prepared statements to prevent SQL injection, and foreign key constraints ensure historical records are preserved through safe deactivation rather than accidental deletion.
>
> I look forward to demonstrating the live application and answering your questions."

---

## Part 2: Top 35+ Viva Questions and Answers

### Section A: HTML, CSS & Frontend Basics

#### Q1. What is the difference between a static website and a dynamic website?
**Answer:**  
A **static website** consists of pre-built HTML, CSS, and JavaScript files where the content served to the browser is fixed and identical for every visitor, running entirely on the client side. A **dynamic website** uses server-side programming (like PHP) and a database (like MySQL) to generate HTML content on the fly based on user requests, live database records, authentication states, and submitted form inputs.

#### Q2. Why did you use Vanilla CSS instead of a framework like Bootstrap or Tailwind?
**Answer:**  
Using Vanilla CSS allows complete, granular control over the design system without bloat or external dependencies. It ensures lightweight page loading, establishes a custom enterprise color palette (Navy Blue, White, and Slate Grey), and demonstrates a thorough understanding of foundational CSS specifications including Flexbox, CSS Grid, Custom Properties (variables), and print stylesheets (`@media print`).

#### Q3. What is the role of Chart.js in your application?
**Answer:**  
Chart.js is an open-source, HTML5 canvas-based JavaScript charting library. In this project, it renders interactive visualizations on the dashboard and performance pages, including:
1. A multi-line trend chart showing monthly purchase orders placed versus on-time completed orders.
2. A doughnut chart showing the distribution of consignment delivery statuses.
3. A grouped bar chart benchmarking supplier on-time compliance rates against weighted composite scores.

#### Q4. What is the difference between client-side and server-side validation? Why are both necessary?
**Answer:**  
- **Client-Side Validation (JavaScript):** Executes immediately in the user's browser before the form is transmitted. It provides immediate user feedback (e.g., highlighting negative quantities or invalid dates) and reduces unnecessary server round trips.
- **Server-Side Validation (PHP):** Executes on the server after form submission. It is the only truly secure validation layer because client-side JavaScript can be easily modified, bypassed, or disabled by an attacker.
- **Both are necessary:** Client-side validation improves user experience, while server-side validation guarantees data integrity and security.

---

### Section B: PHP Basics, Sessions & Authentication

#### Q5. What is PDO in PHP, and why did you choose it over MySQLi?
**Answer:**  
PDO stands for **PHP Data Objects**. It is a database abstraction layer built into PHP. It was chosen because:
1. It supports parameterized prepared statements, providing bulletproof defense against SQL Injection.
2. It supports modern object-oriented error handling via exceptions (`PDOException`).
3. It provides a consistent API across multiple database engines (MySQL, PostgreSQL, SQLite), making the codebase portable.

#### Q6. How is user authentication handled in the application?
**Answer:**  
When a user submits their email and password:
1. The server validates the CSRF token.
2. A prepared statement fetches the user record matching the email.
3. PHP's native `password_verify($password, $user['password_hash'])` securely verifies the plaintext password against the stored Bcrypt hash.
4. If valid, `session_regenerate_id(true)` is invoked to prevent session fixation, and user details (ID, full name, role) are stored in the `$_SESSION` superglobal.

#### Q7. Why should passwords never be stored in plain text or with MD5/SHA1?
**Answer:**  
Plaintext passwords can be read by anyone with database access. Older hash algorithms like MD5 and SHA-1 are computationally fast and vulnerable to collision attacks and rainbow table lookups. In contrast, PHP's `password_hash()` utilizes **Bcrypt** (`PASSWORD_BCRYPT`), which includes an automatic cryptographic salt and a configurable work factor (cost), making brute-force attacks computationally infeasible.

#### Q8. What is Session Fixation, and how did you prevent it?
**Answer:**  
Session fixation is an attack where an adversary forces a victim to use a known session ID, allowing the attacker to hijack the session after the victim logs in. In SDPMS, this is mitigated by calling `session_regenerate_id(true)` immediately after successful password verification. This invalidates the old session identifier and issues a brand-new cryptographic session ID.

#### Q9. What are flash messages in PHP, and how do they work?
**Answer:**  
Flash messages are temporary alert notifications (e.g., "Supplier updated successfully") stored in `$_SESSION['flash_messages']`. When a redirect occurs (Post/Redirect/Get pattern), the next page retrieves and renders the alert, immediately unsetting the session variable so the message displays exactly once and does not repeat on page reloads.

---

### Section C: Database Design, MySQL & Relational Integrity

#### Q10. What is a Primary Key and a Foreign Key?
**Answer:**  
- **Primary Key (PK):** A column or set of columns that uniquely identifies each row in a table (e.g., `id` in `suppliers` or `users`). Primary keys cannot contain NULL values and must be unique.
- **Foreign Key (FK):** A column in one table that references the primary key of another table (e.g., `supplier_id` in `purchase_orders` references `id` in `suppliers`). It establishes a logical relationship and enforces referential integrity between tables.

#### Q11. Explain the relational tables designed in this project.
**Answer:**  
The `supplier_monitoring` database consists of 6 normalized tables:
1. `users`: Stores user accounts, authentication hashes, and roles.
2. `suppliers`: Stores vendor master records and contact details.
3. `purchase_orders`: Stores commercial purchase orders linked to a supplier.
4. `deliveries`: Tracks physical goods receipts, schedules, and actual arrival dates linked 1-to-1 with purchase orders.
5. `delivery_status_history`: Stores a timestamped audit trail of status transitions for each delivery.
6. `audit_logs`: Records critical master data changes, user actions, and timestamps.

#### Q12. What does database normalization mean, and what normal form does your schema satisfy?
**Answer:**  
Database normalization is the systematic technique of organizing tables to eliminate data redundancy and prevent update, insertion, and deletion anomalies. The SDPMS schema satisfies **Third Normal Form (3NF)**:
- **1NF:** All fields contain atomic values, and each record has a unique primary key.
- **2NF:** It is in 1NF and contains no partial dependencies (all non-key columns depend on the full primary key).
- **3NF:** It is in 2NF and contains no transitive dependencies (non-key columns do not depend on other non-key columns).

#### Q13. What is the purpose of database indexes?
**Answer:**  
Indexes are data structures (typically B-Trees) that allow the database engine to locate specific rows rapidly without scanning the entire table. In SDPMS, indexes were created on foreign keys (`supplier_id`, `order_id`) and frequently queried columns (`status`, `expected_date`, `category`) to ensure sub-millisecond query performance during dashboard generation and filtering.

#### Q14. What is a Database Transaction, and where did you use it?
**Answer:**  
A transaction is a sequence of database operations treated as a single atomic unit satisfying the **ACID** properties (Atomicity, Consistency, Isolation, Durability). Either all operations succeed (commit), or if any operation fails, the entire transaction is rolled back. In this project, transactions are used in `purchase-orders/create.php` where inserting a purchase order, creating its synchronized delivery consignment, and logging the initial status history must either all succeed together or all be aborted.

#### Q15. How does your system prevent accidental deletion of suppliers?
**Answer:**  
In `suppliers/delete.php`, when a user requests to delete a vendor, the system executes:
```sql
SELECT COUNT(*) FROM purchase_orders WHERE supplier_id = :id
```
If linked purchase orders exist, direct deletion is blocked to prevent orphaned records. Instead, the system applies a **safe deactivation constraint**, updating the vendor's status to `Inactive`. Deletion is permitted only if no linked purchase orders exist.

---

### Section D: Web Security (OWASP Top 10)

#### Q16. What is SQL Injection, and how did you prevent it?
**Answer:**  
SQL Injection (SQLi) occurs when malicious SQL statements are inserted into entry fields and executed by the backend database. It is prevented completely across the application by using **PDO Prepared Statements with Parameter Binding**. The database engine compiles the SQL structure first, treating user input purely as literal data values rather than executable SQL code:
```php
$stmt = $db->prepare("SELECT * FROM suppliers WHERE category = :cat");
$stmt->execute([':cat' => $userCategory]);
```

#### Q17. What is Cross-Site Scripting (XSS), and how is it mitigated?
**Answer:**  
Cross-Site Scripting occurs when an attacker injects malicious client-side JavaScript that executes in the browser of other users. In SDPMS, XSS is prevented by escaping all user-supplied data prior to rendering in HTML using the helper function `sanitize_output()`, which wraps PHP's `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`.

#### Q18. What is Cross-Site Request Forgery (CSRF), and how did you protect against it?
**Answer:**  
CSRF is an attack where an unauthorized command is transmitted from a trusted user's browser to a web application without their knowledge (e.g., submitting a hidden form to cancel a purchase order). It is protected by generating a cryptographically secure 32-byte pseudo-random token in `$_SESSION['csrf_token']`. Every POST form embeds this token in a hidden input, and `require_csrf_token()` verifies it using `hash_equals()` before executing any state-changing database modification.

---

### Section E: Business Logic & Mathematical Formulas

#### Q19. How is the delivery delay duration calculated?
**Answer:**  
The delay duration is computed as calendar days using PHP's `DateTime::diff`:
1. **For Completed Deliveries:**
   $$\text{Delay Days} = \text{DateDiff}(\text{actual\_date}, \text{expected\_date})$$
   - If positive ($>0$): The shipment was delivered late.
   - If zero ($=0$): The shipment was delivered on the expected date (On Time).
   - If negative ($<0$): The shipment arrived early ($|\text{days}|$ days early).
2. **For Undelivered Orders:**
   $$\text{Overdue Days} = \text{DateDiff}(\text{today}, \text{expected\_date})$$
   - If positive, the active order is flagged as overdue.

#### Q20. What is the formula for On-Time Delivery Percentage?
**Answer:**  
$$\text{On-Time Delivery Rate (\%)} = \left( \frac{\text{On-Time Completed Deliveries}}{\text{Total Eligible Completed Deliveries}} \right) \times 100$$
*Key Rule:* Cancelled orders and active pending deliveries are strictly excluded from the completed-delivery metric. If a supplier has zero completed deliveries, the formula handles division by zero safely and returns `null` or "N/A".

#### Q21. How is the Average Delivery Delay calculated?
**Answer:**  
$$\text{Average Delay (Days)} = \frac{\sum \text{Positive Late Days for Completed Deliveries}}{\text{Total Eligible Completed Deliveries}}$$
This formula measures the average magnitude of delay across a supplier's shipments, allowing managers to differentiate between minor 1-day delays and severe 2-week disruptions.

#### Q22. How is Quantity Fulfilment calculated?
**Answer:**  
$$\text{Quantity Fulfilment (\%)} = \left( \frac{\text{Total Accepted Received Quantity}}{\text{Total Contracted Ordered Quantity}} \right) \times 100$$
This metric tracks whether the vendor consistently delivers the full contracted batch count or delivers partial counts.

#### Q23. Explain your Overall Composite Supplier Score and Rating Tiers.
**Answer:**  
The composite score evaluates overall reliability out of 100 using a documented 70/30 weighting:
$$\text{Composite Score} = (0.70 \times \text{On-Time \%}) + (0.30 \times \min(100, \text{Quantity Fulfilment \%}))$$
- **$\ge 90$:** **Excellent** (Top-tier vendor)
- **$\ge 75$ and $< 90$:** **Good** (Reliable partner)
- **$\ge 60$ and $< 75$:** **Average** (Acceptable, requires monitoring)
- **$< 60$:** **Needs Improvement** (Unreliable, consider replacement)
- **0 Completed Deliveries:** **Unrated (New Supplier)**

#### Q24. How does the system handle partial shipments?
**Answer:**  
If a supplier delivers a partial quantity (e.g., 18 units received out of 30 ordered):
1. The delivery record is marked `Partially Delivered`.
2. The linked purchase order status is automatically synchronized to `Partially Delivered`.
3. The delivery table displays a visual progress bar (e.g., 60%).
4. The system prevents marking the consignment as fully "Delivered" until the remaining balance quantity arrives.

#### Q25. Why is over-delivery prevented?
**Answer:**  
In manufacturing, accepting excess raw materials ties up working capital and consumes factory warehouse space unexpectedly. The application validates that `received_qty <= ordered_qty` unless explicitly documented in consignment remarks.

---

### Section F: Reporting, Export & Operational Testing

#### Q26. How did you implement CSV export in PHP?
**Answer:**  
In `reports/export_csv.php`:
1. The script verifies user login and retrieves filtered SQL rows.
2. It sets HTTP headers: `Content-Type: text/csv` and `Content-Disposition: attachment; filename="..."`.
3. It writes a UTF-8 Byte Order Mark (`0xEF, 0xBB, 0xBF`) to ensure Microsoft Excel displays non-ASCII characters correctly.
4. It streams each row to `php://output` using `fputcsv()`, which automatically handles comma escaping and quotation wrapping according to RFC 4180.

#### Q27. How does the printable report feature work?
**Answer:**  
The reports page includes a dedicated print stylesheet:
```css
@media print {
  .sidebar, .top-header, .toolbar-container, .btn { display: none !important; }
  .print-header { display: block !important; }
}
```
When the user clicks "Print Report", `window.print()` triggers the browser's native print engine, generating a clean, branded A4 document without sidebar clutter.

#### Q28. What test cases did you execute during project validation?
**Answer:**  
I conducted 15 structured test cases covering:
1. Authentication (valid and invalid logins).
2. Duplicate identifier rejection for suppliers and purchase orders.
3. Referential integrity constraint checks (deactivation vs. deletion).
4. Date sanity validation (`expected_date >= order_date`).
5. Real-time PO valuation calculations.
6. Multi-table atomic transactions.
7. Delivery delay arithmetic (late vs. early vs. on-time).
8. Partial fulfillment handling.
9. Security testing against CSRF and SQL injection.

#### Q29. What are the key limitations of your project?
**Answer:**  
1. Currency is currently fixed in Indian Rupees (INR) without multi-currency forex support.
2. Purchase orders support a single product line item rather than a multi-item nested Bill of Materials (BOM).
3. Notifications are displayed in-app on the dashboard rather than sent via external SMS/SMTP gateways.

#### Q30. What future enhancements would you propose?
**Answer:**  
1. Multi-item purchase orders using a `purchase_order_items` table.
2. Barcode or QR code dock scanning for instant Goods Receipt Note processing.
3. Automated email reminders dispatched to suppliers 48 hours prior to expected delivery dates.
4. A dedicated supplier self-service portal where vendors can acknowledge orders and confirm dispatch details.

---

### Section G: Rapid-Fire Technical Definitions

#### Q31. What is the difference between GET and POST HTTP methods?
**Answer:**  
- **GET:** Appends parameters to the URL query string; used for safe, idempotent read operations (e.g., search, report filtering). It can be bookmarked and cached.
- **POST:** Transmits parameters in the HTTP request body; used for state-changing, sensitive operations (e.g., login, creating orders, updating deliveries). It is not cached.

#### Q32. What is XAMPP?
**Answer:**  
XAMPP is an open-source, cross-platform local server software package containing **X** (Cross-platform), **A** (Apache HTTP Server), **M** (MariaDB/MySQL), **P** (PHP), and **P** (Perl). It provides an integrated local testing environment for full-stack web applications.

#### Q33. What is phpMyAdmin?
**Answer:**  
phpMyAdmin is a free, web-based graphical administration tool written in PHP for managing MySQL and MariaDB databases. It enables developers to create databases, inspect tables, execute SQL queries, and import/export SQL dump files easily.

#### Q34. What is the Post/Redirect/Get (PRG) pattern?
**Answer:**  
PRG is a web development design pattern where a successful POST request is followed by a server redirect (HTTP 302/303) to a GET request. This prevents duplicate form submissions if a user reloads the browser page.

#### Q35. What is the difference between `==` and `===` in PHP?
**Answer:**  
`==` (loose equality) compares values after automatic type coercion (e.g., `0 == "0"` evaluates to `true`), whereas `===` (strict equality) compares both value and data type without coercion (e.g., `0 === "0"` evaluates to `false`). Strict equality is preferred for secure programming.
