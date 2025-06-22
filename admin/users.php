<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Handle Add/Edit User Form Submission
if (isset($_POST['save_user'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $admin_password = filter_input(INPUT_POST, 'admin_password', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    $error = false;

    if (!$error) {
        try {
            if ($user_id) {
                // Update existing user
                $query = "UPDATE users SET name = ?, email = ?, role = ?";
                $params = [$name, $email, $role];

                // Add password update if provided
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query .= ", password = ?";
                    $params[] = $hashed_password;
                }

                $query .= " WHERE user_id = ?";
                $params[] = $user_id;
            } else {
                // Add new user
                if (empty($password)) {
                    $message = "Password is required for new users";
                    $message_type = "error";
                    $error = true;
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
                    $params = [$name, $email, $hashed_password, $role];
                }
            }

            if (!$error) {
                $stmt = $db->prepare($query);
                if ($stmt->execute($params)) {
                    $message = $user_id ? "User updated successfully" : "User added successfully";
                    $message_type = "success";
                    header('Location: users.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                    exit();
                }
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Handle Delete User
if (isset($_POST['delete_user'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    if ($user_id) {
        $query = "DELETE FROM users WHERE user_id = ?";
        try {
            $stmt = $db->prepare($query);
            if ($stmt->execute([$user_id])) {
                $message = "User deleted successfully";
                $message_type = "success";
                header('Location: users.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                exit();
            }
        } catch (PDOException $e) {
            $message = "Error deleting user: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Get message from URL if redirected
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $message_type = $_GET['type'];
}

// Initialize variables
$message = isset($message) ? $message : '';
$message_type = isset($message_type) ? $message_type : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

// After database connection, update the variable initializations
$database = new Database();
$db = $database->getConnection();

// Initialize all variables
$message = '';
$message_type = '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$role = $role_filter; // Add this line to fix the undefined variable

// Remove the duplicate initialization that appears later in the code
$query = "SELECT * FROM users WHERE 1=1";
if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
}
if ($role_filter) {
    $query .= " AND role = ?";
}
$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);

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

$page_content = <<<HTML
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Manage Users</h1>
        <button onclick="openAddUserModal()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="mb-4 flex space-x-4">
            <input type="text" id="searchInput" placeholder="Search users..." 
                   class="border rounded px-3 py-2 w-64" value="{$search}">
            <select id="roleFilter" class="border rounded px-3 py-2">
                <option value="">All Roles</option>
                <option value="ADMIN" <?= $role_filter == 'ADMIN' ? 'selected' : '' ?>Admin</option>
                <option value="TEACHER" <?= $role_filter == 'TEACHER' ? 'selected' : '' ?>Teacher</option>
                <option value="STUDENT" <?= $role_filter == 'STUDENT' ? 'selected' : '' ?>Student</option>
            </select>
            <button onclick="applyFilters()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Filter
            </button>
        </div>
HTML;

if ($message) {
    $messageClass = $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
    $page_content .= <<<HTML
        <div class="mb-4 p-4 rounded {$messageClass}">
            {$message}
        </div>
HTML;
}

$page_content .= <<<HTML
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
HTML;

foreach ($users as $user) {
    $created_date = date('M d, Y', strtotime($user['created_at']));
    $roleClass = $user['role'] === 'ADMIN' ? 'bg-red-100 text-red-800' : 
                ($user['role'] === 'TEACHER' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800');
    
    $page_content .= <<<HTML
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

$page_content .= <<<HTML
            </tbody>
        </table>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Add New User</h3>
                <form id="userForm" method="POST" action="users.php">
                    <input type="hidden" name="user_id" id="userId">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                        <input type="text" name="name" id="userName" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <input type="email" name="email" id="userEmail" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Role</label>
                        <select name="role" id="userRole" required onchange="toggleAdminPassword()"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="STUDENT">Student</option>
                            <option value="TEACHER">Teacher</option>
                            <option value="ADMIN">Admin</option>
                        </select>
                    </div>
                    <div class="mb-4" id="passwordField">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                        <input type="password" name="password" id="userPassword"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4 hidden" id="adminPasswordField">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Admin Secret Key</label>
                        <input type="password" name="admin_password" id="adminPassword"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeUserModal()"
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit" name="save_user"
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Deletion</h3>
            <p class="mb-4">Are you sure you want to delete this user?</p>
            <form method="POST" class="flex justify-end space-x-3">
                <input type="hidden" name="user_id" id="deleteUserId">
                <button type="button" onclick="closeDeleteModal()"
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit" name="delete_user"
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <script>
        function applyFilters() {
            const searchInput = document.getElementById('searchInput').value.trim();
            const roleFilter = document.getElementById('roleFilter').value;
            
            // Build the query string
            let queryParams = [];
            if (searchInput) {
                queryParams.push('search=' + encodeURIComponent(searchInput));
            }
            if (roleFilter) {
                queryParams.push('role=' + encodeURIComponent(roleFilter));
            }
            
            // Create the URL
            let url = 'users.php';
            if (queryParams.length > 0) {
                url += '?' + queryParams.join('&');
            }
            
            // Redirect to the filtered results
            window.location.href = url;
        }

        // Add this new function for live search
        function liveSearch() {
            const searchInput = document.getElementById('searchInput').value.trim();
            const roleFilter = document.getElementById('roleFilter').value;
            
            // Create FormData object
            const formData = new FormData();
            formData.append('search', searchInput);
            formData.append('role', roleFilter);
            
            // Fetch API for AJAX request
            fetch('users_search.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.querySelector('tbody').innerHTML = data;
            })
            .catch(error => console.error('Error:', error));
        }

        // Add event listeners for live search
        document.getElementById('searchInput').addEventListener('input', liveSearch);
        document.getElementById('roleFilter').addEventListener('change', liveSearch);

        // Remove the old applyFilters function and its event listener
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        // Update the editUser function in JavaScript
        function editUser(id, name, email, role) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = id;
            document.getElementById('userName').value = name;
            document.getElementById('userEmail').value = email;
            document.getElementById('userRole').value = role;
            document.getElementById('passwordField').style.display = 'block'; // Show password field
            document.getElementById('userPassword').required = false; // Make password optional for editing
            document.getElementById('passwordField').querySelector('label').textContent = 'New Password (leave blank to keep current)';
            document.getElementById('adminPasswordField').classList.add('hidden');
            document.getElementById('userModal').classList.remove('hidden');
        }

        function openAddUserModal() {
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('adminPasswordField').classList.add('hidden');
            document.getElementById('userModal').classList.remove('hidden');
            document.getElementById('userPassword').required = true;
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        function deleteUser(id) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function toggleAdminPassword() {
            const roleSelect = document.getElementById('userRole');
            const adminPasswordField = document.getElementById('adminPasswordField');
            const userId = document.getElementById('userId').value;
            
            if (roleSelect.value === 'ADMIN' && !userId) {
                adminPasswordField.classList.remove('hidden');
            } else {
                adminPasswordField.classList.add('hidden');
            }
        }
    </script>
HTML;

require_once 'includes/admin_layout.php';
?>