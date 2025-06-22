<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    exit(json_encode(['error' => 'Unauthorized']));
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT 
    e.exam_id, 
    e.exam_name, 
    e.subject,
    e.exam_date,
    e.duration,
    COUNT(DISTINCT r.student_id) as students_attempted,
    (SELECT COUNT(*) FROM questions WHERE exam_id = e.exam_id) as total_questions
    FROM exams e
    LEFT JOIN results r ON e.exam_id = r.exam_id
    WHERE e.exam_date <= NOW() 
    AND DATE_ADD(e.exam_date, INTERVAL e.duration MINUTE) >= NOW()
    GROUP BY e.exam_id");
$stmt->execute();
$active_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($active_exams);
?>