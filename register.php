<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'STUDENT'; // Allow STUDENT or TEACHER
    
    $errors = [];

    // Name validation
    if (!preg_match('/^[a-zA-Z\s\'-]{2,50}$/', $name)) {
        $errors[] = "Name must be 2-50 characters long and contain only letters, spaces, hyphens, and apostrophes";
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Password strength validation
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $errors[] = "Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Email already exists";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);

        if ($stmt->execute([$name, $email, $hashed_password, $role])) {
            $_SESSION['register_success'] = true;
            header('Location: login.php');
            exit();
        } else {
            $errors[] = "Registration failed";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Examination System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-strength { margin-top: 5px; font-size: 0.875rem; }
        .weak { color: #dc2626; }
        .medium { color: #d97706; }
        .strong { color: #059669; }
    </style>
</head>
<body style="background: url('assets/background.png') no-repeat center center fixed; background-size: cover; position: relative;">
    <div style="position: fixed; inset: 0; background: rgba(255,255,255,0.4); z-index: 0;"></div>
    <div class="min-h-screen flex items-center" style="justify-content: flex-end; position: relative; z-index: 1;">
        <div class="bg-white p-8 rounded-lg shadow-md w-96" style="margin-right: 120px;">
            <h1 class="text-2xl font-bold mb-6 text-center">Register</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Role selection -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                        Role
                    </label>
                    <select name="role" id="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="STUDENT">Student</option>
                        <option value="TEACHER">Teacher</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        Full Name
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="name" type="text" name="name" required>
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
                    <div class="relative">
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline pr-10"
                               id="password" type="password" name="password" required>
                        <span class="absolute right-2 top-2 cursor-pointer">
                            <i class="fas fa-eye-slash toggle-password" data-target="password"></i>
                        </span>
                    </div>
                    <div id="password-strength" class="password-strength"></div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline pr-10"
                               id="confirm_password" type="password" name="confirm_password" required>
                        <span class="absolute right-2 top-2 cursor-pointer">
                            <i class="fas fa-eye-slash toggle-password" data-target="confirm_password"></i>
                        </span>
                    </div>
                    <div id="password-match" class="password-strength"></div>
                </div>

                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                        type="submit">
                    Register
                </button>

                <p class="text-center mt-4">
                    Already have an account? <a href="login.php" class="text-blue-500 hover:text-blue-700">Login here</a>
                </p>
            </form>

            <script>
                function checkPasswordStrength(password) {
                    let strength = 0;
                    if (password.length >= 8) strength++;
                    if (password.match(/[a-z]+/)) strength++;
                    if (password.match(/[A-Z]+/)) strength++;
                    if (password.match(/[0-9]+/)) strength++;
                    if (password.match(/[@$!%*?&]+/)) strength++;
                    return strength;
                }

                const passwordInput = document.getElementById('password');
                const confirmPasswordInput = document.getElementById('confirm_password');
                const strengthDiv = document.getElementById('password-strength');
                const matchDiv = document.getElementById('password-match');
                const submitButton = document.querySelector('button[type="submit"]');

                passwordInput.addEventListener('input', function() {
                    const strength = checkPasswordStrength(this.value);
                    let message = '';
                    let className = '';

                    if (strength < 3) {
                        message = 'Weak password';
                        className = 'weak';
                        submitButton.disabled = true;
                    } else if (strength < 5) {
                        message = 'Medium strength password';
                        className = 'medium';
                        submitButton.disabled = true;
                    } else {
                        message = 'Strong password';
                        className = 'strong';
                        submitButton.disabled = false;
                    }

                    strengthDiv.textContent = message;
                    strengthDiv.className = 'password-strength ' + className;

                    if (confirmPasswordInput.value) {
                        checkPasswordMatch();
                    }
                });

                function checkPasswordMatch() {
                    if (passwordInput.value === confirmPasswordInput.value) {
                        matchDiv.textContent = 'Passwords match';
                        matchDiv.className = 'password-strength strong';
                        submitButton.disabled = false;
                    } else {
                        matchDiv.textContent = 'Passwords do not match';
                        matchDiv.className = 'password-strength weak';
                        submitButton.disabled = true;
                    }
                }

                confirmPasswordInput.addEventListener('input', checkPasswordMatch);

                document.querySelectorAll('.toggle-password').forEach(icon => {
                    icon.addEventListener('click', function () {
                        const targetId = this.getAttribute('data-target');
                        const input = document.getElementById(targetId);

                        if (input.type === 'password') {
                            input.type = 'text';
                            this.classList.remove('fa-eye-slash');
                            this.classList.add('fa-eye');
                        } else {
                            input.type = 'password';
                            this.classList.remove('fa-eye');
                            this.classList.add('fa-eye-slash');
                        }
                    });
                });
            </script>
        </div>
    </div>
</body>
</html>
