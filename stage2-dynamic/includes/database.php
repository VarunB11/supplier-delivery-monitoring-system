<?php
/**
 * SDPMS - Database Connection Manager (PDO)
 * Singleton Pattern with Prepared Statements & Exception Handling
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                DB_HOST,
                DB_PORT,
                DB_NAME
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Friendly error screen for college lab execution
                die('
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <title>Database Connection Notice | ProcureTrack</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 3rem; display: flex; align-items: center; justify-content: center; min-height: 80vh; }
                        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 2rem; max-width: 650px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
                        h2 { color: #ef4444; margin-top: 0; font-size: 1.4rem; }
                        p { color: #cbd5e1; line-height: 1.6; font-size: 0.95rem; }
                        .code-box { background: #0f172a; border: 1px solid #334155; padding: 1rem; border-radius: 6px; font-family: monospace; font-size: 0.85rem; color: #38bdf8; margin: 1rem 0; overflow-x: auto; }
                        ol { padding-left: 1.25rem; color: #94a3b8; font-size: 0.9rem; line-height: 1.8; }
                        strong { color: #ffffff; }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <h2>MySQL Database Connection Error</h2>
                        <p>Unable to connect to the MySQL database <strong>' . htmlspecialchars(DB_NAME) . '</strong> on host <strong>' . htmlspecialchars(DB_HOST) . '</strong>.</p>
                        <div class="code-box">PDO Exception: ' . htmlspecialchars($e->getMessage()) . '</div>
                        <p><strong>To resolve this in XAMPP:</strong></p>
                        <ol>
                            <li>Ensure Apache and MySQL services are started in the <strong>XAMPP Control Panel</strong>.</li>
                            <li>Open <strong>phpMyAdmin</strong> (<a href="http://localhost/phpmyadmin" style="color:#38bdf8;" target="_blank">http://localhost/phpmyadmin</a>).</li>
                            <li>Import the database file: <code>database/schema.sql</code></li>
                            <li>Import sample seed records: <code>database/seed.sql</code></li>
                            <li>Refresh this page.</li>
                        </ol>
                    </div>
                </body>
                </html>');
            }
        }
        return self::$instance;
    }
}
