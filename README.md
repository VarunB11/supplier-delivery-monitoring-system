# Supplier Delivery and Performance Monitoring System (SDPMS)

A comprehensive, enterprise-grade web application developed for manufacturing procurement teams to manage approved vendors, issue purchase orders, track consignment delivery schedules, compute delivery delay days, and evaluate supplier reliability using transparent mathematical scorecards.

Developed for an individual Bachelor of Engineering (B.E.) Computer Science and Engineering **Laboratory with Project (LWP)** course.

---

## 1. Project Overview & Deliverables

This repository is organized into three distinct, self-contained sections:

```
supplier-monitoring/
├── stage1-static/                 # Stage 1: Interactive Frontend Prototype (HTML5, CSS3, Vanilla JS, Chart.js)
├── stage2-dynamic/                # Stage 2: Full-Stack Enterprise Web App (PHP 8, MySQL/MariaDB, PDO, XAMPP)
└── documentation/                 # Comprehensive Academic Project Deliverables & Reports
```

### Key Deliverables Included:
1. **Stage 1 (Static Website):**
   - 6 fully navigable pages (`index.html`, `suppliers.html`, `purchase-orders.html`, `deliveries.html`, `performance.html`, `reports.html`).
   - Custom enterprise design system (Navy Blue, White, Slate Grey theme) with zero external CSS frameworks.
   - Live client-side LocalStorage demonstration store with reset capability.
   - Interactive Chart.js trend and benchmark charts.
   - Real-time client-side calculation of PO valuations, delivery delay days, and supplier performance scorecards.
   - Printable view and client-side CSV exporter.

2. **Stage 2 (Dynamic Web Application):**
   - Built on PHP 8 and MySQL / MariaDB using the Apache server in XAMPP.
   - Complete normalized relational database (`schema.sql` and realistic fictional `seed.sql`).
   - Secure authentication with `password_verify()`, session regeneration upon login, and CSRF protection on all forms.
   - Full CRUD across Suppliers, Purchase Orders, and Delivery Consignments.
   - Atomic database transactions synchronizing purchase orders and delivery tracking.
   - Safety constraints: deactivates vendors linked to purchase orders instead of deleting them.
   - Live SQL-driven KPI cards and Chart.js visualizations.
   - Multi-criteria report filtering, print layout, and RFC 4180 streaming CSV exporter.
   - User security profile and password change facility with immutable audit logs.

3. **Academic Documentation (`documentation/`):**
   - **`project_report.md`:** Complete 20-chapter academic project report conforming to BE CSE university laboratory guidelines (Cover, Certificate, Acknowledgement, Abstract, SRS, Architecture, Mermaid Use Cases, Level 0/1 DFDs, Mermaid ER diagram, Data Dictionary, Algorithms, Implementation, Testing, Limitations, and References).
   - **`stage1_presentation.md`:** Slide-by-slide presentation deck for Stage 1 (LWP Review 01).
   - **`stage2_presentation.md`:** Slide-by-slide presentation deck for Stage 2 (LWP Review 02).
   - **`viva_questions.md`:** 35+ technical viva questions and beginner-friendly answers + a 2–3 minute project elevator pitch.
   - **`testing_document.md`:** Complete software testing log with 15 verified functional and security test cases.

---

## 2. Mathematical Formulations Implemented

1. **Delivery Delay Days:**
   - Completed deliveries: $\text{Delay Days} = \text{Actual Date} - \text{Expected Date}$
     - If $>0$: Consignment is late.
     - If $=0$: Consignment is on time.
     - If $<0$: Consignment is early.
   - Overdue undelivered orders: $\text{Overdue Days} = \text{Today} - \text{Expected Date}$

2. **On-Time Delivery Compliance Rate (%):**
   $$\text{On-Time Rate} = \left( \frac{\text{On-Time Completed Deliveries}}{\text{Total Eligible Completed Deliveries}} \right) \times 100$$
   *(Excludes cancelled and active pending orders; safely handles zero completed deliveries).*

3. **Average Delivery Delay (Days):**
   $$\text{Average Delay} = \frac{\sum \text{Positive Late Days for Completed Deliveries}}{\text{Total Eligible Completed Deliveries}}$$

4. **Quantity Fulfilment Percentage (%):**
   $$\text{Quantity Fulfilment} = \left( \frac{\text{Total Accepted Received Quantity}}{\text{Total Contracted Ordered Quantity}} \right) \times 100$$

5. **Weighted Composite Reliability Score (Out of 100):**
   $$\text{Score} = (0.70 \times \text{On-Time \%}) + (0.30 \times \min(100, \text{Quantity Fulfilment \%}))$$

6. **Rating Thresholds:**
   - $\ge 90$: **Excellent** (Green badge)
   - $\ge 75$ and $< 90$: **Good** (Blue badge)
   - $\ge 60$ and $< 75$: **Average** (Amber badge)
   - $< 60$: **Needs Improvement** (Red badge)
   - 0 completed deliveries: **Unrated (New Supplier)** (Grey badge)

---

## 3. How to Run Stage 1 (Static Prototype) Locally

No web server or database installation is required for Stage 1.

