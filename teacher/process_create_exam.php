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

    $exam_name = $_POST['exam_name'];
    $subject = $_POST['subject'];
    $exam_date = $_POST['exam_date'];
    $duration = intval($_POST['duration']);
    $passing_percentage = floatval($_POST['passing_percentage']);
    $teacher_id = $_SESSION['user_id'];

    try {
        $stmt = $db->prepare("INSERT INTO exams (exam_name, subject, teacher_id, exam_date, duration, passing_percentage) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$exam_name, $subject, $teacher_id, $exam_date, $duration, $passing_percentage]);
        
        $exam_id = $db->lastInsertId();
        header("Location: add_questions.php?exam_id=" . $exam_id);
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error creating exam: " . $e->getMessage();
        header("Location: create_exam.php");
        exit();
    }
} else {
    header("Location: create_exam.php");
    exit();
}
?>