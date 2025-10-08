<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>
<?php include "../config/db.php"; ?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <h1 class="text-center">Roofing Services</h1>
            <p class="text-center">Installation, inspection, and repair services to keep your roof strong and weatherproof.</p>
            <div class="row">
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🏗️ Roofing Expertise</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>New roof installation</li>
                                <li>Leak detection & patching</li>
                                <li>Shingle & tile replacement</li>
                                <li>Flat & sloped roofing</li>
                                <li>Waterproofing & sealing</li>
                                <li>Gutter cleaning & setup</li>
                                <li>Storm damage restoration</li>
                                <li>Annual inspection services</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Why Hire Our Roofers?</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>✅ Safety-compliant teams</li>
                                <li>✅ Quality materials sourcing</li>
                                <li>✅ Weather-resistant finishes</li>
                                <li>✅ Insurance claim assistance</li>
                                <li>✅ Structural integrity focus</li>
                                <li>✅ Cleanup after completion</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Available Roofing Specialists</h3>
                </div>
                <div class="card-body">
                    <?php
                    $result = $conn->query("SELECT u.name, u.phone, u.email, sr.rate 
                                            FROM users u 
                                            JOIN service_rates sr ON u.id = sr.provider_id 
                                            JOIN services s ON sr.service_id = s.id 
                                            WHERE u.role = 'provider' AND s.name = 'roofing'");
                    if ($result && $result->num_rows > 0) {
                        echo "<div class='row'>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<div class='col-third'>";
                            echo "<div class='card'>";
                            echo "<h4>" . htmlspecialchars($row['name']) . "</h4>";
                            echo "<p><strong>Rate:</strong> ₹" . htmlspecialchars($row['rate']) . "/hour</p>";
                            echo "<p><strong>Phone:</strong> " . htmlspecialchars($row['phone']) . "</p>";
                            echo "<a href='../customer/book_service.php?service=roofing&provider=" . urlencode($row['name']) . "' class='btn btn-primary'>Book Now</a>";
                            echo "</div>";
                            echo "</div>";
                        }
                        echo "</div>";
                    } else {
                        echo "<p class='text-center'>No roofing specialists are currently available. Please check back later.</p>";
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
