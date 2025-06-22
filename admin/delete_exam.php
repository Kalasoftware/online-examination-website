<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

if (isset($_POST['exam_id'])) {
    $database = new Database();
    $db = $database->getConnection();

    try {
        $db->beginTransaction();

        // Delete options first (using JOIN instead of subquery)
        $stmt = $db->prepare("DELETE options FROM options 
            INNER JOIN questions ON options.question_id = questions.question_id 
            WHERE questions.exam_id = ?");
        $stmt->execute([$_POST['exam_id']]);

        // Delete questions
        $stmt = $db->prepare("DELETE FROM questions WHERE exam_id = ?");
        $stmt->execute([$_POST['exam_id']]);

        // Delete results
        $stmt = $db->prepare("DELETE FROM results WHERE exam_id = ?");
        $stmt->execute([$_POST['exam_id']]);

        // Finally delete exam
        $stmt = $db->prepare("DELETE FROM exams WHERE exam_id = ?");
        $stmt->execute([$_POST['exam_id']]);

        $db->commit();
        $_SESSION['success'] = "Exam deleted successfully";
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error deleting exam: " . $e->getMessage();
    }
}

header('Location: manage_exams.php');
exit();
?>