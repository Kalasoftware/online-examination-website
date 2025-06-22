<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    exit('Unauthorized');
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get search parameters
$search = isset($_POST['search']) ? $_POST['search'] : '';
$role_filter = isset($_POST['role']) ? $_POST['role'] : '';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
}
if ($role_filter) {
    $query .= " AND role = ?";
}
$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);

// Bind parameters
$params = [];
if ($search) {
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}
if ($role_filter) {
    $params[] = $role_filter;
}

$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate HTML for table rows
foreach ($users as $user) {
    $created_date = date('M d, Y', strtotime($user['created_at']));
    $roleClass = $user['role'] === 'ADMIN' ? 'bg-red-100 text-red-800' : 
                ($user['role'] === 'TEACHER' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800');
    
    echo <<<HTML
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">{$user['name']}</td>
            <td class="px-6 py-4 whitespace-nowrap">{$user['email']}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {$roleClass}">
                    {$user['role']}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">{$created_date}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick='editUser({$user["user_id"]}, 
                    "{$user["name"]}", 
                    "{$user["email"]}", 
                    "{$user["role"]}")' 
                    class="text-blue-600 hover:text-blue-900 mr-3">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteUser({$user['user_id']})" 
                    class="text-red-600 hover:text-red-900">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
HTML;
}
?>