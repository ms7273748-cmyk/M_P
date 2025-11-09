<?php
declare(strict_types=1);

session_start();
require_once 'functions.php';
require_once 'config.php';

// ---------- Helper: inline email sender (simple, self-contained) ----------
if (!function_exists('sendVerificationEmail')) {
    /**
     * Sends a verification email with a token link.
     * Uses PHP mail() for simplicity. Ensure your XAMPP/SMTP is configured.
     */
    function sendVerificationEmail(string $toEmail, string $fullName, string $token): bool
    {
        // If mailing is disabled in config, just pretend success to avoid blocking registration
        try {
            $mailEnabled = (bool) (config('mail.enabled') ?? false);
        } catch (Throwable) {
            $mailEnabled = false;
        }
        if (!$mailEnabled) return true;

        $base = BASE_URL ?? baseUrl();
        $verifyUrl = rtrim($base, '/') . '/verify.php?token=' . urlencode($token);

        $subject = 'Verify your ClubSphere account';
        $from    = (string) (config('mail.from_email') ?? 'no-reply@clubsphere.local');
        $fromName = (string) (config('mail.from_name') ?? 'ClubSphere');

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";

        $safeName = htmlspecialchars($fullName ?: 'there', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $message = "
        <html>
        <body style='font-family:Arial,Helvetica,sans-serif; color:#222;'>
            <div style='max-width:600px;margin:auto;padding:20px;border:1px solid #eee;border-radius:8px;'>
                <h2 style='margin-top:0;'>Welcome to ClubSphere, {$safeName}!</h2>
                <p>Thanks for signing up. Please verify your email address by clicking the button below:</p>
                <p style='margin:24px 0;'>
                    <a href='{$verifyUrl}' style='background:#ffcf70;color:#000;text-decoration:none;padding:12px 18px;border-radius:6px;font-weight:bold;'>
                        Verify My Email
                    </a>
                </p>
                <p>If the button doesn't work, copy and paste this link into your browser:</p>
                <p style='word-break:break-all;'><a href='{$verifyUrl}'>{$verifyUrl}</a></p>
                <hr style='border:none;border-top:1px solid #eee;margin:24px 0;'>
                <p style='font-size:12px;color:#777;'>If you didn't create an account, you can ignore this email.</p>
            </div>
        </body>
        </html>";

        // Send
        return @mail($toEmail, $subject, $message, $headers);
    }
}

// ---------- Redirect if already logged in ----------
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('dashboard/admin_dashboard.php');
    } else {
        redirect('dashboard/user_dashboard.php');
    }
    exit;
}

// ---------- Handle form submission ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        handleLogin();
    } elseif ($action === 'register') {
        handleRegistration();
    }
}

