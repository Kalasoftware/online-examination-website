<?php
if (!isset($page_title)) {
    $page_title = 'Teacher Panel';
    // dark mode added 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - OES</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php require_once 'includes/dark_mode.php'; ?> 
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="dashboard.php" class="text-2xl font-bold text-blue-600">
                            <i class="fas fa-graduation-cap mr-2"></i>Teacher Portal
                        </a>
                    </div>
                    <div class="hidden sm:ml-8 sm:flex sm:space-x-6">
                        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                        <a href="manage_exams.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_exams.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            <i class="fas fa-file-alt mr-2"></i>Exams
                        </a>
                        <!-- Add Questions Bank Link -->
                        <a href="questions_bank.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'questions_bank.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            <i class="fas fa-book mr-2"></i>Questions Bank
                        </a>
                        <a href="manage_students.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_students.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            <i class="fas fa-users mr-2"></i>Students
                        </a>
                        <a href="monitor_exam.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'monitor_exam.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            <i class="fas fa-desktop mr-2"></i>Monitor
                        </a>
                        <a href="view_results.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'view_results.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            <i class="fas fa-chart-bar mr-2"></i>Results
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="hidden sm:flex sm:items-center">
                        <div class="flex items-center space-x-4">
                            <!-- Add Dark Mode Toggle Button -->
                            <form method="POST" class="mr-4">
                                <button type="submit" name="toggle_dark_mode" class="p-2 rounded-lg hover:bg-gray-100 transition-all duration-300">
                                    <?php if (!$_SESSION['dark_mode']): ?>
                                        <i class="fas fa-moon text-gray-600"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sun text-red-500"></i>
                                    <?php endif; ?>
                                </button>
                            </form>
                            <div class="flex items-center space-x-2">
                                <a href="profile.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600">
                                    <i class="fas fa-user-circle text-gray-600 text-xl"></i>
                                    <span class="font-medium"><?php echo $_SESSION['name'] ?? 'Teacher'; ?></span>
                                </a>
                            </div>
                            <a href="../logout.php" class="inline-flex items-center px-3 py-2 border border-red-500 text-red-500 hover:bg-red-500 hover:text-white rounded-md text-sm font-medium transition-colors duration-200">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Button - Show on small screens -->
    <div class="sm:hidden">
        <button type="button" class="mobile-menu-button p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu hidden sm:hidden bg-white border-b border-gray-200">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-500'; ?> block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-home mr-2"></i>Dashboard
            </a>
            <a href="manage_exams.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_exams.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-500'; ?> block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-file-alt mr-2"></i>Exams
            </a>
            <a href="manage_students.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_students.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-500'; ?> block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-users mr-2"></i>Students
            </a>
            <a href="monitor_exam.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'monitor_exam.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-500'; ?> block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-desktop mr-2"></i>Monitor
            </a>
            <a href="view_results.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'view_results.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-500'; ?> block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-chart-bar mr-2"></i>Results
            </a>
            <a href="questions_bank.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'questions_bank.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-500'; ?> block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-book mr-2"></i>Questions Bank
            </a>

            <!-- Add Profile and Logout for mobile -->
            <div class="border-t border-gray-200 mt-2 pt-2">
                <form method="POST" class="px-3 py-2">
                    <button type="submit" name="toggle_dark_mode" class="w-full text-left text-gray-500 rounded-md text-base font-medium">
                        <?php if (!$_SESSION['dark_mode']): ?>
                            <i class="fas fa-moon mr-2"></i>Dark Mode
                        <?php else: ?>
                            <i class="fas fa-sun mr-2 text-red-500"></i>Light Mode
                        <?php endif; ?>
                    </button>
                </form>
                <a href="profile.php" class="block px-3 py-2 text-gray-500 rounded-md text-base font-medium">
                    <i class="fas fa-user-circle mr-2"></i><?php echo $_SESSION['name'] ?? 'Teacher'; ?>
                </a>
                <a href="../logout.php" class="block px-3 py-2 text-red-500 rounded-md text-base font-medium">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo $_SESSION['success']; ?></p>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo $_SESSION['error']; ?></p>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <main class="flex-grow">
        <?php echo $page_content; ?>
    </main>

    <footer class="bg-white shadow-md mt-8">
        <div class="max-w-7xl mx-auto py-4 px-4">
            <p class="text-center text-gray-600 text-sm">
                Online Examination System © <?php echo date('Y'); ?>
            </p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-button').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>