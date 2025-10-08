<?php
/**
 * Test Professional Email Templates
 * 
 * This file tests the improved professional email templates by sending actual emails
 */

// Include email functions
require_once __DIR__ . '/includes/email_functions.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>🎨 Testing Professional Email Templates</h2>";

// Sample booking data for testing
$booking_data = [
    'booking_id' => 'PROF_' . time(),
    'customer_name' => 'Sarah Wilson',
    'customer_email' => 'prakashkarekar4@gmail.com', // Your email for testing
    'provider_name' => 'Professional Electrician Services',
    'provider_email' => 'prakashkarekar4@gmail.com', // Your email for testing
    'service_name' => 'Complete Electrical System Upgrade',
    'rate' => 850,
    'status' => 'pending'
];

echo "<h3>📋 Test Booking Data:</h3>";
echo "<ul>";
foreach ($booking_data as $key => $value) {
    echo "<li><strong>" . ucwords(str_replace('_', ' ', $key)) . ":</strong> $value</li>";
}
echo "</ul>";

echo "<hr>";

echo "<h3>🔧 Testing Provider Notification Email (Professional Design)</h3>";

$provider_result = sendProviderBookingNotification($booking_data);

if ($provider_result) {
    echo "<div style='color: green; font-weight: bold; padding: 15px; background: linear-gradient(135deg, #d4edda, #c3e6cb); border: 1px solid #28a745; border-radius: 12px; margin: 15px 0; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);'>";
    echo "✅ <strong>Professional provider notification sent successfully!</strong><br>";
    echo "📧 Sent to: {$booking_data['provider_email']}<br>";
    echo "📋 Booking ID: {$booking_data['booking_id']}<br>";
    echo "🎨 Features: Modern design, gradients, professional typography";
    echo "</div>";
} else {
    echo "<div style='color: red; font-weight: bold; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 12px; margin: 15px 0;'>";
    echo "❌ <strong>Failed to send provider notification email!</strong>";
    echo "</div>";
}

echo "<hr>";

echo "<h3>👤 Testing Customer Confirmation Email (Professional Design)</h3>";

$customer_result = sendCustomerBookingConfirmation($booking_data);

if ($customer_result) {
    echo "<div style='color: green; font-weight: bold; padding: 15px; background: linear-gradient(135deg, #d4edda, #c3e6cb); border: 1px solid #28a745; border-radius: 12px; margin: 15px 0; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);'>";
    echo "✅ <strong>Professional customer confirmation sent successfully!</strong><br>";
    echo "📧 Sent to: {$booking_data['customer_email']}<br>";
    echo "📋 Booking ID: {$booking_data['booking_id']}<br>";
    echo "🎨 Features: Success design, step guides, professional layout";
    echo "</div>";
} else {
    echo "<div style='color: red; font-weight: bold; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 12px; margin: 15px 0;'>";
    echo "❌ <strong>Failed to send customer confirmation email!</strong>";
    echo "</div>";
}

echo "<hr>";

// Summary
if ($provider_result && $customer_result) {
    echo "<div style='color: white; font-size: 20px; font-weight: bold; padding: 30px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 16px; text-align: center; margin: 30px 0; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);'>";
    echo "🎉 PROFESSIONAL EMAIL TEMPLATES WORKING PERFECTLY! 🎉<br><br>";
    echo "✅ Modern provider notification: SUCCESS<br>";
    echo "✅ Professional customer confirmation: SUCCESS<br><br>";
    echo "📧 Both emails sent with enhanced professional styling<br>";
    echo "📬 Check your inbox for beautiful, modern email notifications!";
    echo "</div>";
} else {
    echo "<div style='color: orange; font-size: 18px; font-weight: bold; padding: 20px; background-color: #fff3cd; border: 2px solid #ffc107; border-radius: 10px; text-align: center; margin: 20px 0;'>";
    echo "⚠️ PARTIAL SUCCESS ⚠️<br><br>";
    echo "Provider notification: " . ($provider_result ? "✅ SUCCESS" : "❌ FAILED") . "<br>";
    echo "Customer confirmation: " . ($customer_result ? "✅ SUCCESS" : "❌ FAILED") . "<br><br>";
    echo "Check error logs for more details.";
    echo "</div>";
}

echo "<h3>🎨 Professional Design Features:</h3>";
echo "<div style='background: #f8fafc; padding: 20px; border-radius: 12px; border-left: 4px solid #667eea; margin: 20px 0;'>";
echo "<ul style='margin: 0; padding-left: 20px;'>";
echo "<li>🎨 <strong>Modern Typography:</strong> System fonts for better readability</li>";
echo "<li>🌈 <strong>Gradient Backgrounds:</strong> Beautiful color transitions</li>";
echo "<li>📱 <strong>Mobile Responsive:</strong> Perfect on all devices</li>";
echo "<li>🎯 <strong>Enhanced CTAs:</strong> Prominent, hover-effect buttons</li>";
echo "<li>📊 <strong>Status Badges:</strong> Visual status indicators</li>";
echo "<li>📋 <strong>Card Layouts:</strong> Organized information sections</li>";
echo "<li>🔢 <strong>Step Guides:</strong> Numbered action steps</li>";
echo "<li>🏠 <strong>Brand Consistency:</strong> HomeAid visual identity</li>";
echo "<li>💫 <strong>Shadow Effects:</strong> Modern depth and dimension</li>";
echo "<li>⚡ <strong>Professional Footer:</strong> Complete contact information</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: linear-gradient(135deg, #e6fffa, #b2f5ea); padding: 20px; border-radius: 12px; border: 1px solid #10b981; margin: 20px 0;'>";
echo "<h4 style='color: #1a202c; margin-top: 0;'>📧 Email Template Preview:</h4>";
echo "<p style='color: #2d3748; margin-bottom: 10px;'>View the professional email templates in your browser:</p>";
echo "<a href='email_preview.php' style='background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-block;'>🎨 View Email Preview</a>";
echo "</div>";

?>
