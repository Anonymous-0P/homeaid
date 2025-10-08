<?php 
require_once "../includes/session_manager.php";

// Check authentication with session timeout
if (!SessionManager::checkAuth('provider')) {
    header("Location: login.php");
    exit();
}

$provider_id = $_SESSION['user_id'];

// Handle success/error messages
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
$error_message = isset($_GET['error']) ? $_GET['error'] : '';

$error_messages = [
    'invalid_status' => 'Invalid booking status provided.',
    'booking_not_found' => 'Booking not found or you do not have permission to modify it.',
    'invalid_transition' => 'Invalid status transition. This action is not allowed.',
    'update_failed' => 'Failed to update booking status. Please try again.',
    'system_error' => 'A system error occurred. Please try again later.'
];

if ($error_message && isset($error_messages[$error_message])) {
    $error_message = $error_messages[$error_message];
}

include "../includes/header.php"; 
include "../includes/navbar.php"; 
include "../config/db.php";
?>

<main>
    <div class="container">
        <div class="content-wrapper">
           
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <h1>Provider Dashboard</h1>
            <p>Welcome back! Manage your services and bookings below.</p>
            
            <?php
            // Check if provider has set up any services
            $services_check = $conn->query("SELECT COUNT(*) as count FROM provider_services WHERE provider_id = $provider_id AND is_active = TRUE");
            $active_services = $services_check->fetch_assoc()['count'];
            
            if ($active_services == 0): ?>
                <div class="alert alert-warning">
                    <h4>⚠️ Setup Required</h4>
                    <p><strong>You haven't set up your service rates yet!</strong></p>
                    <p>Customers won't be able to find and book your services until you set your rates.</p>
                    <a href="set_rates.php" class="btn btn-primary">Set Your Service Rates Now</a>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <p>✅ You're offering <strong><?php echo $active_services; ?></strong> service<?php echo $active_services > 1 ? 's' : ''; ?>. Great job!</p>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Set Rates</h3>
                    <p>Update your service rates and availability.</p>
                    <a href="set_rates.php" class="btn btn-primary">Manage Rates</a>
                </div>
                
                <div class="dashboard-card">
                    <h3>Notifications</h3>
                    <?php
                    // Get unread notification count
                    $notification_count = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $provider_id AND is_read = FALSE")->fetch_assoc()['count'];
                    ?>
                    <p>Check your latest booking requests and updates.</p>
                    <?php if ($notification_count > 0): ?>
                        <div class="notification-badge" style="background: #dc2626; color: white; border-radius: 50%; width: 24px; height: 24px; text-align: center; font-size: 12px; font-weight: bold; margin: 0.5rem 0;">
                            <?php echo $notification_count; ?>
                        </div>
                        <p style="color: #dc2626; font-weight: 600;"><?php echo $notification_count; ?> new notification<?php echo $notification_count > 1 ? 's' : ''; ?>!</p>
                    <?php endif; ?>
                    <a href="notifications.php" class="btn btn-secondary">View Notifications</a>
                </div>
                
                <div class="dashboard-card">
                    <h3>My Profile</h3>
                    <p>Update your profile information and settings.</p>
                    <a href="edit_profile.php" class="btn btn-outline">Edit Profile</a>
                </div>
                
                <div class="dashboard-card">
                    <h3>Earnings</h3>
                    <p>View your earnings and payment history.</p>
                    <a href="earnings.php" class="btn btn-outline">View Earnings</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">My Bookings</h2>
                </div>
                <div class="card-body">
                    <?php
                    // REPLACED original direct $conn->query with prepared, resilient query
                    $bookings_sql = "SELECT b.id, b.booking_time, b.status,
                                            cu.name AS customer, cu.email AS customer_email, cu.phone AS customer_phone,
                                            s.name AS service, COALESCE(ps.rate, 0) AS rate,
                                            (CASE WHEN ps.rate IS NULL THEN 1 ELSE 0 END) AS missing_rate
                                     FROM bookings b
                                     JOIN users cu ON b.customer_id = cu.id
                                     JOIN services s ON b.service_id = s.id
                                     LEFT JOIN provider_services ps ON b.provider_id = ps.provider_id AND b.service_id = ps.service_id
                                     WHERE b.provider_id = ?
                                     ORDER BY b.booking_time DESC";
                    $bookings_stmt = $conn->prepare($bookings_sql);
                    $bookings_stmt->bind_param("i", $provider_id);
                    $bookings_stmt->execute();
                    $result = $bookings_stmt->get_result();
                    ?>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class='table-responsive'>
                            <table class='table'>
                                <thead>
                                    <tr><th>Booking ID</th><th>Customer</th><th>Service</th><th>Rate</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="<?php echo $row['status']==='pending' ? 'row-pending' : ''; ?>">
                                        <td><strong>#<?php echo $row['id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['customer']); ?></strong><br>
                                            <?php if ($row['customer_email']) echo '<small>'.htmlspecialchars($row['customer_email']).'</small><br>'; ?>
                                            <?php if ($row['customer_phone']) echo '<small>'.htmlspecialchars($row['customer_phone']).'</small>'; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['service']); ?></td>
                                        <td>
                                            <?php if ($row['missing_rate']): ?>
                                                <span style='color:#dc2626;font-weight:600;'>Set Rate</span>
                                            <?php else: ?>
                                                <strong>₹<?php echo number_format($row['rate'],2); ?>/hr</strong>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo date('M j, Y<br>g:i A', strtotime($row['booking_time'])); ?></small></td>
                                        <td><span class='badge badge-<?php echo $row['status']; ?>'><?php echo ucfirst($row['status']); ?></span></td>
                                        <td>
                                            <?php if ($row['status']==='pending'): ?>
                                                <div class='btn-group-vertical' style='gap:0.25rem;'>
                                                    <a href='update_booking.php?id=<?php echo $row['id']; ?>&status=accepted' class='btn btn-success btn-small' onclick="return confirm('Accept this booking request?')">✓ Accept</a>
                                                    <a href='update_booking.php?id=<?php echo $row['id']; ?>&status=rejected' class='btn btn-danger btn-small' onclick="return confirm('Reject this booking request?')">✗ Reject</a>
                                                </div>
                                            <?php elseif ($row['status']==='accepted'): ?>
                                                <a href='update_booking.php?id=<?php echo $row['id']; ?>&status=completed' class='btn btn-primary btn-small' onclick="return confirm('Mark this service as completed?')">✓ Mark Completed</a>
                                            <?php elseif ($row['status']==='completed'): ?>
                                                <span class='text-success'><strong>✓ Completed</strong></span>
                                            <?php elseif ($row['status']==='rejected'): ?>
                                                <span class='text-danger'><strong>✗ Rejected</strong></span>
                                            <?php else: ?>
                                                <span class='text-secondary'>No actions</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                            <p style='font-size:12px;color:#6b7280;margin-top:0.5rem;'>Tip: Rows highlighted indicate new pending requests. If rate shows "Set Rate" please configure it so customer pricing is accurate.</p>
                        </div>
                    <?php else: ?>
                        <div class='text-center' style='padding:3rem;'>
                            <div style='font-size:3rem; margin-bottom:1rem;'>📋</div>
                            <h3>No Bookings Yet</h3>
                            <p class='text-secondary'>If you recently received an email about a booking but it is not appearing, ensure the service rate is configured in "Set Rates".</p>
                            <a href='set_rates.php' class='btn btn-primary'>Set Your Service Rates</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="../index.php" class="btn btn-outline">Back to Home</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
<style>
.row-pending {background: #fff9ef;}
.row-pending:hover {background:#fff4dc;}
</style>
