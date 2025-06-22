<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($question_id && $exam_id) {
    try {
        $db->beginTransaction();

        // // Delete options first (due to foreign key constraint)
        // $stmt = $db->prepare("DELETE FROM options WHERE question_id = ?");
        // $stmt->execute([$question_id]);  

        // Then delete the question
        $stmt = $db->prepare("DELETE FROM questions WHERE question_id = ? AND exam_id = ?");
        $stmt->execute([$question_id, $exam_id]);

        $db->commit();
        $_SESSION['success'] = "Question deleted successfully.";
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error deleting question: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid question or exam ID.";
}

// Redirect back to manage questions page
header("Location: manage_questions.php?exam_id=" . $exam_id);
exit();
?>