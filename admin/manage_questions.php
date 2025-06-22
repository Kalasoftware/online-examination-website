<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Fetch exam details
$stmt = $db->prepare("SELECT * FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header('Location: manage_exams.php');
    exit();
}

// Fetch questions for this exam
$stmt = $db->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_id");  // Changed from question_number to question_id
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = isset($_SESSION['success']) ? "<div class='bg-green-100 border-green-400 text-green-700 px-4 py-3 rounded mb-4' role='alert'>" . htmlspecialchars($_SESSION['success']) . "</div>" : "";
$error_message = isset($_SESSION['error']) ? "<div class='bg-red-100 border-red-400 text-red-700 px-4 py-3 rounded mb-4' role='alert'>" . htmlspecialchars($_SESSION['error']) . "</div>" : "";

$page_title = "Manage Questions - {$exam['exam_name']}";
// Modify the table headers
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Manage Questions</h1>
            <p class="text-gray-600">Exam: {$exam['exam_name']} ({$exam['subject']})</p>
        </div>
        <div class="space-x-4">
            <a href="add_question.php?exam_id={$exam_id}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Add New Question
            </a>
            <a href="manage_exams.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                Back to Exams
            </a>
        </div>
    </div>

    {$success_message}
    {$error_message}

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Question #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Question</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mark</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
HTML;

// Modify the table rows
// In the table rows section, change question_number to question_id
if (!empty($questions)) {
    $question_number = 1; // Add counter for display purposes
    foreach ($questions as $question) {
        $question_preview = htmlspecialchars(substr($question['question_text'], 0, 100)) . (strlen($question['question_text']) > 100 ? '...' : '');
        
        // In the table row section, change 'mark' to 'marks'
        $page_content .= <<<HTML
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4">{$question_number}</td>
            <td class="px-6 py-4">{$question_preview}</td>
            <td class="px-6 py-4">{$question['marks']}</td>
            <td class="px-6 py-4">
                <div class="flex space-x-3">
                    <a href="edit_question.php?id={$question['question_id']}" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button onclick="deleteQuestion({$question['question_id']})" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        HTML;
        $question_number++; // Increment counter
    }
}
else {
    $page_content .= <<<HTML
        <tr>
            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No questions found for this exam. Click "Add New Question" to get started.</td>
        </tr>
    HTML;
}

$page_content .= <<<HTML
            </tbody>
        </table>
    </div>
</div>

<script>
function deleteQuestion(questionId) {
    if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
        window.location.href = `delete_question.php?id=\${questionId}&exam_id={$exam_id}`;
    }
}
</script>
HTML;

// Clear session messages
unset($_SESSION['success']);
unset($_SESSION['error']);

require_once 'includes/admin_layout.php';
?>