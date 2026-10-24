# SOFTWARE TESTING & VERIFICATION DOCUMENT
## Supplier Delivery and Performance Monitoring System (SDPMS)

**Project Name:** Supplier Delivery and Performance Monitoring System (SDPMS)  
**Academic Course:** BE CSE Web Programming Laboratory with Project  
**Testing Environment:** Apache 2.4, PHP 8.2, MySQL 8.0 / MariaDB 10.4, Windows 10/11, Google Chrome  
**Test Execution Date:** 24 October 2026  
**Tested By:** [STUDENT NAME] (Register No: [REGISTER NUMBER])  

---

## 1. Test Summary
- **Total Test Cases Executed:** 15
- **Passed:** 15
- **Failed:** 0
- **Success Rate:** 100%

---

## 2. Test Execution Log Matrix

### Test Case TC-01: Valid User Authentication & Session Establishment
- **Module:** Authentication (`login.php`)
- **Preconditions:** MySQL service running; user record exists in `users` table (`admin@procuretrack.example.com`).
- **Test Steps:**
  1. Navigate to `/login.php`.
  2. Input valid email: `admin@procuretrack.example.com`.
  3. Input valid password: `password123`.
  4. Submit login form.
- **Expected Outcome:** Credentials verified via `password_verify()`, session ID regenerated to prevent session fixation, user redirected to `/dashboard.php`.
- **Actual Outcome:** Successfully authenticated; redirected to dashboard with user initials and name rendered in sidebar.
- **Status:** **PASS**

---

### Test Case TC-02: Rejection of Invalid Authentication Attempts
- **Module:** Authentication (`login.php`)
- **Preconditions:** User on login page.
- **Test Steps:**
  1. Input email `admin@procuretrack.example.com`.
  2. Input incorrect password: `WrongPassword@999`.
  3. Submit form.
- **Expected Outcome:** Access denied; user remains on `/login.php`; clear, non-leaking error message displayed ("Invalid email address or password").
- **Actual Outcome:** Access blocked; displayed alert banner with exact expected warning message.
- **Status:** **PASS**

---

### Test Case TC-03: Protection of Private Pages from Unauthenticated Access
- **Module:** Access Control (`includes/auth.php`)
- **Preconditions:** Browser session cleared (user logged out).
- **Test Steps:**
  1. Directly attempt to access `http://localhost/supplier-monitoring/dashboard.php`.
  2. Directly attempt to access `http://localhost/supplier-monitoring/suppliers/create.php`.
- **Expected Outcome:** Protected page redirects unauthenticated request to `/login.php?notice=session_required`.
- **Actual Outcome:** Both direct URL requests intercepted by `require_login()`; redirected to login with session required notice.
- **Status:** **PASS**

---

### Test Case TC-04: Supplier Registration & Duplicate Primary Key Prevention
- **Module:** Supplier Management (`suppliers/create.php`)
- **Preconditions:** Supplier `SUP-1001` already exists in `suppliers` table.
- **Test Steps:**
  1. Open `/suppliers/create.php`.
  2. Attempt to register a new vendor with ID `SUP-1001`.
  3. Submit form.
- **Expected Outcome:** System detects duplicate primary key; rejects submission with error "Supplier ID SUP-1001 already exists".
- **Actual Outcome:** Duplicate caught by prepared query check; error banner displayed; no database insertion performed.
- **Status:** **PASS**

---

### Test Case TC-05: Safe Deactivation Constraint on Linked Suppliers
- **Module:** Supplier Management (`suppliers/delete.php`)
- **Preconditions:** Supplier `SUP-1001` has 2 linked purchase orders in `purchase_orders` table.
- **Test Steps:**
  1. Click delete action for `SUP-1001`.
  2. Confirm browser confirmation prompt.
- **Expected Outcome:** Deletion prevented to preserve relational integrity and prevent foreign key violation; supplier status updated to `Inactive` with explanatory notice.
- **Actual Outcome:** Constraint triggered; vendor status changed to `Inactive`; flash message alerted user of safe deactivation; linked POs remained intact.
- **Status:** **PASS**

