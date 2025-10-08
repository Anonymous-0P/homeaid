<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>
<?php include "../config/db.php"; ?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <h1 class="text-center">Electrical Services</h1>
            <p class="text-center">Safe and certified electrical work for your home and office.</p>
            
            <div class="row">
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">⚡ Our Electrical Services</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>Electrical installations and repairs</li>
                                <li>Wiring and rewiring</li>
                                <li>Circuit breaker services</li>
                                <li>Outlet and switch installation</li>
                                <li>Lighting installation and repair</li>
                                <li>Electrical panel upgrades</li>
                                <li>Safety inspections</li>
                                <li>Emergency electrical services</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Why Choose Our Electricians?</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>✅ Licensed and certified electricians</li>
                                <li>✅ Full insurance coverage</li>
                                <li>✅ Code-compliant work</li>
                                <li>✅ Safety-first approach</li>
                                <li>✅ Modern diagnostic equipment</li>
                                <li>✅ Warranty on all work</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Available Electricians</h3>
                </div>
                <div class="card-body">
                    <?php
                    // Get electrical service providers
                    $result = $conn->query("SELECT u.name, u.phone, u.email, sr.rate 
                                           FROM users u 
                                           JOIN service_rates sr ON u.id = sr.provider_id 
                                           JOIN services s ON sr.service_id = s.id 
                                           WHERE u.role = 'provider' AND s.name = 'electrical'");
                    
                    if ($result && $result->num_rows > 0) {
                        echo "<div class='row'>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<div class='col-third'>";
                            echo "<div class='card'>";
                            echo "<h4>" . htmlspecialchars($row['name']) . "</h4>";
                            echo "<p><strong>Rate:</strong> ₹" . htmlspecialchars($row['rate']) . "/hour</p>";
                            echo "<p><strong>Phone:</strong> " . htmlspecialchars($row['phone']) . "</p>";
                            echo "<a href='../customer/book_service.php?service=electrical&provider=" . urlencode($row['name']) . "' class='btn btn-primary'>Book Now</a>";
                            echo "</div>";
                            echo "</div>";
                        }
                        echo "</div>";
                    } else {
                        echo "<p class='text-center'>No electricians are currently available. Please check back later.</p>";
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