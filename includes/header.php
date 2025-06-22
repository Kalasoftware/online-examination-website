<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /oesv2/login.php');
    exit();
}

function checkRole($allowedRoles) {
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header('Location: /oesv2/unauthorized.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Examination System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">OES</h1>
            <div class="flex items-center space-x-4">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="/oesv2/logout.php" class="hover:text-gray-200">Logout</a>
            </div>
        </div>
    </nav>