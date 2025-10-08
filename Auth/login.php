<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>
<?php include "../config/db.php"; ?>

<main>
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="content-wrapper">
                    <h1 class="text-center">Universal Login</h1>
                    <p class="text-center">Login to access your HomeAid account (Customer, Provider, or Admin).</p>
                    
                    <?php
                    session_start();

                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        $email = $_POST['email'];
                        $password = $_POST['password'];

                        // Get user from database
                        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                        $stmt->bind_param("s", $email);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows == 1) {
                            $user = $result->fetch_assoc();
                            
                            // Verify password (supports both old md5 and new password_hash)
                            $password_valid = false;
                            if (password_verify($password, $user['password'])) {
                                $password_valid = true;
                            } elseif (md5($password) === $user['password']) {
                                // Legacy support for old md5 passwords
                                $password_valid = true;
                                // Update to secure hash
                                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                                $conn->query("UPDATE users SET password='$new_hash' WHERE id=" . $user['id']);
                            }
                            
                            if ($password_valid) {
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['role'] = $user['role'];
                                
                                // Set role-specific session variables
                                if ($user['role'] == 'customer') {
                                    $_SESSION['customer_id'] = $user['id'];
                                    header("Location: ../customer/dashboard.php");
                                } elseif ($user['role'] == 'provider') {
                                    $_SESSION['provider_id'] = $user['id'];
                                    header("Location: ../provider/dashboard.php");
                                } elseif ($user['role'] == 'admin') {
                                    $_SESSION['admin_id'] = $user['id'];
                                    header("Location: ../admin/dashboard.php");
                                }
                                exit;
                            } else {
                                echo "<div class='alert alert-danger'>Invalid password.</div>";
                            }
                        } else {
                            echo "<div class='alert alert-danger'>No account found with this email address.</div>";
                        }
                    }
                    ?>

                    <div class="row">
                        <div class="col-half" style="margin: 0 auto;">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <p><strong>Don't have an account?</strong></p>
                                <p>
                                    <a href="../customer/register.php">Register as Customer</a> | 
                                    <a href="../provider/register.php">Register as Provider</a>
                                </p>
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
