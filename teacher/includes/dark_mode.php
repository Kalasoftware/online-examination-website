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
.dark body { background-color: #1a1a1a; color: #ffffff; }
.dark .bg-white { background-color: #1f1f1f !important; }

/* Text Colors */
.dark .text-gray-500, 
.dark .text-gray-600, 
.dark .text-gray-700 { color: #e0e0e0 !important; }

/* Tables */
.dark table { background-color: #1f1f1f; }
.dark th { background-color: #2d2d2d; color: #ffffff; }
.dark td { border-color: #333333; }
.dark tr:hover { background-color: #2a2a2a; }
.dark .border { border-color: #333333 !important; }

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
    background-color: #2d2d2d !important;
    border-color: #404040 !important;
    color: #ffffff !important;
}

/* Calendar Widget Styles */
.dark input[type="date"]::-webkit-calendar-picker-indicator,
.dark input[type="datetime-local"]::-webkit-calendar-picker-indicator,
.dark input[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.7;
}

.dark input[type="date"]::-webkit-calendar-picker-indicator:hover,
.dark input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover,
.dark input[type="time"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}

/* Calendar Dropdown Styles */
.dark ::-webkit-calendar-picker {
    background-color: #2d2d2d;
    color: #ffffff;
}

.dark ::-webkit-datetime-edit {
    color: #ffffff;
}

.dark ::-webkit-datetime-edit-fields-wrapper {
    color: #ffffff;
}

.dark ::-webkit-datetime-edit-text {
    color: #ff4444;
    padding: 0 0.3em;
}

.dark ::-webkit-datetime-edit-month-field,
.dark ::-webkit-datetime-edit-day-field,
.dark ::-webkit-datetime-edit-year-field,
.dark ::-webkit-datetime-edit-hour-field,
.dark ::-webkit-datetime-edit-minute-field,
.dark ::-webkit-datetime-edit-second-field,
.dark ::-webkit-datetime-edit-millisecond-field,
.dark ::-webkit-datetime-edit-meridiem-field {
    color: #ffffff;
}

.dark select option {
    background-color: #2d2d2d;
    color: #ffffff;
}

/* Cards and Containers */
.dark .shadow-md { box-shadow: 0 4px 6px -1px rgba(255, 0, 0, 0.1) !important; }
.dark .border-gray-200 { border-color: #333333 !important; }
.dark .bg-gray-50 { background-color: #2d2d2d !important; }
.dark .bg-gray-100 { background-color: #333333 !important; }

/* Buttons and Interactive Elements */
.dark .hover\:bg-gray-100:hover { background-color: #333333 !important; }
.dark .hover\:bg-red-500:hover { background-color: #ff4444 !important; }
.dark .text-blue-600 { color: #ff4444 !important; }
.dark .border-blue-500 { border-color: #ff4444 !important; }
.dark .hover\:text-blue-600:hover { color: #ff6666 !important; }

/* Alerts and Messages */
.dark .bg-green-50 { background-color: rgba(0, 255, 0, 0.05) !important; }
.dark .bg-red-50 { background-color: rgba(255, 0, 0, 0.05) !important; }
.dark .text-green-700 { color: #4ade80 !important; }
.dark .text-red-700 { color: #ff6b6b !important; }

/* Modals and Popups */
.dark .modal,
.dark .dropdown-menu {
    background-color: #2d2d2d;
    border-color: #404040;
}

/* Pagination */
.dark .pagination {
    background-color: #2d2d2d;
    border-color: #404040;
}

.dark .pagination a {
    color: #ffffff;
}

.dark .pagination .active {
    background-color: #ff4444;
    color: #ffffff;
}

/* Custom Scrollbar for Dark Mode */
.dark ::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.dark ::-webkit-scrollbar-track {
    background: #1f1f1f;
}

.dark ::-webkit-scrollbar-thumb {
    background: #404040;
    border-radius: 5px;
}

.dark ::-webkit-scrollbar-thumb:hover {
    background: #4a4a4a;
}
</style>