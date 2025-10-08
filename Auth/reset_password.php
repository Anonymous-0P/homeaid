<?php
require_once __DIR__ . '/../includes/session_manager.php';
require_once __DIR__ . '/../config/db.php';

SessionManager::startSession();

$token = $_GET['token'] ?? '';
$success_message = '';
$error_message = '';
$user = null;

if (!$token) {
    $error_message = 'Invalid reset link.';
} else {
    $stmt = $conn->prepare("SELECT prt.id as token_id, prt.user_id, prt.expires_at, prt.used, u.`role` FROM password_reset_tokens prt JOIN users u ON u.id = prt.user_id WHERE prt.token=? LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $now = new DateTime();
        $exp = new DateTime($row['expires_at']);
        if ((int)$row['used'] === 1 || $exp < $now) {
            $error_message = 'This reset link is expired or already used.';
        } else {
            $user = $row; // contains token_id, user_id, role
        }
    } else {
        $error_message = 'Invalid reset token.';
    }
}

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($pass) < 6) {
        $error_message = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
        $error_message = 'Passwords do not match.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $upd->bind_param('si', $hash, $user['user_id']);
        if ($upd->execute()) {
            $mark = $conn->prepare("UPDATE password_reset_tokens SET used=1 WHERE id=?");
            $mark->bind_param('i', $user['token_id']);
            $mark->execute();
            $success_message = 'Your password has been reset. You can now log in.';
        } else {
            $error_message = 'Failed to reset password.';
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
          <h1 class="text-center">Reset Password</h1>
          <?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
          <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
          <?php if ($user && !$success_message): ?>
          <div class="row"><div class="col-half" style="margin:0 auto;">
            <form method="POST">
              <div class="form-group">
                <label for="password" class="form-label">New Password</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6" />
              </div>
              <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6" />
              </div>
              <button type="submit" class="btn btn-primary" style="width:100%;">Update Password</button>
            </form>
          </div></div>
          <?php endif; ?>
          <?php if ($success_message): ?>
          <div class="text-center mt-3">
            <?php if (($user['role'] ?? 'customer') === 'provider'): ?>
            <p><a href="../../provider/login.php">Go to Provider Login</a></p>
            <?php else: ?>
            <p><a href="../../customer/login.php">Go to Customer Login</a></p>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
