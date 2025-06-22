<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get question and verify ownership
$stmt = $db->prepare("
    SELECT q.*, e.exam_id, e.exam_name, e.subject, e.teacher_id 
    FROM questions q
    JOIN exams e ON q.exam_id = e.exam_id
    WHERE q.question_id = ? AND e.teacher_id = ?");
$stmt->execute([$question_id, $_SESSION['user_id']]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    $_SESSION['error'] = "Question not found or unauthorized access.";
    header('Location: manage_exams.php');
    exit();
}

// Get options for this question
$stmt = $db->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY option_id");
$stmt->execute([$question_id]);
$options = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Edit Question";
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="manage_questions.php?exam_id={$question['exam_id']}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to Questions
        </a>
    </div>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h1 class="text-2xl font-bold mb-6">Edit Question</h1>
            <p class="text-gray-600 mb-6">Exam: {$question['exam_name']} ({$question['subject']})</p>

            <form action="process_edit_question.php" method="POST">
                <input type="hidden" name="question_id" value="{$question_id}">
                <input type="hidden" name="exam_id" value="{$question['exam_id']}">

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Question Text
                    </label>
                    <textarea name="question_text" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="3">{$question['question_text']}</textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Marks
                    </label>
                    <input type="number" name="marks" value="{$question['marks']}" required min="1" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Options
                    </label>
                    <div class="space-y-4">
HTML;

foreach ($options as $option) {
    $checked = $option['is_correct'] ? 'checked' : '';
    $page_content .= <<<HTML
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="correct_option" value="{$option['option_id']}" {$checked} required class="form-radio h-4 w-4 text-blue-600">
                            <input type="text" name="options[{$option['option_id']}]" value="{$option['option_text']}" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
HTML;
}

$page_content .= <<<HTML
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="history.back()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
HTML;

require_once 'includes/teacher_layout.php';
?>