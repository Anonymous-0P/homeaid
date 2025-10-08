<?php
/**
 * Preview Professional Email Templates
 * 
 * This file generates and displays the improved professional email templates
 */

// Include email functions
require_once __DIR__ . '/includes/email_functions.php';

// Sample booking data for preview
$provider_booking_data = [
    'booking_id' => 'DEMO_12345',
    'customer_name' => 'John Smith',
    'customer_email' => 'john.smith@example.com',
    'provider_name' => 'Mike Johnson',
    'provider_email' => 'mike.johnson@example.com',
    'service_name' => 'Electrical Repair & Installation',
    'rate' => 750,
    'status' => 'pending'
];

$customer_booking_data = [
    'booking_id' => 'DEMO_12345',
    'customer_name' => 'John Smith',
    'customer_email' => 'john.smith@example.com',
    'provider_name' => 'Mike Johnson - Expert Electrician',
    'provider_email' => 'mike.johnson@example.com',
    'service_name' => 'Electrical Repair & Installation',
    'rate' => 750,
    'status' => 'pending'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Email Templates Preview - HomeAid</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .preview-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .preview-header {
            text-align: center;
            margin-bottom: 40px;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .preview-header h1 {
            color: #1a202c;
            margin-bottom: 10px;
            font-size: 32px;
            font-weight: 700;
        }
        .preview-header p {
            color: #4a5568;
            font-size: 18px;
            margin: 0;
        }
        .email-preview {
            margin-bottom: 60px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .preview-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 30px;
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }
        .email-content {
            padding: 0;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .feature-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .feature-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px;
        }
        .feature-desc {
            color: #4a5568;
            font-size: 15px;
        }
        .improvements-section {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 40px 0;
        }
        .improvements-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 24px;
            text-align: center;
        }
        .improvement-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }
        .improvement-item {
            display: flex;
            align-items: center;
            padding: 16px;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid #10b981;
        }
        .improvement-item::before {
            content: "✓";
            background: #10b981;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        @media (max-width: 768px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
            .improvement-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-header">
            <h1>🎨 Professional Email Templates</h1>
            <p>Beautiful, modern email designs for the HomeAid platform</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <div class="feature-title">Mobile Responsive</div>
                <div class="feature-desc">Perfectly optimized for all devices and email clients</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <div class="feature-title">Modern Design</div>
                <div class="feature-desc">Clean, professional layout with modern typography</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏠</div>
                <div class="feature-title">Branded Experience</div>
                <div class="feature-desc">Consistent HomeAid branding throughout</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <div class="feature-title">Call-to-Action</div>
                <div class="feature-desc">Clear, prominent buttons for better engagement</div>
            </div>
        </div>

        <div class="email-preview">
            <h2 class="preview-title">🔧 Provider Notification Email</h2>
            <div class="email-content">
                <?php echo createBookingEmailTemplate($provider_booking_data); ?>
            </div>
        </div>

        <div class="email-preview">
            <h2 class="preview-title">✅ Customer Confirmation Email</h2>
            <div class="email-content">
                <?php echo createCustomerBookingEmailTemplate($customer_booking_data); ?>
            </div>
        </div>

        <div class="improvements-section">
            <h2 class="improvements-title">🚀 Email Template Improvements</h2>
            <ul class="improvement-list">
                <li class="improvement-item">Modern system fonts for better readability</li>
                <li class="improvement-item">Gradient backgrounds with subtle patterns</li>
                <li class="improvement-item">Card-based layout for better organization</li>
                <li class="improvement-item">Improved color scheme and contrast</li>
                <li class="improvement-item">Professional typography and spacing</li>
                <li class="improvement-item">Enhanced mobile responsiveness</li>
                <li class="improvement-item">Step-by-step visual guides</li>
                <li class="improvement-item">Prominent call-to-action buttons</li>
                <li class="improvement-item">Status badges and visual indicators</li>
                <li class="improvement-item">Professional footer with branding</li>
                <li class="improvement-item">Shadow effects and depth</li>
                <li class="improvement-item">Consistent HomeAid brand identity</li>
            </ul>
        </div>
    </div>
</body>
</html>
