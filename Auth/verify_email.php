<?php
require_once __DIR__ . '/../includes/session_manager.php';
require_once __DIR__ . '/../config/db.php';

SessionManager::startSession();

// Ensure tokens table exists
$conn->query("CREATE TABLE IF NOT EXISTS email_verification_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$token = $_GET['token'] ?? '';
$success_message = '';
$error_message = '';
$role = 'customer';

if (!$token) {
    $error_message = 'Invalid verification link.';
} else {
    $stmt = $conn->prepare("SELECT evt.id as token_id, evt.user_id, evt.expires_at, evt.used, u.`role` FROM email_verification_tokens evt JOIN users u ON u.id = evt.user_id WHERE evt.token=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $role = $row['role'] ?: 'customer';
            $now = new DateTime();
            $exp = new DateTime($row['expires_at']);

            if ((int)$row['used'] === 1 || $exp < $now) {
                $error_message = 'This verification link has expired or was already used.';
            } else {
                // Ensure columns exist
                $checkCol = $conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'email_verified'");
                if ($checkCol && ($checkCol->fetch_row()[0] ?? 0) == 0) {
                    $conn->query("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER password");
                }
                $checkCol2 = $conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'email_verified_at'");
                if ($checkCol2 && ($checkCol2->fetch_row()[0] ?? 0) == 0) {
                    $conn->query("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email_verified");
                }
                // Mark verified
                $upd = $conn->prepare("UPDATE users SET email_verified=1, email_verified_at=NOW() WHERE id=?");
                if ($upd) {
                    $upd->bind_param('i', $row['user_id']);
                    if ($upd->execute()) {
                        // Mark token used
                        $mark = $conn->prepare("UPDATE email_verification_tokens SET used=1 WHERE id=?");
                        if ($mark) { $mark->bind_param('i', $row['token_id']); $mark->execute(); }
                        $success_message = 'Your email has been verified. You can now log in.';
                    } else {
                        $error_message = 'Failed to verify email. Please try again later.';
                    }
                } else {
                    $error_message = 'Failed to update account.';
                }
            }
        } else {
            $error_message = 'Invalid or unknown verification token.';
        }
    } else {
        $error_message = 'Could not process your request.';
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
          <h1 class="text-center">Email Verification</h1>
          <?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
          <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
          <?php if ($success_message && !$error_message): ?>
            <p class="text-center" style="margin-top:10px;color:#6b7280;">Redirecting you to the <?php echo $role==='provider'?'Provider':'Customer'; ?> login page...</p>
            <script>
              setTimeout(function(){
                window.location.href = '<?php echo $role==='provider' ? "../provider/login.php" : "../customer/login.php"; ?>';
              }, 1500);
            </script>
          <?php endif; ?>
          <div class="text-center mt-3">
            <?php if ($role==='provider'): ?>
              <a href="../provider/login.php" class="btn btn-primary">Go to Provider Login</a>
            <?php else: ?>
              <a href="../customer/login.php" class="btn btn-primary">Go to Customer Login</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  </main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
