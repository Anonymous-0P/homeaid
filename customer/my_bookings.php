<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

include "../includes/header.php";
include "../includes/navbar.php";
?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>📋 My Bookings</h1>
                <p>Track your service requests and their status</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Your Service Bookings</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['cancel'])): ?>
                        <?php if ($_GET['cancel']==='ok'): ?>
                            <div class="alert alert-success">Booking cancelled successfully.</div>
                        <?php elseif ($_GET['cancel']==='not_allowed'): ?>
                            <div class="alert alert-warning">This booking cannot be cancelled now.</div>
                        <?php elseif ($_GET['cancel']==='notfound' || $_GET['cancel']==='invalid'): ?>
                            <div class="alert alert-danger">Booking not found or invalid request.</div>
                        <?php else: ?>
                            <div class="alert alert-danger">Could not cancel booking. Please try again.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php
                    $result = $conn->query("SELECT b.id, b.booking_time, b.status, 
                                                   u.name AS provider, u.email as provider_email, u.phone as provider_phone,
                                                   s.name AS service, ps.rate
                                            FROM bookings b
                                            JOIN users u ON b.provider_id = u.id
                                            JOIN services s ON b.service_id = s.id
                                            JOIN provider_services ps ON b.provider_id = ps.provider_id AND b.service_id = ps.service_id
                                            WHERE b.customer_id = $customer_id
                                            ORDER BY b.booking_time DESC");

                    if ($result && $result->num_rows > 0) {
                        echo "<div class='table-responsive'>";
                        echo "<table class='table'>";
                        echo "<thead>";
                        echo "<tr><th>Booking ID</th><th>Service</th><th>Provider</th><th>Rate</th><th>Date</th><th>Status</th><th>Actions</th></tr>";
                        echo "</thead>";
                        echo "<tbody>";
                        
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><strong>#" . $row['id'] . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['service']) . "</td>";
                            echo "<td>";
                            echo "<strong>" . htmlspecialchars($row['provider']) . "</strong><br>";
                            if ($row['provider_email']) echo "<small>" . htmlspecialchars($row['provider_email']) . "</small><br>";
                            if ($row['provider_phone']) echo "<small>" . htmlspecialchars($row['provider_phone']) . "</small>";
                            echo "</td>";
                            echo "<td><strong>₹" . number_format($row['rate'], 2) . "/hr</strong></td>";
                            echo "<td><small>" . date('M j, Y<br>g:i A', strtotime($row['booking_time'])) . "</small></td>";
                            echo "<td><span class='badge badge-" . $row['status'] . "'>" . ucfirst($row['status']) . "</span></td>";
                            echo "<td>";
                            
                            if ($row['status'] == 'pending') {
                                echo "<div style='display: flex; flex-direction: column; gap: 0.25rem;'>";
                                echo "<small class='text-secondary'>⏳ Waiting for provider response</small>";
                                echo "<a href='cancel_booking.php?id=" . $row['id'] . "' class='btn btn-danger btn-small' onclick=\"return confirm('Cancel this booking request?')\">Cancel Request</a>";
                                echo "</div>";
                            } elseif ($row['status'] == 'accepted') {
                                echo "<div style='display: flex; flex-direction: column; gap: 0.25rem;'>";
                                echo "<small class='text-success'><strong>✓ Accepted!</strong></small>";
                                echo "<small class='text-secondary'>Provider will contact you soon</small>";
                                echo "</div>";
                            } elseif ($row['status'] == 'completed') {
                                echo "<div style='display: flex; flex-direction: column; gap: 0.25rem;'>";
                                echo "<small class='text-success'><strong>✓ Completed</strong></small>";
                                echo "<a href='rate_provider.php?booking_id=" . $row['id'] . "' class='btn btn-outline btn-small'>Rate Provider</a>";
                                echo "</div>";
                            } elseif ($row['status'] == 'rejected') {
                                echo "<div style='display: flex; flex-direction: column; gap: 0.25rem;'>";
                                echo "<small class='text-danger'><strong>✗ Declined</strong></small>";
                                echo "<a href='book_service.php?service_id=" . $row['service'] . "' class='btn btn-outline btn-small'>Book Another</a>";
                                echo "</div>";
                            } else {
                                echo "<span class='text-secondary'>No actions available</span>";
                            }
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody>";
                        echo "</table>";
                        echo "</div>";
                    } else {
                        echo "<div class='text-center' style='padding: 3rem;'>";
                        echo "<div style='font-size: 3rem; margin-bottom: 1rem;'>📋</div>";
                        echo "<h3>No Bookings Yet</h3>";
                        echo "<p class='text-secondary'>Your service bookings will appear here when you book services from providers.</p>";
                        echo "<a href='book_service.php' class='btn btn-primary'>Book Your First Service</a>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                <a href="book_service.php" class="btn btn-outline">Book New Service</a>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
