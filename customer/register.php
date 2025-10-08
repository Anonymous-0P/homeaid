<?php 
session_start();
include "../config/db.php";
include "../includes/header.php"; 
include "../includes/navbar.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Confirm password check
    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    }
    
    // Check if email already exists (robust against mysqlnd absence)
    if (!isset($error_message)) {
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($check_email === false) {
            $error_message = "Database error. Please try again later.";
        } else {
            $check_email->bind_param("s", $email);
            if ($check_email->execute() === false) {
                $error_message = "Database error during lookup.";
            } else {
                $check_email->store_result();
                if ($check_email->num_rows > 0) {
                    $error_message = "An account with this email address already exists.";
                }
            }
            $check_email->close();
        }
    }

    if (!isset($error_message)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Ensure email verification columns exist (avoid IF NOT EXISTS for older MySQL)
        $resCol1 = $conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'email_verified'");
        if ($resCol1) {
            $exists1 = (int)($resCol1->fetch_row()[0] ?? 0);
            if ($exists1 === 0) {
                $conn->query("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER password");
            }
        }
        $resCol2 = $conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'email_verified_at'");
        if ($resCol2) {
            $exists2 = (int)($resCol2->fetch_row()[0] ?? 0);
            if ($exists2 === 0) {
                $conn->query("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email_verified");
            }
        }

        // Insert user with prepared statement, default unverified
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, email_verified) VALUES (?, ?, ?, 'customer', 0)");
        if ($stmt === false) {
            $error_message = "Registration failed. Please try again later.";
        } else {
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
            // Create verification token and email
            $uid = $conn->insert_id;
            $conn->query("CREATE TABLE IF NOT EXISTS email_verification_tokens (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token VARCHAR(128) NOT NULL, expires_at DATETIME NOT NULL, used TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_token (token), INDEX idx_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time()+60*60*24);
            $ins = $conn->prepare("INSERT INTO email_verification_tokens (user_id, token, expires_at) VALUES (?,?,?)");
            if ($ins) {
                $ins->bind_param('iss', $uid, $token, $expires);
                if ($ins->execute()) {
                    require_once __DIR__ . '/../includes/email_functions.php';
                    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                    $base = function_exists('getBaseUrl') ? rtrim(getBaseUrl(), '/') : ('http://' . $host);
                    $link = $base . '/Auth/verify_email.php?token=' . urlencode($token);
                    $msg = createEmailVerificationEmailTemplate(['name'=>$name,'verify_link'=>$link]);
                    @sendEmail($email, 'Confirm your HomeAid account', $msg);
                }
            }
            $success_message = "Registration successful. Please check your email to confirm your account.";
            } else {
                $error_message = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<main>
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="content-wrapper">
                    <h1 class="text-center">Customer Registration</h1>
                    <p class="text-center">Join HomeAid as a customer and find trusted service providers.</p>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                            <div class="text-center mt-2">
                                <a href="login.php" class="btn btn-primary">Login Now</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-half" style="margin: 0 auto;">
                            <form method="POST" novalidate>
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter your password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Register as Customer</button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <p>Already have an account? <a href="login.php">Login here</a></p>
                                <p>Want to provide services? <a href="../provider/register.php">Register as Provider</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
<script>
// Client-side check for matching passwords
document.querySelector('form').addEventListener('submit', function(e){
    const pwd = document.getElementById('password').value;
    const cpwd = document.getElementById('confirm_password').value;
    if(pwd !== cpwd){
        e.preventDefault();
        alert('Passwords do not match.');
        return false;
    }
});
</script>
