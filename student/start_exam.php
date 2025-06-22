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

try {
    $db->beginTransaction();

    // First check if attendance record exists
    $stmt = $db->prepare("SELECT attendance_id FROM attendance WHERE exam_id = ? AND student_id = ?");
    $stmt->execute([$exam_id, $student_id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        // Insert new attendance record
        $stmt = $db->prepare("
            INSERT INTO attendance (exam_id, student_id, is_present) 
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$exam_id, $student_id]);
    } else {
        // Update existing attendance record
        $stmt = $db->prepare("
            UPDATE attendance 
            SET is_present = 1 
            WHERE exam_id = ? AND student_id = ?
        ");
        $stmt->execute([$exam_id, $student_id]);
    }

    // Create initial result entry
    $stmt = $db->prepare("
        INSERT INTO results (exam_id, student_id, marks_obtained, grade) 
        VALUES (?, ?, 0, '') 
        ON DUPLICATE KEY UPDATE attempt_date = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$exam_id, $student_id]);

    $db->commit();

    $_SESSION['exam_start_time'] = time();
    header("Location: exam.php?exam_id=" . $exam_id);
    exit();

} catch (PDOException $e) {
    $db->rollBack();
    $_SESSION['error'] = "Error starting exam: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
?>