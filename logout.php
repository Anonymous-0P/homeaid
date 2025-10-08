<?php
require_once "includes/session_manager.php";

SessionManager::startSession();

// Get user role for redirect
$role = $_SESSION['role'] ?? 'customer';

// Logout the user
SessionManager::logout();

// Clear any remaining session cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - HomeAid</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .logout-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .logout-message {
            color: #28a745;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .login-links {
            margin-top: 30px;
        }
        .login-links a {
            display: inline-block;
            margin: 5px 10px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .login-links a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <h1>Successfully Logged Out</h1>
        <div class="logout-message">
            ✅ You have been successfully logged out of HomeAid.
        </div>
        <p>Thank you for using our services. Your session has been securely terminated.</p>
        
        <div class="login-links">
            <h3>Login Again:</h3>
            <a href="customer/login.php">Customer Login</a>
            <a href="provider/login.php">Provider Login</a>
            <a href="admin/login.php">Admin Login</a>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="index.php" style="color: #6c757d;">← Back to Home</a>
        </div>
    </div>

    <script>
        // Auto redirect after 5 seconds to appropriate login page
        setTimeout(function() {
            const role = '<?php echo htmlspecialchars($role); ?>';
            let redirectUrl = 'index.php';
            
            switch(role) {
                case 'customer': redirectUrl = 'customer/login.php'; break;
                case 'provider': redirectUrl = 'provider/login.php'; break;
                case 'admin': redirectUrl = 'admin/login.php'; break;
            }
            
            window.location.href = redirectUrl;
        }, 5000);
    </script>
</body>
</html>
