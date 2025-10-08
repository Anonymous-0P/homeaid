<?php 
require_once "../includes/session_manager.php";

// Check authentication with session timeout
if (!SessionManager::checkAuth('admin')) {
    header("Location: login.php");
    exit();
}

include "../config/db.php";
include "../includes/header.php";
include "../includes/navbar.php";

// Basic counts with error handling
$total_customers = 0;
$total_providers = 0;
$total_bookings = 0;

try {
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='customer'");
    $total_customers = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='provider'");
    $total_providers = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings");
    $total_bookings = $result ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    error_log("Reports query error: " . $e->getMessage());
}

// Booking status breakdown
$booking_status = [];
try {
    $status_result = $conn->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
    if ($status_result) {
        while($row = $status_result->fetch_assoc()) {
            $booking_status[$row['status']] = (int)$row['count'];
        }
    }
} catch (Exception $e) {
    error_log("Booking status query error: " . $e->getMessage());
}

// Ensure we have some default data if no bookings exist
if (empty($booking_status)) {
    $booking_status = ['pending' => 0, 'accepted' => 0, 'completed' => 0, 'rejected' => 0];
}

// Monthly registrations (last 6 months)
$monthly_data = [];
for($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_label = date('M Y', strtotime("-$i months"));
    
    $customers = 0;
    $providers = 0;
    
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='customer' AND DATE_FORMAT(created_at, '%Y-%m') = '$month'");
        $customers = $result ? (int)$result->fetch_assoc()['count'] : 0;
        
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='provider' AND DATE_FORMAT(created_at, '%Y-%m') = '$month'");
        $providers = $result ? (int)$result->fetch_assoc()['count'] : 0;
    } catch (Exception $e) {
        error_log("Monthly data query error: " . $e->getMessage());
    }
    
    $monthly_data[] = [
        'month' => $month_label,
        'customers' => $customers,
        'providers' => $providers
    ];
}

