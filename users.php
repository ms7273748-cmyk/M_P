<?php
/***************************************************************
 * users.php — Admin Users Management
 * Features:
 * - Admin-only guard
 * - List users with search, filter (role/status/club), pagination
 * - Create user (modal)
 * - Edit user (modal)
 * - Delete user (modal confirm)
 * - Toggle status (Active/Inactive)
 * - Bulk actions (activate, deactivate, delete)
 * - Club association picklist
 * - Glossy UI: matches admin_dashboard.php styling vibes
 *
 * Notes:
 * - Assumes getDB(), isAdmin(), redirect(), setFlash(), cleanInput(),
 *   baseUrl(), logActivity() exist in functions/config.
 * - Uses PDO style via getDB() (as in your admin_dashboard.php).
 ***************************************************************/

session_start();
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../header.php';

// ─────────────────────────────────────────────────────────────
// Admin-only guard
// ─────────────────────────────────────────────────────────────
if (!isAdmin()) {
    setFlash('error', 'Access denied. Admin privileges required.');
    redirect('login.php');
    exit;
}

// ─────────────────────────────────────────────────────────────
// Utilities (local fallbacks if not present in functions.php)
// ─────────────────────────────────────────────────────────────
if (!function_exists('requirePost')) {
    function requirePost(array $keys, array $src) {
        foreach ($keys as $k) {
            if (!isset($src[$k])) {
                throw new Exception("Missing required field: $k");
            }
        }
    }
}
if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
if (!function_exists('paginate')) {
    function paginate($total, $page, $limit) {
        $pages = max(1, (int)ceil($total / $limit));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $limit;
        return [$page, $pages, $offset];
    }
}
if (!function_exists('currentUrlNo')) {
    function currentUrlNo(array $dropKeys = []) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        $qs = $_GET;
        foreach ($dropKeys as $k) unset($qs[$k]);
        $query = http_build_query($qs);
        return $scheme . '://' . $host . $uri . ($query ? ('?' . $query) : '');
    }
}

// ─────────────────────────────────────────────────────────────
// DB
// ─────────────────────────────────────────────────────────────
$db = getDB();

