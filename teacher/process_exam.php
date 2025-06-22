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
    
    $exam_name = $_POST['exam_name'];
    $subject = $_POST['subject'];
    $exam_date = $_POST['exam_date'];
    $duration = $_POST['duration'];
    $passing_percentage = $_POST['passing_percentage'];
    $question_count = $_POST['question_count'];
    $teacher_id = $_SESSION['user_id'];
    
    try {
        $stmt = $db->prepare("
            INSERT INTO exams (exam_name, subject, exam_date, duration, passing_percentage, teacher_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$exam_name, $subject, $exam_date, $duration, $passing_percentage, $teacher_id]);
        $exam_id = $db->lastInsertId();
        
        // Store exam details in session for the next step
        $_SESSION['pending_exam'] = [
            'exam_id' => $exam_id,
            'question_count' => $question_count
        ];
        
        $_SESSION['success'] = "Exam created successfully. Now add questions to your exam.";
        header("Location: add_questions.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error creating exam: " . $e->getMessage();
        header("Location: create_exam.php");
        exit();
    }
}

header("Location: create_exam.php");
exit();
?>