<?php 
require_once "../includes/session_manager.php";
include "../config/db.php";

SessionManager::startSession();

// Check if already logged in
if (SessionManager::checkAuth('provider')) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'provider'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

        if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Check both MD5 (legacy) and password_hash formats
        if (password_verify($password, $row['password']) || md5($password) === $row['password']) {
            // Require verified email only if clearly unverified
            $isVerifiedFlag = array_key_exists('email_verified', $row) ? (int)$row['email_verified'] : null;
            $verifiedAt = array_key_exists('email_verified_at', $row) ? $row['email_verified_at'] : null;
            $isClearlyUnverified = ($isVerifiedFlag === 0) && (empty($verifiedAt) || $verifiedAt === '0000-00-00 00:00:00');
            if ($isClearlyUnverified) {
                $resendUrl = "../Auth/resend_verification.php?role=provider&email=" . urlencode($email);
                $error_message = "Please verify your email to continue.";
                $info_html = "<a class=\"btn btn-link\" href='" . htmlspecialchars($resendUrl) . "'>Resend verification email</a>";
            } else {
            // Set session using SessionManager
            SessionManager::setLoginSession($row);
            
            header("Location: dashboard.php");
            exit();
            }
        } else {
            $error_message = "Invalid password. Please try again.";
        }
    } else {
        $error_message = "No provider account found with this email address.";
    }
}

include "../includes/header.php"; 
include "../includes/navbar.php";
?>

<main>
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="content-wrapper">
                    <h1 class="text-center">Provider Login</h1>
                    <p class="text-center">Welcome back! Please login to manage your services.</p>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($info_html)): ?>
                        <div class="alert alert-info">
                            <?php echo $info_html; ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-half" style="margin: 0 auto;">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <p><a href="../Auth/forgot_password.php?role=provider">Forgot your password?</a></p>
                                <p>Don't have an account? <a href="register.php">Register here</a></p>
                                <p>Are you a customer? <a href="../customer/login.php">Customer Login</a></p>
                                <p><a href="../admin/login.php">Admin Login</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
