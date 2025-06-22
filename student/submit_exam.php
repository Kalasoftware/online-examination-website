<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT' || !isset($_POST['exam_id'])) {
    header('Location: exams.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    $exam_id = $_POST['exam_id'];
    $student_id = $_SESSION['user_id'];
    $marks_obtained = 0;
    
    // Calculate marks
    foreach ($_POST['answers'] as $question_id => $selected_option) {
        $stmt = $db->prepare("SELECT marks FROM questions WHERE question_id = ?");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("SELECT is_correct FROM options WHERE option_id = ?");
        $stmt->execute([$selected_option]);
        $option = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($option && $option['is_correct']) {
            $marks_obtained += $question['marks'];
        }
    }
    
    // Save result
    // Update the insert query to include grade
    // Calculate total marks for the exam
    $stmt = $db->prepare("SELECT SUM(marks) as total_marks FROM questions WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $total_marks = $stmt->fetch(PDO::FETCH_ASSOC)['total_marks'];

    // Calculate percentage
    $percentage = ($marks_obtained / $total_marks) * 100;

    // Determine grade
    $grade = '';
    if ($percentage >= 90) $grade = 'A+';
    elseif ($percentage >= 80) $grade = 'A';
    elseif ($percentage >= 70) $grade = 'B';
    elseif ($percentage >= 60) $grade = 'C';
    elseif ($percentage >= 50) $grade = 'D';
    else $grade = 'F';
    
    // Save result with correct column name (attempt_date)
    $stmt = $db->prepare("INSERT INTO results (exam_id, student_id, marks_obtained, grade, attempt_date) 
                         VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$exam_id, $student_id, $marks_obtained, $grade]);
    
    // Store responses
    foreach ($_POST['answers'] as $question_id => $selected_option) {
        $stmt = $db->prepare("INSERT INTO responses (exam_id, student_id, question_id, selected_option) 
                            VALUES (?, ?, ?, ?)");
        $stmt->execute([$exam_id, $student_id, $question_id, $selected_option]);
    }

    $db->commit();
    $_SESSION['success'] = "Exam submitted successfully!";
    
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = "Error submitting exam: " . $e->getMessage();
}

header('Location: results.php');
exit();
?>