// Popular services
$popular_services = [];
try {
    $services_result = $conn->query("SELECT s.name, COUNT(b.id) as booking_count 
                                    FROM services s 
                                    LEFT JOIN bookings b ON s.id = b.service_id 
                                    GROUP BY s.id, s.name 
                                    ORDER BY booking_count DESC 
                                    LIMIT 5");
    if ($services_result) {
        while($row = $services_result->fetch_assoc()) {
            $popular_services[] = [
                'name' => $row['name'],
                'booking_count' => (int)$row['booking_count']
            ];
        }
    }
} catch (Exception $e) {
    error_log("Popular services query error: " . $e->getMessage());
}

// Ensure we have some default data
if (empty($popular_services)) {
    $popular_services = [['name' => 'No services', 'booking_count' => 0]];
}

// Revenue data (assuming completed bookings generate revenue)
$revenue_data = [];
try {
    $revenue_result = $conn->query("SELECT DATE_FORMAT(b.booking_time, '%Y-%m') as month, 
                                          COALESCE(SUM(ps.rate), 0) as revenue,
                                          COUNT(*) as completed_bookings
                                   FROM bookings b 
                                   LEFT JOIN provider_services ps ON b.provider_id = ps.provider_id AND b.service_id = ps.service_id 
                                   WHERE b.status = 'completed' 
                                   GROUP BY DATE_FORMAT(b.booking_time, '%Y-%m') 
                                   ORDER BY month DESC 
                                   LIMIT 6");
    if ($revenue_result) {
        while($row = $revenue_result->fetch_assoc()) {
            $revenue_data[] = [
                'month' => date('M Y', strtotime($row['month'].'-01')),
                'revenue' => (float)$row['revenue'],
                'bookings' => (int)$row['completed_bookings']
            ];
        }
    }
} catch (Exception $e) {
    error_log("Revenue data query error: " . $e->getMessage());
}
$revenue_data = array_reverse($revenue_data);

// Ensure we have some default data
if (empty($revenue_data)) {
    $revenue_data = [['month' => date('M Y'), 'revenue' => 0, 'bookings' => 0]];
}

// Recent activities
$recent_activities = [];
try {
    $activity_result = $conn->query("SELECT 'booking' as type, b.id, b.booking_time as created_at, 
                                            CONCAT('New booking by ', u.name, ' for ', s.name) as description
                                    FROM bookings b 
                                    JOIN users u ON b.customer_id = u.id 
                                    JOIN services s ON b.service_id = s.id 
                                    WHERE b.booking_time >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
                                    UNION ALL
                                    SELECT 'user' as type, id, created_at, 
                                           CONCAT('New ', role, ' registered: ', name) as description
                                    FROM users 
                                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
                                    ORDER BY created_at DESC 
                                    LIMIT 10");
    if ($activity_result) {
        while($row = $activity_result->fetch_assoc()) {
            $recent_activities[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Recent activities query error: " . $e->getMessage());
}
?>

<main>
  <div class="container">
    <div class="content-wrapper">
      <div class="d-flex justify-content-between align-items-center mb-4" style="gap:29.5rem;">
        <h1>📊 Reports & Analytics</h1>
        <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
      </div>

      <!-- Summary Cards -->
      <div class="dashboard-grid" style="margin-bottom:2rem;">
        <div class="dashboard-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
          <h3>Total Customers</h3>
          <p style="font-size:2.5rem;font-weight:700;margin:0.5rem 0;color:white;"><?= $total_customers ?></p>
          <p style="color:white;"><small>Registered users seeking services</small></p>
        </div>
        <div class="dashboard-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
          <h3>Total Providers</h3>
          <p style="font-size:2.5rem;font-weight:700;margin:0.5rem 0;color:white;"><?= $total_providers ?></p>
          <p style="color:white;"><small>Service providers on platform</small></p>
        </div>
        <div class="dashboard-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
          <h3>Total Bookings</h3>
          <p style="font-size:2.5rem;font-weight:700;margin:0.5rem 0;color:white;"><?= $total_bookings ?></p>
          <p style="color:white;"><small>All time booking requests</small></p>
        </div>
        <div class="dashboard-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
          <h3>Platform Health</h3>
          <p style="font-size:2rem;font-weight:700;margin:0.5rem 0;color:white;">
            <?= $total_providers > 0 ? round(($total_bookings / $total_providers), 1) : 0 ?>
          </p>
          <p style="color:white;"><small>Avg bookings per provider</small></p>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="row" style="margin-bottom:2rem;">
        <!-- Booking Status Chart -->
        <div class="col-half">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Booking Status Distribution</h3>
            </div>
            <div class="card-body">
              <canvas id="bookingStatusChart" width="400" height="300"></canvas>
            </div>
          </div>
        </div>
        
        <!-- Monthly Registrations Chart -->
        <div class="col-half">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Monthly User Registrations</h3>
            </div>
            <div class="card-body">
              <canvas id="monthlyChart" width="400" height="300"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Revenue and Services Row -->
      <div class="row" style="margin-bottom:2rem;">
        <!-- Revenue Chart -->
        <div class="col-half">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Monthly Revenue (Completed Bookings)</h3>
            </div>
            <div class="card-body">
              <canvas id="revenueChart" width="400" height="300"></canvas>
            </div>
          </div>
        </div>
        
        <!-- Popular Services -->
        <div class="col-half">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Popular Services</h3>
            </div>
            <div class="card-body">
              <canvas id="servicesChart" width="400" height="300"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Recent Platform Activity (Last 7 Days)</h3>
        </div>
        <div class="card-body">
          <?php if(!empty($recent_activities)): ?>
            <div class="activity-timeline">
              <?php foreach($recent_activities as $activity): ?>
                <div class="activity-item" style="display:flex; align-items:center; padding:0.75rem 0; border-bottom:1px solid #e5e7eb;">
                  <div class="activity-icon" style="width:40px; height:40px; border-radius:50%; background:<?= $activity['type'] === 'booking' ? '#3b82f6' : '#10b981' ?>; color:white; display:flex; align-items:center; justify-content:center; margin-right:1rem;">
                    <?= $activity['type'] === 'booking' ? '📅' : '👤' ?>
                  </div>
                  <div class="activity-content" style="flex:1;">
                    <p style="margin:0; font-weight:500;"><?= htmlspecialchars($activity['description']) ?></p>
                    <small class="text-secondary"><?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?></small>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-center" style="padding:2rem;">
              <p class="text-secondary">No recent activity in the last 7 days.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Booking Status Pie Chart
const bookingStatusCtx = document.getElementById('bookingStatusChart').getContext('2d');
new Chart(bookingStatusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($booking_status)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($booking_status)) ?>,
            backgroundColor: ['#ef4444', '#f59e0b', '#10b981', '#6b7280'],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Monthly Registrations Line Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthly_data, 'month')) ?>,
        datasets: [{
            label: 'Customers',
            data: <?= json_encode(array_column($monthly_data, 'customers')) ?>,
            borderColor: '#3b82f6',
            backgroundColor: '#3b82f6',
            tension: 0.4
        }, {
            label: 'Providers',
            data: <?= json_encode(array_column($monthly_data, 'providers')) ?>,
            borderColor: '#f59e0b',
            backgroundColor: '#f59e0b',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($revenue_data, 'month')) ?>,
        datasets: [{
            label: 'Revenue (₹)',
            data: <?= json_encode(array_column($revenue_data, 'revenue')) ?>,
            backgroundColor: '#10b981',
            borderColor: '#059669',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Popular Services Chart
const servicesCtx = document.getElementById('servicesChart').getContext('2d');
new Chart(servicesCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($popular_services, 'name')) ?>,
        datasets: [{
            label: 'Bookings',
            data: <?= json_encode(array_column($popular_services, 'booking_count')) ?>,
            backgroundColor: ['#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f59e0b'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        indexAxis: 'y',
        scales: {
            x: { beginAtZero: true }
        }
    }
});
</script>

<?php include "../includes/footer.php"; ?>