---

### Test Case TC-06: Purchase Order Creation & Real-Time Valuation Calculation
- **Module:** Purchase Order Management (`purchase-orders/create.php`)
- **Preconditions:** User authenticated as Procurement Manager.
- **Test Steps:**
  1. Open `/purchase-orders/create.php`.
  2. Select active supplier `SUP-1001`.
  3. Input quantity = `50`.
  4. Input unit price = `65000`.
  5. Check calculated total input.
  6. Submit form.
- **Expected Outcome:** Total valuation dynamically calculated as `₹32,50,000.00`; form submission saves PO and automatically initializes delivery consignment in `deliveries`.
- **Actual Outcome:** Client field updated to `₹32,50,000.00`; database transaction committed PO and synchronized delivery record in `deliveries`.
- **Status:** **PASS**

---

### Test Case TC-07: Validation of Invalid Date Sequences on POs
- **Module:** Purchase Order Management (`purchase-orders/create.php`)
- **Preconditions:** PO creation form open.
- **Test Steps:**
  1. Set Order Date to `2026-02-15`.
  2. Set Expected Delivery Date to `2026-02-01` (earlier than order date).
  3. Submit form.
- **Expected Outcome:** Form submission rejected; displays error "Expected delivery date cannot precede the purchase order issue date".
- **Actual Outcome:** Validation error caught both on client and server; record not saved.
- **Status:** **PASS**

---

### Test Case TC-08: Validation of Negative Quantities and Pricing
- **Module:** Purchase Order Management (`purchase-orders/create.php`)
- **Preconditions:** PO creation form open.
- **Test Steps:**
  1. Input quantity = `-10`.
  2. Input unit price = `-50`.
  3. Submit form.
- **Expected Outcome:** Form rejected; displays error requiring positive quantity and non-negative unit rate.
- **Actual Outcome:** Server-side validation blocked submission and alerted user.
- **Status:** **PASS**

---

### Test Case TC-09: Accurate Calculation of Delivery Delay Days (Late Consignment)
- **Module:** Delivery Tracking (`deliveries/edit.php`, `includes/functions.php`)
- **Preconditions:** Delivery consignment `DEL-3002` scheduled for `2026-01-25`.
- **Test Steps:**
  1. Update delivery with actual arrival date: `2026-01-30`.
  2. Input received quantity = `1200` (ordered = `1200`).
  3. Submit form.
- **Expected Outcome:** Delay days computed as $30 - 25 = 5$ positive delay days; status set to `Delivered`; badge displays "+5d late".
- **Actual Outcome:** Difference computed as exactly 5 calendar days; table displays "+5d late" with red indicator.
- **Status:** **PASS**

---

### Test Case TC-10: Accurate Calculation of Prompt / Early Delivery
- **Module:** Delivery Tracking (`includes/functions.php`)
- **Preconditions:** Consignment `DEL-3001` scheduled for `2026-01-20`.
- **Test Steps:**
  1. Actual arrival date recorded as `2026-01-19` (1 day before expected).
  2. Full quantity received: `50` units.
- **Expected Outcome:** Evaluated as on-time; delay days = 0; early days = 1; badge displays "1d early (On Time)".
- **Actual Outcome:** Delay evaluated as 0; early display confirmed; counted as on-time in supplier performance calculations.
- **Status:** **PASS**

---

### Test Case TC-11: Prevention of Undocumented Over-Delivery
- **Module:** Delivery Tracking (`deliveries/edit.php`)
- **Preconditions:** Consignment ordered quantity is `100` units.
- **Test Steps:**
  1. Open delivery update form.
  2. Attempt to input received quantity = `150`.
  3. Submit form.
- **Expected Outcome:** Form rejected with error stating received count cannot exceed contracted ordered count.
- **Actual Outcome:** Submission blocked with message: "Received quantity (150) cannot exceed ordered quantity (100)..."
- **Status:** **PASS**

