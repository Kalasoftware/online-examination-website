<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get available exams (not taken yet by the student)
// Add debugging to check exam data
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Get available exams with simplified query first
// Get available exams with proper status
$stmt = $db->prepare("
    SELECT e.*, 
        (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.exam_id) as total_questions,
        (SELECT SUM(COALESCE(marks, 0)) FROM questions q WHERE q.exam_id = e.exam_id) as total_marks,
        CASE 
            WHEN e.exam_date <= CURRENT_TIMESTAMP AND DATE_ADD(e.exam_date, INTERVAL e.duration MINUTE) >= CURRENT_TIMESTAMP THEN 'ongoing'
            WHEN e.exam_date > CURRENT_TIMESTAMP THEN 'upcoming'
            ELSE 'past'
        END as exam_status,
        CASE WHEN er.registration_id IS NOT NULL THEN 1 ELSE 0 END as is_registered
    FROM exams e
    LEFT JOIN exam_registrations er ON e.exam_id = er.exam_id AND er.student_id = ?
    LEFT JOIN results r ON e.exam_id = r.exam_id AND r.student_id = ?
    WHERE (
        e.exam_date > CURRENT_TIMESTAMP 
        OR (
            e.exam_date <= CURRENT_TIMESTAMP 
            AND DATE_ADD(e.exam_date, INTERVAL e.duration MINUTE) >= CURRENT_TIMESTAMP
        )
    )
    AND r.result_id IS NULL
    ORDER BY e.exam_date ASC");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Remove the duplicate ongoing exams query
// Separate exams by status
$upcoming_exams = array_filter($exams, fn($exam) => $exam['exam_status'] === 'upcoming');
$ongoing_exams = array_filter($exams, fn($exam) => $exam['exam_status'] === 'ongoing');

$page_title = "Available Exams";
$exam_count = count($exams);
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Available Exams</h1>
        <p class="text-gray-600">Total Available Exams: {$exam_count}</p>
    </div>

    <!-- Ongoing Exams Section -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold mb-4 text-red-600">Ongoing Exams</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
HTML;

// Remove this entire block
/*
// Display ongoing exams with "Take Exam Now" button
if (count($ongoing_exams) > 0) {
    foreach ($ongoing_exams as $exam) {
        echo <<<HTML
        <div class="exam-card">
            <h3>{$exam['exam_name']}</h3>
            <a href="take_exam.php?id={$exam['exam_id']}" class="btn btn-primary">
                Take Exam Now
            </a>
        </div>
HTML;
    }
}
*/

// Update the query to properly include total questions and marks
$stmt = $db->prepare("
    SELECT e.*, 
        (SELECT COUNT(*) FROM questions WHERE exam_id = e.exam_id) as total_questions,
        (SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = e.exam_id) as total_marks,
        CASE 
            WHEN e.exam_date <= CURRENT_TIMESTAMP AND DATE_ADD(e.exam_date, INTERVAL e.duration MINUTE) >= CURRENT_TIMESTAMP THEN 'ongoing'
            WHEN e.exam_date > CURRENT_TIMESTAMP THEN 'upcoming'
            ELSE 'past'
        END as exam_status,
        CASE WHEN er.registration_id IS NOT NULL THEN 1 ELSE 0 END as is_registered
    FROM exams e
    LEFT JOIN exam_registrations er ON e.exam_id = er.exam_id AND er.student_id = ?
    LEFT JOIN results r ON e.exam_id = r.exam_id AND r.student_id = ?
    WHERE r.result_id IS NULL
    ORDER BY e.exam_date ASC");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Remove duplicate ongoing exams query and use the data from main query
$ongoing_exams = array_filter($exams, function($exam) {
    return $exam['exam_status'] === 'ongoing' && $exam['is_registered'] === 1;
});
$upcoming_exams = array_filter($exams, function($exam) {
    return $exam['exam_status'] === 'upcoming';
});

// Update the ongoing exams display section
if (!empty($ongoing_exams)) {
    foreach ($ongoing_exams as $exam) {
        $exam_date = date('M d, Y h:i A', strtotime($exam['exam_date']));
        $end_time = date('M d, Y h:i A', strtotime($exam['exam_date'] . " +{$exam['duration']} minutes"));
        
        $page_content .= <<<HTML
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <h2 class="text-xl font-semibold mb-2">{$exam['exam_name']}</h2>
            <div class="text-gray-600 mb-4">
                <p><strong>Subject:</strong> {$exam['subject']}</p>
                <p><strong>Duration:</strong> {$exam['duration']} minutes</p>
                <p><strong>Started:</strong> {$exam_date}</p>
                <p><strong>Ends:</strong> {$end_time}</p>
                <p><strong>Total Questions:</strong> {$exam['total_questions']}</p>
                <p><strong>Total Marks:</strong> {$exam['total_marks']}</p>
            </div>
            <div class="flex justify-end">
                <a href="take_exam.php?id={$exam['exam_id']}" 
                   class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                   Join Exam Now</a>
            </div>
        </div>
HTML;
    }
} else {
    $page_content .= <<<HTML
        <div class="col-span-full text-center py-8 text-gray-500">
            No ongoing exams at the moment.
        </div>
HTML;
}

$page_content .= <<<HTML
        </div>
    </div>

    <!-- Upcoming Exams Section -->
    <div>
        <h2 class="text-xl font-semibold mb-4">Upcoming Exams</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
HTML;

if (!empty($upcoming_exams)) {
    foreach ($upcoming_exams as $exam) {
        $exam_date = date('M d, Y h:i A', strtotime($exam['exam_date']));
        $total_questions = intval($exam['total_questions']);
        $total_marks = intval($exam['total_marks']);
        
        // Check if student has already taken this exam
        $stmt = $db->prepare("SELECT result_id FROM results WHERE exam_id = ? AND student_id = ?");
        $stmt->execute([$exam['exam_id'], $_SESSION['user_id']]);
        $has_attempted = $stmt->fetch(PDO::FETCH_ASSOC);

        // Determine button display
        $button_html = $has_attempted ? 
            '<span class="bg-gray-500 text-white px-4 py-2 rounded">Exam Completed</span>' : 
            '<a href="take_exam.php?id=' . $exam['exam_id'] . '" 
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Start Exam
            </a>';
        
        $page_content .= <<<HTML
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-2">{$exam['exam_name']}</h2>
            <div class="text-gray-600 mb-4">
                <p><strong>Subject:</strong> {$exam['subject']}</p>
                <p><strong>Duration:</strong> {$exam['duration']} minutes</p>
                <p><strong>Date:</strong> {$exam_date}</p>
                <p><strong>Total Questions:</strong> {$total_questions}</p>
                <p><strong>Total Marks:</strong> {$total_marks}</p>
            </div>
            <div class="flex justify-end">
HTML;

if ($has_attempted) {
    $page_content .= '<span class="bg-gray-500 text-white px-4 py-2 rounded">Exam Completed</span>';
} else {
    $exam_start_time = strtotime($exam['exam_date']);
    $current_time = time();
    
    if ($exam_start_time > $current_time) {
        $page_content .= <<<HTML
            <button class="bg-blue-500 text-white px-4 py-2 rounded" disabled>
                Starts in <span id="countdown-{$exam['exam_id']}"></span>
            </button>
            <script>
                // Set the date we're counting down to
                var countDownDate{$exam['exam_id']} = new Date("{$exam['exam_date']}").getTime();
                
                // Update the count down every 1 second
                var x{$exam['exam_id']} = setInterval(function() {
                    // Get today's date and time
                    var now = new Date().getTime();
                    
                    // Find the distance between now and the count down date
                    var distance = countDownDate{$exam['exam_id']} - now;
                    
                    // Time calculations for days, hours, minutes and seconds
                    var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    // Display the result in the element with id="countdown-{$exam['exam_id']}"
                    document.getElementById("countdown-{$exam['exam_id']}").innerHTML = 
                        (days > 0 ? days + "d " : "") +
                        (hours > 0 ? hours + "h " : "") +
                        (minutes > 0 ? minutes + "m " : "") +
                        seconds + "s";
                    
                    // If the count down is finished, enable the button
                    if (distance < 0) {
                        clearInterval(x{$exam['exam_id']});
                        document.getElementById("countdown-{$exam['exam_id']}").parentElement.outerHTML = 
                            '<a href="take_exam.php?id={$exam['exam_id']}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Start Exam</a>';
                    }
                }, 1000);
            </script>
HTML;
    } else {
        $page_content .= <<<HTML
            <a href="take_exam.php?id={$exam['exam_id']}" 
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Start Exam
            </a>
HTML;
    }
}

$page_content .= <<<HTML
            </div>
        </div>
HTML;
    }
} else {
    $page_content .= <<<HTML
        <div class="col-span-full text-center py-8 text-gray-500">
            No exams available at the moment.
        </div>
HTML;
}

$page_content .= <<<HTML
    </div>
</div>
HTML;

require_once 'includes/student_layout.php';
?>