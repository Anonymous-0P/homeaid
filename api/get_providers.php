
<?php
// Always return JSON, even on error
ob_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Enable error logging for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/session_manager.php';
require_once '../config/db.php';

$response = null;
$http_code = 200;

try {
    if (!SessionManager::checkAuth('customer')) {
        $http_code = 401;
        $response = ['error' => 'Unauthorized access. Please log in as a customer.'];
        throw new Exception('Unauthorized');
    }
    if (!isset($_GET['service_id'])) {
        $http_code = 400;
        $response = ['error' => 'Service ID is required'];
        throw new Exception('Missing service_id');
    }
    $service_id = intval($_GET['service_id']);

    // Check provider_services table exists
    $tables_check = $conn->query("SHOW TABLES LIKE 'provider_services'");
    if ($tables_check->num_rows === 0) {
        $create_table = "CREATE TABLE provider_services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider_id INT NOT NULL,
            service_id INT NOT NULL,
            rate DECIMAL(10,2) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
            UNIQUE KEY unique_provider_service (provider_id, service_id)
        )";
        $conn->query($create_table);
    }

    $query = "
        SELECT 
            u.id as user_id,
            u.name as full_name,
            u.email,
            u.phone,
            u.photo,
            ps.rate
        FROM users u
        JOIN provider_services ps ON u.id = ps.provider_id
        WHERE u.role = 'provider' 
        AND ps.service_id = ?
        AND ps.is_active = TRUE
        ORDER BY ps.rate ASC
    ";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        $response = ['error' => 'Database error: Failed to prepare statement. ' . $conn->error . ' (Check if provider_services table exists and has correct columns)'];
        throw new Exception($response['error']);
    }
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $providers = [];
    while ($row = $result->fetch_assoc()) {
        $providers[] = [
            'user_id' => $row['user_id'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'phone' => $row['phone'] ?? 'Not provided',
            'photo' => $row['photo'] ?? null,
            'rate' => number_format($row['rate'], 2),
            'rating' => null
        ];
    }
    $response = $providers;
} catch (Throwable $e) {
    if (!$response) {
        $response = ['error' => 'System error: ' . $e->getMessage()];
    }
    $http_code = $http_code ?: 500;
}

if (isset($conn)) {
    $conn->close();
}

// Clean output buffer and send JSON
http_response_code($http_code);
ob_end_clean();
echo json_encode($response);
exit;
