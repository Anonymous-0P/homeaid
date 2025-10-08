<?php
/**
 * Test Booking Acceptance Notification
 * 
 * This file tests the email notification sent to customers when providers accept their booking
 */

// Include email functions
require_once __DIR__ . '/includes/email_functions.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Testing Booking Acceptance Notification</h2>";

// Simulate booking data when provider accepts the booking
$booking_data = [
    'booking_id' => 'BK_' . time(),
    'customer_name' => 'Sarah Johnson',
    'customer_email' => 'prakashkarekar4@gmail.com', // Using your email for testing
    'provider_name' => 'Expert Electrician Services',
    'provider_email' => 'provider@example.com',
    'service_name' => 'Electrical Wiring & Installation',
    'rate' => 750,
    'status' => 'accepted'
];

echo "<h3>📋 Booking Acceptance Scenario:</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745;'>";
echo "<ul>";
foreach ($booking_data as $key => $value) {
    echo "<li><strong>" . ucwords(str_replace('_', ' ', $key)) . ":</strong> $value</li>";
}
echo "</ul>";
echo "</div>";

echo "<hr>";

echo "<h3>🎉 Sending Booking Acceptance Notification...</h3>";

$result = sendBookingAcceptedNotification($booking_data);

if ($result) {
    echo "<div style='color: green; font-weight: bold; padding: 15px; background-color: #d4edda; border: 2px solid #28a745; border-radius: 8px; margin: 15px 0;'>";
    echo "✅ <strong>Booking acceptance notification sent successfully!</strong><br><br>";
    echo "📧 <strong>Sent to:</strong> {$booking_data['customer_email']}<br>";
    echo "📋 <strong>Booking ID:</strong> {$booking_data['booking_id']}<br>";
    echo "🎯 <strong>Subject:</strong> 🎉 Booking Accepted - {$booking_data['service_name']}<br>";
    echo "👨‍🔧 <strong>Provider:</strong> {$booking_data['provider_name']}";
    echo "</div>";
    
    echo "<div style='background: #cff4fc; color: #055160; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #b8daff;'>";
    echo "<h4 style='margin: 0 0 10px 0;'>📧 Email Features Included:</h4>";
    echo "<ul style='margin: 0; padding-left: 20px;'>";
    echo "<li>🎨 Professional success-themed design</li>";
    echo "<li>📱 Mobile-responsive layout</li>";
    echo "<li>📋 Complete booking and provider details</li>";
    echo "<li>📱 Clear next steps for the customer</li>";
    echo "<li>👨‍🔧 Provider contact information</li>";
    echo "<li>🔗 Call-to-action to view dashboard</li>";
    echo "<li>📞 Support contact information</li>";
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<div style='color: red; font-weight: bold; padding: 15px; background-color: #f8d7da; border: 2px solid #dc3545; border-radius: 8px; margin: 15px 0;'>";
    echo "❌ <strong>Failed to send booking acceptance notification!</strong><br>";
    echo "📧 <strong>Intended recipient:</strong> {$booking_data['customer_email']}";
    echo "</div>";
}

echo "<hr>";

echo "<h3>🔗 Integration Guide:</h3>";
echo "<div style='background: #e9ecef; padding: 20px; border-left: 4px solid #007bff; margin: 15px 0; border-radius: 8px;'>";
echo "<h4>To integrate this into your provider dashboard:</h4>";
echo "<ol>";
echo "<li><strong>Provider Accept Action:</strong> When provider clicks 'Accept' on a booking</li>";
echo "<li><strong>Update Database:</strong> Update booking status to 'accepted' in the database</li>";
echo "<li><strong>Prepare Data:</strong> Fetch booking, customer, and provider details</li>";
echo "<li><strong>Send Notification:</strong> Call <code>sendBookingAcceptedNotification(\$booking_data)</code></li>";
echo "<li><strong>Confirm Success:</strong> Display success message to provider</li>";
echo "</ol>";

echo "<h4>Example Integration Code:</h4>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px;'>";
echo htmlspecialchars('<?php
// In provider dashboard when accepting booking
if ($_POST[\'action\'] === \'accept_booking\') {
    $booking_id = $_POST[\'booking_id\'];
    
    // Update booking status
    $update_query = "UPDATE bookings SET status = \'accepted\' WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    
    // Get booking details for email
    $booking_data = fetchBookingDetails($booking_id);
    
    // Send email notification to customer
    $email_sent = sendBookingAcceptedNotification($booking_data);
    
    if ($email_sent) {
        echo "Booking accepted and customer notified!";
    }
}
?>');
echo "</pre>";
echo "</div>";

echo "<h3>📱 Customer Experience:</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #ffc107;'>";
echo "<p><strong>When a provider accepts a booking, the customer will receive:</strong></p>";
echo "<ul>";
echo "<li>🎉 <strong>Immediate email notification</strong> with celebration design</li>";
echo "<li>📋 <strong>Complete booking details</strong> including updated status</li>";
echo "<li>👨‍🔧 <strong>Provider contact information</strong> for direct communication</li>";
echo "<li>📱 <strong>Clear next steps</strong> explaining what happens next</li>";
echo "<li>🔗 <strong>Dashboard link</strong> to view booking details</li>";
echo "<li>📞 <strong>Support contact</strong> for any questions</li>";
echo "</ul>";
echo "</div>";

echo "<p style='text-align: center; font-size: 16px; margin: 20px 0;'><strong>📬 Check your email inbox for the professional booking acceptance notification!</strong></p>";
?>