---

### Test Case TC-12: Partial Consignment Fulfillment & Status Synchronization
- **Module:** Delivery Tracking (`deliveries/edit.php`)
- **Preconditions:** Purchase order `PO-2026-009` has ordered count = `30` units.
- **Test Steps:**
  1. Input received quantity = `18` units.
  2. Select status `Partially Delivered`.
  3. Submit form.
- **Expected Outcome:** Delivery saved as `Partially Delivered`; progress bar shows 60%; linked `purchase_orders.status` synchronized to `Partially Delivered`.
- **Actual Outcome:** Delivery record updated; PO status updated in lockstep; audit history recorded the partial receipt.
- **Status:** **PASS**

---

### Test Case TC-13: Verification of Supplier Performance Mathematical Formulas
- **Module:** Supplier Performance (`performance/index.php`)
- **Preconditions:** Supplier `SUP-1001` has 2 completed deliveries: 1 early (Jan 19) and 1 on-time (Mar 02); total accepted units = 200 of 200 ordered.
- **Test Steps:**
  1. Open `/performance/index.php`.
  2. Inspect metrics for `SUP-1001`.
- **Expected Outcome:**
  - Eligible Completed = 2
  - On-Time = 2, Late = 0
  - On-Time % = $(2/2) \times 100 = 100.0\%$
  - Average Delay = $0.0$ days
  - Quantity Fulfilment = $(200/200) \times 100 = 100.0\%$
  - Composite Score = $(0.70 \times 100) + (0.30 \times 100) = 100 / 100$
  - Rating = `Excellent`
- **Actual Outcome:** Exact matching output displayed in data table and benchmark bar chart.
- **Status:** **PASS**

---

### Test Case TC-14: Multi-Parameter Report Filtering & CSV Export
- **Module:** Reports & Analytics (`reports/index.php`, `reports/export_csv.php`)
- **Preconditions:** Orders exist across various statuses.
- **Test Steps:**
  1. Select Report Type: "Delayed & Overdue Deliveries".
  2. Click "Apply Filters".
  3. Verify table contents.
  4. Click "Export CSV".
- **Expected Outcome:** Table filters only delayed records; summary cards recalculate delayed counts; browser initiates download of valid CSV file with UTF-8 BOM.
- **Actual Outcome:** Filter rendered delayed records accurately; browser downloaded `DELAYED_REPORT_*.csv`; verified file opens cleanly in Microsoft Excel.
- **Status:** **PASS**

---

### Test Case TC-15: Cross-Site Request Forgery (CSRF) Protection
- **Module:** Security (`includes/csrf.php`)
- **Preconditions:** POST endpoint accessed without valid session token.
- **Test Steps:**
  1. Simulate an unauthorized external POST submission to `suppliers/create.php` omitting `csrf_token`.
- **Expected Outcome:** Request intercepted by `require_csrf_token()`; halts with HTTP 403 Forbidden.
- **Actual Outcome:** Execution aborted; returned 403 Forbidden error page; zero database modifications permitted.
- **Status:** **PASS**

---

## 3. Responsive Layout Testing Across Screen Widths

| Device / Viewport | Width | Layout Behavior Observed | Verification Result |
| :--- | :---: | :--- | :---: |
| **Desktop / Laptop** | 1440px / 1280px | Permanent sidebar (260px), 6-column KPI grid, 2-column chart panels | **PASS** |
| **Tablet** | 768px – 1024px | Stacked 2-column KPI grid, single-column charts, scrollable data tables | **PASS** |
| **Mobile Screen** | 375px – 480px | Hidden sidebar, hamburger drawer toggle, stacked form grids, touch-friendly buttons | **PASS** |

---

## 4. Conclusion
All 15 functional, mathematical, constraint, and security test cases successfully passed verification. The application demonstrates robust data integrity, secure session management, and precise business logic execution.
