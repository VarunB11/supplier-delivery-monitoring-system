# STAGE 1 — LWP REVIEW 01 PRESENTATION
## Supplier Delivery and Performance Monitoring System (SDPMS)

---

### SLIDE 1: TITLE SLIDE
- **Project Title:** Supplier Delivery and Performance Monitoring System (SDPMS)
- **Review Stage:** Stage 1 — Static Frontend Prototype (LWP Review 01)
- **Student Name:** [STUDENT NAME]
- **Register Number:** [REGISTER NUMBER]
- **Degree / Branch:** B.E. Computer Science and Engineering
- **Department:** Department of Computer Science and Engineering
- **Institution:** [COLLEGE / INSTITUTION NAME]
- **Project Guide:** [FACULTY GUIDE NAME, Designation]
- **Academic Year:** 2025 – 2026

*Speaker Notes:*  
"Respected evaluators and faculty guide, welcome to the Stage 1 review of my laboratory project, the Supplier Delivery and Performance Monitoring System. In this phase, I present the complete, interactive frontend prototype designed to streamline raw material procurement and vendor evaluation for manufacturing enterprises."

---

### SLIDE 2: INDUSTRIAL CONTEXT & PROBLEM STATEMENT
- **The Core Problem:**
  - Modern factories purchase components and raw materials from dozens of external suppliers.
  - Deliveries are frequently delayed, causing assembly lines to shut down.
  - Purchase records, expected dates, and delivery receipts are tracked haphazardly in spreadsheets or paper registers.
  - Delays and partial shipments are manually computed, resulting in human calculation errors.
  - Purchasing managers lack centralized historical data when negotiating annual vendor contracts.
- **Key Pain Points:**
  - Production disruptions due to unanticipated material shortages.
  - Incomplete shipments accepted without tracking balance quantities.
  - Subjective, opinion-based vendor evaluation rather than empirical scoring.

*Speaker Notes:*  
"In manufacturing, a delay of even two days in delivering steel coils or microcontroller ICs can halt an entire production shift. The primary issue faced by purchasing teams today is the lack of a centralized, real-time platform to identify delayed orders immediately."

---

### SLIDE 3: PROJECT OBJECTIVES
- Build a responsive, enterprise-grade web application tailored for industrial purchasing managers.
- Provide a centralized repository for approved vendor directories and material categories.
- Automate the generation of purchase order codes and live valuation calculations.
- Implement an automated delay calculation engine comparing expected arrival schedules against dock receipts.
- Devise transparent mathematical formulas for on-time delivery rates, average delay days, and composite reliability scores.
- Deliver printable procurement audit reports and RFC 4180 compliant CSV exports.

---

### SLIDE 4: PROPOSED SOLUTION & ARCHITECTURE
- **Two-Stage Development Lifecycle:**
  - **Stage 1 (Current Review):** Fully functional, responsive static frontend prototype built with HTML5, CSS3, Vanilla JavaScript, and Chart.js.
  - **Stage 2 (Next Review):** Full-stack dynamic database application powered by PHP 8, MySQL/MariaDB, and PDO prepared statements.
- **Client-Side Architecture:**
  - Storage Manager using HTML5 LocalStorage pre-seeded with 8 industrial suppliers, 14 purchase orders, and 14 delivery records.
  - Modular JS separation: `app.js`, `suppliers.js`, `orders.js`, `deliveries.js`, `performance.js`, `reports.js`.

---

### SLIDE 5: MANDATORY TECHNOLOGY STACK
- **HTML5:** Semantic layout elements (`<main>`, `<aside>`, `<header>`, `<section>`, `<table>`, `<canvas>`).
- **CSS3:** Custom Vanilla CSS design system (Navy blue, White, Slate grey color palette; CSS variables; Flexbox and Grid layouts; `@media print` stylesheets).
- **Vanilla JavaScript (ES6+):** Pure client-side logic for DOM manipulation, live event listening, date difference arithmetic, and client storage.
- **Chart.js (v4.4):** Interactive canvas data visualizations (Monthly order trend line graphs, delivery status doughnut charts, and vendor benchmark bar charts).
- **Zero Heavy Frameworks:** No React, Angular, or Vue needed, ensuring maximum performance, lightweight footprint, and beginner-friendly maintainability.

