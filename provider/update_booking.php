<?php
session_start();
require_once "../config/db.php";

// Check if provider is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    header("Location: login.php");
    exit();
}

$provider_id = $_SESSION['user_id'];

// Get parameters
$booking_id = intval($_GET['id']);
$new_status = $_GET['status'];

// Validate status
$allowed_statuses = ['accepted', 'rejected', 'completed', 'cancelled'];
if (!in_array($new_status, $allowed_statuses)) {
    header("Location: dashboard.php?error=invalid_status");
    exit();
}

try {
    // Get booking details first
    $booking_query = "SELECT b.*, u.name as customer_name, u.email as customer_email, s.name as service_name
                      FROM bookings b 
                      JOIN users u ON b.customer_id = u.id 
                      JOIN services s ON b.service_id = s.id 
                      WHERE b.id = ? AND b.provider_id = ?";
    
    $stmt = $conn->prepare($booking_query);
    if (!$stmt) {
        error_log("SQL prepare error: " . $conn->error);
        header("Location: dashboard.php?error=sql_error");
        exit();
    }
    
    $stmt->bind_param("ii", $booking_id, $provider_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    if (!$booking) {
        header("Location: dashboard.php?error=booking_not_found");
        exit();
    }
    
    // Validate status transitions
    $current_status = $booking['status'];
    $valid_transitions = [
        'pending' => ['accepted', 'rejected'],
        'accepted' => ['completed', 'cancelled'],
        'completed' => [],
        'rejected' => [],
        'cancelled' => []
    ];
    
    if (!in_array($new_status, $valid_transitions[$current_status])) {
        header("Location: dashboard.php?error=invalid_transition");
        exit();
    }
    
    // Update booking status
    $update_query = "UPDATE bookings SET status = ? WHERE id = ? AND provider_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sii", $new_status, $booking_id, $provider_id);
    
    if ($update_stmt->execute()) {
        // Create notification for customer
        $customer_id = $booking['customer_id'];
        $service_name = $booking['service_name'];
        
        $notification_messages = [
            'accepted' => "Great news! Your booking for {$service_name} has been accepted by the provider. They will contact you soon.",
            'rejected' => "We're sorry, but your booking for {$service_name} has been declined. Please try booking with another provider.",
            'completed' => "Your {$service_name} service has been marked as completed. Thank you for using HomeAid!",
            'cancelled' => "Your booking for {$service_name} has been cancelled by the provider."
        ];
        
        $notification_message = $notification_messages[$new_status];
        
        $notification_query = "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())";
        $notification_stmt = $conn->prepare($notification_query);
        $notification_stmt->bind_param("is", $customer_id, $notification_message);
        $notification_stmt->execute();
        
        // Send email notification to customer
        require_once "../includes/email_functions.php";
        
        // Get provider rate if booking rate is null
        $rate = $booking['rate'];
        if (!$rate || $rate == 0) {
            // Try to get rate from provider's service rates if available
            $rate_query = "SELECT rate FROM provider_services WHERE provider_id = ? AND service_id = ?";
            $rate_stmt = $conn->prepare($rate_query);
            if ($rate_stmt) {
                $rate_stmt->bind_param("ii", $provider_id, $booking['service_id']);
                $rate_stmt->execute();
                $rate_result = $rate_stmt->get_result()->fetch_assoc();
                if ($rate_result && $rate_result['rate']) {
                    $rate = $rate_result['rate'];
                }
            }
            // Default rate if still not found
            if (!$rate) {
                $rate = 50; // Default rate
            }
        }
        
        // Prepare booking data for email
        $booking_data = [
            'booking_id' => $booking['id'],
            'customer_name' => $booking['customer_name'],
            'customer_email' => $booking['customer_email'],
            'provider_name' => $_SESSION['name'] ?? 'Provider',
            'provider_email' => $_SESSION['email'] ?? '',
            'service_name' => $booking['service_name'],
            'rate' => $rate,
            'status' => $new_status
        ];
        
        // Send appropriate email based on status (prevent duplicates)
        $email_key = "email_sent_" . $booking_id . "_" . $new_status;
        if (!isset($_SESSION[$email_key])) {
            if ($new_status === 'accepted') {
                sendBookingAcceptedNotification($booking_data);
                $_SESSION[$email_key] = time(); // Mark as sent
            } elseif ($new_status === 'rejected') {
                sendBookingRejectedNotification($booking_data);
                $_SESSION[$email_key] = time(); // Mark as sent
            }
        }
        
        // Set success message based on action
        $success_messages = [
            'accepted' => "Booking accepted successfully! Customer has been notified.",
            'rejected' => "Booking rejected. Customer has been notified.",
            'completed' => "Booking marked as completed! Customer has been notified.",
            'cancelled' => "Booking cancelled. Customer has been notified."
        ];
        
        $success_message = $success_messages[$new_status];
        header("Location: dashboard.php?success=" . urlencode($success_message));
        
    } else {
        header("Location: dashboard.php?error=update_failed");
    }
    
} catch (Exception $e) {
    error_log("Booking update error: " . $e->getMessage());
    header("Location: dashboard.php?error=system_error");
}

$conn->close();
?>
