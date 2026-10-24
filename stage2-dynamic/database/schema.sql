-- ============================================================================
-- SUPPLIER DELIVERY AND PERFORMANCE MONITORING SYSTEM (SDPMS)
-- Relational Database Schema Specification
-- Target DBMS: MySQL 8.0+ / MariaDB 10.4+ (Compatible with XAMPP phpMyAdmin)
-- Engine: InnoDB | Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================================

-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS `supplier_monitoring` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `supplier_monitoring`;

-- Disable Foreign Key checks during table setup
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. TABLE: users
-- Procurement officers, purchasing managers, and system administrators
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('Admin', 'Purchasing_Manager', 'Auditor') NOT NULL DEFAULT 'Purchasing_Manager',
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. TABLE: suppliers
-- Approved vendors, contractors, and raw material suppliers
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` VARCHAR(20) PRIMARY KEY,
  `company_name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `address` TEXT NULL,
  `city` VARCHAR(80) NOT NULL,
  `category` VARCHAR(80) NOT NULL,
  `registration_date` DATE NOT NULL,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_supplier_name` (`company_name`),
  INDEX `idx_supplier_category` (`category`),
  INDEX `idx_supplier_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. TABLE: purchase_orders
-- Commercial purchase contracts issued to active suppliers
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` VARCHAR(25) PRIMARY KEY,
  `supplier_id` VARCHAR(20) NOT NULL,
  `order_date` DATE NOT NULL,
  `expected_date` DATE NOT NULL,
  `description` TEXT NOT NULL,
  `quantity` DECIMAL(12, 2) NOT NULL,
  `unit_price` DECIMAL(12, 2) NOT NULL,
  `total_amount` DECIMAL(14, 2) NOT NULL,
  `status` ENUM('Pending', 'Partially Delivered', 'Completed', 'Delayed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) 
    REFERENCES `suppliers` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_po_user` FOREIGN KEY (`created_by`) 
    REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_po_supplier` (`supplier_id`),
  INDEX `idx_po_status` (`status`),
  INDEX `idx_po_dates` (`order_date`, `expected_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. TABLE: deliveries
-- Consignment delivery progress, physical goods receipts, and delay tracking
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `deliveries`;
CREATE TABLE `deliveries` (
  `id` VARCHAR(25) PRIMARY KEY,
  `order_id` VARCHAR(25) NOT NULL UNIQUE,
  `supplier_id` VARCHAR(20) NOT NULL,
  `expected_date` DATE NOT NULL,
  `actual_date` DATE NULL,
  `ordered_qty` DECIMAL(12, 2) NOT NULL,
  `received_qty` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Pending', 'Partially Delivered', 'Delivered', 'Delayed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `remarks` TEXT NULL,
  `updated_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_del_order` FOREIGN KEY (`order_id`) 
    REFERENCES `purchase_orders` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_del_supplier` FOREIGN KEY (`supplier_id`) 
    REFERENCES `suppliers` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_del_user` FOREIGN KEY (`updated_by`) 
    REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_del_supplier` (`supplier_id`),
  INDEX `idx_del_status` (`status`),
  INDEX `idx_del_dates` (`expected_date`, `actual_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. TABLE: delivery_status_history
-- Immutable audit log of delivery status transitions
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `delivery_status_history`;
CREATE TABLE `delivery_status_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `delivery_id` VARCHAR(25) NOT NULL,
  `previous_status` VARCHAR(50) NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `notes` TEXT NULL,
  `changed_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_hist_delivery` FOREIGN KEY (`delivery_id`) 
    REFERENCES `deliveries` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_hist_user` FOREIGN KEY (`changed_by`) 
    REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_hist_delivery` (`delivery_id`),
  INDEX `idx_hist_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. TABLE: audit_logs
-- Security and compliance audit trail for critical master data modifications
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(50) NOT NULL,
  `record_id` VARCHAR(50) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX `idx_audit_record` (`table_name`, `record_id`),
  INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable Foreign Key constraints
SET FOREIGN_KEY_CHECKS = 1;
