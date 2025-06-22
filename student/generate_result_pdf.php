<?php
session_start();
require_once '../config/database.php';
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT' || !isset($_GET['exam_id'])) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get result details
// Update the query to include teacher information
// Update the query to properly handle missing teacher information
$stmt = $db->prepare("
    SELECT 
        e.exam_name,
        e.subject,
        e.duration,
        r.marks_obtained,
        r.grade,
        r.attempt_date,
        u.name as student_name,
        u.user_id as student_id,
        COALESCE(t.name, 'Admin') as teacher_name,
        COALESCE((SELECT SUM(marks) FROM questions WHERE exam_id = e.exam_id), 0) as total_marks,
        CASE 
            WHEN (SELECT SUM(marks) FROM questions WHERE exam_id = e.exam_id) > 0 
            THEN (r.marks_obtained * 100.0 / (SELECT SUM(marks) FROM questions WHERE exam_id = e.exam_id))
            ELSE 0 
        END as percentage
    FROM results r
    JOIN exams e ON r.exam_id = e.exam_id
    JOIN users u ON r.student_id = u.user_id
    LEFT JOIN users t ON e.teacher_id = t.user_id
    WHERE r.exam_id = ? AND r.student_id = ?
");
$stmt->execute([$_GET['exam_id'], $_SESSION['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    $_SESSION['error'] = "Result not found.";
    header('Location: results.php');
    exit();
}

$percentage = number_format($result['percentage'], 1);
$status = $percentage >= 50 ? 'PASSED' : 'FAILED';

// Create HTML content
$html = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .header { text-align: center; font-size: 24px; margin-bottom: 30px; color: #1a56db; }
        .content { margin: 20px; line-height: 1.6; }
        .row { margin: 15px 0; padding: 10px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; display: inline-block; width: 150px; }
        .status { padding: 5px 10px; border-radius: 4px; }
        .passed { background-color: #dcfce7; color: #166534; }
        .failed { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class='header'>EXAM RESULT CERTIFICATE</div>
    <div class='content'>
        <div class='row'>
            <span class='label'>Student Name:</span>
            <span>{$result['student_name']}</span>
        </div>
        <div class='row'>
            <span class='label'>Student ID:</span>
            <span>{$result['student_id']}</span>
        </div>
        <div class='row'>
            <span class='label'>Exam:</span>
            <span>{$result['exam_name']}</span>
        </div>
      
        <div class='row'>
            <span class='label'>Subject:</span>
            <span>{$result['subject']}</span>
        </div>
        <div class='row'>
            <span class='label'>Teacher:</span>
            <span>{$result['teacher_name']}</span>
        </div>
        <div class='row'>
            <span class='label'>Marks:</span>
            <span>{$result['marks_obtained']} out of {$result['total_marks']}</span>
        </div>
        <div class='row'>
            <span class='label'>Percentage:</span>
            <span>{$percentage}%</span>
        </div>
        <div class='row'>
            <span class='label'>Status:</span>
            <span class='status " . strtolower($status) . "'>{$status}</span>
        </div>
        <div class='row'>
            <span class='label'>Completion Date:</span>
            <span>" . date('F d, Y h:i A', strtotime($result['attempt_date'])) . "</span>
        </div>
    </div>
</body>
</html>";

try {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $fileName = "Result_" . $result['exam_name'] . "_" . date('Y-m-d') . ".pdf";
    
    $dompdf->stream($fileName, array("Attachment" => true));
} catch (Exception $e) {
    $_SESSION['error'] = "Error generating PDF. Please try again.";
    header('Location: results.php');
    exit();
}