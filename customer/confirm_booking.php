<?php
session_start();
require_once "../config/db.php";
require_once "../includes/email_functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

// Handle both GET and POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider_id = intval($_POST['provider_id']);
    $service_id = intval($_POST['service_id']);
} else {
    $provider_id = intval($_GET['provider_id']);
    $service_id = intval($_GET['service_id']);
}

if (!$provider_id || !$service_id) {
    header("Location: book_service.php?error=missing_params");
    exit();
}

try {
    // Ensure notifications table exists (in case database imported without it)
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, message TEXT NOT NULL, is_read BOOLEAN DEFAULT FALSE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");

    $conn->begin_transaction();
    // Get service and provider details for the notification
    $details_query = "SELECT s.name as service_name, u.name as provider_name, u.email as provider_email,
                             c.name as customer_name, c.email as customer_email, ps.rate
                      FROM services s 
                      JOIN provider_services ps ON s.id = ps.service_id
                      JOIN users u ON ps.provider_id = u.id 
                      JOIN users c ON c.id = ?
                      WHERE s.id = ? AND u.id = ?";
    
    $stmt = $conn->prepare($details_query);
    $stmt->bind_param("iii", $customer_id, $service_id, $provider_id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();
    
    if (!$details) {
        header("Location: book_service.php?error=invalid_booking");
        exit();
    }
    
    // Insert booking
    $booking_query = "INSERT INTO bookings (customer_id, provider_id, service_id, status, booking_time) 
                      VALUES (?, ?, ?, 'pending', NOW())";
    $booking_stmt = $conn->prepare($booking_query);
    $booking_stmt->bind_param("iii", $customer_id, $provider_id, $service_id);
    
    if ($booking_stmt->execute()) {
        $booking_id = $conn->insert_id;
        
        // Prepare booking data for email notifications
        $booking_data = [
            'booking_id' => $booking_id,
            'customer_name' => $details['customer_name'],
            'customer_email' => $details['customer_email'],
            'provider_name' => $details['provider_name'],
            'provider_email' => $details['provider_email'],
            'service_name' => $details['service_name'],
            'rate' => $details['rate'],
            'status' => 'pending'
        ];
        
        // Create detailed notification for provider
        $provider_message = "🔔 New Booking Request!\n\n";
        $provider_message .= "Customer: {$details['customer_name']}\n";
        $provider_message .= "Service: {$details['service_name']}\n";
        $provider_message .= "Rate: ₹{$details['rate']}/hour\n";
        $provider_message .= "Booking ID: #{$booking_id}\n\n";
        $provider_message .= "Please review and respond to this booking request in your dashboard.";
        
        $provider_notification = "INSERT INTO notifications (user_id, message, is_read, created_at) 
                                  VALUES (?, ?, FALSE, NOW())";
        $provider_stmt = $conn->prepare($provider_notification);
        $provider_stmt->bind_param("is", $provider_id, $provider_message);
    if(!$provider_stmt->execute()) { throw new Exception('Provider notification insert failed: '.$provider_stmt->error); }
        
        // Create confirmation notification for customer
        $customer_message = "✅ Booking Request Submitted!\n\n";
        $customer_message .= "Service: {$details['service_name']}\n";
        $customer_message .= "Provider: {$details['provider_name']}\n";
        $customer_message .= "Booking ID: #{$booking_id}\n\n";
        $customer_message .= "Your booking request has been sent to the provider. You will be notified once they respond.";
        
        $customer_notification = "INSERT INTO notifications (user_id, message, is_read, created_at) 
                                  VALUES (?, ?, FALSE, NOW())";
        $customer_stmt = $conn->prepare($customer_notification);
        $customer_stmt->bind_param("is", $customer_id, $customer_message);
    if(!$customer_stmt->execute()) { throw new Exception('Customer notification insert failed: '.$customer_stmt->error); }
        
        // Send email notifications
        $email_success = [];
        
        // Send email to provider
        try {
            $provider_email_sent = sendProviderBookingNotification($booking_data);
            if ($provider_email_sent) {
                $email_success[] = "Provider notification email sent successfully";
                error_log("Booking notification email sent to provider: " . $details['provider_email']);
            } else {
                error_log("Failed to send booking notification email to provider: " . $details['provider_email']);
            }
        } catch (Exception $e) {
            error_log("Error sending provider email: " . $e->getMessage());
        }
        
        // Send confirmation email to customer
        try {
            $customer_email_sent = sendCustomerBookingConfirmation($booking_data);
            if ($customer_email_sent) {
                $email_success[] = "Customer confirmation email sent successfully";
                error_log("Booking confirmation email sent to customer: " . $details['customer_email']);
            } else {
                error_log("Failed to send booking confirmation email to customer: " . $details['customer_email']);
            }
        } catch (Exception $e) {
            error_log("Error sending customer email: " . $e->getMessage());
        }
        
        // Clear cart if booking came from cart
        if (isset($_SESSION['cart'])) {
            $cart_key = array_search($service_id, $_SESSION['cart']);
            if ($cart_key !== false) {
                unset($_SESSION['cart'][$cart_key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index
            }
        }
        
    $success_message = "Booking successful! Your provider will be notified and will respond soon.";
    if (!empty($email_success)) {
        $success_message .= " Email notifications have been sent to both you and the provider.";
    }
    $conn->commit();
        
    } else {
        throw new Exception("Failed to create booking: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Booking creation error: " . $e->getMessage());
    // Attempt rollback (safe even if no active transaction)
    try { $conn->rollback(); } catch (Throwable $rt) {}
    $error_message = "Sorry, there was an error processing your booking. Please try again.";
}

include "../includes/header.php";
include "../includes/navbar.php";
?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <?php if (isset($success_message)): ?>
                            🎉 Booking Confirmed!
                        <?php else: ?>
                            ❌ Booking Failed
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="card-body text-center">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <h3>Thank you for your booking!</h3>
                            <p><?php echo $success_message; ?></p>
                            
                            <?php if (isset($details)): ?>
                                <div class="booking-details" style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; text-align: left;">
                                    <h4>Booking Details:</h4>
                                    <p><strong>Service:</strong> <?php echo htmlspecialchars($details['service_name']); ?></p>
                                    <p><strong>Provider:</strong> <?php echo htmlspecialchars($details['provider_name']); ?></p>
                                    <p><strong>Rate:</strong> ₹<?php echo number_format($details['rate'], 2); ?>/hour</p>
                                    <p><strong>Booking ID:</strong> #<?php echo $booking_id; ?></p>
                                    <p><strong>Status:</strong> <span class="badge badge-pending">Pending</span></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="next-steps" style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">
                                <h4>What happens next?</h4>
                                <ol style="text-align: left; margin: 1rem 0;">
                                    <li>The provider will be notified of your booking request</li>
                                    <li>They will review and either accept or decline your request</li>
                                    <li>You'll receive a notification with their response</li>
                                    <li>If accepted, the provider will contact you to arrange the service</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                            <a href="book_service.php" class="btn btn-outline">Book Another Service</a>
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <h3>Booking Failed</h3>
                            <p><?php echo isset($error_message) ? $error_message : 'An unexpected error occurred.'; ?></p>
                        </div>
                        
                        <div class="action-buttons">
                            <a href="book_service.php" class="btn btn-primary">Try Again</a>
                            <a href="dashboard.php" class="btn btn-outline">Go to Dashboard</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
