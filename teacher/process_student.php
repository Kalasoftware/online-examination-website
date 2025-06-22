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
    
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        // Check if email already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Email already exists.";
            header("Location: manage_students.php");
            exit();
        }
        
        // Insert new student
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'STUDENT')");
        $stmt->execute([$name, $email, $password]);
        
        $_SESSION['success'] = "Student added successfully.";
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding student: " . $e->getMessage();
    }
    
    header("Location: manage_students.php");
    exit();
}

header("Location: manage_students.php");
exit();
?>