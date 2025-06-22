<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get all exams with additional information
$stmt = $db->prepare("SELECT e.*, 
    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.exam_id) as question_count,
    (SELECT COUNT(*) FROM results r WHERE r.exam_id = e.exam_id) as attempt_count,
    CASE 
        WHEN e.teacher_id IS NOT NULL THEN (SELECT name FROM users WHERE user_id = e.teacher_id)
        WHEN e.admin_id IS NOT NULL THEN 'Admin'
    END as assigned_to
    FROM exams e
    ORDER BY e.exam_date DESC");
$stmt->execute();
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Exams";
$success_message = isset($_SESSION['success']) ? "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4'>{$_SESSION['success']}</div>" : "";
$error_message = isset($_SESSION['error']) ? "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'>{$_SESSION['error']}</div>" : "";

$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Manage Exams</h1>
        <a href="add_exam.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
            Add New Exam
        </a>
    </div>

    <!-- Display Messages -->
    {$success_message}
    {$error_message}

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Questions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attempts</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned To</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
HTML;

if (!empty($exams)) {
    $rows = '';
    // Add this at the top of the file, after session_start()
    if (isset($_GET['delete']) && isset($_GET['exam_id'])) {
        $delete_id = intval($_GET['delete']);
        try {
            $db->beginTransaction();
            
            // // Delete associated options first
            // $stmt = $db->prepare("DELETE o FROM options o 
            //                      JOIN questions q ON o.question_id = q.question_id 
            //                      WHERE q.exam_id = ?");
            // $stmt->execute([$delete_id]);
            
            // // Delete questions
            // $stmt = $db->prepare("DELETE FROM questions WHERE exam_id = ?");
            // $stmt->execute([$delete_id]);
            
            // Delete exam
            $stmt = $db->prepare("DELETE FROM exams WHERE exam_id = ?");
            $stmt->execute([$delete_id]);
            
            $db->commit();
            $_SESSION['success'] = "Exam deleted successfully.";
        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['error'] = "Error deleting exam: " . $e->getMessage();
        }
        header('Location: manage_exams.php');
        exit();
    }
    foreach ($exams as $exam) {
        $formatted_date = date('Y-m-d H:i', strtotime($exam['exam_date']));
        
        // Update the delete button in the table rows section
        $page_content .= <<<HTML
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">{$exam['exam_name']}</td>
                    <td class="px-6 py-4">{$exam['subject']}</td>
                    <td class="px-6 py-4">{$formatted_date}</td>
                    <td class="px-6 py-4">{$exam['question_count']}</td>
                    <td class="px-6 py-4">{$exam['attempt_count']}</td>
                    <td class="px-6 py-4">{$exam['assigned_to']}</td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-3">
                            <a href="edit_exam.php?id={$exam['exam_id']}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="manage_questions.php?exam_id={$exam['exam_id']}" class="text-green-600 hover:text-green-800">
                                <i class="fas fa-question-circle"></i>
                            </a>
                            <a href="manage_exams.php?delete={$exam['exam_id']}&exam_id={$exam['exam_id']}" 
                               onclick="return confirm('Are you sure you want to delete this exam? This will also delete all associated questions and cannot be undone.')" 
                               class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
        HTML;
    }
    $page_content .= $rows;
} else {
    $page_content .= <<<HTML
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No exams found</td>
                </tr>
HTML;
}

$page_content .= <<<HTML
            </tbody>
        </table>
    </div>
</div>
HTML;

// Clear any session messages
unset($_SESSION['success']);
unset($_SESSION['error']);

require_once 'includes/admin_layout.php';
?>
