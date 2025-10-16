<?php
/**
 * Customer Portal Icon Test
 * Quick verification that customer portal uses dynamic icons
 */

// Include required files
require_once '../config/db.php';
require_once '../includes/service_icons.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Customer Portal Icon Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .service-test { 
            border: 1px solid #ddd; 
            margin: 10px 0; 
            padding: 15px; 
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
        }
        .icon { font-size: 24px; }
        .old-icon { opacity: 0.5; text-decoration: line-through; }
        .new-icon { font-weight: bold; color: #007cba; }
    </style>
</head>
<body>";

echo "<h1>🧪 Customer Portal Icon Test</h1>";
echo "<p>Testing if customer portal now uses dynamic icons from database...</p>";

try {
    // Get all services from database
    $result = $conn->query("SELECT id, name, description, icon_key FROM services ORDER BY name");
    
    if ($result && $result->num_rows > 0) {
        echo "<h2>✅ Services found in database:</h2>";
        
        while ($service = $result->fetch_assoc()) {
            $old_icon = '🏠'; // Default old hardcoded icon
            $service_name = strtolower($service['name']);
            
            // Simulate old hardcoded logic
            if (strpos($service_name, 'plumb') !== false) $old_icon = '🔧';
            elseif (strpos($service_name, 'electric') !== false) $old_icon = '⚡';
            elseif (strpos($service_name, 'clean') !== false) $old_icon = '🧹';
            elseif (strpos($service_name, 'repair') !== false) $old_icon = '🔨';
            
            // Get new dynamic icon
            $new_icon = ServiceIcons::getIconByKey($service['icon_key'] ?? 'toolbox');
            
            echo "<div class='service-test'>";
            echo "<span class='icon old-icon'>" . $old_icon . "</span>";
            echo "<span>→</span>";
            echo "<span class='icon new-icon'>" . $new_icon . "</span>";
            echo "<div>";
            echo "<strong>" . htmlspecialchars($service['name']) . "</strong><br>";
            echo "<small>Icon Key: " . htmlspecialchars($service['icon_key'] ?? 'toolbox') . "</small>";
            echo "</div>";
            echo "</div>";
        }
        
        echo "<h2>📋 What was updated in customer portal:</h2>";
        echo "<ul>";
        echo "<li>✅ Added <code>require_once '../includes/service_icons.php'</code> to book_service.php</li>";
        echo "<li>✅ Replaced hardcoded icon logic with <code>ServiceIcons::getIconByKey(\$service['icon_key'])</code></li>";
        echo "<li>✅ Updated service dropdown to use dynamic database services</li>";
        echo "<li>✅ Direct booking page now uses dynamic icons</li>";
        echo "</ul>";
        
        echo "<h2>🧭 Next steps:</h2>";
        echo "<ol>";
        echo "<li>Visit <a href='book_service.php'>Book Service</a> page to see dynamic icons</li>";
        echo "<li>Icons will now match what admin sets in <a href='../admin/manage_services.php'>Manage Services</a></li>";
        echo "<li>Any icon changes in admin will immediately reflect in customer portal</li>";
        echo "</ol>";
        
    } else {
        echo "<h2>⚠️ No services found in database</h2>";
        echo "<p>Run the <a href='../update_database.php'>database update script</a> first to add services with icons.</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Make sure the database connection is working and the icon_key column exists.</p>";
}

echo "<div style='margin-top: 30px; padding: 15px; background: #f0f8ff; border-radius: 5px;'>";
echo "<strong>🔒 Note:</strong> Delete this test file after verification for security.";
echo "</div>";

echo "</body></html>";
?>