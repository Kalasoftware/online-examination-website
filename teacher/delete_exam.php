<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $exam_id = intval($_GET['id']);
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Verify exam belongs to this teacher
        $stmt = $db->prepare("SELECT * FROM exams WHERE exam_id = ? AND teacher_id = ?");
        $stmt->execute([$exam_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Unauthorized access");
        }

        $db->beginTransaction();
        
        // Delete related records
        $db->prepare("DELETE FROM responses WHERE exam_id = ?")->execute([$exam_id]);
        $db->prepare("DELETE FROM results WHERE exam_id = ?")->execute([$exam_id]);
        $db->prepare("DELETE FROM exam_registrations WHERE exam_id = ?")->execute([$exam_id]);
        $db->prepare("DELETE FROM options WHERE question_id IN (SELECT question_id FROM questions WHERE exam_id = ?)")->execute([$exam_id]);
        $db->prepare("DELETE FROM questions WHERE exam_id = ?")->execute([$exam_id]);
        $db->prepare("DELETE FROM exams WHERE exam_id = ?")->execute([$exam_id]);
        
        $db->commit();
        $_SESSION['success'] = "Exam deleted successfully.";
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error deleting exam: " . $e->getMessage();
    }
}

header('Location: manage_exams.php');
exit();
?>