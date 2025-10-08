<?php
// Session info widget - shows remaining time and logout option
require_once __DIR__ . "/session_manager.php";

if (SessionManager::checkAuth()) {
    $session_info = SessionManager::getSessionInfo();
    $remaining_time = SessionManager::getRemainingTime();
    $user_name = $_SESSION['name'] ?? 'User';
    $user_role = $_SESSION['role'] ?? 'user';
?>

<div class="session-info" style="background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
            <strong>Welcome, <?php echo htmlspecialchars($user_name); ?>!</strong>
            <span style="color: #6c757d;">(<?php echo ucfirst($user_role); ?>)</span>
            <br>
            <small style="color: #28a745;">
                ⏱️ Session: <?php echo $session_info['remaining_time']; ?> remaining
            </small>
        </div>
        <div style="flex-shrink: 0; margin-top: 5px;">
            <a href="<?php echo ($_SESSION['role'] === 'admin') ? '../logout.php' : '../logout.php'; ?>" 
               style="background: #dc3545; color: white; padding: 5px 15px; text-decoration: none; border-radius: 3px; font-size: 14px;"
               onclick="return confirm('Are you sure you want to logout?')">
                🚪 Logout
            </a>
        </div>
    </div>
</div>

<script>
// Auto-refresh session timer every minute
setInterval(function() {
    const sessionTimer = document.querySelector('.session-info small');
    if (sessionTimer) {
        fetch('<?php echo ($_SESSION['role'] === 'admin') ? '../' : '../'; ?>check_session.php')
            .then(response => response.json())
            .then(data => {
                if (data.expired) {
                    alert('Your session has expired. You will be redirected to login.');
                    window.location.href = 'login.php';
                } else {
                    sessionTimer.innerHTML = '⏱️ Session: ' + data.remaining_time + ' remaining';
                }
            })
            .catch(error => console.log('Session check error:', error));
    }
}, 60000); // Check every minute

// Warn user 2 minutes before expiry
setTimeout(function() {
    if (confirm('Your session will expire in 2 minutes. Would you like to stay logged in?')) {
        // Refresh the page to update activity
        window.location.reload();
    }
}, <?php echo max(0, ($remaining_time - 120) * 1000); ?>); // 2 minutes before expiry
</script>

<?php
}
?>