// ---------- Handlers ----------
function handleLogin(): void
{
    $email    = cleanInput($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if ($email === '' || $password === '') {
        setFlash('error', 'Please fill in all required fields.');
        return;
    }
    if (!isValidEmail($email)) {
        setFlash('error', 'Please enter a valid email address.');
        return;
    }

    try {
        $db = getDB();

        // Get user + club count
        $sql = "SELECT u.*,
                       (SELECT COUNT(*) FROM memberships m
                        WHERE m.user_id = u.user_id AND m.status = 'Active') AS club_count
                FROM users u
                WHERE u.email = ? AND u.status = 'Active'
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            setFlash('error', 'Invalid email or password.');
            logActivity(0, 'LOGIN_FAILED', 'Login attempt with invalid email: ' . $email);
            return;
        }

        // Password check
        if (!verifyPassword($password, (string)$user['password'])) {
            setFlash('error', 'Invalid email or password.');
            logActivity((int)$user['user_id'], 'LOGIN_FAILED', 'Invalid password');
            return;
        }

        // Fetch memberships
        $sql = "SELECT m.*, c.club_name, c.club_code, c.logo
                FROM memberships m
                JOIN clubs c ON m.club_id = c.club_id
                WHERE m.user_id = ? AND m.status = 'Active' AND c.status = 'Active'
                ORDER BY CASE m.position
                            WHEN 'President' THEN 1
                            WHEN 'VicePresident' THEN 2
                            WHEN 'Secretary' THEN 3
                            WHEN 'Treasurer' THEN 4
                            ELSE 5
                         END";
        $stmt = $db->prepare($sql);
        $stmt->execute([(int)$user['user_id']]);
        $memberships = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Session data
        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['user'] = [
            'id'           => (int)$user['user_id'],
            'username'     => (string)$user['username'],
            'email'        => (string)$user['email'],
            'full_name'    => (string)$user['full_name'],
            'role'         => (string)$user['role'],
            'profile_image'=> $user['profile_image'] ?? null,
            'club_count'   => (int)($user['club_count'] ?? 0),
            'memberships'  => $memberships
        ];

        // Remember token (secure cookie options)
        if ($remember) {
            $token  = generateToken(64);
            $expiry = time() + (86400 * 30); // 30 days

            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            setcookie('remember_token', $token, [
                'expires'  => $expiry,
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            $sql = "UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), (int)$user['user_id']]);
        }

        // Update last login
        $sql = "UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([(int)$user['user_id']]);

        logActivity((int)$user['user_id'], 'LOGIN_SUCCESS', 'User logged in successfully');
        setFlash('success', 'Welcome back, ' . e((string)$user['full_name']) . '!');

        if (isAdmin()) {
            redirect('dashboard/admin_dashboard.php');
        } else {
            redirect('dashboard/user_dashboard.php');
        }
        exit;
    } catch (Throwable $e) {
        setFlash('error', 'An error occurred during login. Please try again.');
        error_log('Login error: ' . $e->getMessage());
    }
}

function handleRegistration(): void
{
    $fullName        = cleanInput($_POST['full_name'] ?? '');
    $username        = cleanInput($_POST['username'] ?? '');
    $email           = cleanInput($_POST['email'] ?? '');
    $password        = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $phone           = cleanInput($_POST['phone'] ?? '');
    $agreeTerms      = isset($_POST['agree_terms']);

    if ($fullName === '' || $username === '' || $email === '' || $password === '') {
        setFlash('error', 'Please fill in all required fields.');
        return;
    }
    if (!$agreeTerms) {
        setFlash('error', 'You must agree to the terms and conditions.');
        return;
    }
    if (!isValidEmail($email)) {
        setFlash('error', 'Please enter a valid email address.');
        return;
    }
    if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        setFlash('error', 'Username must be at least 3 characters and contain only letters, numbers, and underscores.');
        return;
    }
    if ($password !== $confirmPassword) {
        setFlash('error', 'Passwords do not match.');
        return;
    }
    if (!isStrongPassword($password)) {
        setFlash('error', 'Password must be at least 8 characters with upper, lower, numbers, and special characters.');
        return;
    }
    if ($phone !== '' && !isValidPhone($phone)) {
        setFlash('error', 'Please enter a valid phone number.');
        return;
    }

    try {
        $db = getDB();

        // Username check
        $sql = "SELECT COUNT(*) AS cnt FROM users WHERE username = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username]);
        $exists = (int)($stmt->fetchColumn() ?: 0);
        if ($exists > 0) {
            setFlash('error', 'Username already taken.');
            return;
        }

        // Email check
        $sql = "SELECT COUNT(*) AS cnt FROM users WHERE email = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$email]);
        $exists = (int)($stmt->fetchColumn() ?: 0);
        if ($exists > 0) {
            setFlash('error', 'Email already registered.');
            return;
        }

        // Insert new user
        $hashedPassword     = hashPassword($password);
        $verificationToken  = generateToken();

        $sql = "INSERT INTO users
                (username, email, password, full_name, phone, verification_token, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username, $email, $hashedPassword, $fullName, $phone, $verificationToken]);

        $userId = (int)$db->lastInsertId();

        // Send verification email (no-op if mail.enabled=false)
        sendVerificationEmail($email, $fullName, $verificationToken);

        logActivity($userId, 'REGISTRATION_SUCCESS', 'User registered successfully');
        setFlash('success', 'Registration successful! Please check your email for verification.');
    } catch (Throwable $e) {
        setFlash('error', 'An error occurred during registration. Please try again.');
        error_log('Registration error: ' . $e->getMessage());
    }
}

