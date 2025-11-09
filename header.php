<?php
/**
 * ClubSphere - Enhanced Header File
 * Features: Dynamic navigation, user menu, notifications, search
 * 
 * @version 2.0
 * @author ClubSphere Development Team
 */

// Prevent direct access
if (!defined('CLUBSPHERE_INITIALIZED')) {
    require_once 'functions.php';
}

// Get current user data
$currentUser = getCurrentUser();
$notifications = [];
$unreadCount = 0;

// Get notifications if user is logged in
if (isLoggedIn()) {
    $notifications = getUserNotifications($currentUser['id'], 5, false);
    $unreadCount = count(array_filter($notifications, function($n) {
        return !$n['is_read'];
    }));
}

// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = dirname($_SERVER['PHP_SELF']);

// Generate navigation items
$navItems = [
    'index.php' => ['title' => 'Home', 'icon' => 'fas fa-home'],
    'dashboard/events.php' => ['title' => 'Events', 'icon' => 'fas fa-calendar-alt'],
    'dashboard/announcements.php' => ['title' => 'Announcements', 'icon' => 'fas fa-bullhorn'],
    'clubs.php' => ['title' => 'Clubs', 'icon' => 'fas fa-users'],
];

// Add admin items for admin users
if (isAdmin()) {
    $navItems['dashboard/admin_dashboard.php'] = ['title' => 'Admin', 'icon' => 'fas fa-cog'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ClubSphere - Where Passion Meets Innovation'; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="ClubSphere - A comprehensive club management system for students, events, and collaborations">
    <meta name="keywords" content="club management, student organizations, events, announcements, collaboration">
    <meta name="author" content="ClubSphere Development Team">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="ClubSphere - Where Passion Meets Innovation">
    <meta property="og:description" content="Join thousands of students in managing clubs, events, and collaborations seamlessly.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo baseUrl(); ?>">
    <meta property="og:image" content="<?php echo baseUrl('assets/images/og-image.jpg'); ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="ClubSphere - Where Passion Meets Innovation">
    <meta name="twitter:description" content="Join thousands of students in managing clubs, events, and collaborations seamlessly.">
    <meta name="twitter:image" content="<?php echo baseUrl('assets/images/og-image.jpg'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo baseUrl('assets/images/favicon.ico'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo baseUrl('assets/images/apple-touch-icon.png'); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo baseUrl('assets/images/favicon-32x32.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo baseUrl('assets/images/favicon-16x16.png'); ?>">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top right, #1f1c2c, #928dab);
            color: #fff;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Header Styles */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .header.scrolled {
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #fff;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #ffcf70, #f3a683);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 1.2rem;
            color: #000;
        }
        
        .logo-text {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, #ffcf70, #f3a683);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Navigation */
        .nav-menu {
            display: flex;
            align-items: center;
            list-style: none;
            gap: 8px;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: #e0e0e0;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: #ffcf70;
            background: rgba(255, 207, 112, 0.1);
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background: #ffcf70;
            border-radius: 50%;
        }
        
        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-input {
            width: 250px;
            padding: 10px 40px 10px 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #ffcf70;
            background: rgba(255, 255, 255, 0.15);
            width: 300px;
        }
        
        .search-input::placeholder {
            color: #aaa;
        }
        
        .search-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #ccc;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .search-btn:hover {
            color: #ffcf70;
        }
        
        /* Notification Bell */
        .notification-bell {
            position: relative;
            background: none;
            border: none;
            color: #e0e0e0;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .notification-bell:hover {
            color: #ffcf70;
            background: rgba(255, 207, 112, 0.1);
        }
        
        .notification-count {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #e74c3c;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 16px;
            text-align: center;
        }
        
        /* User Avatar */
        .user-avatar {
            position: relative;
            cursor: pointer;
        }
        
        .avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 207, 112, 0.3);
            transition: all 0.3s ease;
        }
        
        .avatar-img:hover {
            border-color: #ffcf70;
            transform: scale(1.05);
        }
        
        /* Dropdown Menus */
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 8px;
            min-width: 200px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1001;
        }
        
        .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: #e0e0e0;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .dropdown-item:hover {
            background: rgba(255, 207, 112, 0.1);
            color: #ffcf70;
        }
        
        .dropdown-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 8px 0;
        }
        
        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffcf70;
        }
        
        /* Main Content */
        .main-content {
            margin-top: 70px;
            min-height: calc(100vh - 70px);
        }
        
        /* Flash Messages */
        .flash-messages {
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 1001;
            max-width: 400px;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                padding: 0 15px;
            }
            
            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(15px);
                flex-direction: column;
                padding: 20px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .nav-menu.show {
                display: flex;
            }
            
            .nav-link {
                justify-content: flex-start;
                padding: 12px 0;
            }
            
            .search-input {
                width: 180px;
            }
            
            .search-input:focus {
                width: 220px;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .user-menu {
                gap: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .logo-text {
                display: none;
            }
            
            .search-box {
                display: none;
            }
            
            .header-container {
                height: 60px;
            }
            
            .main-content {
                margin-top: 60px;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 207, 112, 0.5);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 207, 112, 0.7);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="header-container">
            <!-- Logo -->
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="logo-text">ClubSphere</div>
            </a>
            
            <!-- Navigation Menu -->
            <nav class="nav-menu" id="navMenu">
                <?php foreach ($navItems as $url => $item): ?>
                    <div class="nav-item">
                        <a href="<?php echo $url; ?>" class="nav-link <?php echo $currentPage === $url ? 'active' : ''; ?>">
                            <i class="<?php echo $item['icon']; ?>"></i>
                            <?php echo $item['title']; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </nav>
            
            <!-- User Menu -->
            <div class="user-menu">
                <?php if (isLoggedIn()): ?>
                    <!-- Search Box -->
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Search clubs, events..." id="searchInput">
                        <button class="search-btn" onclick="performSearch()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <!-- Notification Bell -->
                    <div class="dropdown">
                        <button class="notification-bell" onclick="toggleNotifications()">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="notification-count"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </button>
                        
                        <div class="dropdown-menu" id="notificationMenu">
                            <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.1); font-weight: 600;">
                                Notifications
                            </div>
                            
                            <?php if (empty($notifications)): ?>
                                <div style="padding: 20px; text-align: center; color: #ccc;">
                                    No notifications
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <a href="#" class="dropdown-item" onclick="markNotificationAsRead(<?php echo $notification['notification_id']; ?>">
                                        <i class="fas fa-<?php echo $notification['notification_type'] === 'event' ? 'calendar' : 'bullhorn'; ?>"></i>
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; <?php echo !$notification['is_read'] ? 'color: #ffcf70;' : ''; ?>">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #ccc;">
                                                <?php echo timeAgo($notification['created_at']); ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <div class="dropdown-divider"></div>
                            <a href="notifications.php" class="dropdown-item">
                                <i class="fas fa-bell"></i>
                                View All Notifications
                            </a>
                        </div>
                    </div>
                    
                    <!-- User Avatar -->
                    <div class="dropdown">
                        <div class="user-avatar" onclick="toggleUserMenu()">
                            <img src="<?php echo $currentUser['profile_image'] ? baseUrl('uploads/profiles/' . $currentUser['profile_image']) : getGravatarUrl($currentUser['email']); ?>" 
                                 alt="<?php echo htmlspecialchars($currentUser['full_name']); ?>" 
                                 class="avatar-img">
                        </div>
                        
                        <div class="dropdown-menu" id="userMenu">
                            <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #ccc;"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                            </div>
                            
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                My Profile
                            </a>
                            
                            <a href="dashboard/user_dashboard.php" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                            
                            <a href="my-clubs.php" class="dropdown-item">
                                <i class="fas fa-users"></i>
                                My Clubs
                            </a>
                            
                            <a href="my-events.php" class="dropdown-item">
                                <i class="fas fa-calendar-alt"></i>
                                My Events
                            </a>
                            
                            <div class="dropdown-divider"></div>
                            
                            <?php if (isAdmin()): ?>
                                <a href="dashboard/admin_dashboard.php" class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    Admin Panel
                                </a>
                                <div class="dropdown-divider"></div>
                            <?php endif; ?>
                            
                            <a href="settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                            
                            <a href="logout.php" class="dropdown-item" style="color: #e74c3c;">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Login/Register Buttons -->
                    <div style="display: flex; gap: 10px;">
                        <a href="login.php" class="btn btn-outline" style="padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; text-decoration: none; color: #fff; transition: all 0.3s ease;">
                            Login
                        </a>
                        <a href="login.php#register" class="btn btn-primary" style="padding: 8px 16px; background: linear-gradient(90deg, #ffcf70, #f3a683); color: #000; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;">
                            Register
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>
    
    <!-- Flash Messages -->
    <div class="flash-messages" id="flashMessages">
        <?php echo $flashMessages; ?>
    </div>
    
    <!-- Main Content -->
    <main class="main-content">

