<?php
require_once "includes/session_manager.php";

header('Content-Type: application/json');

// Check if session is valid
$is_valid = SessionManager::checkAuth();

if ($is_valid) {
    $remaining_time = SessionManager::getRemainingTimeFormatted();
    echo json_encode([
        'expired' => false,
        'remaining_time' => $remaining_time,
        'user_id' => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ]);
} else {
    echo json_encode([
        'expired' => true,
        'remaining_time' => 'Expired',
        'message' => 'Session has expired'
    ]);
}
?>
