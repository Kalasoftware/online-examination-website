<?php
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Fetch quick statistics
$stats = [
    'total_students' => 0,
    'total_teachers' => 0,
    'total_exams' => 0,
    'active_exams' => 0
];

// Get total students
$query = "SELECT COUNT(*) FROM users WHERE role = 'STUDENT'";
$stmt = $db->query($query);
$stats['total_students'] = $stmt->fetchColumn();

// Get total teachers
$query = "SELECT COUNT(*) FROM users WHERE role = 'TEACHER'";
$stmt = $db->query($query);
$stats['total_teachers'] = $stmt->fetchColumn();

// Get total exams
$query = "SELECT COUNT(*) FROM exams";
$stmt = $db->query($query);
$stats['total_exams'] = $stmt->fetchColumn();

// Get active exams (exams that are currently running or yet to start)
$query = "SELECT COUNT(*) FROM exams 
         WHERE exam_date <= NOW() 
         AND DATE_ADD(exam_date, INTERVAL duration MINUTE) >= NOW()
         OR exam_date > NOW()";
$stmt = $db->query($query);
$stats['active_exams'] = $stmt->fetchColumn();

// Recent Activities
$query = "SELECT e.exam_name, u.name as student_name, r.attempt_date, r.marks_obtained, r.grade 
         FROM results r 
         JOIN exams e ON r.exam_id = e.exam_id 
         JOIN users u ON r.student_id = u.user_id 
         ORDER BY r.attempt_date DESC LIMIT 5";
$stmt = $db->query($query);
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_content = <<<HTML
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-user-graduate text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-gray-500 text-sm">Total Students</h3>
                    <p class="text-2xl font-semibold">{$stats['total_students']}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-chalkboard-teacher text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-gray-500 text-sm">Total Teachers</h3>
                    <p class="text-2xl font-semibold">{$stats['total_teachers']}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-file-alt text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-gray-500 text-sm">Total Exams</h3>
                    <p class="text-2xl font-semibold">{$stats['total_exams']}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-gray-500 text-sm">Active Exams</h3>
                    <p class="text-2xl font-semibold">{$stats['active_exams']}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Recent Activities</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
HTML;

foreach ($recent_activities as $activity) {
    $page_content .= <<<HTML
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{$activity['exam_name']}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{$activity['student_name']}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{$activity['attempt_date']}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{$activity['marks_obtained']}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{$activity['grade']}</td>
                    </tr>
HTML;
}

$page_content .= <<<HTML
                </tbody>
            </table>
        </div>
    </div>
HTML;

require_once 'includes/admin_layout.php';
?>