<?php 
include "../includes/header.php"; 
include "../includes/navbar.php"; 
include "../config/db.php"; 
require_once "../includes/service_icons.php";

// Get service info from database
$service_name = 'plumbing';
$service_info = null;
$service_icon = '🔧'; // fallback

try {
    $stmt = $conn->prepare("SELECT name, description, icon_key FROM services WHERE name = ?");
    $stmt->bind_param("s", $service_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($service_info = $result->fetch_assoc()) {
        $service_icon = ServiceIcons::getIconByKey($service_info['icon_key']);
    }
} catch (Exception $e) {
    // Use fallback icon if database error
}
?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <h1 class="text-center">Plumbing Services</h1>
            <p class="text-center">Professional plumbing solutions for all your water and pipe needs.</p>
            
            <div class="row">
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $service_icon; ?> Our Plumbing Services</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>Pipe repair and installation</li>
                                <li>Leak detection and fixing</li>
                                <li>Drain cleaning and unclogging</li>
                                <li>Faucet and fixture installation</li>
                                <li>Water heater services</li>
                                <li>Emergency plumbing repairs</li>
                                <li>Bathroom and kitchen plumbing</li>
                                <li>Sewer line services</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Why Choose Our Plumbers?</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>✅ Licensed and insured professionals</li>
                                <li>✅ 24/7 emergency service available</li>
                                <li>✅ Competitive pricing</li>
                                <li>✅ Quality workmanship guarantee</li>
                                <li>✅ Modern tools and techniques</li>
                                <li>✅ Transparent quotes</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Available Plumbers</h3>
                </div>
                <div class="card-body">
                    <?php
                    // Get plumbing service providers
                    $result = $conn->query("SELECT u.name, u.phone, u.email, sr.rate 
                                           FROM users u 
                                           JOIN service_rates sr ON u.id = sr.provider_id 
                                           JOIN services s ON sr.service_id = s.id 
                                           WHERE u.role = 'provider' AND s.name = 'plumbing'");
                    
                    if ($result && $result->num_rows > 0) {
                        echo "<div class='row'>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<div class='col-third'>";
                            echo "<div class='card'>";
                            echo "<h4>" . htmlspecialchars($row['name']) . "</h4>";
                            echo "<p><strong>Rate:</strong> ₹" . htmlspecialchars($row['rate']) . "/hour</p>";
                            echo "<p><strong>Phone:</strong> " . htmlspecialchars($row['phone']) . "</p>";
                            echo "<a href='../customer/book_service.php?service=plumbing&provider=" . urlencode($row['name']) . "' class='btn btn-primary'>Book Now</a>";
                            echo "</div>";
                            echo "</div>";
                        }
                        echo "</div>";
                    } else {
                        echo "<p class='text-center'>No plumbers are currently available. Please check back later.</p>";
                    }
                    ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="../index.php" class="btn btn-outline">Back to Home</a>
                <a href="../customer/register.php" class="btn btn-primary">Register to Book</a>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>