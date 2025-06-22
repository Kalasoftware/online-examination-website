<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get teacher's statistics
$teacher_id = $_SESSION['user_id'];

// Get total exams created by teacher
$stmt = $db->prepare("SELECT COUNT(*) FROM exams WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$total_exams = $stmt->fetchColumn();

// Get total questions created
$stmt = $db->prepare("
    SELECT COUNT(*) FROM questions q 
    JOIN exams e ON q.exam_id = e.exam_id 
    WHERE e.teacher_id = ?");
$stmt->execute([$teacher_id]);
$total_questions = $stmt->fetchColumn();

// Get upcoming exams
$stmt = $db->prepare("
    SELECT * FROM exams 
    WHERE teacher_id = ? 
    AND exam_date > NOW()
    ORDER BY exam_date ASC 
    LIMIT 5");
$stmt->execute([$teacher_id]);
$upcoming_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// After getting upcoming exams, add ongoing exams query
$stmt = $db->prepare("
    SELECT * FROM exams 
    WHERE teacher_id = ? 
    AND exam_date <= NOW()
    AND DATE_ADD(exam_date, INTERVAL duration MINUTE) >= NOW()
    ORDER BY exam_date ASC");
$stmt->execute([$teacher_id]);
$ongoing_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-2">Total Exams</h3>
            <p class="text-3xl font-bold text-blue-600">{$total_exams}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-2">Total Questions</h3>
            <p class="text-3xl font-bold text-green-600">{$total_questions}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-2">Quick Actions</h3>
            <div class="space-y-2">
                <a href="create_exam.php" class="block text-blue-600 hover:underline">Create New Exam</a>
                <a href="manage_exams.php" class="block text-blue-600 hover:underline">Manage Exams</a>
                <a href="view_results.php" class="block text-blue-600 hover:underline">View Results</a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Upcoming Exams</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
HTML;

foreach ($upcoming_exams as $exam) {
    $exam_date = date('M d, Y h:i A', strtotime($exam['exam_date']));
    $page_content .= <<<HTML
                    <tr>
                        <td class="px-6 py-4">{$exam['exam_name']}</td>
                        <td class="px-6 py-4">{$exam['subject']}</td>
                        <td class="px-6 py-4">{$exam_date}</td>
                        <td class="px-6 py-4">{$exam['duration']} mins</td>
                        <td class="px-6 py-4">
                            <a href="edit_exam.php?id={$exam['exam_id']}" class="text-blue-600 hover:underline mr-3">Edit</a>
                            <a href="view_registrations.php?id={$exam['exam_id']}" class="text-green-600 hover:underline">View Registrations</a>
                        </td>
                    </tr>
HTML;
}

$page_content .= <<<HTML
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Ongoing Exams Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Ongoing Exams</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ends At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
HTML;

if (empty($ongoing_exams)) {
    $page_content .= <<<HTML
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No ongoing exams at the moment.</td>
                    </tr>
HTML;
} else {
    foreach ($ongoing_exams as $exam) {
        $start_time = date('M d, Y h:i A', strtotime($exam['exam_date']));
        $end_time = date('M d, Y h:i A', strtotime($exam['exam_date'] . " +{$exam['duration']} minutes"));
        $page_content .= <<<HTML
                    <tr>
                        <td class="px-6 py-4">{$exam['exam_name']}</td>
                        <td class="px-6 py-4">{$exam['subject']}</td>
                        <td class="px-6 py-4">{$start_time}</td>
                        <td class="px-6 py-4">{$end_time}</td>
                        <td class="px-6 py-4">
                            <a href="exam_details.php?id={$exam['exam_id']}" class="text-blue-600 hover:underline">View Details</a>
                        </td>
                    </tr>
HTML;
    }
}

$page_content .= <<<HTML
                </tbody>
            </table>
        </div>
    </div>
</div>
HTML;

require_once 'includes/teacher_layout.php';
?>