<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get result ID from URL
$result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;

// Get exam and student details
$stmt = $db->prepare("
    SELECT 
        r.*, 
        e.exam_name,
        e.subject,
        e.exam_date,
        u.name as student_name,
        u.email as student_email,
        (SELECT SUM(marks) FROM questions WHERE exam_id = e.exam_id) as total_marks
    FROM results r
    JOIN exams e ON r.exam_id = e.exam_id
    JOIN users u ON r.student_id = u.user_id
    WHERE r.result_id = ? AND e.teacher_id = ?");
$stmt->execute([$result_id, $_SESSION['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    header('Location: view_results.php');
    exit();
}

// Get student's answers with questions and options
$stmt = $db->prepare("
    SELECT 
        q.question_text,
        q.marks as question_marks,
        r.selected_option,
        o.option_text as selected_answer,
        co.option_text as correct_answer,
        CASE 
            WHEN r.selected_option = (
                SELECT option_id 
                FROM options 
                WHERE question_id = q.question_id 
                AND is_correct = 1
            ) THEN q.marks
            ELSE 0
        END as marks_obtained
    FROM responses r
    JOIN questions q ON r.question_id = q.question_id
    JOIN options o ON r.selected_option = o.option_id
    JOIN options co ON co.question_id = q.question_id AND co.is_correct = 1
    WHERE r.exam_id = ? AND r.student_id = ?
    ORDER BY q.question_id ASC");
$stmt->execute([$result['exam_id'], $result['student_id']]);
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
$status = $percentage >= 50 ? 'Passed' : 'Failed';
$statusClass = $percentage >= 50 ? 'text-green-600' : 'text-red-600';

$page_title = "Student Response Details";
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Student Response Details</h1>
        <a href="view_results.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Back to Results
        </a>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <h2 class="text-xl font-semibold mb-4">Exam Details</h2>
                <p><span class="font-semibold">Exam Name:</span> {$result['exam_name']}</p>
                <p><span class="font-semibold">Subject:</span> {$result['subject']}</p>
                <p><span class="font-semibold">Date:</span> {$result['exam_date']}</p>
            </div>
            <div>
                <h2 class="text-xl font-semibold mb-4">Student Details</h2>
                <p><span class="font-semibold">Name:</span> {$result['student_name']}</p>
                <p><span class="font-semibold">Email:</span> {$result['student_email']}</p>
                <p><span class="font-semibold">Score:</span> {$result['marks_obtained']}/{$result['total_marks']} ({$percentage}%)</p>
                <p><span class="font-semibold">Status:</span> <span class="{$statusClass}">{$status}</span></p>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="text-xl font-semibold mb-4">Question-wise Analysis</h2>
            <div class="space-y-6">
HTML;

foreach ($answers as $index => $answer) {
    $questionNumber = $index + 1;
    $isCorrect = $answer['selected_answer'] === $answer['correct_answer'];
    $answerClass = $isCorrect ? 'text-green-600' : 'text-red-600';
    
    // Update the display part to use question_text
    $page_content .= <<<HTML
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-semibold">Question {$questionNumber}</h3>
                        <span class="text-sm text-gray-500">Marks: {$answer['marks_obtained']}/{$answer['question_marks']}</span>
                    </div>
                    <p class="mb-3">{$answer['question_text']}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Student's Answer:</p>
                            <p class="{$answerClass}">{$answer['selected_answer']}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Correct Answer:</p>
                            <p class="text-green-600">{$answer['correct_answer']}</p>
                        </div>
                    </div>
                </div>
HTML;
}

$page_content .= <<<HTML
            </div>
        </div>
    </div>
</div>
HTML;

require_once 'includes/teacher_layout.php';
?>