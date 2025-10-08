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

// Add service
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO services (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $desc);
        
        if ($stmt->execute()) {
            $success_message = "Service added successfully!";
        } else {
            $error_message = "Error adding service: " . $conn->error;
        }
    } else {
        $error_message = "Service name is required.";
    }
}
?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4" style="gap:37rem;">
                <h1>Manage Services</h1>
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- Add New Service Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Add New Service</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-half">
                                <div class="form-group">
                                    <label for="name" class="form-label">Service Name</label>
                                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g., Plumbing, Electrical" required>
                                </div>
                            </div>
                            <div class="col-half">
                                <div class="form-group">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" id="description" name="description" class="form-control" placeholder="Brief description of the service">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Service</button>
                    </form>
                </div>
            </div>
            
            <!-- Services List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Existing Services</h3>
                </div>
                <div class="card-body">
                    <?php
                    $result = $conn->query("SELECT * FROM services ORDER BY name");
                    
                    if ($result && $result->num_rows > 0) {
                        echo "<div class='table-responsive'>";
                        echo "<table class='table'>";
                        echo "<thead>";
                        echo "<tr>";
                        echo "<th>ID</th>";
                        echo "<th>Service Name</th>";
                        echo "<th>Description</th>";
                        echo "<th>Actions</th>";
                        echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                        
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><strong>#" . htmlspecialchars($row['id']) . "</strong></td>";
                            echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['description'] ?? 'No description') . "</td>";
                            echo "<td>";
                            echo "<a href='delete_service.php?id=" . $row['id'] . "' class='btn btn-danger btn-small' onclick='return confirm(\"Are you sure you want to delete this service?\")'>Delete</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody>";
                        echo "</table>";
                        echo "</div>";
                    } else {
                        echo "<div class='text-center p-4'>";
                        echo "<h4>No Services Found</h4>";
                        echo "<p class='text-secondary'>Add your first service using the form above.</p>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
            
            <!-- Service Statistics -->
            <div class="row mt-4">
                <div class="col-third">
                    <div class="dashboard-card">
                        <div class="dashboard-stat">
                            <span class="dashboard-stat-number">
                                <?php
                                $count_result = $conn->query("SELECT COUNT(*) as count FROM services");
                                echo $count_result ? $count_result->fetch_assoc()['count'] : 0;
                                ?>
                            </span>
                            <span class="dashboard-stat-label">Total Services</span>
                        </div>
                    </div>
                </div>
                <div class="col-third">
                    <div class="dashboard-card">
                        <div class="dashboard-stat">
                            <span class="dashboard-stat-number">
                                <?php
                                $provider_count = $conn->query("SELECT COUNT(DISTINCT provider_id) as count FROM service_rates");
                                echo $provider_count ? $provider_count->fetch_assoc()['count'] : 0;
                                ?>
                            </span>
                            <span class="dashboard-stat-label">Active Providers</span>
                        </div>
                    </div>
                </div>
                <div class="col-third">
                    <div class="dashboard-card">
                        <div class="dashboard-stat">
                            <span class="dashboard-stat-number">
                                <?php
                                $booking_count = $conn->query("SELECT COUNT(*) as count FROM bookings");
                                echo $booking_count ? $booking_count->fetch_assoc()['count'] : 0;
                                ?>
                            </span>
                            <span class="dashboard-stat-label">Total Bookings</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-outline">Back to Admin Dashboard</a>
                <a href="manage_providers.php" class="btn btn-secondary">Manage Providers</a>
                <a href="manage_bookings.php" class="btn btn-secondary">Manage Bookings</a>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
