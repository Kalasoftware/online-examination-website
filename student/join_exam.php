<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$student_id = $_SESSION['user_id'];

// Get exam details including status
$stmt = $db->prepare("SELECT *, 
    CASE 
        WHEN exam_date > NOW() THEN 'upcoming'
        WHEN exam_date <= NOW() AND DATE_ADD(exam_date, INTERVAL duration MINUTE) >= NOW() THEN 'ongoing'
        ELSE 'completed'
    END as status
    FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header('Location: dashboard.php');
    exit();
}

// For both upcoming and ongoing exams, mark registration
$stmt = $db->prepare("
    INSERT INTO exam_registrations (exam_id, student_id) 
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE registered_at = CURRENT_TIMESTAMP
");
$stmt->execute([$exam_id, $student_id]);

if ($exam['status'] === 'ongoing') {
    // For ongoing exams, mark attendance
    $stmt = $db->prepare("
        INSERT INTO attendance (exam_id, student_id, is_present) 
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE is_present = 1
    ");
    $stmt->execute([$exam_id, $student_id]);

    $_SESSION['exam_start_time'] = time();
    $_SESSION['current_exam_id'] = $exam_id;
    header("Location: exam.php?exam_id=" . $exam_id);
} else {
    $_SESSION['message'] = "You have been registered for this exam. You can attempt it when it starts.";
    header("Location: dashboard.php");
}
exit();
?>