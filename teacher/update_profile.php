<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $user_id = $_SESSION['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $current_password = trim($_POST['current_password']);

    // Verify current password
    $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($current_password, $user['password'])) {
        // Update profile
        if ($new_password) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $hashed_password, $user_id]);
        } else {
            // Update without changing password
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $user_id]);
        }

        $_SESSION['name'] = $name;
        $_SESSION['success'] = "Profile updated successfully.";
    } else {
        $_SESSION['error'] = "Current password is incorrect.";
    }
}

header('Location: profile.php');
exit();
?>