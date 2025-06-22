<?php
session_start();
require_once 'config/database.php';

$ADMIN_SECRET = "admin123"; // In production, this should be stored securely

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    $admin_password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
    $selected_role = $_POST['role'];
    
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        if ($user['role'] !== $selected_role) {
            $error = "Invalid role selected for this account";
        } elseif ($user['role'] === 'ADMIN' && $admin_password !== $ADMIN_SECRET) {
            $error = "Invalid admin password";
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            
            switch($user['role']) {
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
    } else {
        $error = "Invalid credentials";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Examination System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100" style="background: url('assets/background.png') no-repeat center center fixed; background-size: cover; position: relative;">
    <div style="position: fixed; inset: 0; background: rgba(255,255,255,0.4); z-index: 0;"></div>
    <div class="min-h-screen flex items-center" style="justify-content: flex-end; position: relative; z-index: 1;">
        <div class="bg-white p-8 rounded-lg shadow-md w-96" style="margin-right: 120px;">
            <h1 class="text-2xl font-bold mb-6 text-center">Login</h1>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                        Login As
                    </label>
                    <select class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="role" name="role" required>
                        <option value="STUDENT" selected>Student</option>
                        <option value="TEACHER">Teacher</option>
                        <option value="ADMIN">Admin</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="email" type="email" name="email" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                           id="password" type="password" name="password" required>
                </div>

                <div id="adminPasswordField" class="mb-4 hidden">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="admin_password">
                        Admin Secret Key
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="admin_password" type="password" name="admin_password">
                </div>

                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                        type="submit">
                    Sign In
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="text-gray-600">New user? 
                    <a href="register.php" class="text-blue-500 hover:text-blue-700 font-semibold">Register now</a>
                </p>
                <div id="teacherResetSection" class="hidden mt-3 pt-3 border-t border-gray-200">
                    <a href="reset_password.php" class="text-blue-500 hover:text-blue-700">
                        <i class="fas fa-key"></i> Reset Teacher Password
                    </a>
                </div>
                <div id="studentResetSection" class="mt-3 pt-3 border-t border-gray-200">
                    <a href="student_reset.php" class="text-blue-500 hover:text-blue-700">
                        <i class="fas fa-key"></i> Reset Student Password
                    </a>
                </div>
            </div>

            <script>
                const roleSelect = document.getElementById('role');
                const adminPasswordField = document.getElementById('adminPasswordField');
                const teacherResetSection = document.getElementById('teacherResetSection');
                const studentResetSection = document.getElementById('studentResetSection');

                function updateRoleSections() {
                    const role = roleSelect.value;

                    if (role === 'ADMIN') {
                        adminPasswordField.classList.remove('hidden');
                        teacherResetSection.classList.add('hidden');
                        studentResetSection.classList.add('hidden');
                    } else if (role === 'TEACHER') {
                        adminPasswordField.classList.add('hidden');
                        teacherResetSection.classList.remove('hidden');
                        studentResetSection.classList.add('hidden');
                    } else {
                        adminPasswordField.classList.add('hidden');
                        teacherResetSection.classList.add('hidden');
                        studentResetSection.classList.remove('hidden');
                    }
                }

                // Run on page load
                window.addEventListener('DOMContentLoaded', updateRoleSections);

                // Run on role change
                roleSelect.addEventListener('change', updateRoleSections);
            </script>
        </div>
    </div>
</body>
</html>
