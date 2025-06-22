<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    try {
        $db->beginTransaction();

        foreach ($_POST['questions'] as $question_data) {
            // Insert question
            $stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, marks) VALUES (?, ?, ?)");
            $stmt->execute([
                $_POST['exam_id'],
                $question_data['text'],
                $question_data['marks']
            ]);

            $question_id = $db->lastInsertId();

            // Insert options (using loop)
            foreach ($question_data['options'] as $option_num => $option_text) {
                $is_correct = ($option_num == $question_data['correct']);
                $stmt = $db->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, $option_text, $is_correct]);
            }
        }

        $db->commit();
        unset($_SESSION['pending_exam']); // Clear the pending exam data

        $_SESSION['success'] = "Exam created successfully!";
        header('Location: manage_exams.php');
        exit();

    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error saving questions: " . $e->getMessage();
        header('Location: add_questions.php');
        exit();
    }
}