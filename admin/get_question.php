<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    exit('Unauthorized');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Question ID required');
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get question details
    $query = "SELECT * FROM questions WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        http_response_code(404);
        exit('Question not found');
    }

    // Get options
    $options_query = "SELECT * FROM question_options WHERE question_id = ? ORDER BY id";
    $options_stmt = $db->prepare($options_query);
    $options_stmt->execute([$_GET['id']]);
    $question['options'] = $options_stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($question);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error: ' . $e->getMessage());
}
?>