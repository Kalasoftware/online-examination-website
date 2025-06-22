<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get all exams for this teacher
$stmt = $db->prepare("SELECT * FROM exams WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Exams";
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Manage Exams</h1>
        <a href="create_exam.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Create New Exam
        </a>
    </div>

    <div class="bg-white shadow-md rounded my-6">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
HTML;

foreach ($exams as $exam) {
    $exam_date = date('M d, Y h:i A', strtotime($exam['exam_date']));
    $page_content .= <<<HTML
                <tr>
                    <td class="px-6 py-4">{$exam['exam_name']}</td>
                    <td class="px-6 py-4">{$exam['subject']}</td>
                    <td class="px-6 py-4">{$exam_date}</td>
                    <td class="px-6 py-4">{$exam['duration']} minutes</td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-3">
                            <a href="edit_exam.php?id={$exam['exam_id']}" class="text-yellow-600 hover:text-yellow-900">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="manage_questions.php?exam_id={$exam['exam_id']}" class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-question-circle"></i> Questions
                            </a>
                            <a href="view_results.php?id={$exam['exam_id']}" class="text-green-600 hover:text-green-900">
                                <i class="fas fa-chart-bar"></i> Results
                            </a>
                            <a href="delete_exam.php?id={$exam['exam_id']}" 
                               onclick="return confirm('Are you sure you want to delete this exam? This action cannot be undone.')"
                               class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </td>
                </tr>
HTML;
}

$page_content .= <<<HTML
            </tbody>
        </table>
    </div>
</div>
HTML;

require_once 'includes/teacher_layout.php';
?>