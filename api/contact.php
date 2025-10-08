<?php
// Simple contact endpoint: validates input and emails the site admin
header('Content-Type: application/json');

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Start session for basic rate limit
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/email_functions.php';
require_once __DIR__ . '/../config/email_config.php';

// Gather and sanitize
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate
$errors = [];
if ($name === '') { $errors[] = 'Name is required'; }
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email is required'; }
if ($message === '') { $errors[] = 'Message is required'; }

if ($errors) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => implode('. ', $errors)]);
    exit;
}

// Basic cooldown: 60s between submissions per session
$now = time();
$cooldown = 60;
if (!empty($_SESSION['contact_last_submit']) && ($now - (int)$_SESSION['contact_last_submit']) < $cooldown) {
    $wait = $cooldown - ($now - (int)$_SESSION['contact_last_submit']);
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Please wait ' . $wait . 's before sending another message']);
    exit;
}

// Build email
$to = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'admin@localhost';
$subject = 'New contact message from HomeAid';
$safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safeMsg = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Contact Message</title></head><body style="font-family:Arial,Helvetica,sans-serif; color:#111827; background:#f9fafb; padding:20px;">'
    . '<div style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,.05);">'
    . '<div style="background:#1f2937;color:#fff;padding:18px 20px;font-weight:700;">New Contact Form Message</div>'
    . '<div style="padding:20px;">'
    . '<p><strong>Name:</strong> ' . $safeName . '</p>'
    . '<p><strong>Email:</strong> ' . $safeEmail . '</p>'
    . '<div style="height:1px;background:#e5e7eb;margin:12px 0;"></div>'
    . '<p style="margin:0 0 6px;"><strong>Message:</strong></p>'
    . '<div style="white-space:pre-wrap;">' . $safeMsg . '</div>'
    . '</div>'
    . '<div style="background:#f9fafb;color:#6b7280;padding:12px 20px;font-size:12px;">This message was sent from the HomeAid website contact form.</div>'
    . '</div>'
    . '</body></html>';

// Set reply-to as the sender so you can reply directly
$sent = sendEmail($to, $subject, $html, SMTP_FROM_NAME, SMTP_FROM_EMAIL, true, $email, $name);

if ($sent) {
    $_SESSION['contact_last_submit'] = $now;
    echo json_encode(['ok' => true, 'message' => 'Thanks! Your message has been sent.']);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to send message. Please try again later.']);
}
?>
