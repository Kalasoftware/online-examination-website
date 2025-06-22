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

// Get search parameter
$search = isset($_POST['search']) ? $_POST['search'] : '';

// Build query
$query = "SELECT e.*, u.name as teacher_name 
          FROM exams e 
          LEFT JOIN users u ON e.teacher_id = u.user_id 
          WHERE 1=1";
if ($search) {
    $query .= " AND (e.exam_name LIKE ? OR e.subject LIKE ?)";
}
$query .= " ORDER BY e.created_at DESC";

$stmt = $db->prepare($query);

// Bind parameters
$params = [];
if ($search) {
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$stmt->execute($params);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate HTML for table rows
foreach ($exams as $exam) {
    $created_date = date('M d, Y', strtotime($exam['created_at']));
    $exam_date = date('M d, Y H:i', strtotime($exam['exam_date']));
    
    echo <<<HTML
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">{$exam['exam_name']}</td>
            <td class="px-6 py-4 whitespace-nowrap">{$exam['subject']}</td>
            <td class="px-6 py-4 whitespace-nowrap">{$exam_date}</td>
            <td class="px-6 py-4 whitespace-nowrap">{$exam['duration']} mins</td>
            <td class="px-6 py-4 whitespace-nowrap">{$exam['teacher_name']}</td>
            <td class="px-6 py-4 whitespace-nowrap">{$created_date}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick='editExam({$exam["exam_id"]}, 
                    "{$exam['exam_name']}", 
                    "{$exam['subject']}", 
                    "{$exam['exam_date']}", 
                    "{$exam['duration']}", 
                    "{$exam['teacher_id']}")' 
                    class="text-blue-600 hover:text-blue-900 mr-3">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteExam({$exam['exam_id']})" 
                    class="text-red-600 hover:text-red-900">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
HTML;
}
?>