<?php
/**
 * Email Functions for HomeAid Application
 * 
 * This file contains functions for sending email notifications
 * using PHPMailer with Gmail SMTP support
 */

// Include PHPMailer
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';

// Include email configuration
require_once __DIR__ . '/../config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email notification using PHPMailer with Gmail SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (can be HTML)
 * @param string $from_name Sender name (optional)
 * @param string $from_email Sender email (optional)
 * @param bool $is_html Whether the message is HTML (default: true)
 * @return bool True if email was sent successfully, false otherwise
 */
function sendEmail($to, $subject, $message, $from_name = null, $from_email = null, $is_html = true, $reply_to = null, $reply_to_name = null) {
    // Use configured values if not provided
    $from_name = $from_name ?: SMTP_FROM_NAME;
    $from_email = $from_email ?: SMTP_FROM_EMAIL;
    
    // Validate email address
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: $to");
        return false;
    }
    
    try {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Fix for WAMP SSL certificate issues
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Debug settings
        if (defined('SMTP_DEBUG') && SMTP_DEBUG > 0) {
            $mail->SMTPDebug = SMTP_DEBUG;
            $mail->Debugoutput = 'error_log';
        }
        
        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to);
        // Use provided reply-to if given, else fall back to from
        if (!empty($reply_to) && filter_var($reply_to, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($reply_to, $reply_to_name ?: $reply_to);
        } else {
            $mail->addReplyTo($from_email, $from_name);
        }
        
        // Content
        $mail->isHTML($is_html);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        // If HTML, also set a plain text version
        if ($is_html) {
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));
        }
        
        // Send the email
        $result = $mail->send();
        
        if ($result) {
            error_log("Email sent successfully to: $to via Gmail SMTP");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Failed to send email to $to via Gmail SMTP: " . $e->getMessage());
        
    // Fallback to basic mail() function if enabled (use constant() to avoid static folding warnings)
    if (defined('EMAIL_FALLBACK') && (bool)constant('EMAIL_FALLBACK')) {
            error_log("Falling back to basic mail() function for: $to");
            return sendEmailFallback($to, $subject, $message, $from_name, $from_email, $is_html);
        }
        
        return false;
    }
}

/**
 * Fallback email function using basic PHP mail()
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body
 * @param string $from_name Sender name
 * @param string $from_email Sender email
 * @param bool $is_html Whether the message is HTML
 * @return bool True if email was sent successfully
 */
function sendEmailFallback($to, $subject, $message, $from_name, $from_email, $is_html) {
    // Prepare headers
    $headers = [];
    $headers[] = "From: $from_name <$from_email>";
    $headers[] = "Reply-To: $from_email";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    $headers[] = "X-Priority: 3";
    
    if ($is_html) {
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
    } else {
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
    }
    
    // Convert headers array to string
    $headers_string = implode("\r\n", $headers);
    
    // Clean subject line
    $subject = str_replace(["\r", "\n"], '', $subject);
    
    // Send email
    $result = mail($to, $subject, $message, $headers_string);
    
    // Log the result
    if ($result) {
        error_log("Email sent successfully to: $to via fallback mail()");
    } else {
        error_log("Failed to send email to: $to via fallback mail()");
    }
    
    return $result;
}

/**
 * Send booking notification to provider
 * 
 * @param array $booking_data Array containing booking information
 * @return bool True if email was sent successfully, false otherwise
 */
function sendProviderBookingNotification($booking_data) {
    $to = $booking_data['provider_email'];
    $subject = "New Booking Request - HomeAid Platform";
    
    // Create HTML email template
    $message = createBookingEmailTemplate($booking_data);
    
    return sendEmail($to, $subject, $message);
}

/**
 * Send booking confirmation to customer
 * 
 * @param array $booking_data Array containing booking information
 * @return bool True if email was sent successfully, false otherwise
 */
function sendCustomerBookingConfirmation($booking_data) {
    // Log confirmation email sending attempt
    error_log("CONFIRMATION EMAIL: Attempting to send booking confirmation for booking ID: " . $booking_data['booking_id'] . " to: " . $booking_data['customer_email']);
    
    $to = $booking_data['customer_email'];
    $subject = "Booking Confirmation - HomeAid Platform";
    
    // Create HTML email template for customer
    $message = createCustomerBookingEmailTemplate($booking_data);
    
    $result = sendEmail($to, $subject, $message);
    
    // Log result
    if ($result) {
        error_log("CONFIRMATION EMAIL: Successfully sent booking confirmation for booking ID: " . $booking_data['booking_id']);
    } else {
        error_log("CONFIRMATION EMAIL: Failed to send booking confirmation for booking ID: " . $booking_data['booking_id']);
    }
    
    return $result;
}

/**
 * Create HTML email template for provider booking notification
 * 
 * @param array $data Booking data
 * @return string HTML email content
 */
