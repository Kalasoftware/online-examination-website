<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER' || !isset($_SESSION['pending_exam'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $exam_id = $_POST['exam_id'];
    
    // Verify exam belongs to this teacher
    $stmt = $db->prepare("SELECT * FROM exams WHERE exam_id = ? AND teacher_id = ?");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Unauthorized access";
        header('Location: manage_exams.php');
        exit();
    }
    
    try {
        $db->beginTransaction();
        
        foreach ($_POST['questions'] as $question) {
            // Insert question
            $stmt = $db->prepare("
                INSERT INTO questions (exam_id, question_text, marks) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$exam_id, $question['text'], $question['marks']]);
            $question_id = $db->lastInsertId();
            
            // Insert options
            $correct_option = $question['correct'];
            foreach ($question['options'] as $option_num => $option_text) {
                $is_correct = ($option_num == $correct_option) ? 1 : 0;
                $stmt = $db->prepare("
                    INSERT INTO options (question_id, option_text, is_correct) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$question_id, $option_text, $is_correct]);
            }
        }
        
        $db->commit();
        unset($_SESSION['pending_exam']); // Clear pending exam data
        
        $_SESSION['success'] = "Questions added successfully!";
        header("Location: manage_exams.php");
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error adding questions: " . $e->getMessage();
        header("Location: add_questions.php");
        exit();
    }
}

header("Location: manage_exams.php");
exit();
?>