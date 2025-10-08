<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>
<?php include "../config/db.php"; ?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <h1 class="text-center">Appliance Repair Services</h1>
            <p class="text-center">Expert diagnostics and repairs for home appliances of all major brands.</p>
            <div class="row">
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🔌 We Repair</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>Refrigerators & freezers</li>
                                <li>Washing machines & dryers</li>
                                <li>Microwaves & ovens</li>
                                <li>Dishwashers</li>
                                <li>Air conditioners</li>
                                <li>Water purifiers</li>
                                <li>Televisions</li>
                                <li>Small kitchen appliances</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Why Choose Our Technicians?</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>✅ Brand-trained professionals</li>
                                <li>✅ Genuine spare parts</li>
                                <li>✅ Transparent diagnostics</li>
                                <li>✅ Upfront service estimates</li>
                                <li>✅ Fast turnaround time</li>
                                <li>✅ Warranty on major repairs</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Available Appliance Technicians</h3>
                </div>
                <div class="card-body">
                    <?php
                    $result = $conn->query("SELECT u.name, u.phone, u.email, sr.rate 
                                            FROM users u 
                                            JOIN service_rates sr ON u.id = sr.provider_id 
                                            JOIN services s ON sr.service_id = s.id 
                                            WHERE u.role = 'provider' AND s.name = 'appliance repair'");
                    if ($result && $result->num_rows > 0) {
                        echo "<div class='row'>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<div class='col-third'>";
                            echo "<div class='card'>";
                            echo "<h4>" . htmlspecialchars($row['name']) . "</h4>";
                            echo "<p><strong>Rate:</strong> ₹" . htmlspecialchars($row['rate']) . "/hour</p>";
                            echo "<p><strong>Phone:</strong> " . htmlspecialchars($row['phone']) . "</p>";
                            echo "<a href='../customer/book_service.php?service=appliance%20repair&provider=" . urlencode($row['name']) . "' class='btn btn-primary'>Book Now</a>";
                            echo "</div>";
                            echo "</div>";
                        }
                        echo "</div>";
                    } else {
                        echo "<p class='text-center'>No appliance technicians are currently available. Please check back later.</p>";
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