// ─────────────────────────────────────────────────────────────
// Handle POST actions
// ─────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
        switch ($action) {
            case 'create_user':
                requirePost(['full_name','username','email','password','role','status'], $_POST);
                $full_name = cleanInput($_POST['full_name']);
                $username  = cleanInput($_POST['username']);
                $email     = cleanInput($_POST['email']);
                $password  = $_POST['password']; // per your prior ask (no hashing). Bad practice.
                $role      = cleanInput($_POST['role']);
                $status    = cleanInput($_POST['status']);
                $club_id   = !empty($_POST['club_id']) ? (int)$_POST['club_id'] : null;

                if (!$full_name || !$username || !$email || !$password) {
                    throw new Exception('All required fields must be filled.');
                }
                if (!isValidEmail($email)) {
                    throw new Exception('Invalid email address.');
                }
                if (!in_array($role, ['SuperAdmin','Admin','President','Member'], true)) {
                    throw new Exception('Invalid role.');
                }
                if (!in_array($status, ['Active','Inactive'], true)) {
                    throw new Exception('Invalid status.');
                }

                // Ensure unique username/email
                $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $check->execute([$username, $email]);
                if ($check->fetchColumn() > 0) {
                    throw new Exception('Username or Email already exists.');
                }

                $stmt = $db->prepare("
                    INSERT INTO users (full_name, username, email, password, role, status, club_id, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$full_name, $username, $email, $password, $role, $status, $club_id]);

                logActivity($_SESSION['user_id'] ?? null, 'USER_CREATED', "Created user: $full_name ($username)");
                setFlash('success', 'User created successfully.');
                redirect('users.php');
                exit;

            case 'update_user':
                requirePost(['user_id','full_name','username','email','role','status'], $_POST);
                $user_id   = (int)$_POST['user_id'];
                $full_name = cleanInput($_POST['full_name']);
                $username  = cleanInput($_POST['username']);
                $email     = cleanInput($_POST['email']);
                $role      = cleanInput($_POST['role']);
                $status    = cleanInput($_POST['status']);
                $club_id   = !empty($_POST['club_id']) ? (int)$_POST['club_id'] : null;
                $new_pass  = $_POST['password'] ?? '';

                if (!$user_id || !$full_name || !$username || !$email) {
                    throw new Exception('Missing required fields.');
                }
                if (!isValidEmail($email)) {
                    throw new Exception('Invalid email address.');
                }
                if (!in_array($role, ['SuperAdmin','Admin','President','Member'], true)) {
                    throw new Exception('Invalid role.');
                }
                if (!in_array($status, ['Active','Inactive'], true)) {
                    throw new Exception('Invalid status.');
                }

                // Unique email/username (excluding this user)
                $chk = $db->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
                $chk->execute([$username, $email, $user_id]);
                if ($chk->fetchColumn() > 0) {
                    throw new Exception('Username or Email already used by another account.');
                }

                if ($new_pass !== '') {
                    $q = "
                        UPDATE users
                           SET full_name = ?, username = ?, email = ?, password = ?, role = ?, status = ?, club_id = ?, updated_at = NOW()
                         WHERE user_id = ?
                    ";
                    $params = [$full_name,$username,$email,$new_pass,$role,$status,$club_id,$user_id];
                } else {
                    $q = "
                        UPDATE users
                           SET full_name = ?, username = ?, email = ?, role = ?, status = ?, club_id = ?, updated_at = NOW()
                         WHERE user_id = ?
                    ";
                    $params = [$full_name,$username,$email,$role,$status,$club_id,$user_id];
                }
                $up = $db->prepare($q);
                $up->execute($params);

                logActivity($_SESSION['user_id'] ?? null, 'USER_UPDATED', "Updated user ID: $user_id");
                setFlash('success', 'User updated successfully.');
                redirect('users.php');
                exit;

            case 'delete_user':
                requirePost(['user_id'], $_POST);
                $user_id = (int)$_POST['user_id'];
                if (!$user_id) throw new Exception('Invalid user ID.');

                // Prevent deleting self (optional)
                if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $user_id) {
                    throw new Exception("You can't delete your own account while logged in.");
                }

                $del = $db->prepare("DELETE FROM users WHERE user_id = ?");
                $del->execute([$user_id]);

                logActivity($_SESSION['user_id'] ?? null, 'USER_DELETED', "Deleted user ID: $user_id");
                setFlash('success', 'User deleted successfully.');
                redirect('users.php');
                exit;

            case 'toggle_status':
                requirePost(['user_id','new_status'], $_POST);
                $user_id = (int)$_POST['user_id'];
                $new_status = cleanInput($_POST['new_status']);
                if (!in_array($new_status, ['Active','Inactive'], true)) {
                    throw new Exception('Invalid status.');
                }
                $t = $db->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?");
                $t->execute([$new_status, $user_id]);

                logActivity($_SESSION['user_id'] ?? null, 'USER_STATUS_CHANGED', "User $user_id status -> $new_status");
                setFlash('success', "Status updated to $new_status.");
                redirect('users.php');
                exit;

            case 'bulk_action':
                requirePost(['bulk_action'], $_POST);
                $bulk = cleanInput($_POST['bulk_action']);
                $ids = isset($_POST['selected_ids']) && is_array($_POST['selected_ids']) ? array_map('intval', $_POST['selected_ids']) : [];
                if (!$ids) throw new Exception('No users selected.');

                if ($bulk === 'activate') {
                    $in  = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $db->prepare("UPDATE users SET status = 'Active', updated_at = NOW() WHERE user_id IN ($in)");
                    $stmt->execute($ids);
                    logActivity($_SESSION['user_id'] ?? null, 'USERS_BULK_ACTIVATE', 'Activated users: '.implode(',',$ids));
                    setFlash('success', 'Selected users activated.');
                } elseif ($bulk === 'deactivate') {
                    $in  = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $db->prepare("UPDATE users SET status = 'Inactive', updated_at = NOW() WHERE user_id IN ($in)");
                    $stmt->execute($ids);
                    logActivity($_SESSION['user_id'] ?? null, 'USERS_BULK_DEACTIVATE', 'Deactivated users: '.implode(',',$ids));
                    setFlash('success', 'Selected users deactivated.');
                } elseif ($bulk === 'delete') {
                    // (Optional) prevent self-delete in bulk
                    if (!empty($_SESSION['user_id'])) {
                        $ids = array_values(array_filter($ids, fn($id) => (int)$id !== (int)$_SESSION['user_id']));
                    }
                    if ($ids) {
                        $in  = implode(',', array_fill(0, count($ids), '?'));
                        $stmt = $db->prepare("DELETE FROM users WHERE user_id IN ($in)");
                        $stmt->execute($ids);
                        logActivity($_SESSION['user_id'] ?? null, 'USERS_BULK_DELETE', 'Deleted users: '.implode(',',$ids));
                        setFlash('success', 'Selected users deleted.');
                    } else {
                        setFlash('warning', 'No valid users to delete (cannot delete your own account).');
                    }
                } else {
                    throw new Exception('Invalid bulk action.');
                }
                redirect('users.php');
                exit;

            default:
                throw new Exception('Unknown action.');
        }
    }
} catch (Exception $e) {
    error_log('Users action error: ' . $e->getMessage());
    setFlash('error', $e->getMessage());
    // fall-through to view with flash
}

