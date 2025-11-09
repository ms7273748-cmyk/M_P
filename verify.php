<?php
declare(strict_types=1);
session_start();
require_once 'functions.php';
require_once 'config.php';

$token = $_GET['token'] ?? '';

if ($token === '') {
    setFlash('error', 'Invalid or missing verification token.');
    redirect('login.php');
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT user_id, email, email_verified_at FROM users WHERE verification_token = ? AND status = 'Active' LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        setFlash('error', 'Invalid or expired verification link.');
        redirect('login.php');
        exit;
    }

    if (!empty($user['email_verified_at'])) {
        setFlash('info', 'Your account is already verified. You can log in.');
        redirect('login.php');
        exit;
    }

    $db->prepare("UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE user_id = ?")
       ->execute([(int)$user['user_id']]);

    logActivity((int)$user['user_id'], 'EMAIL_VERIFIED', 'Email verified successfully');
    setFlash('success', 'Your email has been verified successfully. You can now log in.');
    redirect('login.php');
    exit;
} catch (Throwable $e) {
    setFlash('error', 'Verification failed. Try again later.');
    error_log('Verification error: ' . $e->getMessage());
    redirect('login.php');
    exit;
}
