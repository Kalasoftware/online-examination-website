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

    $exam_id = intval($_POST['exam_id']);

    // Verify exam belongs to teacher
    $stmt = $db->prepare("SELECT exam_id FROM exams WHERE exam_id = ? AND teacher_id = ?");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Unauthorized access.";
        header('Location: manage_exams.php');
        exit();
    }

    try {
        $db->beginTransaction();

        foreach ($_POST['questions'] as $question) {
            // Insert question
            $stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, marks) VALUES (?, ?, ?)");
            $stmt->execute([$exam_id, $question['text'], $question['marks']]);
            $question_id = $db->lastInsertId();

            // Insert options
            foreach ($question['options'] as $option_index => $option_text) {
                $is_correct = ($question['correct'] == $option_index) ? 1 : 0;
                $stmt = $db->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, $option_text, $is_correct]);
            }
        }

        $db->commit();
        $_SESSION['success'] = "Questions added successfully.";
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error adding questions: " . $e->getMessage();
    }
}

header("Location: manage_questions.php?exam_id=" . $exam_id);
exit();
?>