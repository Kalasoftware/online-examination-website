<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch question details
$stmt = $db->prepare("SELECT q.*, e.exam_name, e.subject 
                      FROM questions q 
                      JOIN exams e ON q.exam_id = e.exam_id 
                      WHERE q.question_id = ?");
$stmt->execute([$question_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    $_SESSION['error'] = "Question not found.";
    header('Location: manage_exams.php');
    exit();
}

// Fetch options for this question
$stmt = $db->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY option_id");
$stmt->execute([$question_id]);
$options = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = htmlspecialchars(trim($_POST['question_text']), ENT_QUOTES, 'UTF-8');
    $marks = filter_input(INPUT_POST, 'marks', FILTER_VALIDATE_INT); // Changed from mark to marks
    $correct_option = filter_input(INPUT_POST, 'correct_option', FILTER_VALIDATE_INT);
    $options_text = $_POST['options'] ?? [];

    if (empty($question_text) || !$marks || $marks <= 0) {
        $error = "Question text and marks are required. Marks must be positive.";
    } elseif (count($options_text) < 2) {
        $error = "At least two options are required.";
    } elseif (!in_array($correct_option, array_keys($options_text))) {
        $error = "Please select a valid correct option.";
    } else {
        try {
            $db->beginTransaction();

            // Update question
            $stmt = $db->prepare("UPDATE questions SET question_text = ?, marks = ? WHERE question_id = ?");
            $stmt->execute([$question_text, $marks, $question_id]);

            // Delete existing options
            $stmt = $db->prepare("DELETE FROM options WHERE question_id = ?");
            $stmt->execute([$question_id]);

            // Insert new options
            $stmt = $db->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
            foreach ($options_text as $option_id => $option_text) {
                $is_correct = ($option_id == $correct_option) ? 1 : 0;
                $stmt->execute([$question_id, $option_text, $is_correct]);
            }

            $db->commit();
            $success = "Question updated successfully!";

            // Refresh question and options data
            $stmt = $db->prepare("SELECT q.*, e.exam_name, e.subject FROM questions q JOIN exams e ON q.exam_id = e.exam_id WHERE q.question_id = ?");
            $stmt->execute([$question_id]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY option_id");
            $stmt->execute([$question_id]);
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$error_message = $error ? "<div class='bg-red-100 border-red-400 text-red-700 px-4 py-3 rounded mb-4' role='alert'>{$error}</div>" : "";
$success_message = $success ? "<div class='bg-green-100 border-green-400 text-green-700 px-4 py-3 rounded mb-4' role='alert'>{$success}</div>" : "";

$page_title = "Edit Question - {$question['exam_name']}";
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Edit Question</h1>
            <p class="text-gray-600">Exam: {$question['exam_name']} ({$question['subject']})</p>
        </div>
        <a href="manage_questions.php?exam_id={$question['exam_id']}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            Back to Questions
        </a>
    </div>

    {$error_message}
    {$success_message}

    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="question_text">
                    Question Text
                </label>
                <textarea id="question_text" name="question_text" rows="3" 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    required>{$question['question_text']}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="marks">
                    Marks
                </label>
                <input type="number" id="marks" name="marks" 
                    value="{$question['marks']}"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    required min="1" max="100">
            </div>

            <div class="space-y-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Options
                </label>
HTML;

foreach ($options as $option) {
    $checked = $option['is_correct'] ? 'checked' : '';
    $page_content .= <<<HTML
                <div class="flex items-center space-x-4">
                    <input type="radio" name="correct_option" value="{$option['option_id']}" {$checked}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                    <input type="text" name="options[{$option['option_id']}]" 
                        value="{$option['option_text']}"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required>
                </div>
    HTML;
}

$page_content .= <<<HTML
            </div>

            <div class="flex justify-end space-x-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Update Question
                </button>
            </div>
        </form>
    </div>
</div>
HTML;

require_once 'includes/admin_layout.php';
?>