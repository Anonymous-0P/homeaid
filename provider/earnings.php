<?php
require_once "../includes/session_manager.php";
if(!SessionManager::checkAuth('provider')){ header('Location: login.php'); exit(); }
require_once "../config/db.php";
$provider_id = $_SESSION['user_id'];

// Date filters
$from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : '';
$to   = isset($_GET['to']) && $_GET['to'] !== '' ? $_GET['to'] : '';

$from_date = null; $to_date = null; $whereDates = '';
if($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)) { $from_date = $from.' 00:00:00'; }
if($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)) { $to_date = $to.' 23:59:59'; }
if($from_date && $to_date){ $whereDates = " AND b.booking_time BETWEEN '$from_date' AND '$to_date'"; }
else if($from_date){ $whereDates = " AND b.booking_time >= '$from_date'"; }
else if($to_date){ $whereDates = " AND b.booking_time <= '$to_date'"; }

// Core earnings: assuming each completed booking earns the rate recorded at booking (current rate join used).
// Summaries
$summary = [ 'completed_total' => 0, 'accepted_pending_total' => 0, 'today_total' => 0, 'completed_count' => 0 ];

// Completed total
$res = $conn->query("SELECT COALESCE(SUM(ps.rate),0) as total, COUNT(*) c FROM bookings b JOIN provider_services ps ON b.provider_id=ps.provider_id AND b.service_id=ps.service_id WHERE b.provider_id=$provider_id AND b.status='completed' $whereDates");
if($res){ $row=$res->fetch_assoc(); $summary['completed_total'] = $row['total']; $summary['completed_count']=$row['c']; }
// Accepted (not yet completed) total potential
$res = $conn->query("SELECT COALESCE(SUM(ps.rate),0) as total FROM bookings b JOIN provider_services ps ON b.provider_id=ps.provider_id AND b.service_id=ps.service_id WHERE b.provider_id=$provider_id AND b.status='accepted' $whereDates");
if($res){ $summary['accepted_pending_total'] = $res->fetch_assoc()['total']; }
// Today total (completed today)
$today = date('Y-m-d');
$res = $conn->query("SELECT COALESCE(SUM(ps.rate),0) as total FROM bookings b JOIN provider_services ps ON b.provider_id=ps.provider_id AND b.service_id=ps.service_id WHERE b.provider_id=$provider_id AND b.status='completed' AND DATE(b.booking_time)='$today'");
if($res){ $summary['today_total'] = $res->fetch_assoc()['total']; }

// Detailed list (filtered)
$list_sql = "SELECT b.id, b.status, b.booking_time, s.name as service, ps.rate, u.name AS customer\n            FROM bookings b\n            JOIN services s ON b.service_id=s.id\n            JOIN provider_services ps ON b.provider_id=ps.provider_id AND b.service_id=ps.service_id\n            JOIN users u ON b.customer_id=u.id\n            WHERE b.provider_id=$provider_id $whereDates\n            ORDER BY b.booking_time DESC";
$list_res = $conn->query($list_sql);

// CSV export
if(isset($_GET['export']) && $_GET['export']==='csv'){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="earnings_'.date('Ymd_His').'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out,['Booking ID','Date','Service','Customer','Status','Rate','Earning']);
    if($list_res){
        $list_res->data_seek(0);
        while($r=$list_res->fetch_assoc()){
            $earning = ($r['status']==='completed') ? $r['rate'] : 0; // only completed counts
            fputcsv($out,[$r['id'],$r['booking_time'],$r['service'],$r['customer'],$r['status'],$r['rate'],$earning]);
        }
    }
    fclose($out); exit();
}

include "../includes/header.php"; include "../includes/navbar.php";
?>
<main>
  <div class="container">
    <div class="content-wrapper">
  <div class="d-flex justify-content-between align-items-center mb-4" style="gap:47rem;">
        <h1>Earnings</h1>
        <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
      </div>
      <form method="GET" class="card" style="margin-bottom:1.25rem;">
        <div class="card-header"><h3 class="card-title" style="margin:0;">Filter</h3></div>
        <div class="card-body" style="display:flex; gap:1rem; flex-wrap:wrap;">
          <div>
            <label class="form-label" for="from">From</label>
            <input type="date" id="from" name="from" class="form-control" value="<?=htmlspecialchars($from)?>">
          </div>
          <div>
            <label class="form-label" for="to">To</label>
            <input type="date" id="to" name="to" class="form-control" value="<?=htmlspecialchars($to)?>">
          </div>
          <div style="align-self:flex-end; display:flex; gap:0.5rem;">
            <button type="submit" class="btn btn-primary">Apply</button>
            <a href="earnings.php" class="btn btn-outline">Reset</a>
            <a href="earnings.php?<?=http_build_query(array_filter(['from'=>$from,'to'=>$to]))?>&export=csv" class="btn btn-secondary">Export CSV</a>
          </div>
        </div>
      </form>
      <div class="dashboard-grid" style="margin-bottom:1.25rem;">
        <div class="dashboard-card">
          <h3>Total Completed Earnings</h3>
          <p style="font-size:1.5rem;font-weight:600;">₹<?=number_format($summary['completed_total'],2)?></p>
          <p><small><?= (int)$summary['completed_count'] ?> completed booking(s)</small></p>
        </div>
        <div class="dashboard-card">
          <h3>Potential (Accepted)</h3>
          <p style="font-size:1.5rem;font-weight:600;">₹<?=number_format($summary['accepted_pending_total'],2)?></p>
          <p><small>Accepted, not completed</small></p>
        </div>
        <div class="dashboard-card">
          <h3>Today (Completed)</h3>
          <p style="font-size:1.5rem;font-weight:600;">₹<?=number_format($summary['today_total'],2)?></p>
          <p><small>For <?=date('M j, Y');?></small></p>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h2 class="card-title">Bookings & Earnings</h2></div>
        <div class="card-body">
          <?php if($list_res && $list_res->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Date</th>
                  <th>Service</th>
                  <th>Customer</th>
                  <th>Status</th>
                  <th>Rate</th>
                  <th>Earning</th>
                </tr>
              </thead>
              <tbody>
              <?php $list_res->data_seek(0); while($r=$list_res->fetch_assoc()): ?>
                <tr>
                  <td>#<?=$r['id']?></td>
                  <td><small><?=date('M j, Y g:i A', strtotime($r['booking_time']))?></small></td>
                  <td><?=htmlspecialchars($r['service'])?></td>
                  <td><?=htmlspecialchars($r['customer'])?></td>
                  <td><span class="badge badge-<?=htmlspecialchars($r['status'])?>"><?=ucfirst($r['status'])?></span></td>
                  <td>₹<?=number_format($r['rate'],2)?></td>
                  <td><?= $r['status']==='completed' ? ('<strong>₹'.number_format($r['rate'],2).'</strong>') : '<span class="text-secondary">—</span>' ?></td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
            <div class="text-center" style="padding:2rem;">
              <h3>No bookings found</h3>
              <p class="text-secondary">Try adjusting the date filters or wait for new bookings.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="alert alert-info" style="margin-top:1rem;">
        <strong>Note:</strong> Earnings are calculated per completed booking using the current service rate. If historical rate tracking is required, store the rate at the time of booking in the bookings table.
      </div>
    </div>
  </div>
</main>
<?php include "../includes/footer.php"; ?>
