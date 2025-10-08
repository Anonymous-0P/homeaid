<?php
require_once __DIR__ . '/../includes/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/email_functions.php';

// Require customer auth
if (!SessionManager::checkAuth('customer')) {
    header('Location: login.php');
    exit();
}

$customerId = (int)($_SESSION['user_id'] ?? 0);
$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bookingId <= 0) {
    header('Location: my_bookings.php?cancel=invalid');
    exit();
}

// Fetch booking and ensure ownership + allowed status
$sql = "SELECT b.id, b.status, b.provider_id, b.customer_id, b.service_id,
               s.name AS service_name,
               cust.name AS customer_name, cust.email AS customer_email,
               prov.name AS provider_name, prov.email AS provider_email
        FROM bookings b
        JOIN services s ON s.id = b.service_id
        JOIN users cust ON cust.id = b.customer_id
        JOIN users prov ON prov.id = b.provider_id
        WHERE b.id = ? AND b.customer_id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('cancel_booking SELECT prepare failed: ' . $conn->error);
    header('Location: my_bookings.php?cancel=error');
    exit();
}
$stmt->bind_param('ii', $bookingId, $customerId);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows !== 1) {
    header('Location: my_bookings.php?cancel=notfound');
    exit();
}
$booking = $res->fetch_assoc();

// Only allow cancelling when pending (adjust if you want to allow accepted cancellations)
if (!in_array($booking['status'], ['pending'], true)) {
    header('Location: my_bookings.php?cancel=not_allowed');
    exit();
}

// Update to cancelled (use bound param for status)
$upd = $conn->prepare('UPDATE bookings SET status = ? WHERE id = ? AND customer_id = ?');
if (!$upd) {
    error_log('cancel_booking UPDATE prepare failed: ' . $conn->error);
    header('Location: my_bookings.php?cancel=error');
    exit();
}
$statusVal = 'cancelled';
$upd->bind_param('sii', $statusVal, $bookingId, $customerId);
if (!$upd->execute()) {
    error_log('cancel_booking UPDATE execute failed: ' . $conn->error);
    header('Location: my_bookings.php?cancel=error');
    exit();
}

// Notify provider in notifications table
$notify = $conn->prepare('INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())');
if ($notify) {
    $msg = 'Booking #' . $bookingId . ' (' . $booking['service_name'] . ') was cancelled by the customer.';
    $notify->bind_param('is', $booking['provider_id'], $msg);
    $notify->execute();
}

// Send emails (best-effort)
$payload = [
    'booking_id'    => $bookingId,
    'status'        => 'cancelled',
    'service_name'  => $booking['service_name'],
    'customer_name' => $booking['customer_name'],
    'customer_email'=> $booking['customer_email'],
    'provider_name' => $booking['provider_name'],
    'provider_email'=> $booking['provider_email'],
];
// Email customer and provider
@sendBookingStatusUpdate($payload, 'customer');
@sendBookingStatusUpdate($payload, 'provider');

header('Location: my_bookings.php?cancel=ok');
exit();
