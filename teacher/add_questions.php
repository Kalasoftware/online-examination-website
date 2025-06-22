<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER' || !isset($_SESSION['pending_exam'])) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Add Exam Questions";
$question_count = $_SESSION['pending_exam']['question_count'];
$exam_id = $_SESSION['pending_exam']['exam_id'];

$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Add Questions</h1>
        
        <form id="questionForm" method="POST" action="process_questions.php" class="space-y-6">
            <input type="hidden" name="exam_id" value="{$exam_id}">
HTML;

for ($i = 1; $i <= $question_count; $i++) {
    $page_content .= <<<HTML
            <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <h2 class="text-lg font-semibold mb-4">Question {$i}</h2>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Question Text
                    </label>
                    <textarea name="questions[{$i}][text]" required
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                              rows="3"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Marks
                    </label>
                    <input type="number" name="questions[{$i}][marks]" required min="1"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="space-y-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Options
                    </label>
                    
                    <div class="grid grid-cols-1 gap-4">
HTML;

    for ($j = 1; $j <= 4; $j++) {
        $page_content .= <<<HTML
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="questions[{$i}][correct]" value="{$j}" required
                                   class="form-radio h-4 w-4 text-blue-600">
                            <input type="text" name="questions[{$i}][options][{$j}]" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                   placeholder="Option {$j}">
                        </div>
HTML;
    }

    $page_content .= <<<HTML
                    </div>
                </div>
            </div>
HTML;
}

$page_content .= <<<HTML
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Save Questions
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('questionForm').addEventListener('submit', function(e) {
    const forms = document.querySelectorAll('input[type="radio"]:checked').length;
    if (forms < {$question_count}) {
        e.preventDefault();
        alert('Please select correct answer for all questions');
        return false;
    }
});
</script>
HTML;

require_once 'includes/teacher_layout.php';
?>