<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get exam ID if specified
$exam_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Get all exams for this teacher
$stmt = $db->prepare("
    SELECT exam_id, exam_name, subject, exam_date 
    FROM exams 
    WHERE teacher_id = ? 
    ORDER BY exam_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "View Results";
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Exam Results</h1>
    </div>

    <div class="mb-6">
        <form action="" method="GET" class="flex gap-4 items-center">
            <select name="id" class="shadow border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">Select Exam</option>
HTML;

foreach ($exams as $exam) {
    $selected = ($exam_id == $exam['exam_id']) ? 'selected' : '';
    $exam_date = date('M d, Y', strtotime($exam['exam_date']));
    $page_content .= "<option value='{$exam['exam_id']}' {$selected}>{$exam['exam_name']} - {$exam['subject']} ({$exam_date})</option>";
}

$page_content .= <<<HTML
            </select>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                View Results
            </button>
        </form>
    </div>
HTML;

if ($exam_id) {
    // Get exam details
    $stmt = $db->prepare("
        SELECT r.*, u.name as student_name, u.email,
               (SELECT COUNT(*) FROM questions WHERE exam_id = r.exam_id) as total_questions,
               (SELECT SUM(marks) FROM questions WHERE exam_id = r.exam_id) as total_marks
        FROM results r
        JOIN users u ON r.student_id = u.user_id
        WHERE r.exam_id = ?
        ORDER BY r.marks_obtained DESC");
    $stmt->execute([$exam_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($results)) {
        $page_content .= <<<HTML
        <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks Obtained</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Marks</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attempt Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
HTML;

        foreach ($results as $result) {
            $percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
            $attempt_date = date('M d, Y h:i A', strtotime($result['attempt_date']));
            
            $page_content .= <<<HTML
                    <tr>
                        <td class="px-6 py-4">{$result['student_name']}</td>
                        <td class="px-6 py-4">{$result['email']}</td>
                        <td class="px-6 py-4">{$result['marks_obtained']}</td>
                        <td class="px-6 py-4">{$result['total_marks']}</td>
                        <td class="px-6 py-4">{$percentage}%</td>
                        <td class="px-6 py-4">{$result['grade']}</td>
                        <td class="px-6 py-4">{$attempt_date}</td>
                        <td class="px-6 py-4">
                            <a href="view_student_responses.php?result_id={$result['result_id']}" 
                               class="text-blue-600 hover:underline">View Details</a>
                        </td>
                    </tr>
HTML;
        }

        $page_content .= <<<HTML
                </tbody>
            </table>
        </div>
HTML;
    } else {
        $page_content .= <<<HTML
        <div class="bg-white rounded-lg shadow-md p-6 text-center text-gray-500">
            No results found for this exam.
        </div>
HTML;
    }
}

$page_content .= "</div>";

require_once 'includes/teacher_layout.php';
?>