<script>
// Header functionality
let isMenuOpen = false;
let isNotificationOpen = false;
let isUserMenuOpen = false;

// Toggle mobile menu
function toggleMobileMenu() {
    const navMenu = document.getElementById('navMenu');
    isMenuOpen = !isMenuOpen;
    
    if (isMenuOpen) {
        navMenu.classList.add('show');
    } else {
        navMenu.classList.remove('show');
    }
}

// Toggle notifications dropdown
function toggleNotifications() {
    const notificationMenu = document.getElementById('notificationMenu');
    const userMenu = document.getElementById('userMenu');
    
    isNotificationOpen = !isNotificationOpen;
    isUserMenuOpen = false;
    
    if (isNotificationOpen) {
        notificationMenu.classList.add('show');
        userMenu.classList.remove('show');
    } else {
        notificationMenu.classList.remove('show');
    }
}

// Toggle user menu dropdown
function toggleUserMenu() {
    const userMenu = document.getElementById('userMenu');
    const notificationMenu = document.getElementById('notificationMenu');
    
    isUserMenuOpen = !isUserMenuOpen;
    isNotificationOpen = false;
    
    if (isUserMenuOpen) {
        userMenu.classList.add('show');
        notificationMenu.classList.remove('show');
    } else {
        userMenu.classList.remove('show');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const userMenu = document.getElementById('userMenu');
    const notificationMenu = document.getElementById('notificationMenu');
    const userAvatar = document.querySelector('.user-avatar');
    const notificationBell = document.querySelector('.notification-bell');
    
    if (userAvatar && !userAvatar.contains(event.target) && userMenu && !userMenu.contains(event.target)) {
        userMenu.classList.remove('show');
        isUserMenuOpen = false;
    }
    
    if (notificationBell && !notificationBell.contains(event.target) && notificationMenu && !notificationMenu.contains(event.target)) {
        notificationMenu.classList.remove('show');
        isNotificationOpen = false;
    }
});