function createBookingEmailTemplate($data) {
    $booking_date = date('F j, Y \\a\\t g:i A');
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Booking - HomeAid</title>
<style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        background: #f3f4f6;
        margin: 0;
        padding: 0;
        color: #374151;
        line-height: 1.6;
    }
    .container {
        max-width: 640px;
        margin: 40px auto;
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    }
    .header {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: #fff;
        padding: 28px;
        text-align: center;
    }
    .header h1 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
    }
    .content {
        padding: 32px;
    }
    .content h2 {
        font-size: 18px;
        margin-bottom: 12px;
        font-weight: 600;
        color: #111827;
    }
    .details {
        margin-top: 24px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }
    .row {
        display: flex;
        justify-content: space-between;
        padding: 12px 16px;
        font-size: 14px;
    }
    .row:nth-child(odd) {
        background: #f9fafb;
    }
    .label {
        color: #6b7280;
        font-weight: 500;
    }
    .value {
        font-weight: 600;
        color: #111827;
    }
    .cta {
        text-align: center;
        margin: 30px 0;
    }
    .cta a {
        display: inline-block;
        background: #10b981;
        color: #fff;
        padding: 14px 28px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        transition: background 0.3s ease;
    }
    .cta a:hover {
        background: #059669;
    }
    .footer {
        background: #f9fafb;
        color: #6b7280;
        text-align: center;
        padding: 20px;
        font-size: 12px;
        border-top: 1px solid #e5e7eb;
    }
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📩 New Booking Request</h1>
        </div>
        <div class="content">
            <p>Hello <strong>' . htmlspecialchars($data['provider_name']) . '</strong>,</p>
            <p>You have received a new booking through <strong>HomeAid</strong>. Please review the details below and take action.</p>
            
            <div class="details">
                <div class="row"><span class="label">Booking ID</span><span class="value">#' . htmlspecialchars($data['booking_id']) . '</span></div>
                <div class="row"><span class="label">Customer</span><span class="value">' . htmlspecialchars($data['customer_name']) . '</span></div>
                <div class="row"><span class="label">Email</span><span class="value">' . htmlspecialchars($data['customer_email']) . '</span></div>
                <div class="row"><span class="label">Service</span><span class="value">' . htmlspecialchars($data['service_name']) . '</span></div>
                <div class="row"><span class="label">Rate</span><span class="value">₹' . number_format($data['rate'], 0) . '/hr</span></div>
                <div class="row"><span class="label">Request Date</span><span class="value">' . $booking_date . '</span></div>
                <div class="row"><span class="label">Status</span><span class="value" style="color:#2563eb;">Pending</span></div>
            </div>
            
            <div class="cta">
                <a href="localhost/homeaid/provider/login.php">View Booking</a>
            </div>
        </div>
        <div class="footer">
            © ' . date('Y') . ' HomeAid · All rights reserved<br>
            This is an automated message, please do not reply.
        </div>
    </div>
</body>
</html>';

    return $html;
}

/**
 * Create HTML email template for customer booking confirmation
 * 
 * @param array $data Booking data
 * @return string HTML email content
 */