---

### SLIDE 6: USER INTERFACE & DESIGN SYSTEM
- **Enterprise Dashboard Layout:**
  - Navy Blue primary branding (`#0f172a`, `#1a2f54`) conveying industrial professionalism.
  - Crisp white panel cards on subtle slate grey backgrounds (`#f8fafc`).
  - Fixed sticky desktop sidebar navigation with active page indicators and mobile collapsible drawer.
  - Rich SVG iconography for consistent offline rendering without external icon font dependencies.
  - Semantic status pills with glowing status dots (Pending, On Time, Delayed, Partially Delivered, Completed, Cancelled).
  - Accessible contrast, interactive button hover elevations, and animated backdrop modals.

---

### SLIDE 7: CLIENT-SIDE JAVASCRIPT LOGIC & MATHEMATICAL FORMULAS
- **Delivery Delay Days Formula:**
  - Completed deliveries: $\text{Delay Days} = \text{Actual Date} - \text{Expected Date}$
  - Overdue deliveries: $\text{Overdue Days} = \text{Today's Date} - \text{Expected Date}$
- **On-Time Delivery Compliance Rate:**
  $$\text{On-Time Rate (\%)} = \frac{\text{On-Time Completed Deliveries}}{\text{Total Eligible Completed Deliveries}} \times 100$$
- **Average Delivery Delay:**
  $$\text{Average Delay (Days)} = \frac{\sum \text{Positive Late Days}}{\text{Eligible Completed Deliveries}}$$
- **Composite Reliability Score (Out of 100):**
  $$\text{Score} = (0.70 \times \text{On-Time \%}) + (0.30 \times \text{Quantity Fulfilment \%})$$
  *(Thresholds: $\ge 90$ Excellent, $\ge 75$ Good, $\ge 60$ Average, $< 60$ Needs Improvement).*

---

### SLIDE 8: STAGE 1 MODULES DEMONSTRATION
- **1. Dashboard (`index.html`):** 6 dynamic KPI summary cards, monthly trend chart, urgent delayed orders alert table.
- **2. Supplier Management (`suppliers.html`):** Real-time text search, category/status filtering, add/edit/view modals, safe deactivation prompt.
- **3. Purchase Orders (`purchase-orders.html`):** Automatic valuation calculation (`quantity * unit_price`), date sequence checks, synchronized delivery status.
- **4. Delivery Tracking (`deliveries.html`):** Visual fulfillment progress bars, actual date recording, consignment update modal, status change history log.
- **5. Supplier Performance (`performance.html`):** Live benchmark bar chart, reliability badges, formula breakdown modal.
- **6. Reports & Analytics (`reports.html`):** Multi-criteria filters, summary stat cards, print-friendly layout, and instant CSV download.

---

### SLIDE 9: LIMITATIONS OF A STATIC WEBSITE
- **Session State Ephemerality:** Data modified in LocalStorage is confined to a single browser instance and does not persist across different client devices.
- **Lack of Multi-User Concurrency:** Multiple purchasing managers cannot simultaneously collaborate on shared orders.
- **Absence of Server-Side Security:** Form validation in JavaScript can be bypassed by knowledgeable users; no cryptographic password hashing or CSRF defense is possible without a backend.
- **Need for Relational Integrity:** Business rules requiring foreign key cascade and constraint checks require a relational database management system (RDBMS).

---

### SLIDE 10: CONCLUSION & TRANSITION TO STAGE 2
- **Summary of Stage 1 Achievements:**
  - Delivered a 100% functional, responsive, and validated prototype.
  - Successfully verified all client interactions, mathematical formulas, and Chart.js integrations without errors.
- **Stage 2 Roadmap (Upcoming Work):**
  - Implement full-stack dynamic architecture using **PHP 8** and **MySQL** on **XAMPP**.
  - Migrate LocalStorage data store into normalized relational tables (`users`, `suppliers`, `purchase_orders`, `deliveries`, `history`, `audit_logs`).
  - Introduce PDO prepared statements, session authentication, and CSRF tokens.

*Speaker Notes:*  
"Thank you for your time. I am now pleased to demonstrate the live running prototype in the browser and invite your questions and suggestions for Stage 2 development."