### Option A: Using any Web Browser directly
1. Open the folder:
   ```
   web techno project/stage1-static/
   ```
2. Double-click `index.html` to open it in Google Chrome, Microsoft Edge, or Firefox.
3. Navigate between pages using the sidebar. To restore default sample data at any time, click **Reset Sample Data** in the sidebar.

### Option B: Using VS Code Live Server or Python HTTP Server
Run from the workspace directory:
```bash
python -m http.server 8085 --directory "stage1-static"
```
Then visit:
```
http://localhost:8085/index.html
```

---

## 4. How to Run Stage 2 (Dynamic PHP/MySQL Application) on XAMPP

### Step 1: Install & Launch XAMPP
1. Download and install **XAMPP** (with PHP 8.x and MySQL) from [https://www.apachefriends.org/](https://www.apachefriends.org/) at `C:\xampp`.
2. Launch the **XAMPP Control Panel** and start both the **Apache** and **MySQL** modules.

### Step 2: Copy Project Files to `htdocs`
Copy the `stage2-dynamic` folder into your XAMPP web root and rename it to `supplier-monitoring`:
```
C:\xampp\htdocs\supplier-monitoring\
```

### Step 3: Import Database via phpMyAdmin
1. Open your browser and navigate to:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click the **Import** tab at the top.
3. Click **Choose File**, select `C:\xampp\htdocs\supplier-monitoring\database\schema.sql`, and click **Go** (creates the `supplier_monitoring` database).
4. Click the **Import** tab again, select `C:\xampp\htdocs\supplier-monitoring\database\seed.sql`, and click **Go** (populates 8 suppliers, 14 purchase orders, 14 delivery records, and users).

### Step 4: Access the Application
Open your browser and navigate to:
```
http://localhost/supplier-monitoring/
```

### Default Credentials & Role-Based Access Control (RBAC):
| Role | User Name | Email | Password | Permissions & Operations | Restricted Operations |
|---|---|---|---|---|---|
| **👑 System Administrator** | Rajesh Sengupta | `admin@procuretrack.example.com` | `password123` | • Full CRUD on Suppliers<br>• Full CRUD & Cancellation on Purchase Orders<br>• Delivery Receipts & History<br>• User Activity & Audit Trail<br>• Reports Export | *None (Full Admin Authority)* |
| **👤 Normal User (Buyer)** | Priya Menon | `buyer@procuretrack.example.com` | `password123` | • Register new Suppliers<br>• Edit Supplier contact info<br>• Create Purchase Orders<br>• Edit draft/pending orders<br>• Record Delivery receipts<br>• Export Reports | ❌ **Cannot Delete/Deactivate Suppliers**<br>❌ **Cannot Cancel confirmed Purchase Orders**<br>*(Blocked attempts trigger an alert and are logged in the Activity Trail)* |
| **🔍 Quality & Compliance Auditor** | Amit Deshmukh | `auditor@procuretrack.example.com` | `password123` | • View all Dashboards & KPIs<br>• Inspect Supplier profiles & scorecards<br>• Review delivery delay analytics<br>• Export compliance CSV reports | ❌ **Strict Read-Only Mode**<br>❌ Cannot create/edit/delete suppliers<br>❌ Cannot create/edit/cancel orders<br>❌ Cannot update delivery receipts |

### User Activity & Audit Trail:
The application features a real-time **User Activity & Audit Trail** log on the executive dashboard. Every action—such as PO generation, supplier registration, goods receipt, role switches, and blocked unauthorized privilege attempts—is automatically recorded with:
1. Exact timestamp
2. User name and role badge
3. Action code (e.g. `CREATE_PO`, `DELETE_SUPPLIER_ATTEMPT`)
4. Target entity ID
5. Permission status (`✓ Permitted` vs `✗ Blocked - No Role Privilege`)
6. Comprehensive audit description

*(Optional Safe Admin Reset Tool: `http://localhost/supplier-monitoring/setup_admin.php`)*

---

## 5. Security & Technical Architecture Highlights

- **SQL Injection Prevention:** 100% of queries use PDO prepared statements with parameter binding.
- **CSRF Defense:** Cryptographic 32-byte tokens on all state-changing POST forms.
- **XSS Mitigation:** All output sanitized via `sanitize_output()` (`htmlspecialchars`).
- **Session Fixation Defense:** Session IDs regenerated via `session_regenerate_id(true)` upon login.
- **Safe Referential Integrity:** Safe deactivation constraint prevents accidental deletion of suppliers with linked historical purchase orders.
- **Atomic Transactions:** PDO transactions ensure purchase order creation and delivery tracking initiation occur atomically.
- **RFC 4180 CSV Export:** Direct streaming with UTF-8 Byte Order Mark for clean opening in Microsoft Excel.

---

## 6. Author and Academic Information

- **Student Name:** [STUDENT NAME]
- **Register Number:** [REGISTER NUMBER]
- **Degree:** Bachelor of Engineering (B.E.)
- **Department:** Computer Science and Engineering
- **Institution:** [COLLEGE / INSTITUTION NAME]
- **Academic Year:** 2025 – 2026
