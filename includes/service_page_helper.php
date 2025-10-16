<?php
/**
 * Service Page Dynamic Icon Helper
 * Include this at the top of service pages to get dynamic icons
 */

function getServicePageData($serviceName, $conn) {
    $data = [
        'icon' => ServiceIcons::getServiceIcon($serviceName),
        'name' => ucfirst($serviceName),
        'description' => "Professional {$serviceName} services for your home."
    ];
    
    try {
        $stmt = $conn->prepare("SELECT name, description, icon_key FROM services WHERE name = ? OR name LIKE ?");
        $likePattern = "%{$serviceName}%";
        $stmt->bind_param("ss", $serviceName, $likePattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($service_info = $result->fetch_assoc()) {
            $data['icon'] = ServiceIcons::getIconByKey($service_info['icon_key']);
            $data['name'] = $service_info['name'];
            $data['description'] = $service_info['description'];
        }
    } catch (Exception $e) {
        // Use fallback data if database error
    }
    
    return $data;
}
?>