// ─────────────────────────────────────────────────────────────
// GET filters and pagination
// ─────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$role   = $_GET['role']   ?? '';
$status = $_GET['status'] ?? '';
$clubf  = $_GET['club']   ?? '';

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10; // adjustable
$offset = 0;

$where  = [];
$params = [];

// filters
if ($search !== '') {
    $where[] = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($role !== '' && in_array($role, ['SuperAdmin','Admin','President','Member'], true)) {
    $where[] = 'u.role = ?';
    $params[] = $role;
}
if ($status !== '' && in_array($status, ['Active','Inactive'], true)) {
    $where[] = 'u.status = ?';
    $params[] = $status;
}
if ($clubf !== '' && $clubf !== 'all') {
    $where[] = 'u.club_id = ?';
    $params[] = (int)$clubf;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count total
$countSql = "SELECT COUNT(*) FROM users u $whereSql";
$sc = $db->prepare($countSql);
$sc->execute($params);
$total = (int)$sc->fetchColumn();

list($page, $pages, $offset) = paginate($total, $page, $limit);

// Fetch data
$sql = "
    SELECT 
        u.user_id, u.full_name, u.username, u.email, u.role, u.status, u.club_id, u.profile_image, u.created_at, u.updated_at,
        c.club_name
    FROM users u
    LEFT JOIN clubs c ON u.club_id = c.club_id
    $whereSql
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
";
$st = $db->prepare($sql);
$st->execute($params);
$users = $st->fetchAll(PDO::FETCH_ASSOC);

// Clubs for filters/forms
$cst = $db->query("SELECT club_id, club_name FROM clubs WHERE status = 'Active' ORDER BY club_name");
$clubs = $cst ? $cst->fetchAll(PDO::FETCH_ASSOC) : [];

// ─────────────────────────────────────────────────────────────
// HTML starts
// ─────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Users - Admin | ClubSphere</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <style>
/* =======================
   Glossy Admin Styles
   (aligned with your admin_dashboard look & feel)
   ======================= */
:root{
  --bg:#f6f7f9;
  --surface:#ffffff;
  --surface-alt:#fbfbfd;
  --text:#1d2433;
  --text-soft:#4a5568;
  --muted:#8a94a6;
  --border:#e6e8ee;
  --ring:#d9dfe7;
  --accent:#cfa54a;
  --accent-strong:#b98b2e;
  --accent-soft:#f6e7c5;
  --success:#2e7d32;
  --danger:#c62828;
  --warn:#e67e22;
  --shadow-sm:0 1px 2px rgba(16,24,40,.06);
  --shadow-md:0 4px 10px rgba(16,24,40,.08);
  --shadow-lg:0 14px 30px rgba(16,24,40,.12);
  --radius:14px;
}

/* Layout */
html,body{height:100%}
body{
  background:var(--bg);
  color:var(--text);
  font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
  line-height:1.55;
  -webkit-font-smoothing:antialiased;
  -moz-osx-font-smoothing:grayscale;
}

.dashboard-container{
  display:flex; min-height:100vh; background:var(--bg);
}

/* Sidebar (basic links just like admin_dashboard) */
.sidebar{
  width:280px; position:fixed; top:0; left:0; bottom:0;
  background:var(--surface); border-right:1px solid var(--border);
  box-shadow:var(--shadow-sm); z-index:100; padding-top:70px;
}
.sidebar-header{padding:18px 20px; border-bottom:1px solid var(--border); background:linear-gradient(0deg,#fff,#fff9)}
.user-info{display:flex; align-items:center; gap:12px}
.user-avatar{width:50px;height:50px;border-radius:50%;object-fit:cover;border:2px solid var(--accent-soft)}
.user-details h3{margin:0;font-size:1rem;font-weight:800}
.user-details p{margin:0;color:var(--muted);font-size:.875rem}
.sidebar-menu{padding:14px}
.menu-title{color:var(--text-soft);font-size:.75rem;text-transform:uppercase;font-weight:800;margin:0 0 10px 8px}
.menu-items{list-style:none;margin:0;padding:0}
.menu-item{margin-bottom:6px}
.menu-link{
  display:flex;align-items:center;gap:10px;padding:10px 12px;margin:0 6px;border-radius:10px;
  text-decoration:none;color:var(--text-soft);border:1px solid transparent;transition:.2s ease;
}
.menu-link i{width:18px;text-align:center;color:var(--muted)}
.menu-link:hover{background:linear-gradient(180deg,#fff,#fafbff);border-color:var(--border);color:var(--text)}
.menu-link.active{
  background:linear-gradient(180deg,#fff7e2,#fff); border-color:var(--accent);
  box-shadow:inset 0 0 0 1px rgba(207,165,74,.25), var(--shadow-sm); color:var(--text)
}
.menu-link.active i{color:var(--accent)}

/* Main */
.main-content{flex:1;margin-left:280px;padding:28px;min-height:100vh}

/* Header */
.page-header{
  display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;padding:18px 20px;
  background:var(--surface); border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow-sm)
}
.page-title{
  font-family:"Space Grotesk",Inter,system-ui; font-size:1.6rem; font-weight:800; letter-spacing:.2px; display:flex; gap:10px; align-items:center;
}
.page-title i{
  color:var(--accent);
  background:linear-gradient(180deg,#fff7e2,#ffe9b6);
  border:1px solid #f0d899;
  width:36px;height:36px;border-radius:10px; display:inline-grid;place-items:center
}
.header-actions{display:flex;gap:10px;flex-wrap:wrap}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;font-weight:700;font-size:.95rem;cursor:pointer;border:1px solid transparent;text-decoration:none;transition:.2s ease}
.btn:active{transform:translateY(1px)}
.btn-primary{background:linear-gradient(180deg,#ffefc7,#f6da93);border-color:#f0cf7a;color:#3b2e10;box-shadow:0 6px 14px rgba(207,165,74,.24),var(--shadow-sm)}
.btn-primary:hover{background:linear-gradient(180deg,#ffe6a0,#f3ce75)}
.btn-secondary{background:#fff;color:var(--text);border-color:var(--border)}
.btn-secondary:hover{border-color:var(--ring);box-shadow:var(--shadow-sm)}
.btn-danger{background:#fde7e7;border-color:#f2cccc;color:#8a1f1f}
.btn-danger:hover{filter:brightness(.98)}
.btn-outline{background:#fff;border-color:var(--border);color:var(--text-soft)}
.btn-outline:hover{border-color:var(--ring)}

/* Filters Card */
.filters-card, .table-card{
  background:var(--surface); border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow-sm); margin-bottom:20px; overflow:hidden
}
.card-header{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface-alt)}
.card-title{margin:0;font-size:1.05rem;font-weight:800}
.card-content{padding:16px}

/* Form controls */
.form-row{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
.form-group{grid-column:span 3}
.form-label{display:block;margin-bottom:6px;color:var(--text);font-weight:700}
.form-control{width:100%;padding:10px 12px;border-radius:10px;border:1px solid var(--border);background:#fff;color:var(--text);transition:.2s ease}
.form-control:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 4px rgba(207,165,74,.18)}
.form-select{appearance:none}
.form-actions{display:flex;gap:8px;align-items:end}

/* Table */
.table-wrapper{overflow:auto}
.table{width:100%;border-collapse:separate;border-spacing:0}
.table thead th{position:sticky;top:0;background:linear-gradient(180deg,#ffffff,#fbfbfd);border-bottom:1px solid var(--border);padding:12px;text-align:left;font-size:.9rem;color:var(--text-soft)}
.table tbody td{border-bottom:1px solid #f2f3f7;padding:12px;vertical-align:middle}
.table tr:hover td{background:#fffdf7}
.badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:.75rem;font-weight:800;border:1px solid var(--border);background:#fff}
.badge.role-admin{background:#fde7e7;color:#8a1f1f}
.badge.role-super{background:#f9e8ff;color:#7a1fa1;border-color:#eddbf8}
.badge.role-president{background:#fff3d9;color:#6d4b12}
.badge.role-member{background:#eef2f7;color:#4b5a71}
.badge.status-active{background:#e6f6ea;color:#1e6f25;border-color:#c8e8cf}
.badge.status-inactive{background:#f6e6e6;color:#8a1f1f;border-color:#ebd0d0}

/* Pagination */
.pagination{display:flex;gap:6px;flex-wrap:wrap}
.page-link{padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#fff;text-decoration:none;color:var(--text-soft)}
.page-link.active{border-color:var(--accent);box-shadow:inset 0 0 0 1px rgba(207,165,74,.25);color:#3b2e10;background:linear-gradient(180deg,#fff7e2,#fff)}
.page-link:hover{border-color:var(--ring)}

/* Modals */
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1000;align-items:center;justify-content:center}
.modal.show{display:flex}
.modal-content{background:var(--surface);border:1px solid var(--border);border-radius:18px;width:92%;max-width:720px;max-height:85vh;overflow:auto;box-shadow:var(--shadow-lg)}
.modal-header{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface-alt)}
.modal-title{font-size:1.1rem;font-weight:800;margin:0}
.modal-close{background:none;border:none;color:var(--muted);font-size:1.4rem;cursor:pointer;padding:4px 8px;border-radius:8px}
.modal-close:hover{background:#f4f6fa;color:var(--text)}
.modal-body{padding:18px}
.modal-footer{padding:16px 18px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;background:#fff}

/* Avatars */
.avatar-sm{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--accent-soft)}

/* Responsive */
@media (max-width:1100px){
  .form-group{grid-column:span 6}
}
@media (max-width:700px){
  .form-group{grid-column:span 12}
  .main-content{padding:18px}
}
  </style>
</head>
<body>
<div class="dashboard-container">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="user-info">
        <?php
          // fetch current user details if available
          $currentUser = $_SESSION['user'] ?? [];
          // try helpers from your stack:
          $cu_full = $currentUser['full_name'] ?? ($_SESSION['full_name'] ?? 'Admin User');
          $cu_email = $currentUser['email'] ?? ($_SESSION['email'] ?? 'admin@example.com');
          $cu_role  = $currentUser['role']  ?? ($_SESSION['role']  ?? 'Admin');
          $cu_img   = $currentUser['profile_image'] ?? '';
          $avatar   = $cu_img ? baseUrl('uploads/profiles/' . $cu_img) : (function_exists('getGravatarUrl') ? getGravatarUrl($cu_email) : 'https://www.gravatar.com/avatar?d=mp&s=80');
        ?>
        <img class="user-avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="User">
        <div class="user-details">
          <h3><?php echo htmlspecialchars($cu_full); ?></h3>
          <p><?php echo htmlspecialchars($cu_role); ?></p>
        </div>
      </div>
    </div>

    <nav class="sidebar-menu">
      <div class="menu-title">Main</div>
      <ul class="menu-items">
        <li class="menu-item"><a href="admin_dashboard.php" class="menu-link"><i class="fas fa-gauge"></i>Dashboard</a></li>
        <li class="menu-item"><a href="users.php" class="menu-link active"><i class="fas fa-users"></i>Users</a></li>
        <li class="menu-item"><a href="clubs.php" class="menu-link"><i class="fas fa-building"></i>Clubs</a></li>
      </ul>

      <div class="menu-title">Content</div>
      <ul class="menu-items">
        <li class="menu-item"><a href="events.php" class="menu-link"><i class="fas fa-calendar-alt"></i>Events</a></li>
        <li class="menu-item"><a href="announcements.php" class="menu-link"><i class="fas fa-bullhorn"></i>Announcements</a></li>
        <li class="menu-item"><a href="resources.php" class="menu-link"><i class="fas fa-folder"></i>Resources</a></li>
      </ul>

      <div class="menu-title">System</div>
      <ul class="menu-items">
        <li class="menu-item"><a href="settings.php" class="menu-link"><i class="fas fa-cog"></i>Settings</a></li>
        <li class="menu-item"><a href="logs.php" class="menu-link"><i class="fas fa-file-alt"></i>Activity Logs</a></li>
        <li class="menu-item"><a href="reports.php" class="menu-link"><i class="fas fa-chart-bar"></i>Reports</a></li>
      </ul>

      <div class="menu-title">Account</div>
      <ul class="menu-items">
        <li class="menu-item"><a href="../profile.php" class="menu-link"><i class="fas fa-user"></i>Profile</a></li>
        <li class="menu-item"><a href="../logout.php" class="menu-link"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
      </ul>
    </nav>
  </aside>

  <!-- Main -->
  <main class="main-content">
    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-users"></i> Users</h1>
      <div class="header-actions">
        <button class="btn btn-secondary" onclick="openModal('modalCreateUser')"><i class="fas fa-user-plus"></i>Add User</button>
        <form id="bulkForm" method="POST" style="display:inline-flex; gap:8px; align-items:center;">
          <input type="hidden" name="action" value="bulk_action">
          <select class="form-control" name="bulk_action" style="min-width:160px">
            <option value="">Bulk actions</option>
            <option value="activate">Activate</option>
            <option value="deactivate">Deactivate</option>
            <option value="delete">Delete</option>
          </select>
          <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i>Apply</button>
        </form>
      </div>
    </div>

    <!-- Filters -->
    <section class="filters-card">
      <div class="card-header">
        <h3 class="card-title">Filters</h3>
        <a class="btn btn-outline" href="<?php echo htmlspecialchars(currentUrlNo(['search','role','status','club','page'])); ?>"><i class="fas fa-rotate-left"></i> Reset</a>
      </div>
      <div class="card-content">
        <form method="GET" class="form-row" id="filterForm">
          <div class="form-group" style="grid-column:span 4;">
            <label class="form-label">Search</label>
            <input type="text" class="form-control" name="search" placeholder="Name, username, or email" value="<?php echo htmlspecialchars($search); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Role</label>
            <select class="form-control form-select" name="role">
              <option value="">All</option>
              <?php
              $roles = ['SuperAdmin','Admin','President','Member'];
              foreach ($roles as $r) {
                  $sel = $role===$r ? 'selected' : '';
                  echo "<option value=\"$r\" $sel>$r</option>";
              }
              ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select class="form-control form-select" name="status">
              <option value="">All</option>
              <option value="Active" <?php echo $status==='Active'?'selected':''; ?>>Active</option>
              <option value="Inactive" <?php echo $status==='Inactive'?'selected':''; ?>>Inactive</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Club</label>
            <select class="form-control form-select" name="club">
              <option value="">All</option>
              <?php foreach ($clubs as $c): ?>
                <option value="<?php echo (int)$c['club_id']; ?>" <?php echo ($clubf!==''
                  && (string)$clubf===(string)$c['club_id'])?'selected':''; ?>>
                  <?php echo htmlspecialchars($c['club_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-actions" style="grid-column:span 12; justify-content:flex-end;">
            <button class="btn btn-secondary" type="submit"><i class="fas fa-search"></i> Search</button>
          </div>
        </form>
      </div>
    </section>

    <!-- Users Table -->
    <section class="table-card">
      <div class="card-header">
        <h3 class="card-title">Users (<?php echo (int)$total; ?>)</h3>
        <div>
          <span style="color:var(--muted);font-size:.9rem">Page <?php echo (int)$page; ?> of <?php echo (int)$pages; ?></span>
        </div>
      </div>
      <div class="card-content">
        <div class="table-wrapper">
          <form id="tableForm" method="POST">
            <table class="table">
              <thead>
                <tr>
                  <th><input type="checkbox" id="chkAll" onclick="toggleAll(this)"></th>
                  <th>User</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Club</th>
                  <th>Joined</th>
                  <th style="width:180px">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$users): ?>
                  <tr><td colspan="9" style="text-align:center;color:#999;padding:24px">No users found.</td></tr>
                <?php else: foreach ($users as $u): ?>
                <tr>
                  <td>
                    <input type="checkbox" name="selected_ids[]" value="<?php echo (int)$u['user_id']; ?>" class="rowChk">
                  </td>
                  <td>
                    <?php
                      $img = $u['profile_image'] ? baseUrl('uploads/profiles/'.$u['profile_image']) : (function_exists('getGravatarUrl') ? getGravatarUrl($u['email']) : 'https://www.gravatar.com/avatar?d=mp&s=80');
                    ?>
                    <div style="display:flex;align-items:center;gap:10px">
                      <img src="<?php echo htmlspecialchars($img); ?>" class="avatar-sm" alt="">
                      <div>
                        <div style="font-weight:800"><?php echo htmlspecialchars($u['full_name']); ?></div>
                        <div style="color:var(--muted);font-size:.85rem">ID: <?php echo (int)$u['user_id']; ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($u['username']); ?></td>
                  <td><?php echo htmlspecialchars($u['email']); ?></td>
                  <td>
                    <?php
                      $roleBadge = [
                        'SuperAdmin'=>'role-super',
                        'Admin'=>'role-admin',
                        'President'=>'role-president',
                        'Member'=>'role-member',
                      ][$u['role']] ?? '';
                    ?>
                    <span class="badge <?php echo $roleBadge; ?>"><?php echo htmlspecialchars($u['role']); ?></span>
                  </td>
                  <td>
                    <?php $sb = $u['status']==='Active'?'status-active':'status-inactive'; ?>
                    <span class="badge <?php echo $sb; ?>"><?php echo htmlspecialchars($u['status']); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($u['club_name'] ?? '—'); ?></td>
                  <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($u['created_at']))); ?></td>
                  <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                      <button type="button" class="btn btn-secondary" onclick="openEditUser(<?php echo (int)$u['user_id']; ?>,
                        <?php echo json_encode($u['full_name']); ?>,
                        <?php echo json_encode($u['username']); ?>,
                        <?php echo json_encode($u['email']); ?>,
                        <?php echo json_encode($u['role']); ?>,
                        <?php echo json_encode($u['status']); ?>,
                        <?php echo $u['club_id'] !== null ? (int)$u['club_id'] : 'null'; ?>
                      )"><i class="fas fa-pen"></i>Edit</button>

                      <form method="POST" onsubmit="return confirm('Delete this user permanently?')" style="display:inline">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                        <button class="btn btn-danger" type="submit"><i class="fas fa-trash"></i>Delete</button>
                      </form>

                      <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                        <input type="hidden" name="new_status" value="<?php echo $u['status']==='Active'?'Inactive':'Active'; ?>">
                        <button class="btn btn-outline" type="submit">
                          <i class="fas fa-toggle-<?php echo $u['status']==='Active'?'off':'on'; ?>"></i>
                          Set <?php echo $u['status']==='Active'?'Inactive':'Active'; ?>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </form>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
          <div class="pagination" style="margin-top:16px">
            <?php
              $base = currentUrlNo(['page']);
              $qs = $_GET; unset($qs['page']);
              for ($p=1;$p<=$pages;$p++):
                  $active = $p===$page ? 'active' : '';
                  $q = $qs; $q['page']=$p;
                  $url = strtok($base,'?') . '?' . http_build_query($q);
            ?>
              <a class="page-link <?php echo $active; ?>" href="<?php echo htmlspecialchars($url); ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>

      </div>
    </section>
  </main>
</div>

<!-- Create User Modal -->
<div class="modal" id="modalCreateUser">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title">Add User</h3>
      <button class="modal-close" onclick="closeModal('modalCreateUser')">&times;</button>
    </div>
    <form method="POST" onsubmit="return validateCreate()">
      <div class="modal-body">
        <input type="hidden" name="action" value="create_user">
        <div class="form-row">
          <div class="form-group" style="grid-column:span 6">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="full_name" id="c_full_name" required>
          </div>
          <div class="form-group" style="grid-column:span 6">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" id="c_username" required>
          </div>
          <div class="form-group" style="grid-column:span 6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="c_email" required>
          </div>
          <div class="form-group" style="grid-column:span 6">
            <label class="form-label">Password</label>
            <input type="text" class="form-control" name="password" id="c_password" value="admin123" required>
            <small style="color:var(--muted)">Warning: plain-text password per your request. Use hashing in real apps.</small>
          </div>
          <div class="form-group" style="grid-column:span 4">
            <label class="form-label">Role</label>
            <select class="form-control form-select" name="role" id="c_role" required>
              <option value="Member">Member</option>
              <option value="President">President</option>
              <option value="Admin">Admin</option>
              <option value="SuperAdmin">SuperAdmin</option>
            </select>
          </div>
          <div class="form-group" style="grid-column:span 4">
            <label class="form-label">Status</label>
            <select class="form-control form-select" name="status" id="c_status" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group" style="grid-column:span 4">
            <label class="form-label">Club</label>
            <select class="form-control form-select" name="club_id" id="c_club_id">
              <option value="">None</option>
              <?php foreach ($clubs as $c): ?>
                <option value="<?php echo (int)$c['club_id']; ?>"><?php echo htmlspecialchars($c['club_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" onclick="closeModal('modalCreateUser')">Cancel</button>
        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal" id="modalEditUser">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title">Edit User</h3>
      <button class="modal-close" onclick="closeModal('modalEditUser')">&times;</button>
    </div>
    <form method="POST" onsubmit="return validateEdit()">
      <div class="modal-body">
        <input type="hidden" name="action" value="update_user">
        <input type="hidden" name="user_id" id="e_user_id">

        <div class="form-row">
          <div class="form-group" style="grid-column:span 6">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="full_name" id="e_full_name" required>
          </div>
          <div class="form-group" style="grid-column:span 6">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" id="e_username" required>
          </div>
          <div class="form-group" style="grid-column:span 6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="e_email" required>
          </div>
          <div class="form-group" style="grid-column:span 6">
            <label class="form-label">New Password</label>
            <input type="text" class="form-control" name="password" id="e_password" placeholder="Leave blank to keep unchanged">
          </div>
          <div class="form-group" style="grid-column:span 4">
            <label class="form-label">Role</label>
            <select class="form-control form-select" name="role" id="e_role" required>
              <option value="Member">Member</option>
              <option value="President">President</option>
              <option value="Admin">Admin</option>
              <option value="SuperAdmin">SuperAdmin</option>
            </select>
          </div>
          <div class="form-group" style="grid-column:span 4">
            <label class="form-label">Status</label>
            <select class="form-control form-select" name="status" id="e_status" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group" style="grid-column:span 4">
            <label class="form-label">Club</label>
            <select class="form-control form-select" name="club_id" id="e_club_id">
              <option value="">None</option>
              <?php foreach ($clubs as $c): ?>
                <option value="<?php echo (int)$c['club_id']; ?>"><?php echo htmlspecialchars($c['club_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" onclick="closeModal('modalEditUser')">Cancel</button>
        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Update</button>
      </div>
    </form>
  </div>
</div>

<script>
// ========= Helpers =========
function openModal(id){ document.getElementById(id).classList.add('show'); }
function closeModal(id){ document.getElementById(id).classList.remove('show'); }
function openEditUser(id, full, user, email, role, status, club_id){
  document.getElementById('e_user_id').value = id;
  document.getElementById('e_full_name').value = full;
  document.getElementById('e_username').value = user;
  document.getElementById('e_email').value = email;
  document.getElementById('e_role').value = role;
  document.getElementById('e_status').value = status;
  document.getElementById('e_club_id').value = club_id ?? '';
  openModal('modalEditUser');
}
function openModalById(id){ openModal(id); }
function openCreate(){ openModal('modalCreateUser'); }
function openEdit(){ openModal('modalEditUser'); }

function toggleAll(master){
  document.querySelectorAll('.rowChk').forEach(ch => ch.checked = master.checked);
}
function validateEmail(e){
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
}

// ========= Form validations =========
function validateCreate(){
  const full = document.getElementById('c_full_name').value.trim();
  const user = document.getElementById('c_username').value.trim();
  const email= document.getElementById('c_email').value.trim();
  const pass = document.getElementById('c_password').value;

  if(!full || !user || !email || !pass){
    alert('Please fill in all required fields.');
    return false;
  }
  if(!validateEmail(email)){
    alert('Invalid email.');
    return false;
  }
  return true;
}
function validateEdit(){
  const full = document.getElementById('e_full_name').value.trim();
  const user = document.getElementById('e_username').value.trim();
  const email= document.getElementById('e_email').value.trim();
  if(!full || !user || !email){
    alert('Please fill in all required fields.');
    return false;
  }
  if(!validateEmail(email)){
    alert('Invalid email.');
    return false;
  }
  return true;
}

// ========= Modal openers =========
function openCreateUser(){ openModal('modalCreateUser'); }

// Close modal when clicking backdrop
document.addEventListener('click', function(e){
  if (e.target.classList.contains('modal')) e.target.classList.remove('show');
});
</script>
</body>
</html>
