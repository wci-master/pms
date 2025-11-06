<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require './config/connection.php';
require_once './config/roles.php';

// Ensure user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isRole(ROLE_ADMIN)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate input
if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

$userId = (int)$_POST['user_id'];

// Don't allow deleting self
if ($userId === (int)$_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit;
}

try {
    $con->beginTransaction();

    // Get user info before deletion
    $stmt = $con->prepare("SELECT role, user_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // If user is a patient, delete their related records first
    if ($user['role'] === ROLE_PATIENT) {
        // Get patient ID from username (patient{ID})
        $patientId = (int)substr($user['user_name'], 7);
        
        // Delete medication history
        $stmt = $con->prepare("DELETE FROM patient_medication_history 
                              WHERE patient_visit_id IN 
                              (SELECT id FROM patient_visits WHERE patient_id = ?)");
        $stmt->execute([$patientId]);
        
        // Delete visits
        $stmt = $con->prepare("DELETE FROM patient_visits WHERE patient_id = ?");
        $stmt->execute([$patientId]);
    }

    // Delete user
    $stmt = $con->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    $con->commit();
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);

} catch (Exception $e) {
    $con->rollBack();
    error_log("Error deleting user: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}