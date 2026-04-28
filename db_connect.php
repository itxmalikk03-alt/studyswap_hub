<?php
// ============================================================
//  StudySwap Hub — db_connect.php
//  Single file that handles DB connection + session start
//  Works on Windows (XAMPP) and Linux (LAMP/cPanel)
// ============================================================

// ── Database credentials ─────────────────────────────────────
// Change these to match your MySQL setup
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'studyswap_hub');
define('DB_USER',    'root');      // ← your MySQL username
define('DB_PASS',    '');          // ← your MySQL password (blank on XAMPP default)
define('DB_CHARSET', 'utf8mb4');

// ── PDO connection ────────────────────────────────────────────
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    die('<div style="font-family:sans-serif;padding:30px;color:#c0392b;">
        <h2>Database Connection Error</h2>
        <p>Could not connect to the database. Please check <strong>db_connect.php</strong>:</p>
        <ul>
          <li>Make sure MySQL / XAMPP is running</li>
          <li>Check DB_USER and DB_PASS values</li>
          <li>Make sure the database <strong>studyswap_hub</strong> exists (import database.sql)</li>
        </ul>
        <p style="color:#888;font-size:.85em;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>');
}

// ── Session start ─────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,   // 1 day
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ═══════════════════════════════════════════════════════════════
//  HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════

/** Is the current visitor logged in? */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

/** Redirect to login if not logged in. Saves intended URL. */
function requireLogin(string $loginPage = 'login.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . $loginPage);
        exit;
    }
}

/** Return the logged-in user's ID (or null). */
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/** Escape output to prevent XSS. Always use when printing user data. */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Send a notification row to a user. */
function sendNotification(PDO $pdo, int $userId, string $type, string $message, string $link = ''): void {
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $type, $message, $link]);
}

/** Count unread notifications for a user. */
function unreadCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
    );
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/** Return the CSS badge class for a listing type. */
function badgeClass(string $type): string {
    return match($type) {
        'free'  => 'badge-free',
        'sale'  => 'badge-sale',
        default => 'badge-swap',
    };
}

/** Return the human-readable label for a listing type. */
function badgeLabel(string $type): string {
    return match($type) {
        'free'  => 'Free',
        'sale'  => 'For Sale',
        default => 'Swap',
    };
}

/** Store a flash message in session and redirect. */
function redirectWith(string $url, string $key, string $value): void {
    $_SESSION[$key] = $value;
    header('Location: ' . $url);
    exit;
}

/** Read and remove a flash message from session. */
function flash(string $key): string {
    $msg = $_SESSION[$key] ?? '';
    unset($_SESSION[$key]);
    return (string)$msg;
}
