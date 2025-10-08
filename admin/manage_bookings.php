<?php 
require_once "../includes/session_manager.php";

// Check authentication with session timeout
if (!SessionManager::checkAuth('admin')) {
    header("Location: login.php");
    exit();
}

include "../config/db.php";
include "../includes/header.php";
include "../includes/navbar.php";

$result = $conn->query("SELECT b.id, b.booking_time, b.status, 
                               u1.name as customer, u1.email as customer_email,
                               u2.name as provider, u2.email as provider_email,
                               s.name as service, ps.rate
                        FROM bookings b
                        JOIN users u1 ON b.customer_id = u1.id
                        JOIN users u2 ON b.provider_id = u2.id
                        JOIN services s ON b.service_id = s.id
                        LEFT JOIN provider_services ps ON b.provider_id = ps.provider_id AND b.service_id = ps.service_id
                        ORDER BY b.booking_time DESC");
?>

<main>
  <div class="container">
    <div class="content-wrapper">
      <div class="d-flex justify-content-between align-items-center mb-4" style="gap:1.5rem;">
        <h1>Manage Bookings</h1>
        <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
      </div>
      
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">All Bookings</h2>
        </div>
        <div class="card-body">
          <?php if($result && $result->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Date</th>
                  <th>Customer</th>
                  <th>Provider</th>
                  <th>Service</th>
                  <th>Rate</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
              <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><strong>#<?= htmlspecialchars($row['id']) ?></strong></td>
                  <td><small><?= date('M j, Y g:i A', strtotime($row['booking_time'])) ?></small></td>
                  <td>
                    <strong><?= htmlspecialchars($row['customer']) ?></strong><br>
                    <small><?= htmlspecialchars($row['customer_email']) ?></small>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($row['provider']) ?></strong><br>
                    <small><?= htmlspecialchars($row['provider_email']) ?></small>
                  </td>
                  <td><?= htmlspecialchars($row['service']) ?></td>
                  <td><?= $row['rate'] ? '₹'.number_format($row['rate'], 2).'/hr' : 'N/A' ?></td>
                  <td><span class="badge badge-<?= htmlspecialchars($row['status']) ?>"><?= ucfirst($row['status']) ?></span></td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
            <div class="text-center" style="padding:2rem;">
              <h3>No bookings found</h3>
              <p class="text-secondary">No bookings have been made yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include "../includes/footer.php"; ?>
