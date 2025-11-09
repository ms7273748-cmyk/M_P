<?php
/**
 * functions.php — ClubSphere Utilities (PHP 8.4 compatible)
 * - PDO connection wrapper
 * - Query helper (prepared by default)
 * - Auth helpers, flash, URLs
 * - Data helpers (truncateText, formatDate, timeAgo)
 * - Misc (notifications, logs)
 */

declare(strict_types=1);

// Prevent double initialization
if (!defined('CLUBSPHERE_INITIALIZED')) {
    define('CLUBSPHERE_INITIALIZED', true);
} else {
    return; // already included
}

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration (must define config('database') or DB constants)
require_once __DIR__ . '/config.php';

/* =====================================================
 * DATABASE CONNECTION + QUERY WRAPPER
 * ===================================================== */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Expect config('database') to return:
    // ['host','port','name','charset','username','password','options'=>[]]
    $db = config('database');
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $db['host'],
        (string)$db['port'],
        $db['name'],
        $db['charset']
    );

    $defaultOptions = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $options = ($db['options'] ?? []) + $defaultOptions;

    try {
        $pdo = new PDO($dsn, $db['username'], $db['password'], $options);
    } catch (PDOException $e) {
        error_log('[DB ERROR] ' . $e->getMessage());
        http_response_code(500);
        die('<h2 style="font-family:sans-serif;color:#dc3545;text-align:center;">
                Database connection failed. Check config.php or MySQL service.
            </h2>');
    }

    return $pdo;
}

/**
 * Parameterized query helper.
 * Returns PDOStatement on success, false on failure.
 */