// ---------- Include header (renders nav, etc.) ----------
require_once 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register - ClubSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
       /* =========================================================
          ClubSphere Authentication Page Styles (unchanged visuals)
          ========================================================= */
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { height: 100%; width: 100%; scroll-behavior: smooth; font-family: 'Inter', sans-serif;
    background: radial-gradient(circle at top right, #1f1c2c, #928dab); color: #fff; }
.bg-animation { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden; }
.bg-animation::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(255, 207, 112, 0.15) 0%, transparent 70%); animation: rotate 20s linear infinite; }
@keyframes rotate { from { transform: rotate(0deg);} to { transform: rotate(360deg);} }
.auth-container { width: 100%; max-width: 1200px; margin: auto; display: grid; grid-template-columns: 1fr 1fr; align-items: center; gap: 40px; padding: 40px 20px; position: relative; z-index: 1; }
.welcome-section { padding: 40px; text-align: left; }
.welcome-section h1 { font-family: 'Space Grotesk', sans-serif; font-size: 3rem; margin-bottom: 20px;
    background: linear-gradient(90deg, #ffcf70, #f3a683); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1.2; }
.welcome-section p { font-size: 1.2rem; color: #e0e0e0; margin-bottom: 30px; line-height: 1.6; }
.feature-list { list-style: none; margin-bottom: 30px; }
.feature-list li { display: flex; align-items: center; color: #ccc; font-size: 1rem; margin-bottom: 12px; }
.feature-list li i { color: #ffcf70; margin-right: 10px; font-size: 1.1rem; }
.form-container { background: rgba(255, 255, 255, 0.08); border-radius: 20px; padding: 40px; backdrop-filter: blur(15px);
    box-shadow: 0 0 30px rgba(255, 255, 255, 0.1); animation: fadeInUp 0.8s ease; }
.form-tabs { display: flex; border-bottom: 1px solid rgba(255, 255, 255, 0.15); margin-bottom: 25px; }
.form-tab { flex: 1; text-align: center; cursor: pointer; padding: 15px; background: none; border: none; color: #bbb;
    font-size: 1rem; font-weight: 500; position: relative; transition: all 0.3s ease; }
.form-tab.active { color: #ffcf70; }
.form-tab.active::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 100%; height: 2px; background: linear-gradient(90deg, #ffcf70, #f3a683); }
.form-content { display: none; }
.form-content.active { display: block; animation: fadeIn 0.4s ease-in-out; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-weight: 500; color: #eee; margin-bottom: 8px; }
.form-group input { width: 100%; padding: 12px 15px; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px;
    background: rgba(255, 255, 255, 0.1); color: #fff; font-size: 1rem; transition: border-color 0.3s, box-shadow 0.3s; }
.form-group input::placeholder { color: #aaa; }
.form-group input:focus { outline: none; border-color: #ffcf70; box-shadow: 0 0 0 2px rgba(255, 207, 112, 0.2); }
.form-check { display: flex; align-items: center; gap: 8px; margin-bottom: 15px; }
.form-check label { font-size: 0.9rem; color: #ccc; }
.form-check a { color: #ffcf70; text-decoration: none; }
.form-check a:hover { text-decoration: underline; }
.btn { width: 100%; padding: 14px; border-radius: 8px; font-size: 1rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-primary { background: linear-gradient(90deg, #ffcf70, #f3a683); color: #000; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 207, 112, 0.25); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.password-strength { margin-top: 8px; height: 4px; background: rgba(255, 255, 255, 0.1); border-radius: 2px; overflow: hidden; }
.password-strength-bar { height: 100%; width: 0%; transition: width 0.3s ease; border-radius: 2px; }
.password-strength-weak { background: #e74c3c; }
.password-strength-medium { background: #f39c12; }
.password-strength-strong { background: #2ecc71; }
.social-login { margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 30px; }
.social-login h3 { text-align: center; font-size: 1rem; color: #ccc; margin-bottom: 20px; }
.social-buttons { display: flex; gap: 10px; }
.social-btn { flex: 1; padding: 12px; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; background: rgba(255, 255, 255, 0.05); color: #fff; text-decoration: none; text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease; }
.social-btn:hover { background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.3); }
.flash-messages { position: fixed; top: 20px; right: 20px; z-index: 999; max-width: 400px; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px);} to { opacity: 1; transform: translateY(0);} }
@keyframes fadeIn { from { opacity: 0;} to { opacity: 1;} }
@media (max-width: 992px) {
    .auth-container { grid-template-columns: 1fr; gap: 30px; }
    .welcome-section { text-align: center; padding: 20px; }
    .welcome-section h1 { font-size: 2.5rem; }
}
@media (max-width: 600px) {
    .form-container { padding: 25px 20px; }
    .social-buttons { flex-direction: column; }
    .btn { font-size: 0.9rem; }
}
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    <div class="flash-messages">
        <?php echo $flashMessages ?? ''; ?>
    </div>

    <div class="auth-container">
        <div class="welcome-section">
            <h1>Welcome to ClubSphere</h1>
            <p>Join thousands of students in managing clubs, events, and collaborations seamlessly.</p>
            <ul class="feature-list">
                <li><i class="fas fa-users"></i> Manage multiple clubs and memberships</li>
                <li><i class="fas fa-calendar-alt"></i> Create and track events effortlessly</li>
                <li><i class="fas fa-bullhorn"></i> Share announcements</li>
                <li><i class="fas fa-chart-line"></i> Track engagement</li>
                <li><i class="fas fa-shield-alt"></i> Secure and privacy-focused</li>
            </ul>
        </div>

        <div class="form-container">
            <div class="form-tabs">
                <button class="form-tab active" onclick="switchTab('login')"><i class="fas fa-sign-in-alt"></i> Login</button>
                <button class="form-tab" onclick="switchTab('register')"><i class="fas fa-user-plus"></i> Register</button>
            </div>

            <div id="loginForm" class="form-content active">
                <form method="POST" onsubmit="return validateLoginForm()">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="Enter your email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</button>
                </form>
            </div>

            <div id="registerForm" class="form-content">
                <form method="POST" onsubmit="return validateRegisterForm()">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone (Optional)</label>
                        <input type="tel" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" id="registerPassword" required>
                        <div class="password-strength"><div class="password-strength-bar"></div></div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="agree_terms" id="agree_terms" required>
                        <label for="agree_terms">I agree to <a href="terms.php" target="_blank">Terms</a> and <a href="privacy.php" target="_blank">Privacy</a></label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register</button>
                </form>
            </div>
        </div>
    </div>

    <script>
/* Tab switching */
function switchTab(tab) {
    document.querySelectorAll('.form-tab').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.form-content').forEach(content => content.classList.remove('active'));
    if (tab === 'login') {
        document.querySelector('.form-tab:nth-child(1)').classList.add('active');
        document.getElementById('loginForm').classList.add('active');
    } else {
        document.querySelector('.form-tab:nth-child(2)').classList.add('active');
        document.getElementById('registerForm').classList.add('active');
    }
}

/* Password strength meter */
const passwordInput = document.getElementById('registerPassword');
const strengthBar   = document.querySelector('.password-strength-bar');
if (passwordInput && strengthBar) {
    passwordInput.addEventListener('input', () => {
        const value = passwordInput.value || '';
        let strength = 0;
        if (value.length >= 8) strength++;
        if (/[A-Z]/.test(value)) strength++;
        if (/[a-z]/.test(value)) strength++;
        if (/[0-9]/.test(value)) strength++;
        if (/[\W]/.test(value)) strength++;
        const width = (strength / 5) * 100;
        strengthBar.style.width = width + '%';
        strengthBar.className = 'password-strength-bar';
        if (strength <= 2) {
            strengthBar.classList.add('password-strength-weak');
        } else if (strength === 3 || strength === 4) {
            strengthBar.classList.add('password-strength-medium');
        } else {
            strengthBar.classList.add('password-strength-strong');
        }
    });
}

/* Form validation */
function validateLoginForm() {
    const email = (document.querySelector('#loginForm input[name="email"]')?.value || '').trim();
    const password = (document.querySelector('#loginForm input[name="password"]')?.value || '');
    if (!email || !password) {
        alert('Please fill in all required fields.');
        return false;
    }
    return true;
}
function validateRegisterForm() {
    const fullName = (document.querySelector('#registerForm input[name="full_name"]')?.value || '').trim();
    const username = (document.querySelector('#registerForm input[name="username"]')?.value || '').trim();
    const email = (document.querySelector('#registerForm input[name="email"]')?.value || '').trim();
    const password = (document.querySelector('#registerForm input[name="password"]')?.value || '');
    const confirmPassword = (document.querySelector('#registerForm input[name="confirm_password"]')?.value || '');
    const agreeTerms = !!document.querySelector('#registerForm input[name="agree_terms"]')?.checked;

    if (!fullName || !username || !email || !password || !confirmPassword) {
        alert('Please fill in all required fields.');
        return false;
    }
    if (password !== confirmPassword) {
        alert('Passwords do not match.');
        return false;
    }
    if (!agreeTerms) {
        alert('You must agree to the terms and conditions.');
        return false;
    }
    return true;
}
    </script>
</body>
</html>

<?php require_once 'footer.php'; ?>
