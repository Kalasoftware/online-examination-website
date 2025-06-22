<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Verify current password first
    if (empty($current_password)) {
        $_SESSION['error'] = "Current password is required for any changes.";
        header('Location: profile.php');
        exit();
    }

    // Verify current password
    $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
        header('Location: profile.php');
        exit();
    }
    
    try {
        // Update basic info
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $_SESSION['user_id']]);
        
        // Update password if new password is provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            $_SESSION['success'] = "Profile and password updated successfully.";
        } else {
            $_SESSION['success'] = "Profile updated successfully.";
        }
        
        // Update session name
        $_SESSION['name'] = $name;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
    }
    
    header('Location: profile.php');
    exit();
}

header('Location: profile.php');
exit();
?>