function dbQuery(string $sql, array $params = []): PDOStatement|false {
    $db = getDB();
    try {
        if (!$params) {
            return $db->query($sql);
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('[SQL ERROR] ' . $e->getMessage() . ' | Query: ' . $sql);
        return false;
    }
}

/* =====================================================
 * SECURITY & SANITIZATION
 * ===================================================== */
function cleanInput(mixed $data): mixed {
    if (is_array($data)) return array_map('cleanInput', $data);
    if ($data === null) return null;
    if (!is_string($data)) return $data;
    $data = trim($data);
    $data = preg_replace("/\r\n?/", "\n", $data);
    // Remove control chars
    $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $data);
    return $data;
}
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function isValidEmail(string $email): bool { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
function isValidPhone(string $phone): bool {
    $digits = preg_replace('/\D+/', '', $phone);
    $len = strlen($digits);
    return $len >= 10 && $len <= 15;
}
function isValidUrl(string $url): bool { return filter_var($url, FILTER_VALIDATE_URL) !== false; }
function generateToken(int $length = 32): string { return bin2hex(random_bytes(max(16, $length))); }

function csrf_token(): string {
    if (empty($_SESSION['csrf_token']) || (time() - (int)($_SESSION['csrf_token_time'] ?? 0) > 3600)) {
        $_SESSION['csrf_token'] = generateToken();
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf_token(?string $token): bool {
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}
function isStrongPassword(string $password): bool {
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password);
}

/* =====================================================
 * SESSION & AUTHENTICATION
 * ===================================================== */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}
function hasRole(string $role): bool {
    if (!isLoggedIn()) return false;
    $userRole = $_SESSION['user']['role'] ?? 'Member';
    $roles = ['Member','Treasurer','Secretary','VicePresident','President','Admin','SuperAdmin'];
    $roleIndex = array_search($userRole, $roles, true);
    $requiredIndex = array_search($role, $roles, true);
    return $roleIndex !== false && $requiredIndex !== false && $roleIndex >= $requiredIndex;
}
function isAdmin(): bool { return hasRole('Admin'); }
function isSuperAdmin(): bool { return hasRole('SuperAdmin'); }
function isPresident(?int $clubId = null): bool {
    if (!isLoggedIn()) return false;
    if ($clubId) {
        $stmt = dbQuery(
            "SELECT position FROM memberships WHERE user_id=? AND club_id=? AND status='Active' LIMIT 1",
            [$_SESSION['user_id'], $clubId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return ($row['position'] ?? null) === 'President';
    }
    return hasRole('President');
}
function getCurrentUser(): ?array { return isLoggedIn() ? ($_SESSION['user'] ?? null) : null; }
function requireLogin(string $url = 'login.php'): void { if (!isLoggedIn()) { setFlash('error','Please log in.'); redirect($url); } }
function requireAdmin(string $url = 'index.php'): void { if (!isAdmin()) { setFlash('error','Admin required.'); redirect($url); } }
function requireSuperAdmin(string $url = 'index.php'): void { if (!isSuperAdmin()) { setFlash('error','SuperAdmin required.'); redirect($url); } }

/* =====================================================
 * FLASH MESSAGES
 * ===================================================== */
function setFlash(string $type, string $message): void { $_SESSION['flash'][$type] = $message; }
function getFlash(string $type, bool $remove = true): ?string {
    if (empty($_SESSION['flash'][$type])) return null;
    $m = $_SESSION['flash'][$type];
    if ($remove) unset($_SESSION['flash'][$type]);
    return $m;
}
function showFlashMessages(): string {
    $html = '';
    if (!empty($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $t => $m) {
            $html .= createFlashMessage($t, (string)$m);
        }
        unset($_SESSION['flash']);
    }
    return $html;
}
function createFlashMessage(string $type, string $message): string {
    $colors = ['success'=>'#2ecc71','error'=>'#e74c3c','warning'=>'#f39c12','info'=>'#3498db','primary'=>'#ffcf70'];
    $color = $colors[$type] ?? '#3498db';
    return "<div style='border-left:4px solid {$color};padding:10px;margin:8px 0;border-radius:6px;background:rgba(255,255,255,0.1);color:#fff;'>
                <strong>".e($type).":</strong> ".e($message)."
            </div>";
}

/* =====================================================
 * REDIRECTION & URLS
 * ===================================================== */
function redirect(string $url, int $code = 302): never {
    if (!headers_sent()) {
        header("Location: {$url}", true, $code);
    } else {
        echo "<script>window.location.href=" . json_encode($url) . ";</script>";
    }
    exit;
}
function baseUrl(string $path = ''): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $https ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = rtrim(str_replace('\\','/', dirname($script)), '/.');
    $dir = ($dir === '/' ? '' : $dir);
    $suffix = ltrim($path, '/');
    return $protocol . $host . $dir . ($suffix ? '/' . $suffix : '/');
}

/* =====================================================
 * DATABASE HELPERS
 * ===================================================== */
function getUserById(int $id): ?array {
    $s = dbQuery("SELECT * FROM users WHERE user_id=? AND status='Active' LIMIT 1", [$id]);
    return $s ? $s->fetch() ?: null : null;
}
function getClubById(int $id): ?array {
    $s = dbQuery("SELECT * FROM clubs WHERE club_id=? AND status='Active' LIMIT 1", [$id]);
    return $s ? $s->fetch() ?: null : null;
}
function getUserMemberships(int $id): array {
    $sql = "SELECT m.*, c.club_name, c.club_code, c.logo
            FROM memberships m
            JOIN clubs c ON m.club_id = c.club_id
            WHERE m.user_id = ? AND m.status = 'Active' AND c.status = 'Active'
            ORDER BY m.joined_date DESC";
    $s = dbQuery($sql, [$id]);
    return $s ? $s->fetchAll() : [];
}
function isClubMember(int $userId, int $clubId): bool {
    $s = dbQuery("SELECT COUNT(*) AS cnt FROM memberships WHERE user_id=? AND club_id=? AND status='Active'", [$userId, $clubId]);
    $r = $s ? $s->fetch() : ['cnt' => 0];
    return ((int)($r['cnt'] ?? 0)) > 0;
}
function getClubMembers(int $clubId, ?string $position = null): array {
    $sql = "SELECT u.user_id, u.username, u.full_name, u.email, u.profile_image,
                   m.position, m.joined_date, m.total_contribution_points
            FROM memberships m
            JOIN users u ON m.user_id = u.user_id
            WHERE m.club_id = ? AND m.status = 'Active' AND u.status = 'Active'";
    $params = [$clubId];
    if ($position) { $sql .= " AND m.position = ?"; $params[] = $position; }
    $sql .= " ORDER BY CASE m.position
                WHEN 'President' THEN 1
                WHEN 'VicePresident' THEN 2
                WHEN 'Secretary' THEN 3
                WHEN 'Treasurer' THEN 4
                WHEN 'EventCoordinator' THEN 5
                WHEN 'SocialMediaHead' THEN 6
                ELSE 7 END, m.joined_date ASC";
    $s = dbQuery($sql, $params);
    return $s ? $s->fetchAll() : [];
}
function getUpcomingEvents(int $limit = 10, ?int $clubId = null): array {
    $sql = "SELECT e.*, c.club_name, c.club_code, u.full_name AS organizer_name
            FROM events e
            JOIN clubs c ON e.club_id = c.club_id
            JOIN users u ON e.created_by = u.user_id
            WHERE e.start_datetime > NOW() AND e.status IN ('Published','Completed')";
    $params = [];
    if ($clubId) { $sql .= " AND e.club_id = ?"; $params[] = $clubId; }
    $sql .= " ORDER BY e.start_datetime ASC LIMIT ?";
    $params[] = $limit;
    $s = dbQuery($sql, $params);
    return $s ? $s->fetchAll() : [];
}
function getRecentAnnouncements(int $limit = 10, ?int $clubId = null): array {
    $sql = "SELECT a.*, c.club_name, c.club_code, u.full_name AS author_name
            FROM announcements a
            LEFT JOIN clubs c ON a.club_id = c.club_id
            JOIN users u ON a.created_by = u.user_id
            WHERE a.status = 'Published' AND (a.expires_at IS NULL OR a.expires_at > NOW())";
    $params = [];
    if ($clubId) { $sql .= " AND (a.club_id = ? OR a.target_audience = 'All')"; $params[] = $clubId; }
    $sql .= " ORDER BY a.published_at DESC LIMIT ?";
    $params[] = $limit;
    $s = dbQuery($sql, $params);
    return $s ? $s->fetchAll() : [];
}
function createNotification(
    int $userId, string $type, string $title, string $message,
    ?string $entityType = null, ?int $entityId = null, ?string $url = null
): bool {
    $stmt = dbQuery(
        "INSERT INTO notifications (user_id, notification_type, title, message, related_entity_type, related_entity_id, action_url)
         VALUES (?,?,?,?,?,?,?)",
        [$userId, $type, $title, $message, $entityType, $entityId, $url]
    );
    return $stmt !== false;
}
function getUserNotifications(int $userId, int $limit = 10, bool $unread = false): array {
    $sql = "SELECT * FROM notifications WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW())";
    $params = [$userId];
    if ($unread) $sql .= " AND is_read = FALSE";
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    $s = dbQuery($sql, $params);
    return $s ? $s->fetchAll() : [];
}
function markNotificationAsRead(int $notificationId, int $userId): bool {
    $stmt = dbQuery("UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE notification_id = ? AND user_id = ?", [$notificationId, $userId]);
    return $stmt !== false;
}
function logActivity(int $userId, string $type, string $description, ?string $entityType = null, ?int $entityId = null): bool {
    $stmt = dbQuery(
        "INSERT INTO activity_logs (user_id, action_type, action_description, entity_type, entity_id, ip_address, user_agent)
         VALUES (?,?,?,?,?,?,?)",
        [
            $userId, $type, $description, $entityType, $entityId,
            $_SERVER['REMOTE_ADDR']  ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]
    );
    return $stmt !== false;
}

/* =====================================================
 * UTILITIES
 * ===================================================== */
function ensureDirectory(string $path): bool {
    if ($path === '') return false;
    if (is_dir($path)) return true;
    return @mkdir($path, 0755, true) || is_dir($path);
}

/* =====================================================
 * INITIALIZATION
 * ===================================================== */
$currentUser   = getCurrentUser();
$flashMessages = showFlashMessages();

// If you keep APP_TIMEZONE in env, apply it; otherwise default to UTC
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

if (!defined('BASE_URL'))   define('BASE_URL', baseUrl());
if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', __DIR__ . '/uploads/');

ensureDirectory(UPLOAD_PATH);
ensureDirectory(UPLOAD_PATH . 'profiles/');
ensureDirectory(UPLOAD_PATH . 'events/');
ensureDirectory(UPLOAD_PATH . 'announcements/');
ensureDirectory(UPLOAD_PATH . 'resources/');

// Session hardening
if (isLoggedIn()) {
    $inactive = 1800; // 30m
    $last = (int)($_SESSION['last_activity'] ?? time());
    if ((time() - $last) > $inactive) {
        $msg = 'Session timed out due to inactivity.';
        session_unset();
        session_destroy();
        session_start();
        setFlash('warning', $msg);
        redirect('login.php');
    }
    $_SESSION['last_activity'] = time();
    $regen = (int)($_SESSION['session_regenerated'] ?? 0);
    if ($regen < (time() - 300)) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = time();
    }
}

/* =====================================================
 * VIEW HELPERS NEEDED BY index.php
 * ===================================================== */

/**
 * Format a datetime string into a given PHP date() pattern safely.
 * $format: e.g., 'M j, Y' or 'g:i A'
 */
function formatDate(?string $datetime, string $format = 'Y-m-d H:i'): string {
    if (empty($datetime)) return '';
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Throwable) {
        return '';
    }
}

