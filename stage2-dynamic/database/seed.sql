-- ============================================================================
-- SUPPLIER DELIVERY AND PERFORMANCE MONITORING SYSTEM (SDPMS)
-- Seed Demonstration Dataset (Realistic Industrial Fictional Records)
-- ============================================================================

USE `supplier_monitoring`;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `delivery_status_history`;
TRUNCATE TABLE `deliveries`;
TRUNCATE TABLE `purchase_orders`;
TRUNCATE TABLE `suppliers`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `audit_logs`;

-- ----------------------------------------------------------------------------
-- 1. SEED USERS
-- Default Login Credentials:
-- Email: admin@procuretrack.example.com
-- Password: password123 (or use setup_admin.php to set custom password)
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `status`) VALUES
(1, 'admin', 'admin@procuretrack.example.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'Rajesh Sengupta', 'Admin', 'Active'),
(2, 'buyer1', 'buyer@procuretrack.example.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'Priya Menon', 'Purchasing_Manager', 'Active'),
(3, 'auditor1', 'auditor@procuretrack.example.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'Amit Deshmukh', 'Auditor', 'Active');
-- Note: '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm' is the standard bcrypt hash for 'password123'.

-- ----------------------------------------------------------------------------
-- 2. SEED SUPPLIERS (8 Industrial Vendors)
-- ----------------------------------------------------------------------------
INSERT INTO `suppliers` (`id`, `company_name`, `contact_person`, `email`, `phone`, `address`, `city`, `category`, `registration_date`, `status`) VALUES
('SUP-1001', 'Apex Precision Steel Ltd.', 'Rajesh Sharma', 'r.sharma@apexsteel.example.com', '+91 98230 11223', 'Plot 45, Phase II, Industrial Estate', 'Pune', 'Metals & Alloys', '2025-01-15', 'Active'),
('SUP-1002', 'Vortex Microelectronics Pvt.', 'Anita Deshmukh', 'anita@vortexmicro.example.com', '+91 98450 33445', '7B, Tech Park Road, Whitefield', 'Bengaluru', 'Electronic Components', '2025-02-10', 'Active'),
('SUP-1003', 'Nova Polymer Solutions', 'Vikram Mehta', 'vmehta@novapolymers.example.com', '+91 98112 55667', '12 GIDC Estate, Vatva', 'Ahmedabad', 'Plastics & Polymers', '2025-03-01', 'Active'),
('SUP-1004', 'Bharat Fasteners & Hardware', 'Suresh Patil', 'suresh@bharatfasteners.example.com', '+91 97654 77889', 'D-14, MIDC Ambad', 'Nashik', 'Fasteners & Hardware', '2025-03-20', 'Active'),
('SUP-1005', 'Zenith Industrial Packaging', 'Meera Nair', 'm.nair@zenithpack.example.com', '+91 99401 22334', '88 Ambattur Industrial Estate', 'Chennai', 'Packaging Materials', '2025-04-05', 'Active'),
('SUP-1006', 'Kavita Chemical Reagents', 'Dr. Kavita Joshi', 'kavita@kavitachem.example.com', '+91 98200 44556', 'Plot 102, TTC Industrial Area', 'Navi Mumbai', 'Chemical Reagents', '2025-05-12', 'Active'),
('SUP-1007', 'Dynamic Fluid Systems', 'Arun Verma', 'arun@dynamicfluid.example.com', '+91 98765 88990', '33 Sector 25, HSIIDC', 'Faridabad', 'Hydraulic & Pneumatic', '2025-06-01', 'Active'),
('SUP-1008', 'Delta Rubber & Seals Corp', 'Pooja Hegde', 'pooja@deltarubber.example.com', '+91 97411 99001', 'Plot 19, Peenya 3rd Phase', 'Bengaluru', 'Rubber & Seals', '2025-07-15', 'Inactive');

-- ----------------------------------------------------------------------------
-- 3. SEED PURCHASE ORDERS (14 Industrial POs)
-- ----------------------------------------------------------------------------
INSERT INTO `purchase_orders` (`id`, `supplier_id`, `order_date`, `expected_date`, `description`, `quantity`, `unit_price`, `total_amount`, `status`, `notes`, `created_by`) VALUES
('PO-2026-001', 'SUP-1001', '2026-01-05', '2026-01-20', 'High-Tensile Cold Rolled Steel Coils (Grade EN8)', 50.00, 65000.00, 3250000.00, 'Completed', 'Urgent batch for Q1 chassis fabrication', 1),
('PO-2026-002', 'SUP-1002', '2026-01-10', '2026-01-25', 'STM32 Microcontroller ICs (100-pin LQFP)', 1200.00, 380.00, 456000.00, 'Completed', 'Anti-static moisture barrier packaging required', 1),
('PO-2026-003', 'SUP-1003', '2026-01-15', '2026-02-01', 'High Density Polyethylene (HDPE) Granules - Natural', 2000.00, 115.00, 230000.00, 'Completed', 'Batch test certificate must accompany shipment', 1),
('PO-2026-004', 'SUP-1004', '2026-01-22', '2026-02-05', 'M8 Stainless Steel Hex Socket Cap Screws (A2-70)', 10000.00, 8.50, 85000.00, 'Completed', 'DIN 912 standard compliance verified', 1),
('PO-2026-005', 'SUP-1005', '2026-02-01', '2026-02-14', 'Heavy Duty 5-Ply Corrugated Shipping Cartons', 3500.00, 42.00, 147000.00, 'Completed', 'Moisture resistant coating', 2),
('PO-2026-006', 'SUP-1006', '2026-02-10', '2026-02-24', 'Industrial Degreasing Solvent (Trichloroethylene alt)', 400.00, 520.00, 208000.00, 'Completed', 'MSDS documentation strictly mandatory', 2),
('PO-2026-007', 'SUP-1001', '2026-02-15', '2026-03-02', 'Seamless Hydraulic Tubing (OD 25mm, WT 2.5mm)', 150.00, 1850.00, 277500.00, 'Completed', 'Eddy-current tested tubes', 1),
('PO-2026-008', 'SUP-1002', '2026-02-20', '2026-03-08', 'Optocoupler High-Speed Isolators (SMD)', 2500.00, 65.00, 162500.00, 'Completed', 'Tape and reel packaging', 1),
('PO-2026-009', 'SUP-1007', '2026-03-01', '2026-03-18', 'Pneumatic Actuator Cylinders Double Acting (50mm Bore)', 30.00, 8400.00, 252000.00, 'Partially Delivered', 'Balance quantity delayed due to valve block shortage', 2),
('PO-2026-010', 'SUP-1003', '2026-03-05', '2026-03-22', 'Polypropylene Copolymer Resin (MFI 12)', 1500.00, 130.00, 195000.00, 'Delayed', 'Overdue shipment; logistics breakdown reported', 1),
('PO-2026-011', 'SUP-1004', '2026-03-10', '2026-03-28', 'M12 Grade 8.8 High Strength Anchor Bolts', 4000.00, 24.00, 96000.00, 'Delayed', 'Raw material shortage at supplier forge', 1),
('PO-2026-012', 'SUP-1005', '2026-03-15', '2026-04-05', 'Custom Die-Cut Foam Cushioning Inserts', 1800.00, 68.00, 122400.00, 'Pending', 'Awaiting manufacturing completion', 2),
('PO-2026-013', 'SUP-1006', '2026-03-20', '2026-04-10', 'Surface Phosphating Chemical Dip (200L Drum)', 10.00, 14500.00, 145000.00, 'Pending', 'Scheduled for dispatch next week', 2),
('PO-2026-014', 'SUP-1008', '2026-02-05', '2026-02-25', 'Viton Fluoropolymer O-Rings Assorted Kit', 200.00, 450.00, 90000.00, 'Cancelled', 'Order cancelled due to supplier factory shutdown', 1);

-- ----------------------------------------------------------------------------
-- 4. SEED DELIVERIES (14 Delivery Consignments)
-- ----------------------------------------------------------------------------
INSERT INTO `deliveries` (`id`, `order_id`, `supplier_id`, `expected_date`, `actual_date`, `ordered_qty`, `received_qty`, `status`, `remarks`, `updated_by`) VALUES
('DEL-3001', 'PO-2026-001', 'SUP-1001', '2026-01-20', '2026-01-19', 50.00, 50.00, 'Delivered', 'Delivered 1 day early. Inspection passed with zero defects.', 1),
('DEL-3002', 'PO-2026-002', 'SUP-1002', '2026-01-25', '2026-01-30', 1200.00, 1200.00, 'Delivered', 'Delivered 5 days late due to air cargo customs clearance delay.', 1),
('DEL-3003', 'PO-2026-003', 'SUP-1003', '2026-02-01', '2026-02-01', 2000.00, 2000.00, 'Delivered', 'Delivered exactly on schedule. Quality certificate accepted.', 1),
('DEL-3004', 'PO-2026-004', 'SUP-1004', '2026-02-05', '2026-02-04', 10000.00, 10000.00, 'Delivered', 'Delivered 1 day early. Full count verified via automated weigh counter.', 1),
('DEL-3005', 'PO-2026-005', 'SUP-1005', '2026-02-14', '2026-02-18', 3500.00, 3500.00, 'Delivered', 'Delivered 4 days late due to transportation strike in transit state.', 2),
('DEL-3006', 'PO-2026-006', 'SUP-1006', '2026-02-24', '2026-02-24', 400.00, 400.00, 'Delivered', 'On-time delivery. Sealed drums inspected without leaks.', 2),
('DEL-3007', 'PO-2026-007', 'SUP-1001', '2026-03-02', '2026-03-02', 150.00, 150.00, 'Delivered', 'On-time delivery. Wall thickness confirmed via ultrasonic test.', 1),
('DEL-3008', 'PO-2026-008', 'SUP-1002', '2026-03-08', '2026-03-12', 2500.00, 2500.00, 'Delivered', 'Delivered 4 days late. Supplier warned about buffer planning.', 1),
('DEL-3009', 'PO-2026-009', 'SUP-1007', '2026-03-18', '2026-03-18', 30.00, 18.00, 'Partially Delivered', 'Partial batch of 18 units accepted. Remaining 12 units promised by next month.', 2),
('DEL-3010', 'PO-2026-010', 'SUP-1003', '2026-03-22', NULL, 1500.00, 0.00, 'Delayed', 'Overdue delivery! Supplier alerted; carrier truck had mechanical breakdown.', 1),
('DEL-3011', 'PO-2026-011', 'SUP-1004', '2026-03-28', NULL, 4000.00, 0.00, 'Delayed', 'Overdue shipment pending tooling completion at manufacturer.', 1),
('DEL-3012', 'PO-2026-012', 'SUP-1005', '2026-04-05', NULL, 1800.00, 0.00, 'Pending', 'Production scheduled; tracking number pending.', 2),
('DEL-3013', 'PO-2026-013', 'SUP-1006', '2026-04-10', NULL, 10.00, 0.00, 'Pending', 'Purchase order acknowledged and scheduled.', 2),
('DEL-3014', 'PO-2026-014', 'SUP-1008', '2026-02-25', NULL, 200.00, 0.00, 'Cancelled', 'Purchase order formally cancelled.', 1);

-- ----------------------------------------------------------------------------
-- 5. SEED DELIVERY STATUS HISTORY
-- ----------------------------------------------------------------------------
INSERT INTO `delivery_status_history` (`delivery_id`, `previous_status`, `new_status`, `notes`, `changed_by`, `created_at`) VALUES
('DEL-3001', 'Pending', 'Delivered', 'Received full consignment 1 day early. QA stamped ok.', 1, '2026-01-19 14:30:00'),
('DEL-3002', 'Pending', 'Delivered', 'Received after 5 days delay due to customs clearance.', 1, '2026-01-30 16:45:00'),
('DEL-3009', 'Pending', 'Partially Delivered', 'Received partial quantity: 18 out of 30 units at warehouse dock 3.', 2, '2026-03-18 11:15:00'),
('DEL-3010', 'Pending', 'Delayed', 'Supplier informed transit logistics breakdown.', 1, '2026-03-23 09:00:00');

-- ----------------------------------------------------------------------------
-- 6. SEED AUDIT LOGS
-- ----------------------------------------------------------------------------
INSERT INTO `audit_logs` (`user_id`, `action`, `table_name`, `record_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 'CREATE', 'purchase_orders', 'PO-2026-001', 'Created PO for Cold Rolled Steel Coils with value ₹32,50,000', '127.0.0.1', '2026-01-05 10:00:00'),
(1, 'CANCEL', 'purchase_orders', 'PO-2026-014', 'Cancelled order PO-2026-014 due to supplier shutdown', '127.0.0.1', '2026-02-25 15:30:00');

SET FOREIGN_KEY_CHECKS = 1;
