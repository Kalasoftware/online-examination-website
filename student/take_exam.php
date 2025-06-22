<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT') {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: exams.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$hide_navbar = true;

$exam_id = $_GET['id'];
$student_id = $_SESSION['user_id'];

// Check if exam exists and is available
$stmt = $db->prepare("SELECT * FROM exams WHERE exam_id = ? AND exam_date <= NOW() AND 
    DATE_ADD(exam_date, INTERVAL duration MINUTE) >= NOW()");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    $_SESSION['error'] = "Exam is not available at this time.";
    header('Location: exams.php');
    exit();
}

// Mark attendance
$stmt = $db->prepare("
    INSERT INTO attendance (exam_id, student_id, is_present) 
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE is_present = 1
");
$stmt->execute([$exam_id, $student_id]);

// Check if student has already taken this exam
$stmt = $db->prepare("SELECT * FROM results WHERE exam_id = ? AND student_id = ?");
$stmt->execute([$exam_id, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    $_SESSION['error'] = "You have already taken this exam.";
    header('Location: exams.php');
    exit();
}

// Verify student is registered for this exam
$stmt = $db->prepare("SELECT * FROM exam_registrations 
                     WHERE exam_id = ? AND student_id = ?");
$stmt->execute([$exam_id, $student_id]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "You must register for this exam first.";
    header('Location: exams.php');
    exit();
}

// Get exam questions
$stmt = $db->prepare("SELECT q.*, GROUP_CONCAT(
        JSON_OBJECT(
            'option_id', o.option_id,
            'option_text', o.option_text
        )
    ) as options
    FROM questions q
    LEFT JOIN options o ON q.question_id = o.question_id
    WHERE q.exam_id = ?
    GROUP BY q.question_id
    ORDER BY q.question_id");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add this after getting exam data
$stmt = $db->prepare("SELECT COUNT(*) as total_questions FROM questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$total_questions = $stmt->fetch(PDO::FETCH_ASSOC)['total_questions'];
$exam_duration = $exam['duration'] * 60; // Convert minutes to seconds
// $exam_duration = 1200; // 20 minutes in seconds

$page_title = $exam['exam_name'];
$page_content = <<<HTML
<div class="fixed inset-0 bg-gray-100 overflow-auto">
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">{$exam['exam_name']}</h1>
                <div id="timer" class="text-xl font-bold text-red-600 fixed top-4 right-4 bg-white p-2 rounded-lg shadow-lg"></div>
            </div>
            <div class="mb-4 text-gray-600">
                <p><strong>Subject:</strong> {$exam['subject']}</p>
                <p><strong>Duration:</strong> {$exam['duration']} minutes</p>
                <p><strong>Total Questions:</strong> {$total_questions}</p>
            </div>
        </div>

        <form id="examForm" method="POST" action="submit_exam.php">
            <input type="hidden" name="exam_id" value="{$exam_id}">
            
HTML;

foreach ($questions as $index => $question) {
    $question_number = $index + 1;
    $options = json_decode('[' . $question['options'] . ']', true);
    
    $page_content .= <<<HTML
            <div class="bg-white rounded-lg shadow-md p-6 mb-4">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold">Question {$question_number}</h3>
                    <p class="text-gray-700 mt-2">{$question['question_text']}</p>
                </div>
                <div class="space-y-2">
HTML;

    foreach ($options as $option) {
        // Add proper type casting and validation
        $option_id = isset($option['option_id']) ? (string)$option['option_id'] : '';
        $option_text = isset($option['option_text']) ? htmlspecialchars($option['option_text']) : 'Invalid option';
        
        $page_content .= <<<HTML
                    <label class="flex items-center space-x-3">
                        <input type="radio" name="answers[{$question['question_id']}]" 
                               value="{$option_id}" class="form-radio" required>
                        <span class="text-gray-700">{$option_text}</span>
                    </label>
HTML;
    }

    $page_content .= <<<HTML
                </div>
            </div>
HTML;
}

$page_content .= <<<HTML
            <div class="flex justify-end mt-6">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Submit Exam
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Enter fullscreen mode
function enterFullscreen() {
    const elem = document.documentElement;
    if (elem.requestFullscreen) {
        elem.requestFullscreen();
    } else if (elem.mozRequestFullScreen) { // Firefox
        elem.mozRequestFullScreen();
    } else if (elem.webkitRequestFullscreen) { // Chrome, Safari and Opera
        elem.webkitRequestFullscreen();
    } else if (elem.msRequestFullscreen) { // IE/Edge
        elem.msRequestFullscreen();
    }
}

// Exit fullscreen mode
function exitFullscreen() {
    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.mozCancelFullScreen) { // Firefox
        document.mozCancelFullScreen();
    } else if (document.webkitExitFullscreen) { // Chrome, Safari and Opera
        document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) { // IE/Edge
        document.msExitFullscreen();
    }
}

// Enter fullscreen when page loads
document.addEventListener('DOMContentLoaded', function() {
    enterFullscreen();
});

// Handle fullscreen change events
document.addEventListener('fullscreenchange', () => {
    if (!document.fullscreenElement) {
        enterFullscreen();
    }
});

// Prevent exiting fullscreen
document.addEventListener('fullscreenerror', () => {
    enterFullscreen();
});

const examDuration = {$exam_duration};
let timeLeft = examDuration;
let timerInterval;

function updateTimer() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    const timerElement = document.getElementById('timer');
    
    timerElement.innerHTML = 
        String(minutes).padStart(2, '0') + ':' + 
        String(seconds).padStart(2, '0') + 
        ' (Min remaining)';
    
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        const form = document.getElementById('examForm');
        if (form) {
            form.submit();
        } else {
            console.error('Form not found!');
        }
        return;
    }
    
    timeLeft--;
}

updateTimer();
timerInterval = setInterval(updateTimer, 1000);

document.getElementById('examForm').addEventListener('submit', function() {
    clearInterval(timerInterval);
});

if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Updated mouse tracking functionality
let mouseOutTimer;
const MOUSE_OUT_THRESHOLD = 4000; // 4 seconds
let warningCount = 0;
const MAX_WARNINGS = 2;

function handleVisibilityChange() {
    if (document.hidden) {
        warningCount++;
        if (warningCount >= MAX_WARNINGS) {
            alert('Multiple attempts to leave the exam detected. Submitting exam...');
            document.getElementById('examForm').submit();
        } else {
            alert(`Warning ${warningCount}/${MAX_WARNINGS}: Please stay on the exam tab!`);
        }
    }
}

function handleMouseLeave(e) {
    const mouseY = e.clientY;
    const windowHeight = window.innerHeight;
    
    if (mouseY <= 0 || mouseY >= windowHeight) {
        mouseOutTimer = setTimeout(() => {
            warningCount++;
            if (warningCount >= MAX_WARNINGS) {
                alert('Multiple attempts to leave the exam detected. Submitting exam...');
                document.getElementById('examForm').submit();
            } else {
                alert(`Warning ${warningCount}/${MAX_WARNINGS}: Please keep your mouse within the exam window!`);
            }
        }, MOUSE_OUT_THRESHOLD);
    }
}

function handleMouseEnter() {
    clearTimeout(mouseOutTimer);
}

// Updated event listeners
document.addEventListener('visibilitychange', handleVisibilityChange);
document.addEventListener('mouseleave', handleMouseLeave);
document.addEventListener('mouseenter', handleMouseEnter);

// Prevent right-click
document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
    return false;
});

