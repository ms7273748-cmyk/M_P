<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Handle club joining
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_club'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: index.php');
        exit();
    }
    
    $clubId = (int)$_POST['club_id'];
    $userId = $_SESSION['user_id'];
    
    try {
        // Check if user is already a member
        $stmt = $conn->prepare("SELECT * FROM memberships WHERE club_id = ? AND user_id = ? AND status = 'Active'");
        $stmt->execute([$clubId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            // Add membership
            $stmt = $conn->prepare("INSERT INTO memberships (club_id, user_id, role, joined_date, status) VALUES (?, ?, 'Member', NOW(), 'Active')");
            $stmt->execute([$clubId, $userId]);
            
            // Get club details
            $stmt = $conn->prepare("SELECT name, acronym FROM clubs WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $club = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'Membership', 'Welcome to ' . ?, 'You have successfully joined ' . ? . '! Welcome to our community.', NOW())");
            $stmt->execute([$userId, $club['name'], $club['name']]);
            
            // Log activity
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address, timestamp) VALUES (?, 'Club Join', ?, ?, NOW())");
            $stmt->execute([$userId, 'Joined ' . $club['name'] . ' club', $ipAddress]);
            
            $_SESSION['success'] = 'Successfully joined ' . htmlspecialchars($club['name']) . '! Welcome to the club!';
        } else {
            $_SESSION['warning'] = 'You are already a member of this club.';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Failed to join club. Please try again.';
    }
    
    // Redirect back to the club page
    $stmt = $conn->prepare("SELECT acronym FROM clubs WHERE club_id = ?");
    $stmt->execute([$clubId]);
    $club = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($club) {
        header('Location: clubs/' . strtolower($club['acronym']) . '.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

// If not a POST request, redirect to homepage
header('Location: index.php');
exit();
?>