<?php
/**
 * SDPMS - User Authentication and Session Authorization
 * Password Verification, Session Fixation Defense, and Role Guards
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['username']);
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'email'     => $_SESSION['email'],
        'role'      => $_SESSION['role']
    ];
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php?notice=session_required');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if ($_SESSION['role'] !== 'Admin') {
        http_response_code(403);
        die('
        <!DOCTYPE html>
        <html>
        <head><title>403 Forbidden - Admin Privilege Required</title></head>
        <body style="font-family:sans-serif; padding:3rem; background:#f8fafc; color:#0f172a;">
            <div style="background:#fff; border:1px solid #e2e8f0; border-left:4px solid #ef4444; border-radius:8px; padding:2rem; max-width:550px; margin:auto;">
                <h3 style="color:#dc2626; margin-top:0;">Access Denied (Role Restriction)</h3>
                <p style="font-size:0.95rem; color:#475569;">You do not have administrative privileges to execute this management action. Current role: <strong>' . htmlspecialchars($_SESSION['role'] ?? 'Unknown') . '</strong>.</p>
                <a href="' . BASE_URL . '/dashboard.php" style="display:inline-block; padding:0.5rem 1rem; background:#1e293b; color:#fff; text-decoration:none; border-radius:6px; font-size:0.85rem; font-weight:600;">Return to Dashboard</a>
            </div>
        </body>
        </html>');
    }
}

function require_not_auditor(): void {
    require_login();
    if ($_SESSION['role'] === 'Auditor') {
        http_response_code(403);
        die('
        <!DOCTYPE html>
        <html>
        <head><title>403 Forbidden - Read-Only Auditor Mode</title></head>
        <body style="font-family:sans-serif; padding:3rem; background:#f8fafc; color:#0f172a;">
            <div style="background:#fff; border:1px solid #e2e8f0; border-left:4px solid #f59e0b; border-radius:8px; padding:2rem; max-width:550px; margin:auto;">
                <h3 style="color:#d97706; margin-top:0;">Read-Only Mode Active</h3>
                <p style="font-size:0.95rem; color:#475569;">Quality & Compliance Auditors possess read-only privileges. Creation, modification, and deletion operations are restricted to maintain compliance data integrity.</p>
                <a href="' . BASE_URL . '/dashboard.php" style="display:inline-block; padding:0.5rem 1rem; background:#1e293b; color:#fff; text-decoration:none; border-radius:6px; font-size:0.85rem; font-weight:600;">Return to Dashboard</a>
            </div>
        </body>
        </html>');
    }
}

function login_user(array $user): void {
    // Regenerate session ID to mitigate session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id']   = (int)$user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['logged_at'] = time();

    // Update last_login in database
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE `users` SET `last_login` = NOW() WHERE `id` = :id");
        $stmt->execute([':id' => $user['id']]);
    } catch (Exception $e) {
        // Silently ignore if logging fails
    }
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}
