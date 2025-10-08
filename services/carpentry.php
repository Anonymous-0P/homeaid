<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>
<?php include "../config/db.php"; ?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <h1 class="text-center">Carpentry Services</h1>
            <p class="text-center">Custom woodworking, repairs, and installations to enhance your home.</p>
            <div class="row">
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🪚 Our Carpentry Work</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>Custom furniture builds</li>
                                <li>Cabinet assembly & installation</li>
                                <li>Door & window frame repair</li>
                                <li>Wooden flooring fixes</li>
                                <li>Shelving & storage solutions</li>
                                <li>Deck & outdoor structures</li>
                                <li>Trim & molding installation</li>
                                <li>General wood repairs & refinishing</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Why Choose Our Carpenters?</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>✅ Skilled craftsmanship</li>
                                <li>✅ Precision measurements</li>
                                <li>✅ Quality wood & materials</li>
                                <li>✅ Custom design options</li>
                                <li>✅ Transparent estimates</li>
                                <li>✅ Cleanup after work</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Available Carpenters</h3>
                </div>
                <div class="card-body">
                    <?php
                    $result = $conn->query("SELECT u.name, u.phone, u.email, sr.rate 
                                            FROM users u 
                                            JOIN service_rates sr ON u.id = sr.provider_id 
                                            JOIN services s ON sr.service_id = s.id 
                                            WHERE u.role = 'provider' AND s.name = 'carpentry'");
                    if ($result && $result->num_rows > 0) {
                        echo "<div class='row'>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<div class='col-third'>";
                            echo "<div class='card'>";
                            echo "<h4>" . htmlspecialchars($row['name']) . "</h4>";
                            echo "<p><strong>Rate:</strong> ₹" . htmlspecialchars($row['rate']) . "/hour</p>";
                            echo "<p><strong>Phone:</strong> " . htmlspecialchars($row['phone']) . "</p>";
                            echo "<a href='../customer/book_service.php?service=carpentry&provider=" . urlencode($row['name']) . "' class='btn btn-primary'>Book Now</a>";
                            echo "</div>";
                            echo "</div>";
                        }
                        echo "</div>";
                    } else {
                        echo "<p class='text-center'>No carpenters are currently available. Please check back later.</p>";
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
