<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    if (isset($_POST['action']) || isset($_GET['action'])) {
        // AJAX request
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please login to access cart']);
        exit;
    } else {
        // Regular page request
        header('Location: login.php');
        exit;
    }
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle AJAX requests
if (isset($_POST['action']) || isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? $_GET['action'];
    
    switch ($action) {
        case 'add':
            if (isset($_POST['service_id'])) {
                $service_id = intval($_POST['service_id']);
                
                // Verify service exists
                $service = $conn->query("SELECT name FROM services WHERE id=$service_id")->fetch_assoc();
                if ($service) {
                    if (!in_array($service_id, $_SESSION['cart'])) {
                        $_SESSION['cart'][] = $service_id;
                        echo json_encode(['success' => true, 'message' => 'Service added to cart']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Service already in cart']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Service not found']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Service ID required']);
            }
            break;
            
        case 'remove':
            if (isset($_POST['service_id'])) {
                $service_id = intval($_POST['service_id']);
                if (($key = array_search($service_id, $_SESSION['cart'])) !== false) {
                    unset($_SESSION['cart'][$key]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
                    echo json_encode(['success' => true, 'message' => 'Service removed from cart']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Service not in cart']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Service ID required']);
            }
            break;
            
        case 'count':
            echo json_encode(['count' => count($_SESSION['cart'])]);
            break;
            
        case 'clear':
            $_SESSION['cart'] = [];
            echo json_encode(['success' => true, 'message' => 'Cart cleared']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Handle regular page form submissions (for backwards compatibility)
if (isset($_GET['service_id'])) {
    $service_id = intval($_GET['service_id']);
    if (!in_array($service_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $service_id;
        $success_message = "Service added to cart!";
    }
}

if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    if (($key = array_search($remove_id, $_SESSION['cart'])) !== false) {
        unset($_SESSION['cart'][$key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
        $info_message = "Service removed from cart.";
    }
}

include "../includes/header.php"; 
include "../includes/navbar.php"; 
?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($info_message)): ?>
                <div class="alert alert-info"><?php echo $info_message; ?></div>
            <?php endif; ?>
            
            <h1>Your Cart</h1>
            <p>Review your selected services before proceeding to booking.</p>
            
            <?php if (empty($_SESSION['cart'])): ?>
                <div class="card">
                    <div class="card-body text-center">
                        <h3>Your cart is empty</h3>
                        <p>Browse our services and add them to your cart to get started.</p>
                        <a href="../index.php" class="btn btn-primary">Browse Services</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Selected Services</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Description</th>
                                        <th style="min-width:180px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_services = 0;
                                    foreach ($_SESSION['cart'] as $service_id) {
                                        $service = $conn->query("SELECT * FROM services WHERE id=$service_id")->fetch_assoc();
                                        if ($service) {
                                            $total_services++;
                                            echo "<tr>";
                                            echo "<td><strong>" . htmlspecialchars($service['name']) . "</strong></td>";
                                            echo "<td>" . htmlspecialchars($service['description'] ?? 'Professional service') . "</td>";
                                            echo "<td style='display:flex; gap:0.5rem; flex-wrap:wrap;'>";
                                            echo "<button type='button' class='btn btn-primary btn-small' onclick=\"openProviderModal($service_id,'" . addslashes($service['name']) . "')\">Book</button>";
                                            echo "<a href='cart.php?remove=$service_id' class='btn btn-danger btn-small'>Remove</a>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-half">
                                <div class="card">
                                    <div class="card-body">
                                        <h4>Cart Summary</h4>
                                        <p><strong>Total Services:</strong> <?php echo $total_services; ?></p>
                                        <p class="text-secondary">Final pricing will be determined by selected providers.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-half">
                                <div class="card">
                                    <div class="card-body">
                                        <h4>Next Steps</h4>
                                        <p style="font-size:0.85rem;" class="text-secondary">Click \"Book\" beside a service to choose a provider and confirm the booking. Remove services you no longer need.</p>
                                        <a href="../customer/book_service.php" class="btn btn-outline mt-2" style="width: 100%;">Browse More Services</a>
                                        <a href="../index.php" class="btn btn-outline mt-2" style="width: 100%;">Continue Shopping</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            </div>
        </div>
    </div>
</main>

<!-- Provider Selection Modal (reused) -->
<div id="providerModal" class="provider-modal">
    <div class="provider-modal-content">
        <span class="close" onclick="closeProviderModal()">&times;</span>
        <h2>Select Provider for <span id="modalServiceName"></span></h2>
        <form id="bookingForm" method="POST" action="confirm_booking.php">
            <input type="hidden" id="selectedServiceId" name="service_id" value="">
            <input type="hidden" id="selectedProviderId" name="provider_id" value="">
            <input type="hidden" id="selectedProviderName" name="provider_name" value="">
            <input type="hidden" id="selectedProviderRate" name="provider_rate" value="">
            <div id="providerList" class="provider-list"></div>
            <div class="text-center mt-3">
                <button type="button" id="bookNowBtn" onclick="bookNow()" class="btn btn-primary" disabled>Select a Provider</button>
                <button type="button" onclick="closeProviderModal()" class="btn btn-outline ml-2">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/booking.js"></script>
<script>
// Provide hidden lat/lng if already captured on booking page for distance sorting
document.addEventListener('DOMContentLoaded', ()=>{
  const sLat=localStorage.getItem('homeaid_lat');
  const sLng=localStorage.getItem('homeaid_lng');
  if(sLat && sLng){
     let lat=document.getElementById('userLat'); if(!lat){lat=document.createElement('input');lat.type='hidden';lat.id='userLat';document.body.appendChild(lat);} lat.value=sLat;
     let lng=document.getElementById('userLng'); if(!lng){lng=document.createElement('input');lng.type='hidden';lng.id='userLng';document.body.appendChild(lng);} lng.value=sLng;
  }
});
</script>

<?php include "../includes/footer.php"; ?>
