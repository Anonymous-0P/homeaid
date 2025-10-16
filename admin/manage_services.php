<?php 
require_once "../includes/session_manager.php";
require_once "../includes/service_icons.php";

// Check authentication with session timeout
if (!SessionManager::checkAuth('admin')) {
    header("Location: login.php");
    exit();
}

include "../config/db.php";
include "../includes/header.php";
include "../includes/navbar.php";

// Handle form submissions
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $name = trim($_POST['name']);
            $desc = trim($_POST['description']);
            $icon_key = trim($_POST['icon_key']);
            
            if (!empty($name) && !empty($desc) && !empty($icon_key)) {
                $stmt = $conn->prepare("INSERT INTO services (name, description, icon_key) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $desc, $icon_key);
                
                if ($stmt->execute()) {
                    $success_message = "Service added successfully!";
                } else {
                    $error_message = "Error adding service: " . $conn->error;
                }
            } else {
                $error_message = "All fields are required.";
            }
        } elseif ($_POST['action'] == 'update') {
            $id = $_POST['id'];
            $name = trim($_POST['name']);
            $desc = trim($_POST['description']);
            $icon_key = trim($_POST['icon_key']);
            
            if (!empty($name) && !empty($desc) && !empty($icon_key)) {
                $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, icon_key = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $desc, $icon_key, $id);
                
                if ($stmt->execute()) {
                    $success_message = "Service updated successfully!";
                } else {
                    $error_message = "Error updating service: " . $conn->error;
                }
            } else {
                $error_message = "All fields are required.";
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success_message = "Service deleted successfully!";
            } else {
                $error_message = "Error deleting service: " . $conn->error;
            }
        }
    }
}

// Get available icons
$availableIcons = ServiceIcons::getAvailableIcons();
?>

<style>
/* Icon Selection Styles */
.icon-selector {
    margin: 1rem 0;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 0.5rem;
    margin-top: 0.5rem;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    max-height: 200px;
    overflow-y: auto;
}

.icon-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem 0.5rem;
    border: 2px solid transparent;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.icon-option:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.icon-option.selected {
    background: #eff6ff;
    border-color: #3b82f6;
}

.icon-emoji {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.icon-name {
    font-size: 0.7rem;
    color: #6b7280;
    text-transform: capitalize;
}

.service-icon-display {
    font-size: 1.5rem;
    text-align: center;
}

.edit-form {
    display: none;
    background: #f9fafb;
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1rem 0;
    border: 1px solid #e5e7eb;
}

.edit-form.active {
    display: block;
}

.btn-edit {
    background: #f59e0b;
    color: white;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    margin-right: 0.5rem;
}

@media (max-width: 768px) {
    .icon-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>

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
                    <form method="POST" id="addServiceForm">
                        <input type="hidden" name="action" value="add">
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
                                    <input type="text" id="description" name="description" class="form-control" placeholder="Brief description of the service" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Select Service Icon</label>
                            <input type="hidden" id="icon_key" name="icon_key" required>
                            <div class="icon-grid" id="iconGrid">
                                <?php foreach ($availableIcons as $key => $emoji): ?>
                                    <div class="icon-option" data-key="<?php echo htmlspecialchars($key); ?>">
                                        <span class="icon-emoji"><?php echo $emoji; ?></span>
                                        <span class="icon-name"><?php echo htmlspecialchars($key); ?></span>
                                    </div>
                                <?php endforeach; ?>
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
                        echo "<th>Icon</th>";
                        echo "<th>ID</th>";
                        echo "<th>Service Name</th>";
                        echo "<th>Description</th>";
                        echo "<th>Actions</th>";
                        echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                        
                        while ($row = $result->fetch_assoc()) {
                            $serviceIcon = ServiceIcons::getIconByKey($row['icon_key'] ?? 'toolbox');
                            echo "<tr>";
                            echo "<td class='service-icon-display'>" . $serviceIcon . "</td>";
                            echo "<td><strong>#" . htmlspecialchars($row['id']) . "</strong></td>";
                            echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['description'] ?? 'No description') . "</td>";
                            echo "<td>";
                            echo "<button class='btn btn-edit' onclick='toggleEdit(" . $row['id'] . ")'>Edit</button>";
                            echo "<form method='POST' style='display: inline;'>";
                            echo "<input type='hidden' name='action' value='delete'>";
                            echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
                            echo "<button type='submit' class='btn btn-danger btn-small' onclick='return confirm(\"Are you sure you want to delete this service?\")'>Delete</button>";
                            echo "</form>";
                            echo "</td>";
                            echo "</tr>";
                            
                            // Edit form row
                            echo "<tr>";
                            echo "<td colspan='5'>";
                            echo "<div class='edit-form' id='edit-" . $row['id'] . "'>";
                            echo "<form method='POST'>";
                            echo "<input type='hidden' name='action' value='update'>";
                            echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
                            echo "<div class='row'>";
                            echo "<div class='col-half'>";
                            echo "<div class='form-group'>";
                            echo "<label class='form-label'>Service Name</label>";
                            echo "<input type='text' name='name' class='form-control' value='" . htmlspecialchars($row['name']) . "' required>";
                            echo "</div>";
                            echo "</div>";
                            echo "<div class='col-half'>";
                            echo "<div class='form-group'>";
                            echo "<label class='form-label'>Description</label>";
                            echo "<input type='text' name='description' class='form-control' value='" . htmlspecialchars($row['description']) . "' required>";
                            echo "</div>";
                            echo "</div>";
                            echo "</div>";
                            echo "<div class='form-group'>";
                            echo "<label class='form-label'>Select Icon</label>";
                            echo "<input type='hidden' name='icon_key' value='" . htmlspecialchars($row['icon_key'] ?? 'toolbox') . "' class='edit-icon-input'>";
                            echo "<div class='icon-grid edit-icon-grid'>";
                            foreach ($availableIcons as $key => $emoji) {
                                $selected = ($row['icon_key'] ?? 'toolbox') == $key ? 'selected' : '';
                                echo "<div class='icon-option " . $selected . "' data-key='" . htmlspecialchars($key) . "'>";
                                echo "<span class='icon-emoji'>" . $emoji . "</span>";
                                echo "<span class='icon-name'>" . htmlspecialchars($key) . "</span>";
                                echo "</div>";
                            }
                            echo "</div>";
                            echo "</div>";
                            echo "<button type='submit' class='btn btn-primary'>Update Service</button>";
                            echo "<button type='button' class='btn btn-outline' onclick='toggleEdit(" . $row['id'] . ")' style='margin-left: 10px;'>Cancel</button>";
                            echo "</form>";
                            echo "</div>";
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

<script>
// Icon selection functionality
function initIconSelection() {
    document.querySelectorAll('.icon-grid').forEach(grid => {
        const hiddenInput = grid.parentElement.querySelector('input[type="hidden"]') || 
                          grid.closest('.edit-form')?.querySelector('.edit-icon-input');
        
        grid.querySelectorAll('.icon-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from siblings
                grid.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Update hidden input
                if (hiddenInput) {
                    hiddenInput.value = this.dataset.key;
                }
            });
        });
    });
}

function toggleEdit(serviceId) {
    const editForm = document.getElementById('edit-' + serviceId);
    if (editForm) {
        editForm.classList.toggle('active');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initIconSelection);
</script>

<?php include "../includes/footer.php"; ?>