// Prevent keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (
        (e.ctrlKey && e.key === 'c') || // Ctrl+C
        (e.ctrlKey && e.key === 'v') || // Ctrl+V
        (e.altKey && e.key === 'Tab') || // Alt+Tab
        (e.key === 'F12') || // F12 key
        (e.ctrlKey && e.shiftKey && e.key === 'i') || // Ctrl+Shift+I
        (e.ctrlKey && e.key === 'u') // Ctrl+U
    ) {
        e.preventDefault();
        return false;
    }
});

// Add this after the existing mouse tracking code
let windowFocusTimer;
const FOCUS_OUT_THRESHOLD = 4000; // 4 seconds

function handleWindowBlur() {
    windowFocusTimer = setTimeout(() => {
        warningCount++;
        if (warningCount >= MAX_WARNINGS) {
            alert('Multiple attempts to use other applications detected. Submitting exam...');
            document.getElementById('examForm').submit();
        } else {
            alert(`Warning ${warningCount}/${MAX_WARNINGS}: Please stay in the exam window! Do not switch to other applications.`);
        }
    }, FOCUS_OUT_THRESHOLD);
}

function handleWindowFocus() {
    clearTimeout(windowFocusTimer);
}

// Add these event listeners
window.addEventListener('blur', handleWindowBlur);
window.addEventListener('focus', handleWindowFocus);

// Add this to prevent window switching using Alt+Tab
window.addEventListener('keyup', function(e) {
    if (e.key === 'Alt' || e.key === 'Tab') {
        e.preventDefault();
        warningCount++;
        if (warningCount >= MAX_WARNINGS) {
            alert('Multiple attempts to switch windows detected. Submitting exam...');
            document.getElementById('examForm').submit();
        } else {
            alert(`Warning ${warningCount}/${MAX_WARNINGS}: Please do not use Alt+Tab during the exam!`);
        }
    }
});
</script>
HTML;

require_once 'includes/student_layout.php';
?>
