<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>
<?php include "../config/db.php"; ?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <h1 class="text-center">Pest Control Services</h1>
            <p class="text-center">Safe, eco-friendly extermination and prevention for a pest-free home.</p>
            <div class="row">
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🪲 Our Pest Control Solutions</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>General pest inspection & treatment</li>
                                <li>Termite detection & control</li>
                                <li>Rodent removal & exclusion</li>
                                <li>Bed bug eradication</li>
                                <li>Mosquito & fly management</li>
                                <li>Cockroach elimination</li>
                                <li>Eco-friendly treatment options</li>
                                <li>Preventive maintenance plans</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Why Choose Our Experts?</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>✅ Licensed & trained technicians</li>
                                <li>✅ Family & pet-safe chemicals</li>
                                <li>✅ Long-lasting protection plans</li>
                                <li>✅ Odorless modern treatments</li>
                                <li>✅ Detailed inspection reports</li>
                                <li>✅ Rapid emergency response</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Available Pest Control Providers</h3>
                </div>
                <div class="card-body">
                    <?php
                    $result = $conn->query("SELECT u.name, u.phone, u.email, sr.rate 
                                            FROM users u 
                                            JOIN service_rates sr ON u.id = sr.provider_id 
                                            JOIN services s ON sr.service_id = s.id 
                                            WHERE u.role = 'provider' AND s.name = 'pest control'");
                    if ($result && $result->num_rows > 0) {
                        echo "<div class='row'>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<div class='col-third'>";
                            echo "<div class='card'>";
                            echo "<h4>" . htmlspecialchars($row['name']) . "</h4>";
                            echo "<p><strong>Rate:</strong> ₹" . htmlspecialchars($row['rate']) . "/hour</p>";
                            echo "<p><strong>Phone:</strong> " . htmlspecialchars($row['phone']) . "</p>";
                            echo "<a href='../customer/book_service.php?service=pest%20control&provider=" . urlencode($row['name']) . "' class='btn btn-primary'>Book Now</a>";
                            echo "</div>";
                            echo "</div>";
                        }
                        echo "</div>";
                    } else {
                        echo "<p class='text-center'>No pest control providers are currently available. Please check back later.</p>";
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