// Mark notification as read
function markNotificationAsRead(notificationId) {
    // Make AJAX request to mark notification as read
    fetch('api/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_read',
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update notification count
            const countElement = document.querySelector('.notification-count');
            if (countElement) {
                const currentCount = parseInt(countElement.textContent);
                if (currentCount > 1) {
                    countElement.textContent = currentCount - 1;
                } else {
                    countElement.remove();
                }
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Perform search
function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const query = searchInput.value.trim();
    
    if (query) {
        window.location.href = 'search.php?q=' + encodeURIComponent(query);
    }
}

// Search on Enter key
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
});

// Header scroll effect
window.addEventListener('scroll', function() {
    const header = document.getElementById('header');
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});

// Auto-hide flash messages
setTimeout(function() {
    const flashMessages = document.getElementById('flashMessages');
    if (flashMessages) {
        const messages = flashMessages.querySelectorAll('.flash-message');
        messages.forEach(function(message) {
            setTimeout(function() {
                message.style.opacity = '0';
                message.style.transform = 'translateX(100%)';
                setTimeout(function() {
                    message.remove();
                }, 300);
            }, 5000);
        });
    }
}, 1000);

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add loading states for buttons
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            
            // Re-enable after 5 seconds (fallback)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 5000);
        }
    });
});

// Initialize AOS animations
if (typeof AOS !== 'undefined') {
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });
}

// Performance monitoring
if ('performance' in window) {
    window.addEventListener('load', function() {
        setTimeout(function() {
            const perfData = performance.getEntriesByType('navigation')[0];
            console.log('Page load time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
        }, 0);
    });
}
</script>

<?php
// Close the header and start main content
ob_start();