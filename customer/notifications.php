<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$conn->query("CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, message TEXT NOT NULL, is_read BOOLEAN DEFAULT FALSE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");

// Mark all notifications as read when viewing
$conn->query("UPDATE notifications SET is_read = TRUE WHERE user_id = $customer_id");

include "../includes/header.php";
include "../includes/navbar.php";
?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <div class="top-actions" style="display:flex; justify-content:flex-end; margin-bottom: 1rem;">
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            </div>
            <div class="page-header">
                <h1>📬 Your Notifications</h1>
                <p>Stay updated with your booking status and important updates</p>
            </div>

            <?php
            $notifs = $conn->query("SELECT * FROM notifications WHERE user_id=$customer_id ORDER BY created_at DESC LIMIT 50");

            if ($notifs->num_rows > 0) {
                echo "<div class='notifications-container'>";
                
                while ($row = $notifs->fetch_assoc()) {
                    $time_ago = time() - strtotime($row['created_at']);
                    
                    if ($time_ago < 60) {
                        $time_text = "Just now";
                    } elseif ($time_ago < 3600) {
                        $time_text = floor($time_ago / 60) . " minutes ago";
                    } elseif ($time_ago < 86400) {
                        $time_text = floor($time_ago / 3600) . " hours ago";
                    } else {
                        $time_text = floor($time_ago / 86400) . " days ago";
                    }
                    
                    $is_new = !$row['is_read'];
                    $notification_class = $is_new ? "notification-card notification-new" : "notification-card";
                    
                    echo "<div class='$notification_class'>";
                    
                    if ($is_new) {
                        echo "<div class='notification-badge'>NEW</div>";
                    }
                    
                    echo "<div class='notification-content'>";
                    echo "<div class='notification-message'>";
                    echo nl2br(htmlspecialchars($row['message']));
                    echo "</div>";
                    echo "<div class='notification-time'>";
                    echo "<small>$time_text • " . date('M j, Y g:i A', strtotime($row['created_at'])) . "</small>";
                    echo "</div>";
                    echo "</div>";
                    
                    echo "</div>";
                }
                
                echo "</div>";
            } else {
                echo "<div class='card'>";
                echo "<div class='card-body text-center' style='padding: 4rem;'>";
                echo "<div style='font-size: 4rem; margin-bottom: 1rem;'>🔔</div>";
                echo "<h3>No Notifications Yet</h3>";
                echo "<p class='text-secondary'>Your notifications will appear here when providers respond to your booking requests or other important updates occur.</p>";
                echo "<a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a>";
                echo "</div>";
                echo "</div>";
            }
            ?>
            
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</main>

<style>
.notifications-container {
    max-width: 800px;
    margin: 0 auto;
}

.notification-card {
    background: var(--background-primary);
    border: 1px solid var(--border-light);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    position: relative;
}

.notification-card:hover {
    box-shadow: var(--shadow-md);
}

.notification-new {
    border-left: 4px solid var(--secondary-color);
    background: rgba(5, 150, 105, 0.02);
}

.notification-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: var(--secondary-color);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.notification-message {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 0.75rem;
    color: var(--text-primary);
}

.notification-time {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.page-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem 0;
    background: linear-gradient(135deg, var(--secondary-color), #047857);
    color: white;
    border-radius: var(--border-radius);
}

.page-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.page-header p {
    font-size: 1.1rem;
    opacity: 0.9;
}
</style>

<?php include "../includes/footer.php"; ?>
