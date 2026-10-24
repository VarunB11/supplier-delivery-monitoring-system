<?php
/**
 * SDPMS - Core System Configuration
 * Environment Settings, Database Credentials & Session Security
 */

// Strict error reporting for development (disable sensitive display in production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Database Configuration (Default XAMPP Credentials)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'supplier_monitoring');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Application Constants
define('APP_NAME', 'ProcureTrack');
define('APP_SUBTITLE', 'Supplier Delivery & Performance Monitoring System');
define('APP_VERSION', '2.0.0');

// Base URL helper
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
// Normalize base path
$baseDir = preg_replace('/(\/suppliers|\/purchase-orders|\/deliveries|\/performance|\/reports).*$/', '', $scriptDir);
define('BASE_URL', rtrim($protocol . $host . $baseDir, '/'));

// Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    // Configure session cookie params for security
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
