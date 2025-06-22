<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get student details
$stmt = $db->prepare("SELECT name, email FROM users WHERE user_id = ? AND role = 'STUDENT'");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['error'] = "Student not found.";
    header('Location: manage_students.php');
    exit();
}

// Get exam performance for exams created by this teacher
$stmt = $db->prepare("
    SELECT 
        e.exam_name,
        e.subject,
        e.exam_date,
        r.attempt_date,
        r.marks_obtained,
        (SELECT COUNT(*) FROM questions WHERE exam_id = e.exam_id) as total_questions,
        (SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = e.exam_id) as total_marks,
        (SELECT COUNT(*) FROM responses 
         WHERE exam_id = e.exam_id AND student_id = ? AND selected_option IS NOT NULL) as questions_answered
    FROM exams e
    LEFT JOIN results r ON e.exam_id = r.exam_id AND r.student_id = ?
    WHERE e.teacher_id = ? AND e.exam_id IN (
        SELECT exam_id FROM exam_registrations WHERE student_id = ?
    )
    ORDER BY e.exam_date DESC");
$stmt->execute([$student_id, $student_id, $_SESSION['user_id'], $student_id]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Student Performance - " . $student['name'];
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="manage_students.php" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to Students
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-2xl font-bold mb-4">Student Performance</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-gray-600">Name:</p>
                <p class="font-semibold">{$student['name']}</p>
            </div>
            <div>
                <p class="text-gray-600">Email:</p>
                <p class="font-semibold">{$student['email']}</p>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold">Exam History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
HTML;

foreach ($exams as $exam) {
    $exam_date = date('M d, Y h:i A', strtotime($exam['exam_date']));
    
    // Calculate status and score
    if (!$exam['attempt_date']) {
        $status = '<span class="text-yellow-600">Not Started</span>';
        $score = '-';
        $progress = 0;
    } else {
        if ($exam['marks_obtained'] !== null) {
            $status = '<span class="text-gray-600">Completed</span>';
            $percentage = round(($exam['marks_obtained'] / $exam['total_marks']) * 100);
            $score = "{$exam['marks_obtained']}/{$exam['total_marks']} ({$percentage}%)";
        } else {
            $status = '<span class="text-green-600">In Progress</span>';
            $score = '-';
        }
        $progress = round(($exam['questions_answered'] / $exam['total_questions']) * 100);
    }

    $page_content .= <<<HTML
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">{$exam['exam_name']}</td>
                        <td class="px-6 py-4">{$exam['subject']}</td>
                        <td class="px-6 py-4">{$exam_date}</td>
                        <td class="px-6 py-4">{$status}</td>
                        <td class="px-6 py-4">{$score}</td>
                        <td class="px-6 py-4">
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-1">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {$progress}%"></div>
                            </div>
                            <div class="text-sm text-gray-600">{$progress}%</div>
                        </td>
                    </tr>
HTML;
}

if (empty($exams)) {
    $page_content .= <<<HTML
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No exam records found for this student.
                        </td>
                    </tr>
HTML;
}

$page_content .= <<<HTML
                </tbody>
            </table>
        </div>
    </div>
</div>
HTML;

require_once 'includes/teacher_layout.php';
?>