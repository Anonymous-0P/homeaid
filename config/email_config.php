<?php
/**
 * Email Configuration for HomeAid Platform
 * 
 * Gmail SMTP Configuration
 * Before using this configuration:
 * 1. Enable 2-factor authentication on your Gmail account
 * 2. Generate an app password: https://myaccount.google.com/apppasswords
 * 3. Replace the placeholders below with your actual Gmail credentials
 */

// Gmail SMTP Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // or 'ssl' for port 465
define('SMTP_AUTH', true);

// Your Gmail Credentials (CHANGE THESE!)
define('SMTP_USERNAME', 'prakashkarekar4@gmail.com'); // Replace with your Gmail address
define('SMTP_PASSWORD', 'xkscvjyackclkyvi');    // Replace with your 16-character Gmail app password

// Email sender details
define('SMTP_FROM_EMAIL', 'prakashkarekar4@gmail.com'); // Must match your Gmail address
define('SMTP_FROM_NAME', 'HomeAid Platform');

// Debug settings (set to 0 for production, 2 for debugging)
define('SMTP_DEBUG', 0);

// Backup email settings (fallback to basic mail() if SMTP fails)
define('EMAIL_FALLBACK', false); // Disabled since Gmail SMTP is working
?>
