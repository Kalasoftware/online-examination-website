<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TEACHER') {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $registration_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    try {
        // Verify the exam belongs to this teacher
        $stmt = $db->prepare("
            SELECT er.* FROM exam_registrations er
            JOIN exams e ON er.exam_id = e.exam_id
            WHERE er.registration_id = ? AND e.teacher_id = ?");
        $stmt->execute([$registration_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $new_status = ($action === 'approve') ? 'APPROVED' : 'REJECTED';
            
            $stmt = $db->prepare("
                UPDATE exam_registrations 
                SET status = ?, updated_at = NOW() 
                WHERE registration_id = ?");
            $stmt->execute([$new_status, $registration_id]);
            
            $_SESSION['success'] = "Registration " . strtolower($new_status) . " successfully.";
        } else {
            $_SESSION['error'] = "Unauthorized access.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error processing registration: " . $e->getMessage();
    }
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>