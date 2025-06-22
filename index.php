<?php
session_start();
if (isset($_SESSION['user_id'])) {
    switch($_SESSION['role']) {
        case 'ADMIN':
            header('Location: admin/dashboard.php');
            break;
        case 'TEACHER':
            header('Location: teacher/dashboard.php');
            break;
        case 'STUDENT':
            header('Location: student/dashboard.php');
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Online Examination System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body class="bg-gradient-to-br from-blue-50 to-white">
    <div class="min-h-screen">
        <nav class="bg-blue-600 text-white p-4 fixed w-full z-50 animate__animated animate__fadeInDown">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-2xl font-bold">Online Examination System</h1>
                <div class="space-x-4">
                    <a href="login.php" class="hover:text-gray-200 transition-colors duration-300">Login</a>
                    <a href="register.php" class="bg-white text-blue-600 px-4 py-2 rounded hover:bg-gray-100 transition-all duration-300 transform hover:scale-105">Register</a>
                </div>
            </div>
        </nav>

        <div class="container mx-auto p-8 pt-24">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-5xl font-bold mb-6 text-blue-800">Welcome to Our Online Examination Platform</h2>
                <p class="text-2xl text-gray-600">A secure and efficient way to conduct online examinations</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <div class="bg-white p-8 rounded-lg shadow-lg transform transition-all duration-300 hover:scale-105" 
                     data-aos="fade-right" data-aos-delay="100">
                    <div class="text-blue-500 mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-blue-800">For Students</h3>
                    <p class="text-gray-600">Take exams online from anywhere</p>
                    <ul class="mt-4 space-y-2 text-gray-600">
                        <li>✓ Easy exam access</li>
                        <li>✓ Real-time results</li>
                        <li>✓ Progress tracking</li>
                    </ul>
                </div>

                <div class="bg-white p-8 rounded-lg shadow-lg transform transition-all duration-300 hover:scale-105" 
                     data-aos="fade-up" data-aos-delay="200">
                    <div class="text-blue-500 mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2 2 0 00-2-2h-2"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-blue-800">For Teachers</h3>
                    <p class="text-gray-600">Create and manage exams efficiently</p>
                    <ul class="mt-4 space-y-2 text-gray-600">
                        <li>✓ Easy exam creation</li>
                        <li>✓ Real-time monitoring</li>
                        <li>✓ Automated grading</li>
                    </ul>
                </div>

                <div class="bg-white p-8 rounded-lg shadow-lg transform transition-all duration-300 hover:scale-105" 
                     data-aos="fade-left" data-aos-delay="300">
                    <div class="text-blue-500 mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-blue-800">For Administrators</h3>
                    <p class="text-gray-600">Complete system oversight</p>
                    <ul class="mt-4 space-y-2 text-gray-600">
                        <li>✓ User management</li>
                        <li>✓ System monitoring</li>
                        <li>✓ Detailed analytics</li>
                    </ul>
                </div>
            </div>
        </div>

        <footer class="bg-blue-600 text-white py-6 mt-16" data-aos="fade-up">
            <div class="container mx-auto text-center">
                <p class="text-lg">Made by Sem 6th Student</p>
                <p class="text-sm mt-2">JP Dawer Department of ICT</p>
                <p class="text-xs mt-4 text-blue-200">© 2024 Online Examination System</p>
            </div>
        </footer>
    </div>

    <script>
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
    </script>
</body>
</html>