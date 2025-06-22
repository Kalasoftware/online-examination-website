<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$current_datetime = date('Y-m-d H:i:s');

// Verify exam belongs to teacher
$stmt = $db->prepare("
    SELECT e.*, 
           CASE 
               WHEN e.exam_date > ? THEN 'Upcoming'
               WHEN e.exam_date <= ? AND DATE_ADD(e.exam_date, INTERVAL e.duration MINUTE) >= ? THEN 'Ongoing'
               ELSE 'Completed'
           END as status
    FROM exams e
    WHERE e.exam_id = ? AND e.teacher_id = ?
");
$stmt->execute([$current_datetime, $current_datetime, $current_datetime, $exam_id, $_SESSION['user_id']]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    $_SESSION['error'] = "Exam not found or unauthorized access.";
    header('Location: monitor_exam.php');
    exit();
}

// Get total questions
$stmt = $db->prepare("SELECT COUNT(*) FROM questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$total_questions = $stmt->fetchColumn();

$exam_start = new DateTime($exam['exam_date']);
$exam_end = (clone $exam_start)->add(new DateInterval("PT{$exam['duration']}M"));

// Add status color definition
$status_color = [
    'Upcoming' => 'bg-blue-100 text-blue-800',
    'Ongoing' => 'bg-green-100 text-green-800 animate-pulse',
    'Completed' => 'bg-gray-100 text-gray-800'
][$exam['status']];

$page_title = "Exam Details - " . $exam['exam_name'];
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">{$exam['exam_name']}</h1>
                <p class="text-gray-600">{$exam['subject']}</p>
            </div>
            <div class="text-gray-600">Current Time: <span id="current-time"></span></div>
        </div>
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-sm text-gray-600">Date</div>
                <div class="font-semibold">{$exam_start->format('M d, Y H:i')}</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-sm text-gray-600">Duration</div>
                <div class="font-semibold">{$exam['duration']} minutes</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-sm text-gray-600">Questions</div>
                <div class="font-semibold">{$total_questions}</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-sm text-gray-600">Status</div>
                <div class="px-2 py-1 rounded text-sm {$status_color} inline-block">
                    {$exam['status']}
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold">Student Progress</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
HTML;

// Get registered students and their progress
$stmt = $db->prepare("
    SELECT 
        u.name, u.email,
        r.attempt_date as start_time,
        DATE_ADD(r.attempt_date, INTERVAL ? MINUTE) as end_time,
        r.marks_obtained as score,
        (SELECT COUNT(*) FROM responses resp 
         WHERE resp.student_id = er.student_id 
         AND resp.exam_id = er.exam_id 
         AND resp.selected_option IS NOT NULL) as questions_answered,
        (SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = er.exam_id) as total_possible_marks,
        CASE 
            WHEN r.attempt_date IS NULL THEN 'Not Started'
            WHEN r.attempt_date IS NOT NULL AND r.marks_obtained IS NULL THEN 'In Progress'
            WHEN r.marks_obtained IS NOT NULL THEN 'Completed'
            ELSE 'Not Started'
        END as exam_status
    FROM exam_registrations er
    JOIN users u ON er.student_id = u.user_id
    LEFT JOIN results r ON er.student_id = r.student_id AND er.exam_id = r.exam_id
    WHERE er.exam_id = ?
    ORDER BY u.name ASC");
$stmt->execute([$exam['duration'], $exam_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($students as $student) {
    $status = $student['exam_status'];
    
    $status_color = [
        'Not Started' => 'text-yellow-600',
        'In Progress' => 'text-green-600',
        'Completed' => 'text-gray-600'
    ][$status];

    $questions_answered = $student['questions_answered'] ?? 0;
    $progress = $total_questions > 0 ? round(($questions_answered / $total_questions) * 100) : 0;
    
    $start_time = $student['start_time'] ? date('Y-m-d H:i:s', strtotime($student['start_time'])) : '-';
    $end_time = $student['start_time'] && $student['exam_status'] === 'Completed' ? 
                date('Y-m-d H:i:s', strtotime($student['end_time'])) : '-';
    
    $score_display = '-';
    if (isset($student['score']) && $student['score'] !== null) {
        $total_marks = $student['total_possible_marks'] ?? 0;
        if ($total_marks > 0) {
            $percentage = round(($student['score'] / $total_marks) * 100);
            $score_display = "{$student['score']}/{$total_marks} ({$percentage}%)";
        }
    }

    $page_content .= <<<HTML
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div>
                                <div class="font-medium">{$student['name']}</div>
                                <div class="text-sm text-gray-500">{$student['email']}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="{$status_color}">{$status}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-1">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {$progress}%"></div>
                            </div>
                            <div class="text-sm text-gray-600">{$progress}%</div>
                        </td>
                        <td class="px-6 py-4">{$start_time}</td>
                        <td class="px-6 py-4">{$end_time}</td>
                        <td class="px-6 py-4">{$score_display}</td>
                    </tr>
HTML;
}

$page_content .= <<<HTML
                </tbody>
            </table>
        </div>
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
</script>
HTML;

require_once 'includes/teacher_layout.php';
?>