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

$result = $conn->query("SELECT id, name, email, phone, created_at FROM users WHERE role='customer' ORDER BY created_at DESC");
?>

<main>
  <div class="container">
    <div class="content-wrapper">
      <div class="d-flex justify-content-between align-items-center mb-4" style="gap:1.5rem;">
        <h1>Manage Customers</h1>
        <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
      </div>
      
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">All Customers</h2>
        </div>
        <div class="card-body">
          <?php if($result && $result->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Joined</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                  <td>#<?= htmlspecialchars($row['id']) ?></td>
                  <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                  <td><?= htmlspecialchars($row['email']) ?></td>
                  <td><?= htmlspecialchars($row['phone'] ?? 'N/A') ?></td>
                  <td><small><?= date('M j, Y', strtotime($row['created_at'])) ?></small></td>
                  <td>
                    <a href="delete_user.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-small" 
                       onclick="return confirm('Are you sure you want to delete this customer?')">Delete</a>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
            <div class="text-center" style="padding:2rem;">
              <h3>No customers found</h3>
              <p class="text-secondary">No customers have registered yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include "../includes/footer.php"; ?>