/**
 * Truncate plain text safely to $limit characters, preserving whole words where possible.
 */
function truncateText(?string $text, int $limit = 120, string $suffix = '...'): string {
    $text = trim((string)($text ?? ''));
    if ($text === '' || mb_strlen($text) <= $limit) return $text;
    $cut = mb_substr($text, 0, $limit);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > (int)($limit * 0.6)) {
        $cut = mb_substr($cut, 0, $space);
    }
    return rtrim($cut, " \t\n\r\0\x0B.,;:!?'\"") . $suffix;
}

/**
 * Convert datetime string into "time ago" text.
 */
function timeAgo(?string $datetime): string {
    if (empty($datetime)) return 'Unknown time';
    $timestamp = strtotime($datetime);
    if (!$timestamp) return 'Invalid time';

    $diff = time() - $timestamp;
    if ($diff < 1) return 'just now';

    $units = [
        365 * 24 * 60 * 60 => 'year',
        30  * 24 * 60 * 60 => 'month',
        7   * 24 * 60 * 60 => 'week',
        24  * 60 * 60      => 'day',
        60  * 60           => 'hour',
        60                 => 'minute',
        1                  => 'second',
    ];

    foreach ($units as $secs => $label) {
        $val = (int)floor($diff / $secs);
        if ($val >= 1) {
            return $val . ' ' . $label . ($val > 1 ? 's' : '') . ' ago';
        }
    }
    return 'just now';
}

/**
 * Simple action logger for user actions.
 */
function logAction(int $userId, string $action): void {
    $conn = getDB();
    $stmt = $conn->prepare("
        INSERT INTO logs (user_id, username, role, action, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $_SESSION['username'] ?? 'Guest',
        $_SESSION['role'] ?? 'Unknown',
        $action,
        $_SERVER['REMOTE_ADDR'] ?? 'CLI'
    ]);
}
