<?php 
require_once "../includes/session_manager.php";

// Check authentication with session timeout
if (!SessionManager::checkAuth('admin')) {
    header("Location: login.php");
    exit();
}

include "../config/db.php";

$status = 'ok';
if ($conn && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Also cascade delete dependent records if needed: provider_services, notifications (DB FK may handle)
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'provider'");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) { $status = 'fail'; }
    } else { $status = 'fail'; }
} else { $status = 'fail'; }
header("Location: manage_providers.php?deleted=".$status);
exit();

