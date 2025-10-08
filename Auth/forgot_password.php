<?php
require_once __DIR__ . '/../includes/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/email_functions.php';

SessionManager::startSession();

$role = isset($_GET['role']) && in_array($_GET['role'], ['customer','provider'], true) ? $_GET['role'] : 'customer';
$success_message = '';
$error_message = '';

// Ensure reset token table exists (fallback without FK if needed)
$createSql = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!$conn->query($createSql)) {
  error_log('CREATE TABLE password_reset_tokens failed: ' . $conn->error);
  // Fallback: no engine/charset clause for broader compatibility
  $fallbackSql = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_user (user_id)
  )";
  if (!$conn->query($fallbackSql)) {
    error_log('Fallback CREATE TABLE password_reset_tokens failed: ' . $conn->error);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $role_post = $_POST['role'] ?? $role;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Enter a valid email.';
  } else {
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email=? AND `role`=? LIMIT 1");
    if (!$stmt) {
      error_log('ForgotPassword SELECT prepare failed: ' . $conn->error);
      $error_message = 'We\'re having trouble processing your request. Please try again later.';
    } else {
      $stmt->bind_param('ss', $email, $role_post);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res === false) {
        error_log('ForgotPassword get_result failed: ' . $conn->error);
      }
    }
    if (empty($error_message) && $res && $res->num_rows === 1) {
            $user = $res->fetch_assoc();
            // Create token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 60*60); // 1 hour
      $ins = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?,?,?)");
      if (!$ins) {
        error_log('ForgotPassword INSERT prepare failed: ' . $conn->error);
        $error_message = 'We\'re having trouble processing your request. Please try again later.';
      } else {
        $ins->bind_param('iss', $user['id'], $token, $expires);
        if ($ins->execute()) {
                $baseUrl = rtrim(getBaseUrl(), '/');
                $rootUrl = preg_replace('#/Auth$#i', '', $baseUrl);
                $resetLink = $rootUrl . '/Auth/Auth/reset_password.php?token=' . urlencode($token);
                $subject = 'Reset your HomeAid password';
                $message = createPasswordResetEmailTemplate([
                    'name' => $user['name'],
                    'role' => $role_post,
                    'reset_link' => $resetLink,
                    'expires_in' => '1 hour'
                ]);
                if (sendEmail($user['email'], $subject, $message)) {
                    $success_message = 'We\'ve sent a reset link if the email exists in our system.';
          } else {
            error_log('ForgotPassword sendEmail failed to ' . $user['email']);
            $error_message = 'Failed to send reset email. Please try again later.';
          }
        } else {
          error_log('ForgotPassword INSERT execute failed: ' . $conn->error);
          $error_message = 'Could not create reset token.';
        }
      }
        } else {
            // Do not reveal existence
            $success_message = 'We\'ve sent a reset link if the email exists in our system.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main>
  <div class="container">
    <div class="row">
      <div class="col">
        <div class="content-wrapper">
          <h1 class="text-center">Forgot Password</h1>
          <p class="text-center">Enter your email to receive a password reset link.</p>
          <?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
          <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
          <div class="row"><div class="col-half" style="margin:0 auto;">
            <form method="POST">
              <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>" />
              <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required />
              </div>
              <button type="submit" class="btn btn-primary" style="width:100%;">Send Reset Link</button>
            </form>
            <div class="text-center mt-3">
              <?php if ($role==='customer'): ?>
              <p><a href="../customer/login.php">Back to Customer Login</a></p>
              <?php else: ?>
              <p><a href="../provider/login.php">Back to Provider Login</a></p>
              <?php endif; ?>
            </div>
          </div></div>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
