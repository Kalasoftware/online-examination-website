<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Verify exam exists and belongs to teacher
$stmt = $db->prepare("SELECT * FROM exams WHERE exam_id = ? AND (teacher_id = ? OR admin_id IS NOT NULL)");
$stmt->execute([$exam_id, $_SESSION['user_id']]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    $_SESSION['error'] = "Exam not found or unauthorized access.";
    header('Location: manage_exams.php');
    exit();
}

// Get questions for this exam
$stmt = $db->prepare("
    SELECT q.*, 
           (SELECT COUNT(*) FROM options WHERE question_id = q.question_id) as option_count
    FROM questions q 
    WHERE q.exam_id = ?
    ORDER BY q.question_id");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Questions - " . $exam['exam_name'];
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="manage_exams.php" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to Exams
        </a>
    </div>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">{$exam['exam_name']} - Questions</h1>
            <p class="text-gray-600">Subject: {$exam['subject']}</p>
        </div>
        <a href="add_question.php?exam_id={$exam_id}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Add New Questions
        </a>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-6">
            <div class="space-y-6">
HTML;

if (empty($questions)) {
    $page_content .= <<<HTML
                <div class="text-center py-8 text-gray-500">
                    No questions added yet. Click "Add New Questions" to get started.
                </div>
HTML;
} else {
    foreach ($questions as $index => $question) {
        $question_number = $index + 1;
        
        // Get options for this question
        $stmt = $db->prepare("SELECT * FROM options WHERE question_id = ?");
        $stmt->execute([$question['question_id']]);
        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $page_content .= <<<HTML
                <div class="border rounded-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold">Question {$question_number}</h3>
                        <div class="flex space-x-2">
                            <a href="edit_question.php?id={$question['question_id']}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete_question.php?id={$question['question_id']}&exam_id={$exam_id}" 
                               onclick="return confirm('Are you sure you want to delete this question?')"
                               class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <p class="mb-2">{$question['question_text']}</p>
                        <span class="text-sm text-gray-600">Marks: {$question['marks']}</span>
                    </div>
                    
                    <div class="space-y-2">
HTML;

        foreach ($options as $option) {
            $is_correct = $option['is_correct'] ? 'text-green-600 font-semibold' : 'text-gray-700';
            $correct_icon = $option['is_correct'] ? '<i class="fas fa-check text-green-600 ml-2"></i>' : '';
            
            $page_content .= <<<HTML
                        <div class="flex items-center {$is_correct}">
                            <span class="mr-2">•</span>
                            {$option['option_text']} {$correct_icon}
                        </div>
HTML;
        }

        $page_content .= <<<HTML
                    </div>
                </div>
HTML;
    }
}

$page_content .= <<<HTML
            </div>
        </div>
    </div>
</div>
HTML;

require_once 'includes/teacher_layout.php';
?>