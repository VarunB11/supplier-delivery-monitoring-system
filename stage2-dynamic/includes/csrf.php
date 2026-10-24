<?php
/**
 * SDPMS - Cross-Site Request Forgery (CSRF) Protection
 * Cryptographic Token Generation & Form Verification
 */

require_once __DIR__ . '/config.php';

function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function render_csrf_input(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf_token(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            die('
            <!DOCTYPE html>
            <html>
            <head><title>403 Forbidden - CSRF Validation Failed</title></head>
            <body style="font-family:sans-serif; padding:3rem; background:#f8fafc; color:#0f172a;">
                <div style="background:#fff; border:1px solid #fee2e2; border-left:4px solid #ef4444; border-radius:8px; padding:2rem; max-width:550px; margin:auto;">
                    <h3 style="color:#dc2626; margin-top:0;">Security Error: Invalid or Expired CSRF Token</h3>
                    <p style="font-size:0.95rem; color:#475569;">Your form session timed out or a cross-site request violation was detected. Please go back and refresh the form before submitting.</p>
                    <a href="javascript:history.back()" style="display:inline-block; padding:0.5rem 1rem; background:#1e293b; color:#fff; text-decoration:none; border-radius:6px; font-size:0.85rem; font-weight:600;">Return to Form</a>
                </div>
            </body>
            </html>');
        }
    }
}
