<?php 
require_once "../includes/session_manager.php";
include "../config/db.php";

SessionManager::startSession();

// Check if already logged in
if (SessionManager::checkAuth('admin')) {
    header("Location: dashboard.php");
    exit();
}

include "../includes/header.php"; 
include "../includes/navbar.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Check both MD5 (legacy) and password_hash formats
        if (password_verify($password, $row['password']) || md5($password) === $row['password']) {
            // Set session using SessionManager
            SessionManager::setLoginSession($row);
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Invalid password. Please try again.";
        }
    } else {
        $error_message = "No admin account found with this email address.";
    }
}
?>

<main>
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="content-wrapper">
                    <h1 class="text-center">Admin Login</h1>
                    <p class="text-center">Administrator access to HomeAid management.</p>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
           
                    ?>

                    <div class="row">
                        <div class="col-half" style="margin: 0 auto;">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="email" class="form-label">Admin Email</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter admin email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Admin Login</button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <p><a href="../index.php">Back to Home</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
