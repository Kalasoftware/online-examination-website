<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

// Fetch exam details
$stmt = $db->prepare("SELECT * FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header('Location: manage_exams.php');
    exit();
}

// Add input validation and sanitization
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Replace deprecated FILTER_SANITIZE_STRING with htmlspecialchars
    $exam_name = htmlspecialchars(trim($_POST['exam_name']), ENT_QUOTES, 'UTF-8');
    $subject = htmlspecialchars(trim($_POST['subject']), ENT_QUOTES, 'UTF-8');
    $exam_date = htmlspecialchars(trim($_POST['exam_date']), ENT_QUOTES, 'UTF-8');
    $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT);

    // Validate exam date
    $exam_timestamp = strtotime($exam_date);
    if ($exam_timestamp === false) {
        $error = "Invalid date format.";
    } elseif ($exam_timestamp < time()) {
        $error = "Exam date must be in the future.";
    } elseif (empty($exam_name) || empty($subject) || !$duration || $duration <= 0) {
        $error = "All fields are required and duration must be positive.";
    } else {
        try {
            $formatted_exam_date = date('Y-m-d H:i:s', $exam_timestamp);
            $stmt = $db->prepare("UPDATE exams SET exam_name = ?, subject = ?, exam_date = ?, duration = ? WHERE exam_id = ?");
            if ($stmt->execute([$exam_name, $subject, $formatted_exam_date, $duration, $exam_id])) {
                $success = "Exam updated successfully!";
                
                // Refresh exam data
                $stmt = $db->prepare("SELECT * FROM exams WHERE exam_id = ?");
                $stmt->execute([$exam_id]);
                $exam = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Failed to update exam.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Add this before the form HTML
$min_date = date('Y-m-d\TH:i');
$formatted_date = isset($exam['exam_date']) ? date('Y-m-d\TH:i', strtotime($exam['exam_date'])) : '';

// Define error and success messages
$error_message = $error ? "<div class='bg-red-100 border-red-400 text-red-700 px-4 py-3 rounded mb-4' role='alert'>" . htmlspecialchars($error) . "</div>" : "";
$success_message = $success ? "<div class='bg-green-100 border-green-400 text-green-700 px-4 py-3 rounded mb-4' role='alert'>" . htmlspecialchars($success) . "</div>" : "";

$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Edit Exam</h1>
        <a href="manage_exams.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Back to Exams</a>
    </div>

    {$error_message}
    {$success_message}

    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" class="space-y-4" novalidate>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="exam_name">Exam Name</label>
                <input type="text" id="exam_name" name="exam_name" 
                    value="{$exam['exam_name']}"
                    class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="subject">Subject</label>
                <input type="text" id="subject" name="subject" 
                    value="{$exam['subject']}"
                    class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="exam_date">Exam Date & Time</label>
                <input type="datetime-local" id="exam_date" name="exam_date" 
                    value="{$formatted_date}"
                    min="{$min_date}"
                    class="shadow border rounded w-full py-2 px-3 text-gray-700" required>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="duration">Duration (minutes)</label>
                <input type="number" id="duration" name="duration" 
                    value="{$exam['duration']}"
                    class="shadow border rounded w-full py-2 px-3 text-gray-700" 
                    required min="1" max="480">
            </div>
            
            <div class="flex justify-end space-x-4">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 transition-colors">
                    Update Exam
                </button>
            </div>
        </form>
    </div>
</div>
HTML;

require_once 'includes/admin_layout.php';
?>