# Supplier Delivery and Performance Monitoring System (Stage 2 Dynamic)

A production-grade, secure, relational web application developed with **PHP 8**, **MySQL / MariaDB**, **PDO prepared statements**, and **Vanilla CSS/JS** for manufacturing procurement teams.

---

## 1. System Requirements

- **Web Server:** Apache 2.4+ (Included in XAMPP)
- **Programming Language:** PHP 8.0, 8.1, or 8.2 with PDO and OpenSSL extensions
- **Database Server:** MySQL 5.7+ or MariaDB 10.4+ (Included in XAMPP)
- **Database Management Tool:** phpMyAdmin (Bundled with XAMPP)
- **Web Browser:** Google Chrome, Microsoft Edge, Firefox, or Safari (Modern ES6 compatible)

---

## 2. Installation & Local Setup Instructions (XAMPP)

### Step 1: Install XAMPP
If XAMPP is not already installed on your computer:
1. Download **XAMPP for Windows** from [https://www.apachefriends.org/](https://www.apachefriends.org/).
2. Run the installer and choose the default installation directory: `C:\xampp`.
3. Complete the setup.

### Step 2: Deploy Project Files
1. Open Windows File Explorer and navigate to your XAMPP web root:
   ```
   C:\xampp\htdocs\
   ```
2. Copy the entire `stage2-dynamic` folder into `htdocs` and rename it to `supplier-monitoring`:
   ```
   C:\xampp\htdocs\supplier-monitoring\
   ```

### Step 3: Start Apache & MySQL Services
1. Launch the **XAMPP Control Panel** from the Windows Start menu or `C:\xampp\xampp-control.exe`.
2. Click the **Start** button adjacent to **Apache**. (The background turns green).
3. Click the **Start** button adjacent to **MySQL**. (The background turns green).

### Step 4: Import the Database Schema & Demo Records
1. Open your browser and go to:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click on the **Import** tab in the top navigation bar.
3. Click **Choose File** and select:
   ```
   C:\xampp\htdocs\supplier-monitoring\database\schema.sql
   ```
4. Scroll to the bottom and click **Import** (or **Go**).
   *(This creates the `supplier_monitoring` database and all 6 normalized tables).*
5. Click on the **Import** tab again, click **Choose File** and select:
   ```
   C:\xampp\htdocs\supplier-monitoring\database\seed.sql
   ```
6. Click **Import** (or **Go**).
   *(This seeds the database with 8 industrial vendors, 14 purchase orders, 14 delivery records, audit logs, and default user accounts).*

---

## 3. Accessing the Application

Open your browser and navigate to:
```
http://localhost/supplier-monitoring/
```
You will be directed to the login portal.

### Default Authorized User Credentials:
- **Email:** `admin@procuretrack.example.com`
- **Password:** `password123`
- **Role:** Administrator

*(Secondary Buyer Account: `buyer@procuretrack.example.com` / `password123`)*

### Safe Admin Initializer (If Needed):
If you wish to reset or configure a fresh administrator password, open:
```
http://localhost/supplier-monitoring/setup_admin.php
```
This utility uses native PHP `password_hash($pass, PASSWORD_BCRYPT)` to directly generate matching database credentials.

---

## 4. Key Architecture & Features

| Module | Description & Business Logic |
| :--- | :--- |
| **Authentication** | PHP session management, CSRF form tokens, `password_verify()`, session regeneration upon login, access control guards. |
| **Dashboard** | Live database metrics, real-time KPI counts, Chart.js monthly trend and status doughnut charts, delayed deliveries alert banner. |
| **Suppliers** | Full CRUD, unique code validation, contact records, safe constraint handling (deactivates vendor if linked POs exist). |
| **Purchase Orders** | Unique PO ID generation, live client/server total valuation (`qty * price`), date consistency validation, transaction integration. |
| **Delivery Tracking** | Expected vs actual arrival tracking, positive delay days calculation, partial fulfillment tracking, status change history log. |
| **Supplier Performance** | Exact mathematical formulas for On-Time %, Average Delay Days, Quantity Fulfilment %, 70/30 Composite Score, and Rating Tiers. |
| **Reports & Analytics** | Parameter filters (Supplier, Status, Date range), summary statistics cards, print-friendly stylesheet, and RFC 4180 CSV export. |
| **Security & Auditing** | PDO prepared statements across all queries, XSS output sanitization, CSRF tokens on all POST requests, and immutable audit logs. |

---

## 5. Troubleshooting & FAQ

- **Port 80/443 Conflict:** If Apache fails to start, click *Config* > *httpd.conf* in XAMPP and change `Listen 80` to `Listen 8080`. Then access the project via `http://localhost:8080/supplier-monitoring/`.
- **Database Connection Refused:** Ensure MySQL service is running in XAMPP Control Panel. If you changed the MySQL password from blank (`""`), update `includes/config.php` with your password.
