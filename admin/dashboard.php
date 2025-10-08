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
?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <!-- <?php include "../includes/session_info.php"; ?> -->
            
            <h1>Admin Dashboard</h1>
            <p>Welcome to the HomeAid administration panel. Manage your platform below.</p>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="dashboard-stat">
                        <span class="dashboard-stat-number">
                            <?php
                            $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='customer'");
                            if ($result) {
                                $count = $result->fetch_assoc()['count'];
                                echo $count;
                            } else {
                                echo "0";
                            }
                            ?>
                        </span>
                        <span class="dashboard-stat-label">Total Customers</span>
                    </div>
                    <div class="text-center">
                        <a href="manage_users.php" class="btn btn-primary">Manage Customers</a>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-stat">
                        <span class="dashboard-stat-number">
                            <?php
                            $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='provider'");
                            if ($result) {
                                $count = $result->fetch_assoc()['count'];
                                echo $count;
                            } else {
                                echo "0";
                            }
                            ?>
                        </span>
                        <span class="dashboard-stat-label">Total Providers</span>
                    </div>
                    <div class="text-center">
                        <a href="manage_providers.php" class="btn btn-secondary">Manage Providers</a>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-stat">
                        <span class="dashboard-stat-number">
                            <?php
                            $result = $conn->query("SELECT COUNT(*) as count FROM services");
                            if ($result) {
                                $count = $result->fetch_assoc()['count'];
                                echo $count;
                            } else {
                                echo "0";
                            }
                            ?>
                        </span>
                        <span class="dashboard-stat-label">Total Services</span>
                    </div>
                    <div class="text-center">
                        <a href="manage_services.php" class="btn btn-outline">Manage Services</a>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-stat">
                        <span class="dashboard-stat-number">
                            <?php
                            $result = $conn->query("SELECT COUNT(*) as count FROM bookings");
                            if ($result) {
                                $count = $result->fetch_assoc()['count'];
                                echo $count;
                            } else {
                                echo "0";
                            }
                            ?>
                        </span>
                        <span class="dashboard-stat-label">Total Bookings</span>
                    </div>
                    <div class="text-center">
                        <a href="manage_bookings.php" class="btn btn-outline">Manage Bookings</a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-2">
                                <a href="reports.php" class="btn btn-primary">View Reports</a>
                                <a href="manage_bookings.php" class="btn btn-secondary">Recent Bookings</a>
                                <a href="manage_users.php" class="btn btn-outline">User Management</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">System Status</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <strong>System Status:</strong> All services operational
                            </div>
                            <p><strong>Last Login:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                            <p><strong>Platform Version:</strong> HomeAid v1.0</p>
                        </div>
                    </div>
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
