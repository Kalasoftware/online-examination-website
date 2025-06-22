<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get teachers list for dropdown
$stmt = $db->prepare("SELECT user_id, name FROM users WHERE role = 'TEACHER'");
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Add New Exam";
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Add New Exam</h1>
        
        <form id="examForm" method="POST" action="process_exam.php" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="exam_name">
                    Exam Name
                </label>
                <input type="text" id="exam_name" name="exam_name" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="subject">
                    Subject
                </label>
                <input type="text" id="subject" name="subject" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Assigned To
                </label>
                <div class="mt-2">
                    <label class="inline-flex items-center mr-6">
                        <input type="radio" name="assigned_to" value="admin" class="form-radio" checked
                               onchange="document.getElementById('teacher_select').disabled = true">
                        <span class="ml-2">Admin (Self)</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="assigned_to" value="teacher" class="form-radio"
                               onchange="document.getElementById('teacher_select').disabled = false">
                        <span class="ml-2">Teacher</span>
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="teacher_select">
                    Select Teacher
                </label>
                <select id="teacher_select" name="teacher_id" disabled
                        class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">Select a teacher</option>
HTML;

foreach ($teachers as $teacher) {
    $page_content .= <<<HTML
                    <option value="{$teacher['user_id']}">{$teacher['name']}</option>
HTML;
}

$page_content .= <<<HTML
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="exam_date">
                    Exam Date and Time
                </label>
                <input type="datetime-local" id="exam_date" name="exam_date" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="duration">
                    Duration (minutes)
                </label>
                <input type="number" id="duration" name="duration" required min="1"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="question_count">
                    Number of Questions
                </label>
                <input type="number" id="question_count" name="question_count" required min="1"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="flex items-center justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Create Exam
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('examForm').addEventListener('submit', function(e) {
    const teacherRadio = document.querySelector('input[name="assigned_to"][value="teacher"]');
    const teacherSelect = document.getElementById('teacher_select');
    
    if (teacherRadio.checked && !teacherSelect.value) {
        e.preventDefault();
        alert('Please select a teacher when assigning to teacher.');
        return false;
    }
});

// Set minimum datetime to current
const examDateInput = document.getElementById('exam_date');
const now = new Date();
now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
examDateInput.min = now.toISOString().slice(0, 16);
</script>
HTML;

require_once 'includes/admin_layout.php';
?>