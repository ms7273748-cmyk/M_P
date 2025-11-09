<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

// =============================
// Flash Message Helpers (Fix)
// =============================
if (!function_exists('setFlash')) {
    function setFlash(string $type, string $message): void {
        $_SESSION['flashes'][$type][] = $message;
    }
}
if (!function_exists('getFlashes')) {
    function getFlashes(): array {
        if (empty($_SESSION['flashes'])) return [];
        $flashes = $_SESSION['flashes'];
        unset($_SESSION['flashes']);
        return $flashes;
    }
}

// =============================
// Authentication Check
// =============================
if (!isLoggedIn()) {
    setFlash('error', 'Please log in to view your profile.');
    redirect('login.php');
    exit;
}

$db = getDB();
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    setFlash('error', 'Session expired. Please log in again.');
    redirect('login.php');
    exit;
}

// =============================
// Fetch User Profile (Fixed Query)
// =============================
$stmt = $db->prepare("
    SELECT u.user_id, u.full_name, u.username, u.email, u.password, 
           u.role, u.status, u.profile_image, u.created_at, u.updated_at
    FROM users u
    WHERE u.user_id = ?
");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    setFlash('error', 'User not found.');
    redirect('login.php');
    exit;
}

// =============================
// Handle Profile Update
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = cleanInput($_POST['full_name'] ?? '');
    $username  = cleanInput($_POST['username'] ?? '');
    $email     = cleanInput($_POST['email'] ?? '');
    $new_pass  = $_POST['new_password'] ?? '';
    $errors    = [];

    if ($full_name === '' || $username === '' || $email === '') {
        $errors[] = 'Full name, Username, and Email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    // Check duplicate username/email
    if (!$errors) {
        $chk = $db->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $chk->execute([$username, $email, $userId]);
        if ($chk->fetchColumn() > 0) {
            $errors[] = 'Username or email already exists.';
        }
    }

    // Handle profile image
    $uploadFileName = $currentUser['profile_image'] ?? null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['profile_image'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $errors[] = 'Invalid image type.';
            } elseif ($file['size'] > config('upload.max_file_size')) {
                $errors[] = 'Image too large.';
            } else {
                $dir = rtrim(config('upload.upload_path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR;
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $newName = 'user_' . $userId . '_' . time() . '.' . $ext;
                $target = $dir . $newName;

                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $uploadFileName = 'profiles/' . $newName;
                    if (!empty($currentUser['profile_image'])) {
                        $old = rtrim(config('upload.upload_path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $currentUser['profile_image'];
                        if (file_exists($old)) @unlink($old);
                    }
                } else {
                    $errors[] = 'Failed to upload file.';
                }
            }
        }
    }

    // Apply updates
    if (!$errors) {
        if ($new_pass !== '') {
            $sql = "UPDATE users SET full_name=?, username=?, email=?, password=?, profile_image=?, updated_at=NOW() WHERE user_id=?";
            $params = [$full_name, $username, $email, $new_pass, $uploadFileName, $userId];
        } else {
            $sql = "UPDATE users SET full_name=?, username=?, email=?, profile_image=?, updated_at=NOW() WHERE user_id=?";
            $params = [$full_name, $username, $email, $uploadFileName, $userId];
        }
        $up = $db->prepare($sql);
        $up->execute($params);

        setFlash('success', 'Profile updated successfully.');
        redirect('profile.php');
        exit;
    } else {
        setFlash('error', implode('<br>', $errors));
    }
}

// =============================
// Prepare Data for Display
// =============================
$avatar = $currentUser['profile_image']
    ? baseUrl('uploads/' . $currentUser['profile_image'])
    : 'https://www.gravatar.com/avatar?d=mp&s=200';
$joined = $currentUser['created_at'] ? date('M d, Y', strtotime($currentUser['created_at'])) : '—';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile | ClubSphere</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
body{font-family:'Inter',sans-serif;background:#f7f8fa;margin:0;color:#222}
.container{max-width:1100px;margin:40px auto;padding:0 20px}
h1{display:flex;align-items:center;gap:10px;font-size:1.8rem;font-weight:800;margin-bottom:20px}
h1 i{color:#b78b1f}
.card{background:#fff;border:1px solid #eee;border-radius:16px;box-shadow:0 4px 14px rgba(0,0,0,.05);padding:24px;margin-bottom:20px}
.avatar{width:120px;height:120px;border-radius:16px;object-fit:cover;border:3px solid #ffe89a;box-shadow:0 3px 8px rgba(0,0,0,.1)}
.grid{display:grid;grid-template-columns:350px 1fr;gap:20px}@media(max-width:900px){.grid{grid-template-columns:1fr}}
label{display:block;font-weight:700;margin-bottom:6px}
input[type=text],input[type=email],input[type=file]{width:100%;padding:10px;border-radius:8px;border:1px solid #ddd;font-size:.95rem}
input:focus{border-color:#b78b1f;outline:none}
.btn{padding:10px 16px;border:none;border-radius:8px;font-weight:700;cursor:pointer}
.btn-primary{background:linear-gradient(180deg,#ffecb3,#f3d479);color:#442c00}
.btn-secondary{background:#f5f6f8;color:#333}
.btn:hover{filter:brightness(.95)}
.flash{padding:12px 16px;border-radius:10px;margin-bottom:10px;font-weight:600}
.flash.success{background:#eafaea;color:#1b5e20;border:1px solid #c8e6c9}
.flash.error{background:#fdeaea;color:#c62828;border:1px solid #f5c6cb}
</style>
</head>
<body>
<div class="container">
    <?php foreach (getFlashes() as $type => $messages): ?>
        <?php foreach ((array)$messages as $msg): ?>
            <div class="flash <?php echo htmlspecialchars($type); ?>"><?php echo $msg; ?></div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <h1><i class="fas fa-user-circle"></i> Your Profile</h1>

    <div class="grid">
        <!-- LEFT: Info -->
        <div class="card">
            <div style="display:flex;align-items:center;gap:16px">
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Profile" class="avatar">
                <div>
                    <div style="font-size:1.3rem;font-weight:800"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                    <div style="color:#777">@<?php echo htmlspecialchars($currentUser['username']); ?></div>
                    <div style="margin-top:6px;font-size:.9rem;color:#555">
                        Role: <strong><?php echo htmlspecialchars($currentUser['role']); ?></strong><br>
                        Status: <strong><?php echo htmlspecialchars($currentUser['status']); ?></strong><br>
                        Joined: <strong><?php echo htmlspecialchars($joined); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Edit Form -->
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                <div>
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
                </div>
                <div>
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                </div>
                <div>
                    <label>New Password (leave blank to keep current)</label>
                    <input type="text" name="new_password" placeholder="Enter new password (optional)">
                </div>
                <div>
                    <label>Profile Image</label>
                    <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.gif,.webp">
                </div>
                <div style="margin-top:14px;text-align:right">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
