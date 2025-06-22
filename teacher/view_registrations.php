<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get exam ID if specified
$exam_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Get all exams for this teacher
$stmt = $db->prepare("
    SELECT exam_id, exam_name, subject, exam_date 
    FROM exams 
    WHERE teacher_id = ? 
    ORDER BY exam_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "View Registrations";
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Exam Registrations</h1>
    </div>

    <div class="mb-6">
        <form action="" method="GET" class="flex gap-4 items-center">
            <select name="id" class="shadow border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">Select Exam</option>
HTML;

foreach ($exams as $exam) {
    $selected = ($exam_id == $exam['exam_id']) ? 'selected' : '';
    $exam_date = date('M d, Y', strtotime($exam['exam_date']));
    $page_content .= "<option value='{$exam['exam_id']}' {$selected}>{$exam['exam_name']} - {$exam['subject']} ({$exam_date})</option>";
}

$page_content .= <<<HTML
            </select>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                View Registrations
            </button>
        </form>
    </div>
HTML;

if ($exam_id) {
    // Get registrations for selected exam
    $stmt = $db->prepare("
        SELECT er.*, u.name as student_name, u.email, e.exam_name, e.exam_date
        FROM exam_registrations er
        JOIN users u ON er.student_id = u.user_id
        JOIN exams e ON er.exam_id = e.exam_id
        WHERE er.exam_id = ?
        ORDER BY er.registered_at DESC");
    $stmt->execute([$exam_id]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($registrations)) {
        $page_content .= <<<HTML
        <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registration Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
HTML;

        foreach ($registrations as $reg) {
            $reg_date = date('M d, Y h:i A', strtotime($reg['registered_at']));
            
            $page_content .= <<<HTML
                    <tr>
                        <td class="px-6 py-4">{$reg['student_name']}</td>
                        <td class="px-6 py-4">{$reg['email']}</td>
                        <td class="px-6 py-4">{$reg_date}</td>
                    </tr>
HTML;
        }

        $page_content .= <<<HTML
                </tbody>
            </table>
        </div>
HTML;
    } else {
        $page_content .= <<<HTML
        <div class="bg-white rounded-lg shadow-md p-6 text-center text-gray-500">
            No registrations found for this exam.
        </div>
HTML;
    }
}

$page_content .= "</div>";

require_once 'includes/teacher_layout.php';
?>