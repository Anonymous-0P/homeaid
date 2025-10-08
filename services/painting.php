<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>
<?php include "../config/db.php"; ?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <h1 class="text-center">Painting Services</h1>
            <p class="text-center">Interior & exterior painting with professional finish and durable coatings.</p>
            <div class="row">
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🎨 Our Painting Services</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>Interior wall painting</li>
                                <li>Exterior structure painting</li>
                                <li>Ceiling & trim finishing</li>
                                <li>Waterproof & weatherproof coatings</li>
                                <li>Wallpaper removal & prep</li>
                                <li>Surface crack filling & sanding</li>
                                <li>Texture & accent wall design</li>
                                <li>Protective cleanup & masking</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-half">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Why Choose Our Painters?</h3>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>✅ Quality branded paints</li>
                                <li>✅ Proper surface preparation</li>
                                <li>✅ Color consultation support</li>
                                <li>✅ Clean & timely execution</li>
                                <li>✅ Attention to detail finish</li>
                                <li>✅ Dust & spill control</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Available Painters</h3>
                </div>
                <div class="card-body">
                    <?php
                    $result = $conn->query("SELECT u.name, u.phone, u.email, sr.rate 
                                            FROM users u 
                                            JOIN service_rates sr ON u.id = sr.provider_id 
                                            JOIN services s ON sr.service_id = s.id 
                                            WHERE u.role = 'provider' AND s.name = 'painting'");
                    if ($result && $result->num_rows > 0) {
                        echo "<div class='row'>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<div class='col-third'>";
                            echo "<div class='card'>";
                            echo "<h4>" . htmlspecialchars($row['name']) . "</h4>";
                            echo "<p><strong>Rate:</strong> ₹" . htmlspecialchars($row['rate']) . "/hour</p>";
                            echo "<p><strong>Phone:</strong> " . htmlspecialchars($row['phone']) . "</p>";
                            echo "<a href='../customer/book_service.php?service=painting&provider=" . urlencode($row['name']) . "' class='btn btn-primary'>Book Now</a>";
                            echo "</div>";
                            echo "</div>";
                        }
                        echo "</div>";
                    } else {
                        echo "<p class='text-center'>No painters are currently available. Please check back later.</p>";
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
