<?php require_once 'includes/darkmode.php'; ?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#6B7280'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="<?php echo $darkModeColors['background']['primary']; ?> transition-colors duration-200">
    <?php if (empty($hide_navbar)): ?>
   <nav class="<?php echo $darkModeColors['background']['secondary']; ?> shadow-lg w-full relative z-10">

        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="dashboard.php" class="text-2xl font-bold text-primary dark:text-white">
                            <i class="fas fa-graduation-cap mr-2"></i>Student Portal
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="dashboard.php" 
                           class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'border-primary text-primary dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400'; ?> 
                                  hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                        <a href="exams.php" 
                           class="<?php echo basename($_SERVER['PHP_SELF']) === 'exams.php' ? 'border-primary text-primary dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400'; ?> 
                                  hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            <i class="fas fa-edit mr-2"></i>Available Exams
                        </a>
                        <a href="results.php" 
                           class="<?php echo basename($_SERVER['PHP_SELF']) === 'results.php' ? 'border-primary text-primary dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400'; ?> 
                                  hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                            <i class="fas fa-chart-bar mr-2"></i>My Results
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="theme-toggle" class="<?php echo $darkModeColors['text']['muted']; ?> hover:text-primary">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                    <div class="relative">
                        <button id="profile-menu" class="flex items-center space-x-2 <?php echo $darkModeColors['text']['primary']; ?>">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name']); ?>&background=random" 
                                 alt="Profile" 
                                 class="h-8 w-8 rounded-full">
                            <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5">
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-user mr-2"></i>My Profile
                            </a>
                            <a href="../logout.php" class="block px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>
   <main class="py-10 px-4 max-w-7xl mx-auto">
        <?php echo $page_content; ?>
    </main>

    <footer class="bg-white dark:bg-gray-800 shadow-lg mt-8">
    <div class="max-w-7xl mx-auto py-4 px-4 text-center text-gray-600 dark:text-gray-400">
        <p>
            &copy; <?php echo date('Y'); ?> Online Examination System. All rights reserved.
            <br>
            Contact: <a href="mailto:admin@me.priyatal.buzz" class="text-blue-500 hover:underline">admin@me.priyatal.buzz</a> for help.
        </p>
    </div>
</footer>

    <script>
        // Profile dropdown toggle
        const profileMenu = document.getElementById('profile-menu');
        const profileDropdown = document.getElementById('profile-dropdown');

        profileMenu.addEventListener('click', () => {
            profileDropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!profileMenu.contains(e.target)) {
                profileDropdown.classList.add('hidden');
            }
        });

        // Theme toggle functionality only
        const html = document.documentElement;
        const themeToggle = document.getElementById('theme-toggle');
        
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }

        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            localStorage.theme = html.classList.contains('dark') ? 'dark' : 'light';
        });
    </script>
</body>
</html>