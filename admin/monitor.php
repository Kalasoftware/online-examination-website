<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get current date and time
$current_datetime = date('Y-m-d H:i:s');

// Fetch ongoing and upcoming exams with registered student counts
// Update the status calculation in the SQL query
// Update the SQL query to match exam_details.php status logic
// Update the SQL query with proper datetime comparison
// Remove the first execute statement
// Update the JOIN condition in the query to include admin-created exams
$stmt = $db->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM exam_registrations er WHERE er.exam_id = e.exam_id) as registered_count,
           (SELECT COUNT(*) FROM attendance a 
            INNER JOIN exam_registrations er ON a.student_id = er.student_id AND er.exam_id = e.exam_id
            WHERE a.exam_id = e.exam_id AND a.is_present = 1) as present_count,
           (SELECT COUNT(*) FROM results r 
            INNER JOIN exam_registrations er ON r.student_id = er.student_id AND er.exam_id = e.exam_id
            WHERE r.exam_id = e.exam_id AND r.marks_obtained IS NOT NULL) as completed_count,
           (SELECT COUNT(*) FROM questions WHERE exam_id = e.exam_id) as total_questions,
           (SELECT COUNT(*) FROM responses resp 
            WHERE resp.exam_id = e.exam_id AND resp.selected_option IS NOT NULL) as answered_questions,
           CASE 
               WHEN NOW() < exam_date THEN 'Upcoming'
               WHEN (
                   SELECT COUNT(*) FROM exam_registrations er 
                   WHERE er.exam_id = e.exam_id
               ) = (
                   SELECT COUNT(*) FROM results r 
                   WHERE r.exam_id = e.exam_id 
                   AND r.marks_obtained IS NOT NULL
               ) AND (
                   SELECT COUNT(*) FROM exam_registrations er 
                   WHERE er.exam_id = e.exam_id
               ) > 0 THEN 'Completed'
               WHEN NOW() >= exam_date AND NOW() <= DATE_ADD(exam_date, INTERVAL duration MINUTE) THEN 'Ongoing'
               ELSE 'Completed'
           END as status,
           u.name as teacher_name
    FROM exams e
    LEFT JOIN users u ON e.teacher_id = u.user_id
    WHERE e.exam_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ORDER BY e.exam_date ASC
");

// Execute the query once without parameters since we're using NOW()
$stmt->execute();
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize page content first
$page_title = "Monitor Exams";
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Monitor Exams</h1>
        <div class="text-gray-600">Current Time: <span id="current-time"></span></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
HTML;


// Process each exam
    foreach ($exams as $exam) {
        $exam_start = new DateTime($exam['exam_date']);
        $exam_end = (clone $exam_start)->add(new DateInterval("PT{$exam['duration']}M"));
        
        $status_color = [
            'Upcoming' => 'bg-red-100 text-red-800',
            'Ongoing' => 'bg-blue-100 text-blue-800',
            'Completed' => 'bg-green-100 text-green-800'
        ][$exam['status']];

        $card_bg = [
            'Upcoming' => 'bg-red-50',
            'Ongoing' => 'bg-blue-50',
            'Completed' => 'bg-green-50'
        ][$exam['status']];

    $page_content .= <<<HTML
        <div class="{$card_bg} rounded-lg shadow-md p-6 space-y-4">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-semibold">{$exam['exam_name']}</h2>
                    <p class="text-gray-600">{$exam['subject']}</p>
                </div>
                <span class="px-2 py-1 rounded text-sm {$status_color}">
                    {$exam['status']}
                </span>
            </div>

            <div class="space-y-2">
                <p class="text-sm">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    {$exam_start->format('Y-m-d H:i')}
                </p>
                <p class="text-sm">
                    <i class="fas fa-clock mr-2"></i>
                    Duration: {$exam['duration']} minutes
                </p>
            </div>

            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="bg-gray-50 rounded p-2">
                    <div class="text-lg font-semibold">{$exam['registered_count']}</div>
                    <div class="text-sm text-gray-600">Registered</div>
                </div>
                <div class="bg-gray-50 rounded p-2">
                    <div class="text-lg font-semibold">{$exam['present_count']}</div>
                    <div class="text-sm text-gray-600">Present</div>
                </div>
                <div class="bg-gray-50 rounded p-2">
                    <div class="text-lg font-semibold">{$exam['completed_count']}</div>
                    <div class="text-sm text-gray-600">Completed</div>
                </div>
            </div>

            <div class="pt-4">
                <a href="exam_details.php?id={$exam['exam_id']}" 
                   class="block w-full text-center bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                    View Details
                </a>
            </div>
        </div>
    HTML;
}

// Add auto-refresh to keep status current
$page_content .= <<<HTML
    </div>
</div>

<script>
function updateCurrentTime() {
    const now = new Date();
    document.getElementById('current-time').textContent = 
        now.toLocaleString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        });
}

updateCurrentTime();
setInterval(updateCurrentTime, 1000);

// Auto refresh the page every 30 seconds to update status
setTimeout(function() {
    window.location.reload();
}, 30000);
</script>
HTML;

require_once 'includes/admin_layout.php';
?>