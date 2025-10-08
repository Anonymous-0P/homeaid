<?php
require_once "../includes/session_manager.php";
if (!SessionManager::checkAuth('admin')) {
    http_response_code(403);
    echo "Forbidden";
    exit();
}
require_once "../config/db.php";
$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
if (!$uid) { http_response_code(400); echo "Bad request"; exit(); }
$stmt = $conn->prepare("SELECT aadhaar_file FROM users WHERE id=? AND role='provider' LIMIT 1");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows===0) { http_response_code(404); echo "Not found"; exit(); }
$row = $res->fetch_assoc();
$file = $row['aadhaar_file'];
if (!$file) { http_response_code(404); echo "Not found"; exit(); }
$path = realpath(__DIR__ . '/../assets/uploads/kyc/' . $file);
$base = realpath(__DIR__ . '/../assets/uploads/kyc');
if (!$path || strpos($path, $base) !== 0 || !file_exists($path)) { http_response_code(404); echo "Not found"; exit(); }
$mime = mime_content_type($path);
$allowed = ['image/jpeg','image/png','application/pdf'];
if (!in_array($mime, $allowed, true)) { $mime='application/octet-stream'; }
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit();
