<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($question_id && $exam_id) {
    $database = new Database();
    $db = $database->getConnection();

    // Verify ownership
    $stmt = $db->prepare("
        SELECT e.teacher_id 
        FROM questions q 
        JOIN exams e ON q.exam_id = e.exam_id 
        WHERE q.question_id = ? AND e.teacher_id = ?");
    $stmt->execute([$question_id, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        try {
            $db->beginTransaction();
            
            // Delete options first
            $stmt = $db->prepare("DELETE FROM options WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            // Then delete question
            $stmt = $db->prepare("DELETE FROM questions WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            $db->commit();
            $_SESSION['success'] = "Question deleted successfully.";
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Error deleting question.";
        }
    } else {
        $_SESSION['error'] = "Unauthorized access.";
    }
}

header("Location: manage_questions.php?exam_id=" . $exam_id);
exit();
?>