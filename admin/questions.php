<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get filters
$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get unique subjects from exams table
$stmt = $db->query("SELECT DISTINCT subject FROM exams ORDER BY subject");
$subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Build query for questions
$query = "SELECT q.*, e.subject, e.exam_name, 
          (SELECT option_text FROM options o WHERE o.question_id = q.question_id AND o.is_correct = 1) as correct_answer
          FROM questions q
          JOIN exams e ON q.exam_id = e.exam_id
          WHERE 1=1";
$params = [];

if ($subject_filter) {
    $query .= " AND e.subject = ?";
    $params[] = $subject_filter;
}

if ($search) {
    $query .= " AND q.question_text LIKE ?";
    $params[] = "%$search%";
}

$query .= " ORDER BY e.subject, q.question_id";
$stmt = $db->prepare($query);
$stmt->execute($params);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Questions";
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Questions</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6">
        <form method="GET" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                <select name="subject" class="rounded border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Subjects</option>
HTML;

foreach ($subjects as $subject) {
    $selected = $subject_filter === $subject ? 'selected' : '';
    $page_content .= "<option value=\"" . htmlspecialchars($subject) . "\" {$selected}>" . htmlspecialchars($subject) . "</option>";
}

$page_content .= <<<HTML
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Question</label>
                <input type="text" name="search" value="{$search}" 
                    class="rounded border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    placeholder="Search questions...">
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Filter
            </button>
        </form>
    </div>

    <!-- Questions List -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Question</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correct Answer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
HTML;

if (!empty($questions)) {
    foreach ($questions as $question) {
        $question_preview = htmlspecialchars(substr($question['question_text'], 0, 100)) . (strlen($question['question_text']) > 100 ? '...' : '');
        $answer_preview = htmlspecialchars(substr($question['correct_answer'], 0, 50)) . (strlen($question['correct_answer']) > 50 ? '...' : '');
        
        $page_content .= <<<HTML
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">{$question['subject']}</td>
                    <td class="px-6 py-4">{$question['exam_name']}</td>
                    <td class="px-6 py-4">{$question_preview}</td>
                    <td class="px-6 py-4">{$answer_preview}</td>
                    <td class="px-6 py-4">{$question['marks']}</td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-3">
                            <a href="edit_question.php?id={$question['question_id']}" class="text-green-600 hover:text-green-800">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
        HTML;
    }
} else {
    $page_content .= <<<HTML
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No questions found</td>
                </tr>
    HTML;
}

$page_content .= <<<HTML
            </tbody>
        </table>
    </div>
</div>
HTML;

require_once 'includes/admin_layout.php';
?>