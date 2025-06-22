<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$error_message = '';
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Fetch exam details
$stmt = $db->prepare("SELECT * FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    $_SESSION['error'] = "Invalid exam selected.";
    header('Location: manage_exams.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text']);
    $marks = filter_input(INPUT_POST, 'marks', FILTER_VALIDATE_INT);
    $options = $_POST['options'] ?? [];
    $correct_option = isset($_POST['correct_option']) ? intval($_POST['correct_option']) : -1;

    if (empty($question_text) || !$marks || $marks <= 0) {
        $error = "Question text and marks are required. Marks must be positive.";
    } elseif (count($options) < 2) {
        $error = "At least two options are required.";
    } elseif ($correct_option < 0 || $correct_option >= count($options)) {
        $error = "Please select a valid correct option.";
    } else {
        try {
            $db->beginTransaction();

            // Insert question
            $stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, marks) VALUES (?, ?, ?)");
            $stmt->execute([$exam_id, $question_text, $marks]);
            $question_id = $db->lastInsertId();

            // Insert options
            $stmt = $db->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
            foreach ($options as $index => $option_text) {
                if (trim($option_text) !== '') {
                    $is_correct = ($index === $correct_option) ? 1 : 0;
                    $stmt->execute([$question_id, $option_text, $is_correct]);
                }
            }

            $db->commit();
            $_SESSION['success'] = "Question added successfully.";
            header("Location: manage_questions.php?exam_id=" . $exam_id);
            exit();

        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = "Add Question - {$exam['exam_name']}";
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Add New Question</h1>
            <p class="text-gray-600">Exam: {$exam['exam_name']} ({$exam['subject']})</p>
        </div>
        <a href="manage_questions.php?exam_id={$exam_id}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            Back to Questions
        </a>
    </div>

    {$error_message}

    <div class="bg-white shadow-md rounded-lg p-6">
        <form method="POST" class="space-y-6" id="questionForm">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="question_text">
                    Question Text
                </label>
                <textarea id="question_text" name="question_text" rows="3" required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                ></textarea>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="marks">
                    Marks
                </label>
                <input type="number" id="marks" name="marks" required min="1" max="100"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="space-y-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Options
                </label>
                <div id="options-container">
                    <div class="flex items-center space-x-4 mb-2">
                        <input type="radio" name="correct_option" value="0" required
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <input type="text" name="options[]" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            placeholder="Option 1">
                    </div>
                    <div class="flex items-center space-x-4 mb-2">
                        <input type="radio" name="correct_option" value="1" required
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <input type="text" name="options[]" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            placeholder="Option 2">
                    </div>
                </div>
                <button type="button" onclick="addOption()" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-plus"></i> Add Another Option
                </button>
            </div>

            <div class="flex justify-end space-x-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Add Question
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let optionCount = 2;

function addOption() {
    optionCount++;
    const container = document.getElementById('options-container');
    const newOption = document.createElement('div');
    newOption.className = 'flex items-center space-x-4 mb-2';
    newOption.innerHTML = `
        <input type="radio" name="correct_option" value="\${optionCount - 1}" required
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
        <input type="text" name="options[]" required
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            placeholder="Option \${optionCount}">
        <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(newOption);
}
</script>
HTML;

require_once 'includes/admin_layout.php';
?>