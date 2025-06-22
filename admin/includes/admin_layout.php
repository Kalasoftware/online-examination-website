<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php require_once 'includes/dark_mode.php'; ?>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Mobile Menu Toggle -->
        <button id="menuToggle" class="md:hidden fixed top-4 left-4 z-50 bg-blue-600 dark:bg-red-600 text-white p-2 rounded-lg">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <div id="sidebar" class="bg-gradient-to-b from-blue-800 to-blue-900 dark:from-red-900 dark:to-red-950 text-white w-full md:w-64 py-4 flex-shrink-0 fixed md:static h-full transform -translate-x-full md:translate-x-0 transition-transform duration-200 ease-in-out z-40">
            <div class="px-6 py-4 flex items-center space-x-3 mt-8 md:mt-0">
                <i class="fas fa-user-shield text-2xl"></i>
                <h1 class="text-2xl font-semibold">Admin Panel</h1>
            </div>
            
            <nav class="mt-8 space-y-1 px-3">
                <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 transition <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'bg-blue-700' : ''; ?>">
                    <i class="fas fa-home w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 transition <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'bg-blue-700' : ''; ?>">
                    <i class="fas fa-users w-6"></i>
                    <span>Users</span>
                </a>
                <a href="manage_exams.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 transition <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_exams.php') ? 'bg-blue-700' : ''; ?>">
                    <i class="fas fa-file-alt w-6"></i>
                    <span>Exams</span>
                </a>
                <a href="questions.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 transition <?php echo (basename($_SERVER['PHP_SELF']) == 'questions.php') ? 'bg-blue-700' : ''; ?>">
                    <i class="fas fa-question-circle w-6"></i>
                    <span>Questions</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 transition <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'bg-blue-700' : ''; ?>">
                    <i class="fas fa-chart-bar w-6"></i>
                    <span>Reports</span>
                </a>
                <a href="monitor.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 transition <?php echo (basename($_SERVER['PHP_SELF']) == 'monitor.php') ? 'bg-blue-700' : ''; ?>">
                    <i class="fas fa-desktop w-6"></i>
                    <span>Monitor</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen">
            <!-- Top Navigation -->
            <div class="bg-white dark:bg-gray-800 shadow-sm border-b dark:border-gray-700 px-4 py-3 flex justify-between items-center sticky top-0 z-30">
                <div class="flex items-center space-x-3">
                    <span class="text-xl font-semibold text-gray-900 dark:text-white">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Add Dark Mode Toggle -->
                    <form method="POST" class="mr-4">
                        <button type="submit" name="toggle_dark_mode" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300">
                            <?php if (!$_SESSION['dark_mode']): ?>
                                <i class="fas fa-moon text-gray-700 dark:text-gray-400"></i>
                            <?php else: ?>
                                <i class="fas fa-sun text-yellow-400"></i>
                            <?php endif; ?>
                        </button>
                    </form>
                    <!-- Account Dropdown -->
                    <div class="relative" id="accountDropdown">
                        <button class="flex items-center space-x-1 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-red-400 focus:outline-none" id="accountBtn">
                            <i class="fas fa-user-circle text-xl"></i>
                            <span class="hidden md:inline">Account</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 w-48 mt-2 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-xl border dark:border-gray-700 hidden" id="dropdownMenu">
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                <i class="fas fa-user-edit mr-2"></i> Edit Profile
                            </a>
                            <a href="../logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="flex-1 p-6 overflow-auto dark:bg-gray-900">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                        <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                        <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($page_content)) echo $page_content; ?>
            </div>

            <!-- Footer -->
            <footer class="bg-white dark:bg-gray-800 border-t dark:border-gray-700 py-4 px-6">
                <div class="container mx-auto">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="text-gray-600 dark:text-gray-400 text-sm mb-2 md:mb-0">
                            &copy; <?php echo date('Y'); ?> Online Examination System. All rights reserved.
                        </div>
                        <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                            <a href="#" class="hover:text-blue-600 dark:hover:text-red-400">Privacy Policy</a>
                            <span class="text-gray-300 dark:text-gray-600">|</span>
                            <a href="#" class="hover:text-blue-600 dark:hover:text-red-400">Terms of Service</a>
                            <span class="text-gray-300 dark:text-gray-600">|</span>
                            <a href="#" class="hover:text-blue-600 dark:hover:text-red-400">Help Center</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
            });
        }

        // Account dropdown toggle
        const accountBtn = document.getElementById('accountBtn');
        const dropdownMenu = document.getElementById('dropdownMenu');

        if (accountBtn && dropdownMenu) {
            accountBtn.addEventListener('click', (e) => {
                e.preventDefault();
                dropdownMenu.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!accountBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>