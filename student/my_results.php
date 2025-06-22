<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get student's exam results
$stmt = $db->prepare("
    SELECT 
        er.*,
        e.exam_name,
        e.subject,
        e.total_marks
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.exam_id
    WHERE er.student_id = ?
    ORDER BY er.completion_time DESC
");
$stmt->execute([$_SESSION['user_id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "My Results";
$page_content = <<<HTML
<div class="container mx-auto px-4 py-6">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">My Exam Results</h1>
        
        <div class="bg-white shadow-md rounded my-6">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Exam Name</th>
                        <th class="py-3 px-6 text-left">Subject</th>
                        <th class="py-3 px-6 text-left">Score</th>
                        <th class="py-3 px-6 text-left">Percentage</th>
                        <th class="py-3 px-6 text-left">Completion Time</th>
                        <th class="py-3 px-6 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
HTML;

foreach ($results as $result) {
    $percentage = round(($result['score'] / $result['total_marks']) * 100, 2);
    $page_content .= <<<HTML
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6">{$result['exam_name']}</td>
                        <td class="py-3 px-6">{$result['subject']}</td>
                        <td class="py-3 px-6">{$result['score']} / {$result['total_marks']}</td>
                        <td class="py-3 px-6">{$percentage}%</td>
                        <td class="py-3 px-6">{$result['completion_time']}</td>
                        <td class="py-3 px-6">
                            <div class="flex space-x-3">
                                <a href="view_result.php?exam_id={$result['exam_id']}" 
                                   class="text-blue-600 hover:text-blue-800" 
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="generate_result_pdf.php?exam_id={$result['exam_id']}" 
                                   class="text-green-600 hover:text-green-800"
                                   title="Download PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
HTML;
}

$page_content .= <<<HTML
                </tbody>
            </table>
        </div>
    </div>
</div>
HTML;

require_once 'includes/student_layout.php';
?>