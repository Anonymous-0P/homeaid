<?php
session_start();
include "../config/db.php";
include "../includes/header.php";
include "../includes/navbar.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    echo "<div class='alert alert-danger'>Provider access required. Please login.</div>";
    echo "<a href='login.php' class='btn btn-primary'>Provider Login</a>";
    exit();
}

$provider_id = $_SESSION['user_id'];
// Migration safeguard: add is_active column if missing
$col_check = $conn->query("SHOW COLUMNS FROM provider_services LIKE 'is_active'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE provider_services ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER rate");
}
$success_message = "";
$error_message = "";
$welcome_message = "";

// Check for welcome parameter (new registration)
if (isset($_GET['welcome']) && $_GET['welcome'] == '1') {
    $welcome_message = "Welcome to HomeAid! Please set your service rates below to start receiving booking requests from customers.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle existing rates update
    $rates = $_POST['rate'] ?? [];
    foreach ($rates as $service_id => $rate) {
        $service_id = (int)$service_id;
        $rate = (float)$rate;
        if ($rate > 0) {
            // Update existing rate
            $stmt = $conn->prepare("UPDATE provider_services SET rate = ?, is_active = 1 WHERE provider_id = ? AND service_id = ?");
            $stmt->bind_param("dii", $rate, $provider_id, $service_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Deactivate if set to 0
            $stmt = $conn->prepare("UPDATE provider_services SET is_active = 0 WHERE provider_id = ? AND service_id = ?");
            $stmt->bind_param("ii", $provider_id, $service_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Handle new service rates
    $new_service_ids = $_POST['service_id'] ?? [];
    $new_rates = $_POST['rate_new'] ?? [];
    if (is_array($new_service_ids) && is_array($new_rates)) {
        foreach ($new_service_ids as $idx => $sid) {
            $sid = (int)$sid;
            $rate = isset($new_rates[$idx]) ? (float)$new_rates[$idx] : 0;
            if ($sid > 0 && $rate > 0) {
                // Insert new service rate if not already present
                $check = $conn->prepare("SELECT COUNT(*) FROM provider_services WHERE provider_id = ? AND service_id = ?");
                $check->bind_param("ii", $provider_id, $sid);
                $check->execute();
                $check->bind_result($count);
                $check->fetch();
                $check->close();
                if ($count == 0) {
                    $stmt = $conn->prepare("INSERT INTO provider_services (provider_id, service_id, rate, is_active) VALUES (?, ?, ?, 1)");
                    $stmt->bind_param("iid", $provider_id, $sid, $rate);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // If already present, update and activate
                    $stmt = $conn->prepare("UPDATE provider_services SET rate = ?, is_active = 1 WHERE provider_id = ? AND service_id = ?");
                    $stmt->bind_param("dii", $rate, $provider_id, $sid);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
    // After processing, verify at least one active service > 0
    $check_active = $conn->prepare("SELECT COUNT(*) FROM provider_services WHERE provider_id=? AND is_active=1");
    $check_active->bind_param("i", $provider_id);
    $check_active->execute();
    $check_active->bind_result($active_count);
    $check_active->fetch();
    $check_active->close();
    if ($active_count > 0) {
        $success_message = "Rates saved successfully.";
    } else {
        $error_message = "You must set at least one active service rate above 0 to appear in searches.";
    }
}



// Get all services
$all_services = [];
$services_result = $conn->query("SELECT id, name, description FROM services ORDER BY name");
while ($row = $services_result->fetch_assoc()) {
    $all_services[$row['id']] = $row;
}

// Get current rates for this provider
$current_rates = [];
$rates_result = $conn->query("SELECT service_id, rate FROM provider_services WHERE provider_id = $provider_id");
while ($row = $rates_result->fetch_assoc()) {
    $current_rates[$row['service_id']] = $row['rate'];
}

// Get provider info
$provider_info = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$provider_info->bind_param("i", $provider_id);
$provider_info->execute();
$provider = $provider_info->get_result()->fetch_assoc();
?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Set Your Service Rates</h1>
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            </div>
            
            <?php if ($welcome_message): ?>
                <div class="alert alert-info">
                    <h4>🎉 Registration Successful!</h4>
                    <p><?php echo $welcome_message; ?></p>
                    <p><strong>Important:</strong> You won't appear in customer searches until you set at least one service rate below.</p>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- Provider Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Provider Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-half">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($provider['name']); ?></p>
                        </div>
                        <div class="col-half">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($provider['email']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            

            <!-- Rate Setting Form (Dropdown + Add) -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Service Rates</h3>
                    <p class="text-secondary mt-2">Select a service and set your hourly rate. Click "Add Service" to add more.</p>
                </div>
                <div class="card-body">
                    <form method="POST" id="ratesForm">
                        <div id="serviceRateRows">
                            <!-- Existing rates -->
                            <?php foreach ($current_rates as $sid => $rate): ?>
                                <div class="service-rate-row" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                    <select name="service_id_existing[]" class="form-control" disabled>
                                        <option value="<?php echo $sid; ?>" selected><?php echo htmlspecialchars($all_services[$sid]['name']); ?></option>
                                    </select>
                                    <input type="number" name="rate[<?php echo $sid; ?>]" class="form-control" step="0.01" min="0" value="<?php echo number_format($rate, 2); ?>" required>
                                    <span>/hour</span>
                                    <span class="badge badge-accepted">Already Added</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="addServiceRow" style="margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <select name="service_id[]" class="form-control service-dropdown">
                                    <option value="">Select Service</option>
                                    <?php foreach ($all_services as $sid => $service): ?>
                                        <?php if (!isset($current_rates[$sid])): ?>
                                            <option value="<?php echo $sid; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="rate_new[]" class="form-control rate-input" step="0.01" min="0" placeholder="0.00">
                                <span>/hour</span>
                                <button type="button" class="btn btn-secondary" onclick="addServiceRow()">Add Service</button>
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-large">Save All Rates</button>
                            <button type="reset" class="btn btn-outline btn-large">Reset Form</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            function addServiceRow() {
                // Clone the addServiceRow div
                var row = document.getElementById('addServiceRow');
                var clone = row.cloneNode(true);
                // Remove the Add button from the clone
                clone.querySelector('button').remove();
                // Clear the dropdown and input
                clone.querySelector('select').selectedIndex = 0;
                clone.querySelector('input').value = '';
                // Add a remove button
                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-danger';
                removeBtn.textContent = 'Remove';
                removeBtn.onclick = function() { clone.remove(); };
                clone.querySelector('div').appendChild(removeBtn);
                // Insert before the original addServiceRow
                row.parentNode.insertBefore(clone, row);
            }
            </script>
            
            <!-- Current Rates Summary -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Current Rate Summary</h3>
                </div>
                <div class="card-body">
                    <?php
                    // Get current rates for summary
                    $current_rates = $conn->prepare("
                        SELECT s.name, ps.rate 
                        FROM services s 
                        JOIN provider_services ps ON s.id = ps.service_id 
                        WHERE ps.provider_id = ? 
                        ORDER BY s.name
                    ");
                    $current_rates->bind_param("i", $provider_id);
                    $current_rates->execute();
                    $rates_result = $current_rates->get_result();
                    
                    if ($rates_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Hourly Rate</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($rate = $rates_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($rate['name']); ?></strong></td>
                                            <td>₹<?php echo number_format($rate['rate'], 2); ?>/hour</td>
                                            <td><span class="badge badge-accepted">Active</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <h4>No Active Services</h4>
                            <p class="text-secondary">Set your rates above to start offering services.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <?php
                // Re-check active services to optionally disable dashboard navigation
                $active_now = $conn->query("SELECT COUNT(*) as c FROM provider_services WHERE provider_id=$provider_id AND is_active=1")->fetch_assoc()['c'];
                if ($active_now > 0) {
                    echo '<a href="dashboard.php" class="btn btn-outline">Back to Provider Dashboard</a>';
                } else {
                    echo '<button class="btn btn-outline" disabled title="Add at least one active rate to proceed">Back to Provider Dashboard</button>';
                }
                ?>
                <a href="notifications.php" class="btn btn-secondary">View Notifications</a>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
