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
body { background-color: #f8fafc; }
.bg-white { background-color: #ffffff !important; }
#sidebar { background: linear-gradient(to bottom, #4a0404, #2d0404) !important; }

/* Dark Theme Base */
.dark body { background-color: #1a0000; color: #ffffff; }
.dark .bg-white { background-color: #2d0404 !important; }
.dark .bg-gray-50, .dark .bg-gray-900 { background-color: #1a0000 !important; }
.dark .bg-gray-100 { background-color: #2d0404 !important; }

/* Dark Theme Text Colors */
.dark .text-gray-500, 
.dark .text-gray-600, 
.dark .text-gray-700,
.dark .text-gray-800 { color: #ffcccc !important; }

/* Add these new styles for the specific class */
.dark .px-2.py-1.rounded.text-sm.bg-gray-100 {
    background-color: #4a0404 !important;
    color: #ffffff !important;
}

/* Dark Theme Tables */
.dark table { background-color: #2d0404; }
.dark th { background-color: #4a0404; color: #ffffff; }
.dark td { border-color: #600505; }
.dark tr:hover { background-color: #3d0404; }
.dark .border { border-color: #600505 !important; }

/* Dark Theme Form Elements */
.dark select,
.dark input[type="text"],
.dark input[type="number"],
.dark input[type="email"],
.dark input[type="password"],
.dark input[type="date"],
.dark input[type="datetime-local"],
.dark input[type="time"],
.dark textarea {
    background-color: #4a0404 !important;
    border-color: #600505 !important;
    color: #ffffff !important;
}

/* Dark Theme Navigation */
.dark .hover\:bg-blue-700:hover { background-color: #6b0707 !important; }
.dark .bg-blue-700 { background-color: #4a0404 !important; }

/* Dark Theme Cards and Containers */
.dark .shadow-md { box-shadow: 0 4px 6px -1px rgba(255, 0, 0, 0.2) !important; }
.dark .border-gray-200 { border-color: #600505 !important; }

/* Dark Theme Interactive Elements */
.dark .hover\:bg-gray-100:hover { background-color: #4a0404 !important; }
.dark .hover\:bg-red-500:hover { background-color: #8b0000 !important; }
.dark .text-blue-600 { color: #ff4444 !important; }
.dark .hover\:text-blue-600:hover { color: #ff6666 !important; }

/* Dark Theme Scrollbar */
.dark ::-webkit-scrollbar-track { background: #2d0404; }
.dark ::-webkit-scrollbar-thumb { background: #4a0404; }
.dark ::-webkit-scrollbar-thumb:hover { background: #6b0707; }

/* Dark Theme Alerts */
.dark .bg-green-50 { background-color: rgba(0, 100, 0, 0.1) !important; }
.dark .bg-red-50 { background-color: rgba(139, 0, 0, 0.1) !important; }
.dark .text-green-700 { color: #4ade80 !important; }
.dark .text-red-700 { color: #ff6b6b !important; }

/* Transitions */
* {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}
/* Base Theme */
.flex-1.p-6.overflow-auto { background-color: #ffffff !important; }
.dark .flex-1.p-6.overflow-auto { background-color: #1a0000 !important; }

/* Dark Theme Base */
.dark body { background-color: #1a0000; color: #ffffff; }
.dark .bg-white { background-color: #2d0404 !important; }
.dark .bg-gray-50, .dark .bg-gray-900 { background-color: #1a0000 !important; }
.dark .bg-gray-100 { background-color: #2d0404 !important; }
</style>