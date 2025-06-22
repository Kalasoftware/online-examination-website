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

    $question_id = intval($_POST['question_id']);
    $exam_id = intval($_POST['exam_id']);

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

            // Update question
            $stmt = $db->prepare("
                UPDATE questions 
                SET question_text = ?, marks = ? 
                WHERE question_id = ?");
            $stmt->execute([
                $_POST['question_text'],
                $_POST['marks'],
                $question_id
            ]);

            // Update options
            foreach ($_POST['options'] as $option_id => $option_text) {
                $is_correct = ($_POST['correct_option'] == $option_id) ? 1 : 0;
                $stmt = $db->prepare("
                    UPDATE options 
                    SET option_text = ?, is_correct = ? 
                    WHERE option_id = ? AND question_id = ?");
                $stmt->execute([
                    $option_text,
                    $is_correct,
                    $option_id,
                    $question_id
                ]);
            }

            $db->commit();
            $_SESSION['success'] = "Question updated successfully.";
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Error updating question: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Unauthorized access.";
    }
}

header("Location: manage_questions.php?exam_id=" . $exam_id);
exit();
?>