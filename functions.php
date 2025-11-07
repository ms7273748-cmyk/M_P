<?php
/**
 * ClubSphere - Comprehensive Utility Functions (Fixed for PHP 8+)
 * Fully PDO-safe, parameterized queries, no deprecated calls
 * 
 * @version 3.3 (PDO-safe + stable)
 * @author ClubSphere
 */

// Prevent double initialization
if (!defined('CLUBSPHERE_INITIALIZED')) {
    define('CLUBSPHERE_INITIALIZED', true);
} else {
    return;
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/config.php';

/* =====================================================
 * DATABASE CONNECTION + QUERY WRAPPER
 * ===================================================== */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $db = config('database');
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );

    try {
        $pdo = new PDO($dsn, $db['username'], $db['password'], $db['options']);
    } catch (PDOException $e) {
        error_log('[DB ERROR] ' . $e->getMessage());
        http_response_code(500);
        die('<h2 style="font-family:sans-serif;color:#dc3545;text-align:center;">
                Database connection failed. Check config.php or XAMPP MySQL.
             </h2>');
    }

    return $pdo;
}

/**
 * Safe parameterized query helper.
 * Always use this instead of raw PDO::query() when parameters are passed.
 */
function dbQuery(string $sql, array $params = []): PDOStatement|false {
    $db = getDB();
    try {
        if (empty($params)) {
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
function cleanInput($data) {
    if (is_array($data)) return array_map('cleanInput', $data);
    if ($data === null) return null;
    if (!is_string($data)) return $data;
    $data = trim($data);
    $data = preg_replace("/\r\n?/", "\n", $data);
    $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $data);
    return $data;
}
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function isValidEmail($email) { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
function isValidPhone($phone) {
    $digits = preg_replace('/\D+/', '', (string)$phone);
    return strlen($digits) >= 10 && strlen($digits) <= 15;
}
function isValidUrl($url) { return filter_var($url, FILTER_VALIDATE_URL) !== false; }
function generateToken($length = 32) { return bin2hex(random_bytes(max(16, (int)$length))); }
function csrf_token() {
    if (empty($_SESSION['csrf_token']) || (time() - ($_SESSION['csrf_token_time'] ?? 0) > 3600)) {
        $_SESSION['csrf_token'] = generateToken();
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf_token($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}
function hashPassword($password) { return password_hash((string)$password, PASSWORD_BCRYPT, ['cost' => 12]); }
function verifyPassword($password, $hash) { return is_string($hash) && password_verify((string)$password, $hash); }
function isStrongPassword($password) {
    return strlen($password) >= 8 &&
        preg_match('/[A-Z]/', $password) &&
        preg_match('/[a-z]/', $password) &&
        preg_match('/[0-9]/', $password) &&
        preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password);
}

/* =====================================================
 * SESSION & AUTHENTICATION
 * ===================================================== */
function isLoggedIn() { return !empty($_SESSION['user_id']); }
function hasRole($role) {
    if (!isLoggedIn()) return false;
    $userRole = $_SESSION['user']['role'] ?? 'Member';
    $roles = ['Member','Treasurer','Secretary','VicePresident','President','Admin','SuperAdmin'];
    $roleIndex = array_search($userRole,$roles,true);
    $requiredIndex = array_search($role,$roles,true);
    return $roleIndex !== false && $requiredIndex !== false && $roleIndex >= $requiredIndex;
}
function isAdmin() { return hasRole('Admin'); }
function isSuperAdmin() { return hasRole('SuperAdmin'); }
function isPresident($clubId=null) {
    if (!isLoggedIn()) return false;
    if ($clubId) {
        $stmt = dbQuery("SELECT position FROM memberships WHERE user_id=? AND club_id=? AND status='Active' LIMIT 1",
                        [$_SESSION['user_id'],$clubId]);
        $row = $stmt? $stmt->fetch(PDO::FETCH_ASSOC):null;
        return $row && $row['position']==='President';
    }
    return hasRole('President');
}
function getCurrentUser() { return isLoggedIn()?($_SESSION['user']??null):null; }
function requireLogin($url='login.php'){ if(!isLoggedIn()){setFlash('error','Please log in.');redirect($url);} }
function requireAdmin($url='index.php'){ if(!isAdmin()){setFlash('error','Admin required.');redirect($url);} }
function requireSuperAdmin($url='index.php'){ if(!isSuperAdmin()){setFlash('error','SuperAdmin required.');redirect($url);} }

/* =====================================================
 * FLASH MESSAGES
 * ===================================================== */
function setFlash($k,$m){$_SESSION['flash'][$k]=$m;}
function getFlash($k,$r=true){if(empty($_SESSION['flash'][$k]))return null;$m=$_SESSION['flash'][$k];if($r)unset($_SESSION['flash'][$k]);return $m;}
function showFlashMessages(){
    $h='';if(!empty($_SESSION['flash'])){foreach($_SESSION['flash'] as $t=>$m){$h.=createFlashMessage($t,$m);}unset($_SESSION['flash']);}
    return $h;
}
function createFlashMessage($t,$m){
    $colors=['success'=>'#2ecc71','error'=>'#e74c3c','warning'=>'#f39c12','info'=>'#3498db','primary'=>'#ffcf70'];
    $c=$colors[$t]??'#3498db';$msg=e($m);
    return "<div style='border-left:4px solid {$c};padding:10px;margin:8px 0;border-radius:6px;background:rgba(255,255,255,0.1);color:#fff;'>
            <strong>{$t}:</strong> {$msg}</div>";
}

/* =====================================================
 * REDIRECTION & URLS
 * ===================================================== */
function redirect($url,$code=302){if(!headers_sent())header("Location: {$url}",true,$code);else echo"<script>window.location.href=".json_encode($url).";</script>";exit;}
function baseUrl($path=''){
    $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])&&$_SERVER['HTTP_X_FORWARDED_PROTO']==='https');
    $protocol=$https?'https://':'http://';$host=$_SERVER['HTTP_HOST']??'localhost';
    $script=$_SERVER['SCRIPT_NAME']??'';$dir=rtrim(str_replace('\\','/',dirname($script)),'/.');
    $dir=($dir==='/'?'':$dir);$suffix=ltrim($path,'/');
    return $protocol.$host.$dir.($suffix?'/'.$suffix:'/'); }

/* =====================================================
 * DATABASE HELPERS (use dbQuery)
 * ===================================================== */
function getUserById($id){$s=dbQuery("SELECT * FROM users WHERE user_id=? AND status='Active' LIMIT 1",[$id]);return $s?$s->fetch(PDO::FETCH_ASSOC):null;}
function getClubById($id){$s=dbQuery("SELECT * FROM clubs WHERE club_id=? AND status='Active' LIMIT 1",[$id]);return $s?$s->fetch(PDO::FETCH_ASSOC):null;}
function getUserMemberships($id){
    $sql="SELECT m.*,c.club_name,c.club_code,c.logo FROM memberships m JOIN clubs c ON m.club_id=c.club_id
          WHERE m.user_id=? AND m.status='Active' AND c.status='Active' ORDER BY m.joined_date DESC";
    $s=dbQuery($sql,[$id]);return $s?$s->fetchAll(PDO::FETCH_ASSOC):[];
}
function isClubMember($uid,$cid){
    $s=dbQuery("SELECT COUNT(*) cnt FROM memberships WHERE user_id=? AND club_id=? AND status='Active'",[$uid,$cid]);
    $r=$s?$s->fetch(PDO::FETCH_ASSOC):['cnt'=>0];return ((int)$r['cnt'])>0;
}
function getClubMembers($cid,$pos=null){
    $sql="SELECT u.user_id,u.username,u.full_name,u.email,u.profile_image,m.position,m.joined_date,m.total_contribution_points
          FROM memberships m JOIN users u ON m.user_id=u.user_id
          WHERE m.club_id=? AND m.status='Active' AND u.status='Active'";
    $p=[$cid];if($pos){$sql.=" AND m.position=?";$p[]=$pos;}
    $sql.=" ORDER BY CASE m.position WHEN 'President' THEN 1 WHEN 'VicePresident' THEN 2 WHEN 'Secretary' THEN 3
           WHEN 'Treasurer' THEN 4 WHEN 'EventCoordinator' THEN 5 WHEN 'SocialMediaHead' THEN 6 ELSE 7 END, m.joined_date ASC";
    $s=dbQuery($sql,$p);return $s?$s->fetchAll(PDO::FETCH_ASSOC):[];
}
function getUpcomingEvents($limit=10,$clubId=null){
    $sql="SELECT e.*,c.club_name,c.club_code,u.full_name organizer_name FROM events e
          JOIN clubs c ON e.club_id=c.club_id JOIN users u ON e.created_by=u.user_id
          WHERE e.start_datetime>NOW() AND e.status IN ('Published','Completed')";
    $p=[];if($clubId){$sql.=" AND e.club_id=?";$p[]=$clubId;}
    $sql.=" ORDER BY e.start_datetime ASC LIMIT ?";$p[]=(int)$limit;
    $s=dbQuery($sql,$p);return $s?$s->fetchAll(PDO::FETCH_ASSOC):[];
}
function getRecentAnnouncements($limit=10,$clubId=null){
    $sql="SELECT a.*,c.club_name,c.club_code,u.full_name author_name FROM announcements a
          LEFT JOIN clubs c ON a.club_id=c.club_id JOIN users u ON a.created_by=u.user_id
          WHERE a.status='Published' AND (a.expires_at IS NULL OR a.expires_at>NOW())";
    $p=[];if($clubId){$sql.=" AND (a.club_id=? OR a.target_audience='All')";$p[]=$clubId;}
    $sql.=" ORDER BY a.published_at DESC LIMIT ?";$p[]=(int)$limit;
    $s=dbQuery($sql,$p);return $s?$s->fetchAll(PDO::FETCH_ASSOC):[];
}
function createNotification($uid,$type,$title,$msg,$ent=null,$eid=null,$url=null){
    return dbQuery("INSERT INTO notifications(user_id,notification_type,title,message,related_entity_type,related_entity_id,action_url)
                    VALUES(?,?,?,?,?,?,?)",[$uid,$type,$title,$msg,$ent,$eid,$url]);
}
function getUserNotifications($uid,$limit=10,$unread=false){
    $sql="SELECT * FROM notifications WHERE user_id=? AND (expires_at IS NULL OR expires_at>NOW())";
    $p=[$uid];if($unread)$sql.=" AND is_read=FALSE";$sql.=" ORDER BY created_at DESC LIMIT ?";$p[]=(int)$limit;
    $s=dbQuery($sql,$p);return $s?$s->fetchAll(PDO::FETCH_ASSOC):[];
}
function markNotificationAsRead($nid,$uid){
    return dbQuery("UPDATE notifications SET is_read=TRUE,read_at=NOW() WHERE notification_id=? AND user_id=?",[$nid,$uid]);
}
function logActivity($uid,$type,$desc,$ent=null,$eid=null){
    return dbQuery("INSERT INTO activity_logs(user_id,action_type,action_description,entity_type,entity_id,ip_address,user_agent)
                    VALUES(?,?,?,?,?,?,?)",[$uid,$type,$desc,$ent,$eid,$_SERVER['REMOTE_ADDR']??'0.0.0.0',$_SERVER['HTTP_USER_AGENT']??'']);
}

/* =====================================================
 * UTILITIES
 * ===================================================== */
function ensureDirectory($p){if($p===''||$p===null)return false;if(is_dir($p))return true;return @mkdir($p,0755,true)||is_dir($p);}

/* =====================================================
 * INITIALIZATION
 * ===================================================== */
$currentUser=getCurrentUser();
$flashMessages=showFlashMessages();
date_default_timezone_set($_ENV['APP_TIMEZONE']??'UTC');
if(!defined('BASE_URL'))define('BASE_URL',baseUrl());
if(!defined('UPLOAD_PATH'))define('UPLOAD_PATH',__DIR__.'/uploads/');
ensureDirectory(UPLOAD_PATH);ensureDirectory(UPLOAD_PATH.'profiles/');ensureDirectory(UPLOAD_PATH.'events/');ensureDirectory(UPLOAD_PATH.'announcements/');ensureDirectory(UPLOAD_PATH.'resources/');

if(isLoggedIn()){
    $inactive=1800;$last=$_SESSION['last_activity']??time();
    if((time()-$last)>$inactive){$msg='Session timed out due to inactivity.';session_unset();session_destroy();session_start();setFlash('warning',$msg);redirect('login.php');}
    $_SESSION['last_activity']=time();
    $regen=$_SESSION['session_regenerated']??0;if($regen<(time()-300)){session_regenerate_id(true);$_SESSION['session_regenerated']=time();}
}

// Converts a datetime string (e.g. '2025-11-07 01:23:45')
// into a human-readable "time ago" format.
function timeAgo($datetime) {
    if (empty($datetime)) return 'Unknown time';

    $timestamp = strtotime($datetime);
    if (!$timestamp) return 'Invalid time';

    $diff = time() - $timestamp;
    if ($diff < 1) return 'just now';

    $units = [
        365 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60  => 'month',
        7 * 24 * 60 * 60   => 'week',
        24 * 60 * 60       => 'day',
        60 * 60            => 'hour',
        60                 => 'minute',
        1                  => 'second'
    ];

    foreach ($units as $secs => $label) {
        $val = floor($diff / $secs);
        if ($val >= 1) {
            return $val . ' ' . $label . ($val > 1 ? 's' : '') . ' ago';
        }
    }
}
