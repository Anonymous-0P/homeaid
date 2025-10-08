<?php 
require_once "../includes/session_manager.php";

// Check authentication with session timeout
if (!SessionManager::checkAuth('admin')) {
    header("Location: login.php");
    exit();
}

include "../config/db.php";


$id = intval($_GET['id']);
$conn->query("DELETE FROM services WHERE id=$id");
header("Location: manage_services.php");
