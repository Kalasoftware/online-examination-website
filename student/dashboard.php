<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT') {
    header('Location: ../login.php');
    exit();
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get student's exam statistics with proper null handling
$stmt = $db->prepare("SELECT 
    COALESCE(COUNT(DISTINCT r.exam_id), 0) as total_exams,
    COALESCE(SUM(CASE WHEN (r.marks_obtained/(SELECT SUM(marks) FROM questions WHERE exam_id = r.exam_id))*100 >= 50 THEN 1 ELSE 0 END), 0) as passed_exams,
    COALESCE(NULLIF(AVG((r.marks_obtained/(SELECT SUM(marks) FROM questions WHERE exam_id = r.exam_id))*100), 0), 0) as average_score
    FROM results r
    WHERE r.student_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get upcoming exams
// Get upcoming exams
// Update the upcoming exams query to include teacher information
$stmt = $db->prepare("
    SELECT e.*, 
        CASE WHEN er.registration_id IS NOT NULL THEN 1 ELSE 0 END as is_registered,
        (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.exam_id) as total_questions,
        COALESCE(u.name, 'Admin') as teacher_name
    FROM exams e
    LEFT JOIN exam_registrations er ON e.exam_id = er.exam_id AND er.student_id = ?
    LEFT JOIN results r ON e.exam_id = r.exam_id AND r.student_id = ?
    LEFT JOIN users u ON e.teacher_id = u.user_id
    WHERE e.exam_date > NOW()
    AND r.result_id IS NULL
    ORDER BY e.exam_date ASC");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$upcoming_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ongoing exams (exams that have started but not ended)
$stmt = $db->prepare("SELECT * FROM exams 
    WHERE exam_date <= NOW() 
    AND DATE_ADD(exam_date, INTERVAL duration MINUTE) >= NOW()
    AND exam_id NOT IN (SELECT exam_id FROM results WHERE student_id = ?)
    ORDER BY exam_date ASC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$ongoing_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ongoing exams with registration status
$stmt = $db->prepare("SELECT e.*, 
    CASE WHEN er.registration_id IS NOT NULL THEN 1 ELSE 0 END as is_registered
    FROM exams e
    LEFT JOIN exam_registrations er ON e.exam_id = er.exam_id AND er.student_id = ?
    WHERE e.exam_date <= NOW() 
    AND DATE_ADD(e.exam_date, INTERVAL e.duration MINUTE) >= NOW()
    AND e.exam_id NOT IN (SELECT exam_id FROM results WHERE student_id = ?)
    ORDER BY e.exam_date ASC LIMIT 5");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$ongoing_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize stats if null
$stats['total_exams'] = $stats['total_exams'] ?? 0;
$stats['passed_exams'] = $stats['passed_exams'] ?? 0;
$stats['average_score'] = $stats['average_score'] ?? 0;

// Format statistics for display
$avg_score = number_format($stats['average_score'], 1);
$total_exams = (int)$stats['total_exams'];
$passed_exams = (int)$stats['passed_exams'];

$page_title = "Student Dashboard";
// Format the name before using in heredoc
$student_name = htmlspecialchars($_SESSION['name']);

$page_content = <<<HTML
<div class="container mx-auto px-4">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Welcome Back, {$student_name}</h1>
            <p class="text-gray-600">Here's your exam overview</p>
        </div>
        <div class="text-xl font-semibold text-gray-700" id="currentTime"></div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700">Total Exams Taken</h3>
            <p class="text-3xl font-bold text-blue-600">{$total_exams}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700">Exams Passed</h3>
            <p class="text-3xl font-bold text-green-600">{$passed_exams}</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700">Average Score</h3>
            <p class="text-3xl font-bold text-purple-600">{$avg_score}%</p>
        </div>
    </div>

    <!-- Upcoming Exams -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Upcoming Exams</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
HTML;

if (count($upcoming_exams) > 0) {
    foreach ($upcoming_exams as $exam) {
        $exam_date = date('M d, Y h:i A', strtotime($exam['exam_date']));
        $end_time = date('M d, Y h:i A', strtotime($exam['exam_date'] . " + {$exam['duration']} minutes"));
        $teacher_name = $exam['teacher_name'] ?? 'Admin';
        $button_text = $exam['is_registered'] ? 'Registered' : 'Register for Exam';
        $button_class = $exam['is_registered'] ? 'bg-gray-500' : 'bg-blue-500 hover:bg-blue-600';
        $disabled = $exam['is_registered'] ? 'disabled' : '';
        
        $page_content .= <<<HTML
                    <tr>
                        <td class="px-6 py-4">{$exam['exam_name']}</td>
                        <td class="px-6 py-4">{$exam['subject']}</td>
                        <td class="px-6 py-4">{$teacher_name}</td>
                        <td class="px-6 py-4">{$exam_date}</td>
                        <td class="px-6 py-4">{$end_time}</td>
                        <td class="px-6 py-4">{$exam['duration']} mins</td>
                        <td class="px-6 py-4">
                            <a href="join_exam.php?exam_id={$exam['exam_id']}" 
                               class="{$button_class} text-white px-4 py-2 rounded" {$disabled}>
                                {$button_text}
                            </a>
                        </td>
                    </tr>
HTML;
    }
} else {
    $page_content .= <<<HTML
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No upcoming exams available
                        </td>
                    </tr>
HTML;
}

$page_content .= <<<HTML
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add this script before the closing HTML -->
<script>
function updateCurrentTime() {
    const now = new Date();
    document.getElementById('currentTime').textContent = 
        now.toLocaleString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        });
}

// Update time immediately and then every second
updateCurrentTime();
setInterval(updateCurrentTime, 1000);
</script>
HTML;

// Add message display after $page_content is defined
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
    $page_content = <<<HTML
<div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4" role="alert">
    <p>{$message}</p>
</div>
{$page_content}
HTML;
}

require_once 'includes/student_layout.php';
?>