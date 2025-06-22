<?php
if (!isset($_SESSION['dark_mode'])) {
    $_SESSION['dark_mode'] = false;
}

if (isset($_POST['toggle_dark_mode'])) {
    $_SESSION['dark_mode'] = !$_SESSION['dark_mode'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}
?>

<script>
document.documentElement.classList.toggle('dark', <?php echo $_SESSION['dark_mode'] ? 'true' : 'false'; ?>);
</script>

<style>
/* Base Theme */
.dark body { background-color: #1c1c1c; color: #ffffff; }
.dark .bg-white { background-color: #262626 !important; }

/* Text Colors */
.dark .text-gray-500, 
.dark .text-gray-600, 
.dark .text-gray-700 { color: #ffd700 !important; }

/* Tables */
.dark table { background-color: #262626; }
.dark th { background-color: #333333; color: #ffd700; }
.dark td { border-color: #404040; }
.dark tr:hover { background-color: #333333; }
.dark .border { border-color: #ffd700 !important; }

/* Form Elements */
.dark select,
.dark input[type="text"],
.dark input[type="number"],
.dark input[type="email"],
.dark input[type="password"],
.dark input[type="date"],
.dark input[type="datetime-local"],
.dark input[type="time"],
.dark textarea {
    background-color: #333333 !important;
    border-color: #ffd700 !important;
    color: #ffffff !important;
}

/* Calendar Widget Styles */
.dark input[type="date"]::-webkit-calendar-picker-indicator,
.dark input[type="datetime-local"]::-webkit-calendar-picker-indicator,
.dark input[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(1) sepia(1) saturate(5) hue-rotate(0deg);
    opacity: 0.8;
}

/* Cards and Containers */
.dark .shadow-md { 
    box-shadow: 0 4px 6px -1px rgba(255, 215, 0, 0.1) !important; 
}
.dark .border-gray-200 { border-color: #ffd700 !important; }
.dark .bg-gray-50 { background-color: #262626 !important; }
.dark .bg-gray-100 { background-color: #333333 !important; }

/* Buttons and Interactive Elements */
.dark .hover\:bg-gray-100:hover { background-color: #333333 !important; }
.dark .hover\:bg-yellow-500:hover { background-color: #ffd700 !important; }
.dark .text-blue-600 { color: #ffd700 !important; }
.dark .border-blue-500 { border-color: #ffd700 !important; }
.dark .hover\:text-blue-600:hover { color: #fff3b0 !important; }

/* Navigation */
.dark .nav-link { color: #ffd700 !important; }
.dark .nav-link:hover { color: #fff3b0 !important; }
.dark .active-nav { border-color: #ffd700 !important; }

/* Alerts and Messages */
.dark .bg-green-50 { background-color: rgba(255, 215, 0, 0.1) !important; }
.dark .bg-red-50 { background-color: rgba(255, 215, 0, 0.05) !important; }
.dark .text-green-700 { color: #ffd700 !important; }
.dark .text-red-700 { color: #ffd700 !important; }

/* Custom Scrollbar */
.dark ::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.dark ::-webkit-scrollbar-track {
    background: #262626;
}

.dark ::-webkit-scrollbar-thumb {
    background: #ffd700;
    border-radius: 5px;
}

.dark ::-webkit-scrollbar-thumb:hover {
    background: #fff3b0;
}

/* Stats Cards */
.dark .stats-card {
    background-color: #262626 !important;
    border: 1px solid #ffd700 !important;
}

.dark .stats-number {
    color: #ffd700 !important;
}

/* Exam Interface */
.dark .question-card {
    background-color: #262626 !important;
    border-left: 4px solid #ffd700 !important;
}

.dark .option-card {
    border: 1px solid #404040 !important;
}

.dark .option-card:hover {
    border-color: #ffd700 !important;
    background-color: rgba(255, 215, 0, 0.1) !important;
}

.dark .selected-option {
    background-color: rgba(255, 215, 0, 0.2) !important;
    border-color: #ffd700 !important;
}

/* Timer */
.dark .exam-timer {
    color: #ffd700 !important;
    border: 2px solid #ffd700 !important;
}

/* Results Page */
.dark .result-card {
    background-color: #262626 !important;
    border: 1px solid #ffd700 !important;
}

.dark .score-display {
    color: #ffd700 !important;
}
</style>