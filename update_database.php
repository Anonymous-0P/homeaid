<?php
/**
 * Database Update Script for Service Icons
 * Run this file once to add icon support to your HomeAid database
 */

// Include database connection
require_once 'config/db.php';
require_once 'includes/service_icons.php';

// Set content type for proper display
header('Content-Type: text/html; charset=UTF-8');

// Security check - only allow running from localhost or specific IPs
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';

if (!in_array($client_ip, $allowed_ips) && $client_ip !== 'unknown') {
    die('Access denied. This script can only be run from localhost.');
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>HomeAid Database Update</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #2196F3; background: #f8f9fa; }
        .success { border-left-color: #4CAF50; background: #f1f8e9; color: #2e7d32; }
        .error { border-left-color: #f44336; background: #ffebee; color: #c62828; }
        .warning { border-left-color: #ff9800; background: #fff3e0; color: #ef6c00; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        .icon-preview { font-size: 24px; margin: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🛠️ HomeAid Database Update Script</h1>
        <p>This script will add service icon support to your HomeAid database.</p>";

$updates_performed = [];
$errors = [];

try {
    // Step 1: Check if icon_key column already exists
    echo "<div class='step'><strong>Step 1:</strong> Checking current database structure...</div>";
    
    $result = $conn->query("SHOW COLUMNS FROM services LIKE 'icon_key'");
    $icon_column_exists = ($result && $result->num_rows > 0);
    
    if ($icon_column_exists) {
        echo "<div class='warning'>⚠️ Icon column already exists. Skipping column creation.</div>";
    } else {
        // Step 2: Add icon_key column
        echo "<div class='step'><strong>Step 2:</strong> Adding icon_key column to services table...</div>";
        
        $sql = "ALTER TABLE services ADD COLUMN icon_key VARCHAR(50) DEFAULT 'toolbox'";
        if ($conn->query($sql)) {
            echo "<div class='success'>✅ Successfully added icon_key column to services table.</div>";
            $updates_performed[] = "Added icon_key column";
        } else {
            $error = "Error adding column: " . $conn->error;
            echo "<div class='error'>❌ $error</div>";
            $errors[] = $error;
        }
    }
    
    // Step 3: Update existing services with appropriate icons
    echo "<div class='step'><strong>Step 3:</strong> Updating existing services with default icons...</div>";
    
    $icon_updates = [
        'plumbing' => 'wrench',
        'electrical' => 'electrical', 
        'electrician' => 'electrical',
        'cleaning' => 'cleaning',
        'gardening' => 'garden',
        'pest control' => 'pest',
        'painting' => 'paint',
        'appliance repair' => 'appliance',
        'home security' => 'security',
        'roofing' => 'roof',
        'carpentry' => 'saw',
        'hvac' => 'snowflake',
        'hvac services' => 'snowflake',
        'home repair' => 'hammer'
    ];
    
    $updated_count = 0;
    foreach ($icon_updates as $service_name => $icon_key) {
        $stmt = $conn->prepare("UPDATE services SET icon_key = ? WHERE LOWER(name) LIKE ?");
        $like_pattern = "%{$service_name}%";
        $stmt->bind_param("ss", $icon_key, $like_pattern);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $icon = ServiceIcons::getIconByKey($icon_key);
            echo "<div class='success'>✅ Updated '{$service_name}' services with icon: <span class='icon-preview'>{$icon}</span> ($icon_key)</div>";
            $updated_count += $stmt->affected_rows;
        }
    }
    
    if ($updated_count > 0) {
        $updates_performed[] = "Updated $updated_count existing services with icons";
    }
    
    // Step 4: Insert core services if they don't exist
    echo "<div class='step'><strong>Step 4:</strong> Adding core services if missing...</div>";
    
    $core_services = [
        ['plumbing', 'Expert plumbers for leak repairs, pipe installation, and water heater services', 'wrench'],
        ['electrical', 'Licensed electricians for wiring, repairs, and smart home installations', 'electrical'],
        ['cleaning', 'Professional deep cleaning for homes, offices, and post-construction sites', 'cleaning'],
        ['gardening', 'Landscaping, lawn care, and garden maintenance services', 'garden'],
        ['pest control', 'Eco-friendly pest elimination and preventive protection for your home', 'pest'],
        ['painting', 'Interior & exterior painting with professional surface prep and finish', 'paint'],
        ['appliance repair', 'Fast diagnostics and repairs for all major household appliances', 'appliance'],
        ['home security', 'Smart surveillance, alarms, and secure access installations', 'security'],
        ['roofing', 'Roof installation, leak repair, and weatherproof maintenance', 'roof'],
        ['carpentry', 'Custom woodwork, repairs, and installations for interiors and outdoors', 'saw'],
        ['hvac services', 'AC repair, installation, and maintenance for year-round comfort', 'snowflake'],
        ['home repair', 'Skilled handymen for painting, carpentry, and general maintenance', 'hammer']
    ];
    
    $inserted_count = 0;
    foreach ($core_services as $service) {
        list($name, $description, $icon_key) = $service;
        
        // Check if service already exists
        $check_stmt = $conn->prepare("SELECT id FROM services WHERE LOWER(name) = LOWER(?)");
        $check_stmt->bind_param("s", $name);
        $check_stmt->execute();
        $exists = $check_stmt->get_result()->num_rows > 0;
        
        if (!$exists) {
            $insert_stmt = $conn->prepare("INSERT INTO services (name, description, icon_key) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $name, $description, $icon_key);
            
            if ($insert_stmt->execute()) {
                $icon = ServiceIcons::getIconByKey($icon_key);
                echo "<div class='success'>✅ Added new service: '{$name}' with icon: <span class='icon-preview'>{$icon}</span></div>";
                $inserted_count++;
            } else {
                $error = "Error inserting $name: " . $conn->error;
                echo "<div class='error'>❌ $error</div>";
                $errors[] = $error;
            }
        } else {
            echo "<div class='warning'>⚠️ Service '{$name}' already exists, skipping insertion.</div>";
        }
    }
    
    if ($inserted_count > 0) {
        $updates_performed[] = "Added $inserted_count new core services";
    }
    
    // Step 5: Show current services with icons
    echo "<div class='step'><strong>Step 5:</strong> Current services in database:</div>";
    
    $result = $conn->query("SELECT id, name, description, icon_key FROM services ORDER BY name");
    if ($result && $result->num_rows > 0) {
        echo "<div class='code'>";
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th style='padding: 8px; text-align: left;'>ID</th><th style='padding: 8px; text-align: left;'>Icon</th><th style='padding: 8px; text-align: left;'>Name</th><th style='padding: 8px; text-align: left;'>Description</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $icon = ServiceIcons::getIconByKey($row['icon_key']);
            echo "<tr>";
            echo "<td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . $row['id'] . "</td>";
            echo "<td style='padding: 8px; border-bottom: 1px solid #ddd; font-size: 20px;'>" . $icon . "</td>";
            echo "<td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
            echo "<td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($row['description']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
    
    // Final summary
    echo "<div class='step'><strong>Update Summary:</strong></div>";
    
    if (empty($errors) && !empty($updates_performed)) {
        echo "<div class='success'><strong>🎉 Update completed successfully!</strong><ul>";
        foreach ($updates_performed as $update) {
            echo "<li>$update</li>";
        }
        echo "</ul></div>";
        
        echo "<div class='step'>
                <strong>Next Steps:</strong>
                <ol>
                    <li>Visit <a href='admin/manage_services.php'>Admin > Manage Services</a> to test the icon selector</li>
                    <li>Check your <a href='index.php'>homepage</a> to see dynamic service icons</li>
                    <li>Delete this update script for security: <code>update_database.php</code></li>
                </ol>
              </div>";
              
    } elseif (!empty($errors)) {
        echo "<div class='error'><strong>❌ Some errors occurred:</strong><ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul></div>";
        
        if (!empty($updates_performed)) {
            echo "<div class='warning'><strong>⚠️ Partial updates completed:</strong><ul>";
            foreach ($updates_performed as $update) {
                echo "<li>$update</li>";
            }
            echo "</ul></div>";
        }
    } else {
        echo "<div class='warning'>⚠️ No updates were needed. Your database is already up to date!</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Critical error: " . $e->getMessage() . "</div>";
    $errors[] = $e->getMessage();
}

echo "
        <div class='step' style='margin-top: 30px; text-align: center;'>
            <strong>Available Icons Preview:</strong><br>
            <div style='margin: 15px 0;'>";

// Show all available icons
foreach (ServiceIcons::getAvailableIcons() as $key => $emoji) {
    echo "<span class='icon-preview' title='$key'>$emoji</span>";
}

echo "
            </div>
            <small style='color: #666;'>Total: " . count(ServiceIcons::getAvailableIcons()) . " icons available</small>
        </div>
        
        <div style='margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 5px;'>
            <strong>🔒 Security Note:</strong> Please delete this file (<code>update_database.php</code>) after running it successfully to prevent unauthorized access.
        </div>
    </div>
</body>
</html>";

// Close database connection
$conn->close();
?>