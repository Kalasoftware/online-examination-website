<?php
session_start();
require_once '../config/database.php';

// Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    echo "<h2>Access Denied. You are not authorized to access this page.</h2>";
    exit();
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Handle Add/Edit Exam
if (isset($_POST['save_exam'])) {
    $exam_name = filter_input(INPUT_POST, 'exam_name', FILTER_SANITIZE_STRING);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $duration = filter_input(INPUT_POST, 'duration', FILTER_SANITIZE_NUMBER_INT);
    $exam_date = filter_input(INPUT_POST, 'exam_date', FILTER_SANITIZE_STRING);
    $exam_id = filter_input(INPUT_POST, 'exam_id', FILTER_SANITIZE_NUMBER_INT);
    $admin_id = $_SESSION['user_id']; // Current admin

    if ($exam_id) {
        $query = "UPDATE exams SET exam_name = ?, subject = ?, duration = ?, exam_date = ?, admin_id = ? WHERE exam_id = ?";
        $params = [$exam_name, $subject, $duration, $exam_date, $admin_id, $exam_id];
    } else {
        $query = "INSERT INTO exams (exam_name, subject, duration, exam_date, admin_id) VALUES (?, ?, ?, ?, ?)";
        $params = [$exam_name, $subject, $duration, $exam_date, $admin_id];
    }

    try {
        $stmt = $db->prepare($query);
        if ($stmt->execute($params)) {
            $message = $exam_id ? "Exam updated successfully" : "Exam added successfully";
            $message_type = "success";
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Handle Delete Exam
if (isset($_POST['delete_exam'])) {
    $exam_id = filter_input(INPUT_POST, 'exam_id', FILTER_SANITIZE_NUMBER_INT);
    if ($exam_id) {
        $query = "DELETE FROM exams WHERE exam_id = ?";
        try {
            $stmt = $db->prepare($query);
            if ($stmt->execute([$exam_id])) {
                $message = "Exam deleted successfully";
                $message_type = "success";
            }
        } catch (PDOException $e) {
            $message = "Error deleting exam: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Fetch exams
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT e.*, 
          CASE 
              WHEN u.role = 'ADMIN' THEN u.name
              WHEN u.role = 'TEACHER' THEN u.name
              ELSE 'Unknown'
          END as admin_name
          FROM exams e 
          LEFT JOIN users u ON (e.admin_id = u.user_id OR e.teacher_id = u.user_id)
          WHERE 1=1";
$params = [];
if (!empty($search)) {
    $query .= " AND (e.exam_name LIKE ? OR e.subject LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}
$query .= " ORDER BY e.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_content = <<<HTML
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Manage Exams</h1>
        <a href="add_exam.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus"></i> Add New Exam
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="mb-4 flex space-x-4">
            <input type="text" id="searchInput" placeholder="Search exams..." 
                   class="border rounded px-3 py-2 w-64" value="{$search}">
            <button onclick="applyFilters()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Search
            </button>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration (mins)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
HTML;

foreach ($exams as $exam) {
    $exam_date = date('M d, Y H:i', strtotime($exam['exam_date']));
    $created_by = $exam['admin_name'] ?? 'Unknown';
    
    $page_content .= <<<HTML
                <tr>
                    <td class="px-6 py-4">{$exam['exam_name']}</td>
                    <td class="px-6 py-4">{$exam['subject']}</td>
                    <td class="px-6 py-4">{$exam['duration']}</td>
                    <td class="px-6 py-4">{$exam_date}</td>
                    <td class="px-6 py-4">{$created_by}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="edit_exam.php?id={$exam['exam_id']}" 
                           class="text-blue-600 hover:text-blue-900 mr-3">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="manage_questions.php?exam_id={$exam['exam_id']}" 
                           class="text-green-600 hover:text-green-900 mr-3">
                            <i class="fas fa-question-circle"></i> Questions
                        </a>
                        <button onclick="deleteExam({$exam['exam_id']})" 
                                class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
HTML;
}

$page_content .= <<<HTML
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Exam Modal -->
    <div id="examModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Add New Exam</h3>
                <form id="examForm" method="POST">
                    <input type="hidden" name="exam_id" id="examId">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Exam Name</label>
                        <input type="text" name="exam_name" id="examName" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Subject</label>
                        <input type="text" name="subject" id="subject" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Duration (minutes)</label>
                        <input type="number" name="duration" id="duration" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Exam Date & Time</label>
                        <input type="datetime-local" name="exam_date" id="examDate" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeExamModal()"
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit" name="save_exam"
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
            <p class="mb-4">Are you sure you want to delete this exam?</p>
            <form method="POST" class="flex justify-end space-x-3">
                <input type="hidden" name="exam_id" id="deleteExamId">
                <button type="button" onclick="closeDeleteModal()"
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Cancel
                </button>
                <button type="submit" name="delete_exam"
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <script>
        // Add event listener for Enter key on search input
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        function applyFilters() {
            const search = document.getElementById('searchInput').value.trim();
            // Fix: Add proper URL construction with existing parameters
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('search', search);
            window.location.href = currentUrl.toString();
        }

        function openAddExamModal() {
            document.getElementById('modalTitle').textContent = 'Add New Exam';
            document.getElementById('examForm').reset();
            document.getElementById('examId').value = '';
            document.getElementById('examModal').classList.remove('hidden');
        }

        function editExam(id, name, subject, duration, examDate) {
            document.getElementById('modalTitle').textContent = 'Edit Exam';
            document.getElementById('examId').value = id;
            document.getElementById('examName').value = name;
            document.getElementById('subject').value = subject;
            document.getElementById('duration').value = duration;
            document.getElementById('examDate').value = examDate.slice(0, 16);
            document.getElementById('examModal').classList.remove('hidden');
        }

        function closeExamModal() {
            document.getElementById('examModal').classList.add('hidden');
        }

        function deleteExam(id) {
            document.getElementById('deleteExamId').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
HTML;

require_once 'includes/admin_layout.php';
?>
