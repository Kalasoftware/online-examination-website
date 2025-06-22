<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['exam_id']) && isset($_GET['student_id']) && isset($_GET['status'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $exam_id = intval($_GET['exam_id']);
    $student_id = intval($_GET['student_id']);
    $status = intval($_GET['status']);
    
    try {
        // Verify exam belongs to this teacher
        $stmt = $db->prepare("SELECT * FROM exams WHERE exam_id = ? AND teacher_id = ?");
        $stmt->execute([$exam_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            // Check if attendance record exists
            $stmt = $db->prepare("SELECT * FROM attendance WHERE exam_id = ? AND student_id = ?");
            $stmt->execute([$exam_id, $student_id]);
            
            if ($stmt->fetch()) {
                // Update existing record
                $stmt = $db->prepare("UPDATE attendance SET is_present = ? WHERE exam_id = ? AND student_id = ?");
                $stmt->execute([$status, $exam_id, $student_id]);
            } else {
                // Insert new record
                $stmt = $db->prepare("INSERT INTO attendance (exam_id, student_id, is_present) VALUES (?, ?, ?)");
                $stmt->execute([$exam_id, $student_id, $status]);
            }
            
            $_SESSION['success'] = "Attendance marked successfully.";
        } else {
            $_SESSION['error'] = "Unauthorized access.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error marking attendance: " . $e->getMessage();
    }
}

header('Location: monitor_exam.php?id=' . $exam_id);
exit();
?>