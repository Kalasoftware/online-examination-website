<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get student's exam results with total marks
// Update the SQL query to use attempt_date from the results table
// Update the SQL query to include teacher information
// Update the SQL query to properly handle teacher information
$stmt = $db->prepare("SELECT 
    e.exam_id,
    e.exam_name,
    e.subject,
    e.duration,
    r.marks_obtained,
    r.grade,
    r.attempt_date,
    COALESCE(u.name, 'Admin') as teacher_name,
    COALESCE((SELECT SUM(marks) FROM questions WHERE exam_id = e.exam_id), 0) as total_marks,
    COALESCE((SELECT COUNT(*) FROM questions WHERE exam_id = e.exam_id), 0) as total_questions,
    CASE 
        WHEN (SELECT SUM(marks) FROM questions WHERE exam_id = e.exam_id) > 0 
        THEN (r.marks_obtained * 100.0 / (SELECT SUM(marks) FROM questions WHERE exam_id = e.exam_id))
        ELSE 0 
    END as percentage
    FROM results r
    JOIN exams e ON r.exam_id = e.exam_id
    LEFT JOIN users u ON e.teacher_id = u.user_id
    WHERE r.student_id = ?
    ORDER BY r.attempt_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "My Results";
// Fix session message handling
$success_message = isset($_SESSION['success']) ? "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4'>{$_SESSION['success']}</div>" : "";
$error_message = isset($_SESSION['error']) ? "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'>{$_SESSION['error']}</div>" : "";

$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">My Exam Results</h1>
    </div>

    <!-- Success/Error Messages -->
    {$success_message}
    {$error_message}

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submission Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
HTML;

if (!empty($results)) {
    if (count($results) > 0) {
        foreach ($results as $result) {
            $percentage = number_format($result['percentage'], 1);
            $status = $percentage >= 50 ? 
                '<span class="px-2 py-1 text-sm font-semibold text-green-700 bg-green-100 rounded-full">Passed</span>' : 
                '<span class="px-2 py-1 text-sm font-semibold text-red-700 bg-red-100 rounded-full">Failed</span>';
            $submission_date = date('M d, Y h:i A', strtotime($result['attempt_date']));
            
            $page_content .= <<<HTML
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">{$result['exam_name']}</td>
                        <td class="px-6 py-4">{$result['subject']}</td>
                        <td class="px-6 py-4">{$result['teacher_name']}</td>
                        <td class="px-6 py-4">{$result['marks_obtained']}/{$result['total_marks']}</td>
                        <td class="px-6 py-4">{$percentage}%</td>
                        <td class="px-6 py-4">{$status}</td>
                        <td class="px-6 py-4">{$submission_date}</td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-3">
                                <a href="generate_result_pdf.php?exam_id={$result['exam_id']}" 
                                   class="text-green-600 hover:text-green-800 transition-colors duration-200"
                                   title="Download Result PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
HTML;
        }
    } else {
        $page_content .= <<<HTML
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No exam results found</td>
                    </tr>
HTML;
    }
} else {
    $page_content .= <<<HTML
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No exam results found</td>
                </tr>
HTML;
}

$page_content .= <<<HTML
            </tbody>
        </table>
    </div>
</div>
HTML;

// Clear session messages
unset($_SESSION['success']);
unset($_SESSION['error']);

require_once 'includes/student_layout.php';
?>