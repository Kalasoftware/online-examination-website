<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_name'])) {
        $new_name = trim($_POST['name']);
        if (!empty($new_name)) {
            $stmt = $db->prepare("UPDATE users SET name = ? WHERE user_id = ?");
            if ($stmt->execute([$new_name, $_SESSION['user_id']])) {
                $_SESSION['name'] = $new_name;
                $_SESSION['message'] = "Name updated successfully!";
            }
        }
    }
    
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password === $confirm_password) {
            $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_data = $stmt->fetch();
            
            if (password_verify($current_password, $user_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    $_SESSION['message'] = "Password updated successfully!";
                }
            } else {
                $_SESSION['error'] = "Current password is incorrect!";
            }
        } else {
            $_SESSION['error'] = "New passwords do not match!";
        }
    }
    
    header('Location: profile.php');
    exit();
}

// Get student details
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = "My Profile";
$message_display = '';
if (isset($_SESSION['message'])) {
    $message_display .= "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4'>" . htmlspecialchars($_SESSION['message']) . "</div>";
}
if (isset($_SESSION['error'])) {
    $message_display .= "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'>" . htmlspecialchars($_SESSION['error']) . "</div>";
}

$page_content = <<<HTML
<div class="max-w-4xl mx-auto">
    {$message_display}

    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden transition-all duration-300 hover:shadow-xl">
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-6">
            <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6">
                <img src="https://ui-avatars.com/api/?name={$user['name']}&size=128&background=random&bold=true&color=ffffff" 
                     alt="Profile" 
                     class="h-32 w-32 rounded-full border-4 border-white shadow-lg transform hover:scale-105 transition-transform duration-300">
                <div class="text-center md:text-left">
                    <h1 class="text-3xl font-bold text-white">{$user['name']}</h1>
                    <p class="text-blue-100"><i class="fas fa-envelope mr-2"></i>{$user['email']}</p>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-8">
            <!-- Update Name Form -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6 transition-all duration-300 hover:shadow-md">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-user-edit mr-2 text-primary"></i>Update Name
                </h2>
                <form method="POST" class="space-y-4">
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Name</label>
                        <input type="text" name="name" value="{$user['name']}" required
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors duration-200">
                    </div>
                    <button type="submit" name="update_name" 
                            class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50 transition-all duration-200 flex items-center">
                        <i class="fas fa-save mr-2"></i>Update Name
                    </button>
                </form>
            </div>

            <!-- Update Password Form -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6 transition-all duration-300 hover:shadow-md">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-lock mr-2 text-primary"></i>Change Password
                </h2>
                <form method="POST" class="space-y-4">
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current Password</label>
                        <div class="relative">
                            <input type="password" name="current_password" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors duration-200 pr-10">
                            <i class="fas fa-eye absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer toggle-password"></i>
                        </div>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Password</label>
                        <div class="relative">
                            <input type="password" name="new_password" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors duration-200 pr-10">
                            <i class="fas fa-eye absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer toggle-password"></i>
                        </div>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm New Password</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors duration-200 pr-10">
                            <i class="fas fa-eye absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer toggle-password"></i>
                        </div>
                    </div>
                    <button type="submit" name="update_password"
                            class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50 transition-all duration-200 flex items-center">
                        <i class="fas fa-key mr-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        if (input.type === 'password') {
            input.type = 'text';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        }
    });
});
</script>
HTML;

// Clear messages after displaying
unset($_SESSION['message'], $_SESSION['error']);

require_once 'includes/student_layout.php';
?>