function createCustomerBookingEmailTemplate($data) {
        $booking_date = date('F j, Y \\a\\t g:i A');
        $rate_display = is_numeric($data['rate'] ?? null) ? number_format((float)$data['rate'], 2) : htmlspecialchars((string)($data['rate'] ?? ''));
        $base = function_exists('getBaseUrl') ? rtrim(getBaseUrl(), '/') : '';
        $dashboardUrl = $base . '/customer/my_bookings.php';
    
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Confirmation - HomeAid</title>
<style>
        body {font-family: Arial, Helvetica, sans-serif; background:#f3f4f6; margin:0; padding:0; color:#243043; line-height:1.6;}
        .wrapper {max-width:640px; margin:40px auto; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 6px 20px rgba(0,0,0,0.06);}    
        .header {background:linear-gradient(135deg,#10b981,#059669); color:#fff; padding:28px; text-align:center;}
        .header h1 {margin:0; font-size:22px; font-weight:700;}
        .content {padding:32px;}
        .content h2 {font-size:18px; margin:0 0 12px; font-weight:600; color:#111827;}
        .notice {background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 16px; border-radius:10px; margin:12px 0 22px; font-size:14px;}
        .details {margin-top:12px; border-radius:10px; overflow:hidden; border:1px solid #e5e7eb;}
        .row {display:flex; justify-content:space-between; padding:12px 16px; font-size:14px;}
        .row:nth-child(odd) {background:#f9fafb;}
        .label {color:#6b7280; font-weight:500;}
        .value {font-weight:600; color:#111827;}
        .cta {text-align:center; margin:26px 0 6px;}
        .cta a {display:inline-block; background:#2563eb; color:#fff; padding:12px 22px; border-radius:8px; text-decoration:none; font-weight:600; font-size:15px;}
        .footer {background:#f9fafb; color:#6b7280; text-align:center; padding:18px; font-size:12px; border-top:1px solid #e5e7eb;}
        @media (max-width:620px){ .wrapper{margin:0; border-radius:0;} .content{padding:24px;} }
        .muted {color:#6b7280; font-size:12px;}
        .status {background:#fef3c7; color:#92400e; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700; letter-spacing:.3px;}
        .rate {white-space:nowrap;}
        .id {white-space:nowrap;}
        .nowrap {white-space:nowrap;}
        .icon {margin-right:6px;}
        .title {display:flex; align-items:center; gap:8px;}
        .title .icon {font-size:18px;}
        .pill {display:inline-block; padding:4px 10px; border-radius:999px; background:#e5e7eb; color:#374151; font-size:12px; font-weight:600;}
        .success {background:#d1fae5; color:#065f46;}
        .pending {background:#fef3c7; color:#92400e;}
        .info {background:#dbeafe; color:#1e40af;}
        .divider {height:1px; background:#e5e7eb; margin:18px 0;}
        .small {font-size:12px;}
        .tag {background:#f3f4f6; border:1px solid #e5e7eb; color:#374151; padding:3px 8px; border-radius:6px; font-size:12px; display:inline-block;}
        .space {height:6px;}
        .grid {display:grid; grid-template-columns:1fr 1fr; gap:10px;}
        .grid .row {display:flex; justify-content:space-between; align-items:center; padding:10px 12px; background:#ffffff; border:1px solid #e5e7eb; border-radius:10px;}
        .grid .row .label {font-size:12px;}
</style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>✅ Booking Request Submitted</h1>
        </div>
        <div class="content">
            <div class="title"><span class="icon">👋</span><h2>Hello ' . htmlspecialchars($data['customer_name']) . ',</h2></div>
            <p>Thanks for choosing <strong>HomeAid</strong>! Your booking request has been sent to <strong>' . htmlspecialchars($data['provider_name']) . '</strong>.</p>
            <div class="notice"><strong>Status:</strong> <span class="status">Pending Provider Response</span></div>
            <div class="details">
                <div class="row"><span class="label">Booking ID</span><span class="value id">#' . htmlspecialchars($data['booking_id']) . '</span></div>
                <div class="row"><span class="label">Service</span><span class="value">' . htmlspecialchars($data['service_name']) . '</span></div>
                <div class="row"><span class="label">Provider</span><span class="value">' . htmlspecialchars($data['provider_name']) . '</span></div>
                <div class="row"><span class="label">Rate</span><span class="value rate">₹' . $rate_display . '/hr</span></div>
                <div class="row"><span class="label">Requested On</span><span class="value">' . $booking_date . '</span></div>
            </div>
            <div class="cta"><a href="' . htmlspecialchars($dashboardUrl) . '">View My Bookings</a></div>
            <div class="divider"></div>
            <p class="small muted">You will receive an email when the provider responds. You can also track the status from your dashboard.</p>
        </div>
        <div class="footer">© ' . date('Y') . ' HomeAid • This is an automated message. Please do not reply.</div>
    </div>
</body>
</html>';

        return $html;
}
// function createCustomerBookingEmailTemplate($data) {
//     $booking_date = date('F j, Y \\a\\t g:i A');
    
//     $html = '<!DOCTYPE html>
// <html lang="en">
// <head>
//     <meta charset="UTF-8">
//     <meta name="viewport" content="width=device-width, initial-scale=1.0">
//     <title>Booking Confirmation - HomeAid</title>
//     <style>
//         * {
//             margin: 0;
//             padding: 0;
//             box-sizing: border-box;
//         }
//         body {
//             font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
//             line-height: 1.7;
//             color: #2d3748;
//             background-color: #f7fafc;
//             margin: 0;
//             padding: 0;
//         }
//         .email-container {
//             max-width: 650px;
//             margin: 0 auto;
//             background-color: #ffffff;
//             box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
//             border-radius: 16px;
//             overflow: hidden;
//         }
//         .header {
//             background: linear-gradient(135deg, #10b981 0%, #059669 100%);
//             color: white;
//             padding: 40px 30px;
//             text-align: center;
//             position: relative;
//         }
//         .header::before {
//             content: "";
//             position: absolute;
//             top: 0;
//             left: 0;
//             right: 0;
//             bottom: 0;
//             background: url("data:image/svg+xml,%3Csvg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Cpath d="m0 40l40-40h-40v40zm40 0v-40h-40l40 40z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
//         }
//         .header-content {
//             position: relative;
//             z-index: 1;
//         }
//         .logo {
//             font-size: 28px;
//             font-weight: 800;
//             margin-bottom: 8px;
//             letter-spacing: -0.5px;
//         }
//         .header h1 {
//             font-size: 24px;
//             font-weight: 600;
//             margin: 16px 0 8px 0;
//             letter-spacing: -0.3px;
//         }
//         .header p {
//             font-size: 16px;
//             opacity: 0.9;
//             margin: 0;
//         }
//         .content {
//             background: #ffffff;
//             padding: 40px 30px;
//         }
//         .greeting {
//             font-size: 20px;
//             font-weight: 600;
//             color: #1a202c;
//             margin-bottom: 24px;
//         }
//         .intro-text {
//             font-size: 16px;
//             color: #4a5568;
//             margin-bottom: 32px;
//             line-height: 1.6;
//         }
//         .success-alert {
//             background: linear-gradient(135deg, #10b981, #059669);
//             color: white;
//             padding: 24px;
//             border-radius: 12px;
//             text-align: center;
//             margin-bottom: 32px;
//             box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
//         }
//         .success-alert h3 {
//             font-size: 18px;
//             font-weight: 700;
//             margin-bottom: 8px;
//         }
//         .success-alert p {
//             font-size: 15px;
//             opacity: 0.9;
//             margin: 0;
//         }
//         .booking-card {
//             background: #f8fafc;
//             border: 1px solid #e2e8f0;
//             border-radius: 16px;
//             padding: 32px;
//             margin: 32px 0;
//             position: relative;
//             overflow: hidden;
//         }
//         .booking-card::before {
//             content: "";
//             position: absolute;
//             top: 0;
//             left: 0;
//             width: 6px;
//             height: 100%;
//             background: linear-gradient(135deg, #10b981, #059669);
//         }
//         .card-title {
//             font-size: 18px;
//             font-weight: 700;
//             color: #1a202c;
//             margin-bottom: 24px;
//             display: flex;
//             align-items: center;
//         }
//         .detail-grid {
//             display: grid;
//             gap: 16px;
//         }
//         .detail-item {
//             display: flex;
//             justify-content: space-between;
//             align-items: center;
//             padding: 16px 0;
//             border-bottom: 1px solid #e2e8f0;
//         }
//         .detail-item:last-child {
//             border-bottom: none;
//         }
//         .detail-label {
//             font-weight: 600;
//             color: #4a5568;
//             font-size: 14px;
//             text-transform: uppercase;
//             letter-spacing: 0.5px;
//         }
//         .detail-value {
//             color: #1a202c;
//             font-weight: 600;
//             font-size: 15px;
//         }
//         .status-pending {
//             background: #fef3c7;
//             color: #92400e;
//             padding: 6px 12px;
//             border-radius: 20px;
//             font-size: 13px;
//             font-weight: 600;
//             text-transform: uppercase;
//             letter-spacing: 0.3px;
//         }
//         .info-section {
//             background: #e6fffa;
//             border: 1px solid #81e6d9;
//             border-radius: 16px;
//             padding: 32px;
//             margin: 32px 0;
//             border-left: 6px solid #10b981;
//         }
//         .info-title {
//             font-size: 18px;
//             font-weight: 700;
//             color: #1a202c;
//             margin-bottom: 20px;
//             display: flex;
//             align-items: center;
//         }
//         .info-list {
//             list-style: none;
//             padding: 0;
//             margin: 0;
//         }
//         .info-item {
//             display: flex;
//             align-items: flex-start;
//             margin-bottom: 12px;
//             padding: 12px 0;
//             border-bottom: 1px solid #b2f5ea;
//         }
//         .info-item:last-child {
//             border-bottom: none;
//             margin-bottom: 0;
//         }
//         .info-icon {
//             background: #10b981;
//             color: white;
//             width: 24px;
//             height: 24px;
//             border-radius: 50%;
//             display: flex;
//             align-items: center;
//             justify-content: center;
//             font-size: 12px;
//             margin-right: 16px;
//             flex-shrink: 0;
//         }
//         .info-text {
//             color: #2d3748;
//             font-size: 15px;
//             line-height: 1.5;
//         }
//         .cta-section {
//             text-align: center;
//             margin: 40px 0;
//             padding: 32px;
//             background: linear-gradient(135deg, #f7fafc, #edf2f7);
//             border-radius: 16px;
//             border: 1px solid #e2e8f0;
//         }
//         .cta-button {
//             background: linear-gradient(135deg, #667eea, #764ba2);
//             color: white;
//             padding: 16px 32px;
//             text-decoration: none;
//             border-radius: 50px;
//             display: inline-block;
//             font-weight: 600;
//             font-size: 16px;
//             transition: all 0.3s ease;
//             box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
//             border: none;
//             cursor: pointer;
//         }
//         .cta-button:hover {
//             transform: translateY(-2px);
//             box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
//         }
//         .footer {
//             background: #1a202c;
//             color: #a0aec0;
//             padding: 32px 30px;
//             text-align: center;
//             font-size: 14px;
//             line-height: 1.6;
//         }
//         .footer-logo {
//             color: #ffffff;
//             font-size: 20px;
//             font-weight: 700;
//             margin-bottom: 12px;
//         }
//         .footer-links {
//             margin: 16px 0;
//         }
//         .footer-link {
//             color: #10b981;
//             text-decoration: none;
//             margin: 0 8px;
//         }
//         .support-section {
//             background: #f7fafc;
//             border-radius: 12px;
//             padding: 24px;
//             margin: 24px 0;
//             text-align: center;
//             border: 1px solid #e2e8f0;
//         }
//         .support-section h3 {
//             color: #1a202c;
//             font-size: 18px;
//             font-weight: 700;
//             margin-bottom: 12px;
//         }
//         .support-section p {
//             color: #4a5568;
//             font-size: 15px;
//             margin: 0;
//         }
//         @media only screen and (max-width: 600px) {
//             .email-container {
//                 margin: 0;
//                 border-radius: 0;
//             }
//             .header {
//                 padding: 30px 20px;
//             }
//             .content {
//                 padding: 30px 20px;
//             }
//             .booking-card {
//                 padding: 24px;
//             }
//             .info-section {
//                 padding: 24px;
//             }
//             .detail-item {
//                 flex-direction: column;
//                 align-items: flex-start;
//                 gap: 8px;
//             }
//             .cta-section {
//                 padding: 24px;
//             }
//         }
//     </style>
// </head>
// <body>
//     <div class="email-container">
//         <div class="header">
//             <div class="header-content">
//                 <div class="logo">HomeAid</div>
//                 <h1>Booking Confirmed!</h1>
//                 <p>Your service request has been submitted successfully</p>
//             </div>
//         </div>
        
//         <div class="content">
//             <div class="greeting">Hello ' . htmlspecialchars($data['customer_name']) . ',</div>
            
//             <div class="intro-text">
//                 Thank you for choosing HomeAid for your home service needs! Your booking request has been 
//                 successfully submitted and sent to our professional service provider.
//             </div>
            
//             <div class="success-alert">
//                 <h3>Request Submitted Successfully!</h3>
//                 <p>Your service provider will review your request and respond shortly</p>
//             </div>
            
//             <div class="booking-card">
//                 <div class="card-title">
//                     Your Booking Details
//                 </div>
                
//                 <div class="detail-grid">
//                     <div class="detail-item">
//                         <span class="detail-label">Booking ID</span>
//                         <span class="detail-value">#' . htmlspecialchars($data['booking_id']) . '</span>
//                     </div>
                    
//                     <div class="detail-item">
//                         <span class="detail-label">Service Requested</span>
//                         <span class="detail-value">' . htmlspecialchars($data['service_name']) . '</span>
//                     </div>
                    
//                     <div class="detail-item">
//                         <span class="detail-label">Service Provider</span>
//                         <span class="detail-value">' . htmlspecialchars($data['provider_name']) . '</span>
//                     </div>
                    
//                     <div class="detail-item">
//                         <span class="detail-label">Service Rate</span>
//                         <span class="detail-value">₹' . number_format($data['rate'], 0) . '/hour</span>
//                     </div>
                    
//                     <div class="detail-item">
//                         <span class="detail-label">Request Date</span>
//                         <span class="detail-value">' . $booking_date . '</span>
//                     </div>
                    
//                     <div class="detail-item">
//                         <span class="detail-label">Current Status</span>
//                         <span class="status-pending">Awaiting Provider Response</span>
//                     </div>
//                 </div>
//             </div>
            
//             <div class="info-section">
//                 <div class="info-title">
//                     What Happens Next?
//                 </div>
//                 <ul class="info-list">
//                     <li class="info-item">
//                         <div class="info-icon">1</div>
//                         <div class="info-text">Your service provider will review your booking request</div>
//                     </li>
//                     <li class="info-item">
//                         <div class="info-icon">2</div>
//                         <div class="info-text">You\'ll receive an email notification when they respond</div>
//                     </li>
//                     <li class="info-item">
//                         <div class="info-icon">3</div>
//                         <div class="info-text">If accepted, the provider will contact you to schedule the service</div>
//                     </li>
//                     <li class="info-item">
//                         <div class="info-icon">4</div>
//                         <div class="info-text">Track your booking status in your customer dashboard</div>
//                     </li>
//                 </ul>
//             </div>
            
//             <div class="cta-section">
//                 <h3 style="margin-bottom: 16px; color: #1a202c;">Track Your Booking</h3>
//                 <a href="/customer/my_bookings.php" class="cta-button">
//                     View My Bookings
//                 </a>
//             </div>
            
//             <div class="support-section">
//                 <h3>Need Assistance?</h3>
//                 <p>Our customer support team is here to help! Contact us if you have any questions about your booking or need to make changes.</p>
//             </div>
//         </div>
        
//         <div class="footer">
//             <div class="footer-logo">HomeAid Platform</div>
//             <p>Your trusted partner for professional home services and skilled technicians.</p>
//             <div class="footer-links">
//                 <a href="#" class="footer-link">Help Center</a>
//                 <a href="#" class="footer-link">Booking Guide</a>
//                 <a href="#" class="footer-link">Contact Support</a>
//             </div>
//             <p style="margin-top: 20px; font-size: 12px;">
//                 © ' . date('Y') . ' HomeAid Platform. All rights reserved.<br>
//                 This email was sent to ' . htmlspecialchars($data['customer_email']) . '
//             </p>
//         </div>
//     </div>
// </body>
// </html>';

//     return $html;
// }

/**
 * Get the base URL of the application
 * 
 * @return string Base URL
 */
function getBaseUrl() {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    // Use HTTP on localhost where SSL is usually not configured
    $isLocal = (bool)preg_match('/^(localhost|127\.0\.0\.1)(:\\d+)?$/i', $host);
    $protocol = $isLocal ? 'http' : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
    // Prefer SCRIPT_NAME for current executing script path; fallback to PHP_SELF
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/');
    $path = rtrim(str_replace('\\', '/', dirname($script)), '/');
    // Strip known app subdirectories from the end to get the app root
    // e.g., /homeaid/customer -> /homeaid
    $path = preg_replace('#/(customer|provider|admin|Auth|includes|api|services)(/.*)?$#i', '', $path);
    if ($path === '') { $path = '/'; }
    return $protocol . '://' . $host . $path;
}

/**
 * Send booking status update notification
 * 
 * @param array $booking_data Booking data including status
 * @param string $recipient 'customer' or 'provider'
 * @return bool True if email was sent successfully
 */
function sendBookingStatusUpdate($booking_data, $recipient = 'customer') {
    if ($recipient === 'customer') {
        $to = $booking_data['customer_email'];
        $subject = "Booking Update - " . ucfirst($booking_data['status']);
    } else {
        $to = $booking_data['provider_email'];
        $subject = "Booking Update - " . ucfirst($booking_data['status']);
    }
    
    $message = createBookingStatusUpdateTemplate($booking_data, $recipient);
    
    return sendEmail($to, $subject, $message);
}

/**
 * Create booking status update email template
 * 
 * @param array $data Booking data
 * @param string $recipient 'customer' or 'provider'
 * @return string HTML email content
 */
function createBookingStatusUpdateTemplate($data, $recipient) {
    $status = $data['status'];
    $status_color = [
        'accepted' => '#10b981',
        'rejected' => '#ef4444',
        'completed' => '#6366f1',
        'cancelled' => '#f59e0b'
    ][$status] ?? '#6b7280';
    
    $status_icon = [
        'accepted' => '✅',
        'rejected' => '❌',
        'completed' => '🎉',
        'cancelled' => '⚠️'
    ][$status] ?? '📋';
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, ' . $status_color . ' 0%, ' . $status_color . 'cc 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px 20px;
            border-radius: 0 0 10px 10px;
            border: 1px solid #e9ecef;
        }
        .booking-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid ' . $status_color . ';
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . $status_icon . ' Booking ' . ucfirst($status) . '</h1>
        <p>Your booking status has been updated</p>
    </div>
    
    <div class="content">
        <h2>Hello ' . htmlspecialchars($data[$recipient . '_name']) . ',</h2>
        
        <p>Your booking (#' . htmlspecialchars($data['booking_id']) . ') status has been updated to <strong>' . ucfirst($status) . '</strong>.</p>
        
        <div class="booking-details">
            <h3>📋 Booking Information</h3>
            <p><strong>Service:</strong> ' . htmlspecialchars($data['service_name']) . '</p>
            <p><strong>Booking ID:</strong> #' . htmlspecialchars($data['booking_id']) . '</p>
            <p><strong>Status:</strong> <span style="color: ' . $status_color . '; font-weight: bold;">' . ucfirst($status) . '</span></p>
        </div>
        
        <p>Please check your dashboard for more details and any required actions.</p>
    </div>
    
    <div class="footer">
        <p>© ' . date('Y') . ' HomeAid Platform. All rights reserved.</p>
        <p>This is an automated notification. Please do not reply to this email.</p>
    </div>
</body>
</html>';

    return $html;
}

/**
 * Send email notification to customer when provider accepts booking
 * 
 * @param array $booking_data Array containing booking information
 * @return bool True if email was sent successfully, false otherwise
 */
function sendBookingAcceptedNotification($booking_data) {
    $subject = "Booking Accepted - {$booking_data['service_name']}";
    
    $message = createBookingAcceptedEmailTemplate($booking_data);
    
    return sendEmail(
        $booking_data['customer_email'], 
        $subject, 
        $message,
        'HomeAid Platform'
    );
}

/**
 * Create HTML email template for booking acceptance notification
 * 
 * @param array $data Booking data
 * @return string HTML email template
 */
function createBookingAcceptedEmailTemplate($data) {
    $booking_date = date('F j, Y \\a\\t g:i A');
    
    // Simplified acceptance template aligned with visual blocks demonstrated in test_acceptance_notification.php
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Accepted - HomeAid</title>
<style>
    body {font-family: Arial,Helvetica,sans-serif; background:#f4f6f8; margin:0; padding:24px; line-height:1.6; color:#243043;}
    .wrapper {max-width:640px; margin:0 auto; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,0.08);}
    .header {background:#28a745; background:linear-gradient(135deg,#28a745,#20c997); padding:36px 30px; text-align:center; color:#fff; position:relative;}
    .header h1 {margin:12px 0 4px; font-size:28px; letter-spacing:-0.5px;}
    .header p {margin:0; opacity:.92; font-size:15px;}
    .icon {font-size:52px; line-height:1;}
    .content {padding:38px 34px 32px;}
    h2 {margin:0 0 18px; font-size:20px; color:#1a1f29;}
    .success-box {background:#d4edda; border:2px solid #28a745; color:#155724; padding:22px 20px; border-radius:12px; text-align:center; margin-bottom:30px;}
    .success-box h2 {margin:0 0 8px; font-size:20px; color:#155724;}
    .details-card {background:#f8f9fa; border:1px solid #e2e8f0; border-left:5px solid #28a745; border-radius:14px; padding:26px 26px 6px; margin:6px 0 32px;}
    .card-title {margin:-10px -10px 18px; padding:12px 18px; background:#28a745; color:#fff; font-weight:600; border-radius:10px; font-size:15px;}
    .detail-row {display:flex; justify-content:space-between; padding:10px 0 14px; border-bottom:1px solid #e5e7eb; font-size:14px;}
    .detail-row:last-child {border-bottom:none;}
    .label {font-weight:600; color:#495057; text-transform:uppercase; letter-spacing:.4px; font-size:11px; display:block; margin-bottom:4px;}
    .value {font-weight:600; color:#1f2933; font-size:14px;}
    .status {color:#28a745; font-weight:700; font-size:13px; background:#e3f9e5; padding:4px 10px; border-radius:30px; display:inline-block; letter-spacing:.5px;}
    .provider-panel {background:#e3f2fd; border:1px solid #90caf9; border-radius:14px; padding:24px 24px 10px; margin:0 0 30px;}
    .provider-panel h3 {margin:0 0 14px; font-size:16px; color:#0d47a1; display:flex; align-items:center; gap:8px;}
    .next-steps {background:#fff3cd; border:1px solid #ffe08a; border-left:6px solid #ffc107; border-radius:14px; padding:26px 26px 10px; margin:0 0 34px;}
    .next-steps h3 {margin:0 0 14px; font-size:16px; color:#704c00;}
    .steps {margin:0; padding:0; list-style:none;}
    .steps li {background:#ffffff; margin:0 0 12px; padding:12px 14px 12px 16px; border-radius:10px; font-size:14px; border-left:4px solid #ffc107; box-shadow:0 1px 2px rgba(0,0,0,0.04);} 
    .steps li:before {content:"✓"; color:#28a745; font-weight:700; margin-right:8px;}
    .cta {text-align:center; margin:10px 0 34px;}
    .cta a {background:linear-gradient(135deg,#28a745,#20c997); color:#fff; text-decoration:none; padding:15px 32px; font-size:15px; font-weight:600; border-radius:40px; display:inline-block; box-shadow:0 4px 15px rgba(32,201,151,0.35); transition:.3s;}
    .cta a:hover {transform:translateY(-3px); box-shadow:0 8px 22px rgba(32,201,151,0.45);}    
    .support {background:#e8f5e8; border:1px solid #b7e4c7; padding:22px 20px; text-align:center; border-radius:12px; margin:0 0 8px;}
    .support h3 {margin:0 0 8px; font-size:16px; color:#1b5e20;}
    .footer {background:#1f2933; color:#9aa5b1; padding:26px 26px 32px; font-size:12px; text-align:center; border-radius:0 0 14px 14px;}
    .footer a {color:#28a745; text-decoration:none;}
    @media (max-width:620px){body{padding:0;} .wrapper{border-radius:0;} .content{padding:30px 22px;} .details-card,.provider-panel,.next-steps{padding:22px 22px 6px;} .detail-row{flex-direction:column; align-items:flex-start;} .cta a{width:100%; text-align:center;}}
</style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
        <div class="icon">✓</div>
        <h1>Booking Accepted</h1>
        <p>Your service request has been confirmed</p>
    </div>
    <div class="content">
        <div class="success-box">
            <h2>Great News, ' . htmlspecialchars($data['customer_name']) . '!</h2>
            <p>Your booking for <strong>' . htmlspecialchars($data['service_name']) . '</strong> has been accepted. The provider will reach out soon to coordinate details.</p>
        </div>
        <div class="details-card">
            <div class="card-title">Booking Details</div>
            <div class="detail-row">
                <div style="flex:1">
                    <span class="label">Booking ID</span>
                    <div class="value">#' . htmlspecialchars($data['booking_id']) . '</div>
                </div>
                <div style="flex:1">
                    <span class="label">Status</span>
                    <div class="value status">ACCEPTED</div>
                </div>
            </div>
            <div class="detail-row">
                <div style="flex:1">
                    <span class="label">Service</span>
                    <div class="value">' . htmlspecialchars($data['service_name']) . '</div>
                </div>
                <div style="flex:1">
                    <span class="label">Rate</span>
                    <div class="value">₹' . htmlspecialchars($data['rate']) . '/hour</div>
                </div>
            </div>
            <div class="detail-row">
                <div style="flex:1">
                    <span class="label">Accepted On</span>
                    <div class="value">' . $booking_date . '</div>
                </div>
            </div>
        </div>
        <div class="provider-panel">
            <h3>Provider Information</h3>
            <div class="detail-row" style="border-bottom:1px solid #bbdefb;">
                <div style="flex:1">
                    <span class="label">Provider Name</span>
                    <div class="value">' . htmlspecialchars($data['provider_name']) . '</div>
                </div>
                <div style="flex:1">
                    <span class="label">Provider Email</span>
                    <div class="value">' . htmlspecialchars($data['provider_email']) . '</div>
                </div>
            </div>
            <div style="padding:14px 0 4px; font-size:13px; color:#0d47a1;">They may contact you soon to discuss scheduling & specific requirements.</div>
        </div>
        <div class="next-steps">
            <h3>What Happens Next?</h3>
            <ul class="steps">
                <li>Provider contacts you to confirm schedule and details</li>
                <li>You discuss any special requirements or materials</li>
                <li>Service is performed at the scheduled time</li>
                <li>Payment processed after successful completion</li>
                <li>Leave a rating & review in your dashboard</li>
            </ul>
        </div>
        <div class="cta">
            <a href="localhost/homeaid/customer/login.php">View My Dashboard</a>
        </div>
        <div class="support">
            <h3>Need Help?</h3>
            <p style="margin:0; font-size:14px; color:#2f4f2f;">Our support team is ready to assist you with any questions regarding this booking.</p>
        </div>
    </div>
    <div class="footer">
        <p><strong>HomeAid Platform</strong> • Trusted home services</p>
        <p style="margin:8px 0 0;">This email relates to booking #' . htmlspecialchars($data['booking_id']) . '</p>
        <p style="margin:8px 0 0;">&copy; ' . date('Y') . ' HomeAid Platform. All rights reserved.</p>
        <p style="margin:10px 0 0;"><a href="#">Unsubscribe</a> • <a href="#">Privacy</a> • <a href="#">Support</a></p>
    </div>
  </div>
</body>
</html>';

    return $html;
}

/**
 * Send email notification to customer when provider rejects booking
 * 
 * @param array $booking_data Array containing booking information
 * @return bool True if email was sent successfully, false otherwise
 */
function sendBookingRejectedNotification($booking_data) {
    // Log email sending attempt
    error_log("REJECTION EMAIL: Attempting to send rejection email for booking ID: " . $booking_data['booking_id'] . " to: " . $booking_data['customer_email']);
    
    $subject = "Booking Declined - {$booking_data['service_name']}";
    
    $message = createBookingRejectedEmailTemplate($booking_data);
    
    // Log that we're using rejection template
    error_log("REJECTION EMAIL: Using rejection template for booking ID: " . $booking_data['booking_id']);
    
    $result = sendEmail(
        $booking_data['customer_email'], 
        $subject, 
        $message,
        'HomeAid Platform'
    );
    
    // Log result
    if ($result) {
        error_log("REJECTION EMAIL: Successfully sent rejection email for booking ID: " . $booking_data['booking_id']);
    } else {
        error_log("REJECTION EMAIL: Failed to send rejection email for booking ID: " . $booking_data['booking_id']);
    }
    
    return $result;
}

/**
 * Create HTML email template for booking rejection notification
 * 
 * @param array $data Booking data
 * @return string HTML email template
 */
function createBookingRejectedEmailTemplate($data) {
    $booking_date = date('F j, Y \\a\\t g:i A');
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Declined - HomeAid</title>
<style>
    body {font-family: Arial,Helvetica,sans-serif; background:#f4f6f8; margin:0; padding:24px; line-height:1.6; color:#243043;}
    .wrapper {max-width:640px; margin:0 auto; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,0.08);}
    .header {background:#dc3545; background:linear-gradient(135deg,#dc3545,#c82333); padding:36px 30px; text-align:center; color:#fff; position:relative;}
    .header h1 {margin:12px 0 4px; font-size:28px; letter-spacing:-0.5px;}
    .header p {margin:0; opacity:.92; font-size:15px;}
    .icon {font-size:52px; line-height:1;}
    .content {padding:38px 34px 32px;}
    h2 {margin:0 0 18px; font-size:20px; color:#1a1f29;}
    .rejection-box {background:#f8d7da; border:2px solid #dc3545; color:#721c24; padding:22px 20px; border-radius:12px; text-align:center; margin-bottom:30px;}
    .rejection-box h2 {margin:0 0 8px; font-size:20px; color:#721c24;}
    .details-card {background:#f8f9fa; border:1px solid #e2e8f0; border-left:5px solid #dc3545; border-radius:14px; padding:26px 26px 6px; margin:6px 0 32px;}
    .card-title {margin:-10px -10px 18px; padding:12px 18px; background:#dc3545; color:#fff; font-weight:600; border-radius:10px; font-size:15px;}
    .detail-row {display:flex; justify-content:space-between; padding:10px 0 14px; border-bottom:1px solid #e5e7eb; font-size:14px;}
    .detail-row:last-child {border-bottom:none;}
    .label {font-weight:600; color:#495057; text-transform:uppercase; letter-spacing:.4px; font-size:11px; display:block; margin-bottom:4px;}
    .value {font-weight:600; color:#1f2933; font-size:14px;}
    .status {color:#dc3545; font-weight:700; font-size:13px; background:#f8d7da; padding:4px 10px; border-radius:30px; display:inline-block; letter-spacing:.5px;}
    .alternative-panel {background:#fff3cd; border:1px solid #ffc107; border-radius:14px; padding:24px 24px 10px; margin:0 0 30px;}
    .alternative-panel h3 {margin:0 0 14px; font-size:16px; color:#856404; display:flex; align-items:center; gap:8px;}
    .next-steps {background:#e3f2fd; border:1px solid #90caf9; border-left:6px solid #2196f3; border-radius:14px; padding:26px 26px 10px; margin:0 0 34px;}
    .next-steps h3 {margin:0 0 14px; font-size:16px; color:#0d47a1;}
    .steps {margin:0; padding:0; list-style:none;}
    .steps li {background:#ffffff; margin:0 0 12px; padding:12px 14px 12px 16px; border-radius:10px; font-size:14px; border-left:4px solid #2196f3; box-shadow:0 1px 2px rgba(0,0,0,0.04);} 
    .steps li:before {content:"→"; color:#2196f3; font-weight:700; margin-right:8px;}
    .cta {text-align:center; margin:10px 0 34px;}
    .cta a {background:linear-gradient(135deg,#007bff,#0056b3); color:#fff; text-decoration:none; padding:15px 32px; font-size:15px; font-weight:600; border-radius:40px; display:inline-block; box-shadow:0 4px 15px rgba(0,123,255,0.35); transition:.3s;}
    .cta a:hover {transform:translateY(-3px); box-shadow:0 8px 22px rgba(0,123,255,0.45);}    
    .support {background:#e8f5e8; border:1px solid #b7e4c7; padding:22px 20px; text-align:center; border-radius:12px; margin:0 0 8px;}
    .support h3 {margin:0 0 8px; font-size:16px; color:#1b5e20;}
    .footer {background:#1f2933; color:#9aa5b1; padding:26px 26px 32px; font-size:12px; text-align:center; border-radius:0 0 14px 14px;}
    .footer a {color:#007bff; text-decoration:none;}
    @media (max-width:620px){body{padding:0;} .wrapper{border-radius:0;} .content{padding:30px 22px;} .details-card,.alternative-panel,.next-steps{padding:22px 22px 6px;} .detail-row{flex-direction:column; align-items:flex-start;} .cta a{width:100%; text-align:center;}}
</style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
        <div class="icon">X</div>
        <h1>Booking Declined</h1>
        <p>Unfortunately, your service request was not accepted</p>
    </div>
    <div class="content">
        <div class="rejection-box">
            <h2>We apologize, ' . htmlspecialchars($data['customer_name']) . '</h2>
            <p>The provider for <strong>' . htmlspecialchars($data['service_name']) . '</strong> was unable to accept your booking at this time. Don\'t worry - we have other qualified providers available!</p>
        </div>
        <div class="details-card">
            <div class="card-title">Booking Details</div>
            <div class="detail-row">
                <div style="flex:1">
                    <span class="label">Booking ID</span>
                    <div class="value">#' . htmlspecialchars($data['booking_id']) . '</div>
                </div>
                <div style="flex:1">
                    <span class="label">Status</span>
                    <div class="value status">DECLINED</div>
                </div>
            </div>
            <div class="detail-row">
                <div style="flex:1">
                    <span class="label">Service</span>
                    <div class="value">' . htmlspecialchars($data['service_name']) . '</div>
                </div>
                <div style="flex:1">
                    <span class="label">Rate</span>
                    <div class="value">₹' . htmlspecialchars($data['rate']) . '/hour</div>
                </div>
            </div>
            <div class="detail-row">
                <div style="flex:1">
                    <span class="label">Declined On</span>
                    <div class="value">' . $booking_date . '</div>
                </div>
            </div>
        </div>
        <div class="alternative-panel">
            <h3>Alternative Options Available</h3>
            <div style="padding:14px 0 4px; font-size:13px; color:#856404;">
                <p style="margin:0 0 12px;">Don\'t let this stop you! We have many other qualified service providers who may be available for your project.</p>
                <ul style="margin:0; padding-left:20px; color:#856404;">
                    <li>Browse other providers for the same service</li>
                    <li>Adjust your rate or timing preferences</li>
                    <li>Contact our support team for personalized recommendations</li>
                </ul>
            </div>
        </div>
        <div class="next-steps">
            <h3>What You Can Do Next</h3>
            <ul class="steps">
                <li>Search for alternative providers offering the same service</li>
                <li>Review and adjust your budget or schedule if needed</li>
                <li>Create a new booking request with different parameters</li>
                <li>Contact customer support for assistance finding providers</li>
                <li>Save this service for later and try again another time</li>
            </ul>
        </div>
        <div class="cta">
            <a href="localhost/homeaid/customer/login.php">Find Alternative Providers</a>
        </div>
        <div class="support">
            <h3>Need Help Finding Alternatives?</h3>
            <p style="margin:0; font-size:14px; color:#2f4f2f;">Our customer support team can help you find the right provider for your needs. We\'re here to ensure you get the service you need!</p>
        </div>
    </div>
    <div class="footer">
        <p><strong>HomeAid Platform</strong> • Connecting you with trusted service providers</p>
        <p style="margin:8px 0 0;">This email relates to booking #' . htmlspecialchars($data['booking_id']) . '</p>
        <p style="margin:8px 0 0;">&copy; ' . date('Y') . ' HomeAid Platform. All rights reserved.</p>
        <p style="margin:10px 0 0;"><a href="#">Unsubscribe</a> • <a href="#">Privacy</a> • <a href="#">Support</a></p>
    </div>
  </div>
</body>
</html>';

    return $html;
}

/**
 * Create password reset email template
 * @param array $data ['name','reset_link','expires_in']
 * @return string
 */
function createPasswordResetEmailTemplate($data){
    $name = htmlspecialchars($data['name'] ?? 'there');
    $link = htmlspecialchars($data['reset_link'] ?? '#');
    $expires = htmlspecialchars($data['expires_in'] ?? '1 hour');
    $year = date('Y');
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Reset your password</title><style>body{font-family:Arial,Helvetica,sans-serif;background:#f3f4f6;margin:0;padding:0;color:#111827}.wrap{max-width:640px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,.08)}.head{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;padding:24px;text-align:center}.content{padding:28px}.cta{margin:22px 0;text-align:center}.cta a{background:#2563eb;color:#fff;text-decoration:none;padding:12px 20px;border-radius:8px;display:inline-block;font-weight:700}.note{background:#fef3c7;border:1px solid #fde68a;color:#78350f;padding:12px 14px;border-radius:8px;font-size:13px}.footer{background:#f9fafb;color:#6b7280;text-align:center;padding:16px;font-size:12px;border-top:1px solid #e5e7eb}</style></head><body><div class="wrap"><div class="head"><h1>Password reset</h1></div><div class="content"><p>Hi <strong>'.$name.'</strong>,</p><p>We received a request to reset your HomeAid account password. Click the button below to choose a new password.</p><div class="cta"><a href="'.$link.'">Reset Password</a></div><p class="note">This link will expire in '.$expires.'. If you didn\'t request this, you can safely ignore this email.</p></div><div class="footer">© '.$year.' HomeAid • Automated message</div></div></body></html>';
}

/**
 * Create email verification email template
 * @param array $data ['name','verify_link']
 * @return string
 */
function createEmailVerificationEmailTemplate($data){
    $name = htmlspecialchars($data['name'] ?? 'there');
    $link = htmlspecialchars($data['verify_link'] ?? '#');
    $year = date('Y');
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Verify your email</title><style>body{font-family:Arial,Helvetica,sans-serif;background:#f3f4f6;margin:0;padding:0;color:#111827}.wrap{max-width:640px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,.08)}.head{background:linear-gradient(135deg,#06b6d4,#0891b2);color:#fff;padding:24px;text-align:center}.content{padding:28px}.cta{margin:22px 0;text-align:center}.cta a{background:#0ea5e9;color:#fff;text-decoration:none;padding:12px 20px;border-radius:8px;display:inline-block;font-weight:700}.note{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;padding:12px 14px;border-radius:8px;font-size:13px}.footer{background:#f9fafb;color:#6b7280;text-align:center;padding:16px;font-size:12px;border-top:1px solid #e5e7eb}</style></head><body><div class="wrap"><div class="head"><h1>Verify your email</h1></div><div class="content"><p>Hi <strong>'.$name.'</strong>,</p><p>Welcome to HomeAid! Please confirm your email to activate your account.</p><div class="cta"><a href="'.$link.'">Confirm my email</a></div><p class="note">If you didn\'t sign up, you can safely ignore this message.</p></div><div class="footer">© '.$year.' HomeAid • Automated message</div></div></body></html>';
}

/**
 * Send booking decision notification (accept or reject)
 * 
 * @param array $booking_data Array containing booking information
 * @param string $decision 'accepted' or 'rejected'
 * @return bool True if email was sent successfully, false otherwise
 */
function sendBookingDecisionNotification($booking_data, $decision) {
    if ($decision === 'accepted') {
        return sendBookingAcceptedNotification($booking_data);
    } elseif ($decision === 'rejected') {
        return sendBookingRejectedNotification($booking_data);
    }
    
    return false;
}
?>
