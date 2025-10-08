<?php
require_once __DIR__ . '/../includes/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/email_functions.php';

SessionManager::startSession();

$role = isset($_GET['role']) && in_array($_GET['role'], ['customer','provider'], true) ? $_GET['role'] : 'customer';
$email = trim($_GET['email'] ?? '');
$success_message = '';
$error_message = '';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = 'Invalid email address.';
} else {
    $stmt = $conn->prepare("SELECT id, name, email, COALESCE(email_verified,0) AS email_verified FROM users WHERE email=? AND `role`=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ss', $email, $role);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $u = $res->fetch_assoc();
            if ((int)$u['email_verified'] === 1) {
                $success_message = 'This email is already verified.';
            } else {
                $conn->query("CREATE TABLE IF NOT EXISTS email_verification_tokens (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token VARCHAR(128) NOT NULL, expires_at DATETIME NOT NULL, used TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_token (token), INDEX idx_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time()+60*60*24);
                $ins = $conn->prepare("INSERT INTO email_verification_tokens (user_id, token, expires_at) VALUES (?,?,?)");
                if ($ins) {
                    $ins->bind_param('iss', $u['id'], $token, $expires);
                    if ($ins->execute()) {
                        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                        $base = function_exists('getBaseUrl') ? rtrim(getBaseUrl(), '/') : ('http://' . $host);
                        $link = $base . '/Auth/verify_email.php?token=' . urlencode($token);
                        $msg = createEmailVerificationEmailTemplate(['name'=>$u['name'],'verify_link'=>$link]);
                        if (sendEmail($u['email'], 'Confirm your HomeAid account', $msg)) {
                            $success_message = 'Verification email sent. Please check your inbox.';
                        } else {
                            $error_message = 'Could not send email. Try again later.';
                        }
                    } else {
                        $error_message = 'Could not create verification token.';
                    }
                } else {
                    $error_message = 'Could not create verification token.';
                }
            }
        } else {
            $error_message = 'No account found for this email/role.';
        }
    } else {
        $error_message = 'We cannot process your request right now.';
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
          <h1 class="text-center">Resend Verification Email</h1>
          <?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
          <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
          <div class="text-center mt-3">
            <?php if ($role==='provider'): ?>
              <a href="../provider/login.php" class="btn btn-primary">Back to Provider Login</a>
            <?php else: ?>
              <a href="../customer/login.php" class="btn btn-primary">Back to Customer Login</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
