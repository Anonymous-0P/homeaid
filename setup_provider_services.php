<?php
include "config/db.php";

echo "<h1>Provider Service Setup Tool</h1>";
echo "<p>This tool will help set up provider services so customers can book them.</p>";

// Check current state
echo "<h2>Current State:</h2>";

// Check providers
$providers = $conn->query("SELECT id, name, email FROM users WHERE role='provider'");
echo "<h3>Registered Providers:</h3>";
if ($providers->num_rows > 0) {
    echo "<ul>";
    while ($provider = $providers->fetch_assoc()) {
        echo "<li>ID: {$provider['id']} - {$provider['name']} ({$provider['email']})</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ No providers found!</p>";
}

// Check services
$services = $conn->query("SELECT id, name FROM services");
echo "<h3>Available Services:</h3>";
if ($services->num_rows > 0) {
    echo "<ul>";
    while ($service = $services->fetch_assoc()) {
        echo "<li>ID: {$service['id']} - {$service['name']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ No services found!</p>";
}

// Check provider_services table
$check_table = $conn->query("SHOW TABLES LIKE 'provider_services'");
if ($check_table->num_rows == 0) {
    echo "<h3>Creating provider_services table...</h3>";
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
    if ($conn->query($create_table)) {
        echo "<p>✅ provider_services table created successfully!</p>";
    } else {
        echo "<p>❌ Failed to create provider_services table: " . $conn->error . "</p>";
    }
}

// Check existing provider services
$provider_services = $conn->query("SELECT ps.*, u.name as provider_name, s.name as service_name FROM provider_services ps LEFT JOIN users u ON ps.provider_id = u.id LEFT JOIN services s ON ps.service_id = s.id");
echo "<h3>Current Provider Services:</h3>";
if ($provider_services->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Provider</th><th>Service</th><th>Rate</th><th>Active</th></tr>";
    while ($ps = $provider_services->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($ps['provider_name']) . "</td>";
        echo "<td>" . htmlspecialchars($ps['service_name']) . "</td>";
        echo "<td>₹" . number_format($ps['rate'], 2) . "/hr</td>";
        echo "<td>" . ($ps['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ No provider services configured!</p>";
    
    // Auto-setup some test provider services
    echo "<h3>Setting up test provider services...</h3>";
    
    $providers_reset = $conn->query("SELECT id, name FROM users WHERE role='provider'");
    $services_reset = $conn->query("SELECT id, name FROM services");
    
    if ($providers_reset->num_rows > 0 && $services_reset->num_rows > 0) {
        $services_array = [];
        while ($service = $services_reset->fetch_assoc()) {
            $services_array[] = $service;
        }
        
        while ($provider = $providers_reset->fetch_assoc()) {
            $provider_id = $provider['id'];
            $provider_name = $provider['name'];
            
            echo "<h4>Setting up services for {$provider_name}:</h4>";
            
            foreach ($services_array as $service) {
                $service_id = $service['id'];
                $service_name = $service['name'];
                
                // Generate a random rate between 200-800
                $rate = rand(200, 800);
                
                $insert = $conn->prepare("INSERT IGNORE INTO provider_services (provider_id, service_id, rate) VALUES (?, ?, ?)");
                $insert->bind_param("iid", $provider_id, $service_id, $rate);
                
                if ($insert->execute()) {
                    echo "<p>✅ Added {$service_name} service for {$provider_name} at ₹{$rate}/hr</p>";
                } else {
                    echo "<p>❌ Failed to add {$service_name} for {$provider_name}: " . $conn->error . "</p>";
                }
            }
        }
        
        echo "<h3>✅ Provider services setup complete!</h3>";
    } else {
        echo "<p>❌ Need both providers and services to set up relationships</p>";
    }
}

// Final verification
echo "<h2>Final Verification:</h2>";
$final_check = $conn->query("SELECT COUNT(*) as count FROM provider_services WHERE is_active = TRUE");
$active_count = $final_check->fetch_assoc()['count'];
echo "<p><strong>Active Provider Services:</strong> {$active_count}</p>";

if ($active_count > 0) {
    echo "<div style='background: #d1fae5; border: 1px solid #059669; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h3 style='color: #059669; margin: 0 0 0.5rem 0;'>🎉 Success!</h3>";
    echo "<p style='margin: 0;'>Customers should now be able to see and book providers for services!</p>";
    echo "</div>";
    
    echo "<h3>Test Links:</h3>";
    echo "<ul>";
    echo "<li><a href='customer/login.php' target='_blank'>Customer Login</a></li>";
    echo "<li><a href='customer/book_service.php' target='_blank'>Customer Booking (after login)</a></li>";
    echo "<li><a href='api/get_providers.php?service_id=1' target='_blank'>Test API for Service ID 1</a></li>";
    echo "</ul>";
} else {
    echo "<div style='background: #fee2e2; border: 1px solid #dc2626; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h3 style='color: #dc2626; margin: 0 0 0.5rem 0;'>❌ Issue Persists</h3>";
    echo "<p style='margin: 0;'>No active provider services found. Check if providers and services exist in the database.</p>";
    echo "</div>";
}

$conn->close();
?>
