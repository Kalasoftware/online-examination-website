<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Fetch all exams for filter dropdown
$stmt = $db->query("SELECT exam_id, exam_name FROM exams ORDER BY exam_name");
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build the results query
$query = "SELECT 
            e.exam_name,
            u.name as student_name,
            r.marks_obtained as score,
            (SELECT SUM(marks) FROM questions WHERE exam_id = e.exam_id) as total_marks,
            ROUND((r.marks_obtained / (SELECT SUM(marks) FROM questions WHERE exam_id = e.exam_id)) * 100, 2) as percentage,
            e.duration as completion_time,
            r.attempt_date as submission_date
          FROM results r
          JOIN exams e ON r.exam_id = e.exam_id
          JOIN users u ON r.student_id = u.user_id
          WHERE 1=1";

$params = [];

if ($exam_id) {
    $query .= " AND r.exam_id = ?";
    $params[] = $exam_id;
}
if ($date_from) {
    $query .= " AND DATE(r.attempt_date) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $query .= " AND DATE(r.attempt_date) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY r.attempt_date DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fix the student count query
// Get both student count and exam attempts
// Get statistics from the database
$stmt = $db->prepare("SELECT 
    COUNT(DISTINCT r.student_id) as total_students,
    SUM(CASE WHEN (r.marks_obtained/(SELECT SUM(marks) FROM questions WHERE exam_id = r.exam_id))*100 >= 50 THEN 1 ELSE 0 END) as total_passed,
    COUNT(*) as total_attempts
    FROM results r");
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Use the correct total students count
$total_students = $stats['total_students'];
$total_passed = $stats['total_passed'];
$total_failed = $stats['total_attempts'] - $total_passed;

// Calculate average score from results
$avg_score = 0;
foreach ($results as $result) {
    $avg_score += $result['percentage'];
}
$avg_score = count($results) > 0 ? round($avg_score / count($results), 2) : 0;

// Helper function for generating exam options
function generateExamOptions($exams, $selected_exam_id) {
    $html = '';
    foreach ($exams as $exam) {
        $selected = ($exam['exam_id'] == $selected_exam_id) ? 'selected' : '';
        $html .= sprintf('<option value="%s" %s>%s</option>', 
            htmlspecialchars($exam['exam_id']), 
            $selected, 
            htmlspecialchars($exam['exam_name'])
        );
    }
    return $html;
}

$page_title = "Exam Reports";
// In the form section, replace the PHP echo with proper string concatenation
$exam_options = generateExamOptions($exams, $exam_id);
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Exam Reports</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="grid grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Select Exam</label>
                <select name="exam_id" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                    <option value="">All Exams</option>
                    {$exam_options}
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">From Date</label>
                <input type="date" name="date_from" value="{$date_from}"
                       class="shadow border rounded w-full py-2 px-3 text-gray-700">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">To Date</label>
                <input type="date" name="date_to" value="{$date_to}"
                       class="shadow border rounded w-full py-2 px-3 text-gray-700">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold text-gray-700">Total Students Given Exam</h3>
            <p class="text-3xl font-bold text-blue-600">{$total_students}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold text-gray-700">Passed</h3>
            <p class="text-3xl font-bold text-green-600">{$total_passed}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold text-gray-700">Failed</h3>
            <p class="text-3xl font-bold text-red-600">{$total_failed}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold text-gray-700">Average Score</h3>
            <p class="text-3xl font-bold text-purple-600">{$avg_score}%</p>
        </div>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time Taken</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submission Date</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
HTML;

foreach ($results as $result) {
    $percentage_class = $result['percentage'] >= 50 ? 'text-green-600' : 'text-red-600';
    $submission_date = date('M d, Y H:i', strtotime($result['submission_date']));
    
    $page_content .= <<<HTML
                <tr>
                    <td class="px-6 py-4">{$result['exam_name']}</td>
                    <td class="px-6 py-4">{$result['student_name']}</td>
                    <td class="px-6 py-4">{$result['score']}/{$result['total_marks']}</td>
                    <td class="px-6 py-4 {$percentage_class}">{$result['percentage']}%</td>
                    <td class="px-6 py-4">{$result['completion_time']} mins</td>
                    <td class="px-6 py-4">{$submission_date}</td>
                </tr>
HTML;
}

$page_content .= <<<HTML
            </tbody>
        </table>
    </div>
</div>

<script>
    // Add any JavaScript functionality here if needed
</script>
HTML;

require_once 'includes/admin_layout.php';
?>