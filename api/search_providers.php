<?php
// Location-aware provider search
ob_start();
header('Content-Type: application/json');
require_once '../includes/session_manager.php';
require_once '../config/db.php';

$response = null; $code = 200;
try {
    if (!SessionManager::checkAuth('customer')) { $code=401; throw new Exception('Unauthorized'); }

    $service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
    if ($service_id <= 0) { $code=400; throw new Exception('service_id required'); }

    $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
    $lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
    $radius = isset($_GET['radius']) ? (float)$_GET['radius'] : 25; // km default
    if ($radius <= 0 || $radius > 500) $radius = 25;

    $hasLocation = $lat !== null && $lng !== null && $lat != 0 && $lng != 0;

    if ($hasLocation) {
        // Haversine formula (approx)
    $sql = "SELECT u.id AS user_id, u.name AS full_name, u.email, u.phone, u.photo, ps.rate,
                u.latitude, u.longitude,
                (6371 * acos( cos( radians(?) ) * cos( radians( u.latitude ) ) * cos( radians( u.longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( u.latitude ) ) ) ) AS distance
                FROM users u
                JOIN provider_services ps ON u.id = ps.provider_id
                WHERE u.role='provider' AND ps.service_id=? AND ps.is_active=1 AND u.latitude IS NOT NULL AND u.longitude IS NOT NULL
                HAVING distance <= ?
                ORDER BY distance ASC, ps.rate ASC";
    $stmt = $conn->prepare($sql);
    if(!$stmt) throw new Exception('DB prepare failed: '.$conn->error);
    // Parameters: lat (d), lng (d), lat (d), service_id (i), radius (d)
    $stmt->bind_param('dddid', $lat,$lng,$lat,$service_id,$radius);
    } else {
    $sql = "SELECT u.id AS user_id, u.name AS full_name, u.email, u.phone, u.photo, ps.rate, NULL AS distance
                FROM users u JOIN provider_services ps ON u.id = ps.provider_id
                WHERE u.role='provider' AND ps.service_id=? AND ps.is_active=1
                ORDER BY ps.rate ASC";
        $stmt = $conn->prepare($sql);
        if(!$stmt) throw new Exception('DB prepare failed: '.$conn->error);
        $stmt->bind_param('i',$service_id);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $providers=[]; while($row=$res->fetch_assoc()){ $providers[]=[
        'user_id'=>(int)$row['user_id'],
        'full_name'=>$row['full_name'],
        'email'=>$row['email'],
        'phone'=>$row['phone'] ?: 'Not provided',
        'photo'=>$row['photo'] ?? null,
        'rate'=>number_format($row['rate'],2),
        'distance'=>$row['distance']!==null?round($row['distance'],2):null
    ]; }
    $response = [
        'providers'=>$providers,
        'location_used'=>$hasLocation,
        'radius_km'=>$hasLocation?$radius:null,
        'fallback_suggested'=>($hasLocation && count($providers)===0)
    ];
} catch(Throwable $e){ if(!$response) $response=['error'=>$e->getMessage()]; if($code===200) $code=500; }
http_response_code($code); ob_end_clean(); echo json_encode($response); exit;
