<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    exit(json_encode(['error' => 'Unauthorized']));
}

$database = new Database();
$db = $database->getConnection();

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

$stmt = $db->prepare("SELECT 
    u.name,
    COUNT(DISTINCT resp.question_id) as answered_questions,
    (SELECT COUNT(*) FROM questions WHERE exam_id = ?) as total_questions,
    TIMESTAMPDIFF(MINUTE, MIN(resp.created_at), COALESCE(r.attempt_date, NOW())) as time_spent,
    CASE 
        WHEN r.result_id IS NOT NULL THEN 'Completed'
        WHEN a.is_present = 1 THEN 'In Progress'
        ELSE 'Not Started'
    END as status
    FROM users u
    LEFT JOIN attendance a ON u.user_id = a.student_id AND a.exam_id = ?
    LEFT JOIN responses resp ON u.user_id = resp.student_id AND resp.exam_id = ?
    LEFT JOIN results r ON u.user_id = r.student_id AND r.exam_id = ?
    WHERE u.role = 'STUDENT'
    AND a.is_present = 1
    GROUP BY u.user_id");

$stmt->execute([$exam_id, $exam_id, $exam_id, $exam_id]);
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($progress);
?>