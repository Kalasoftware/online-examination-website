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
    $question_text = $_POST['question_text'];
    $marks = intval($_POST['marks']);
    $options = $_POST['options'];
    $correct_option = intval($_POST['correct_option']);
    
    try {
        $db->beginTransaction();
        
        // Insert question
        $stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, marks) VALUES (?, ?, ?)");
        $stmt->execute([$exam_id, $question_text, $marks]);
        $question_id = $db->lastInsertId();
        
        // Insert options
        $stmt = $db->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
        foreach ($options as $index => $option_text) {
            $is_correct = ($index === $correct_option) ? 1 : 0;
            $stmt->execute([$question_id, $option_text, $is_correct]);
        }
        
        $db->commit();
        $_SESSION['success'] = "Question added successfully.";
        
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error adding question: " . $e->getMessage();
    }
    
    header("Location: add_questions.php?id=" . $exam_id);
    exit();
}

header("Location: manage_exams.php");
exit();
?>