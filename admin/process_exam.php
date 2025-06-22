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

        // Insert exam details
        $stmt = $db->prepare("INSERT INTO exams (exam_name, subject, teacher_id, admin_id, exam_date, duration) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        
        $teacher_id = null;
        $admin_id = null;
        
        if ($_POST['assigned_to'] === 'teacher') {
            $teacher_id = $_POST['teacher_id'];
        } else {
            $admin_id = $_SESSION['user_id'];
        }

        $stmt->execute([
            $_POST['exam_name'],
            $_POST['subject'],
            $teacher_id,
            $admin_id,
            $_POST['exam_date'],
            $_POST['duration']
        ]);

        $exam_id = $db->lastInsertId();

        // Store question count in session for the next step
        $_SESSION['pending_exam'] = [
            'exam_id' => $exam_id,
            'question_count' => $_POST['question_count']
        ];

        $db->commit();
        
        // Redirect to add questions page
        header('Location: add_questions.php');
        exit();

    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error creating exam: " . $e->getMessage();
        header('Location: add_exam.php');
        exit();
    }
}