<?php
session_start();
require_once "../config/db.php";
require_once "../includes/session_manager.php";
if (!SessionManager::checkAuth('provider')) { header("Location: login.php"); exit(); }
$provider_id = $_SESSION['user_id'];

$success_message = '';
$error_message = '';

// Migration safeguards (quietly ignore errors)
$conn->query("ALTER TABLE users ADD COLUMN latitude DECIMAL(10,8) NULL");
$conn->query("ALTER TABLE users ADD COLUMN longitude DECIMAL(11,8) NULL");
$conn->query("ALTER TABLE users ADD COLUMN photo VARCHAR(255) NULL");
$conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL");

// Fetch current provider data
$stmt = $conn->prepare("SELECT name, email, phone, photo, latitude, longitude FROM users WHERE id=? AND role='provider' LIMIT 1");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$current){ die("Provider not found"); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name = trim($_POST['name']);
    $phone = preg_replace('/\D+/','', $_POST['phone'] ?? '');
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? $_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? $_POST['longitude'] : null;
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $update_photo = false; $photo_filename = $current['photo'];

    // Validation
    if ($name === '') { $error_message = 'Name is required.'; }
    if(!$error_message && ($phone !== '' && (strlen($phone) < 10 || strlen($phone) > 15))) { $error_message = 'Phone must be 10-15 digits.'; }
    if(!$error_message && $password !== '') {
        if(strlen($password) < 8 || !preg_match('/[0-9]/',$password)) { $error_message = 'New password must be at least 8 chars and include a number.'; }
        if(!$error_message && $password !== $confirm) { $error_message = 'Password confirmation does not match.'; }
    }

    // Photo upload (optional)
    if(!$error_message && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'];
            $mime = mime_content_type($_FILES['photo']['tmp_name']);
            if(!isset($allowed[$mime])) { $error_message = 'Photo must be JPG, PNG, or GIF.'; }
            elseif($_FILES['photo']['size'] > 2*1024*1024) { $error_message = 'Photo must be under 2MB.'; }
            else {
                $ext = $allowed[$mime];
                $photo_filename = 'prov_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
                $upload_dir = realpath(__DIR__.'/../assets/uploads'); if(!$upload_dir){ $upload_dir = __DIR__.'/../assets/uploads'; }
                if(!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
                $dest = $upload_dir.DIRECTORY_SEPARATOR.$photo_filename;
                if(!move_uploaded_file($_FILES['photo']['tmp_name'],$dest)) { $error_message = 'Failed to save uploaded photo.'; }
                else { $update_photo = true; }
            }
        } else { $error_message = 'Photo upload error.'; }
    }

  if(!$error_message) {
    // Detect if nothing changed
    $nothingChanged = true;
    if($name !== $current['name']) $nothingChanged = false;
    if($phone !== '' && $phone !== ($current['phone'] ?? '')) $nothingChanged = false;
    if($password !== '') $nothingChanged = false;
    if($update_photo) $nothingChanged = false;
    if(($latitude !== null && $longitude !== null) && ($latitude != $current['latitude'] || $longitude != $current['longitude'])) $nothingChanged = false;
    if($nothingChanged){
      $error_message = 'No changes detected to update.';
    }
  }
  if(!$error_message) {
        try {
            $conn->begin_transaction();
            $fields = ['name=?'];
            $params = [$name];
            $types = 's';
            if($phone !== '' && $phone !== ($current['phone'] ?? '')) { $fields[] = 'phone=?'; $params[] = $phone; $types.='s'; }
            if($latitude !== null && $longitude !== null && is_numeric($latitude) && is_numeric($longitude) && ($latitude != $current['latitude'] || $longitude != $current['longitude'])) { $fields[] = 'latitude=?'; $fields[]='longitude=?'; $params[]=$latitude; $params[]=$longitude; $types.='dd'; }
            if($update_photo) { $fields[] = 'photo=?'; $params[] = $photo_filename; $types.='s'; }
            if($password !== '') { $fields[] = 'password=?'; $params[] = password_hash($password, PASSWORD_DEFAULT); $types.='s'; }
            $params[] = $provider_id; $types.='i';
            $sql = 'UPDATE users SET '.implode(',', $fields).' WHERE id=? AND role="provider"';
            $upd = $conn->prepare($sql);
            $upd->bind_param($types, ...$params);
            if(!$upd->execute()) { throw new Exception('Update failed: '.$upd->error); }
            $upd->close();
            $conn->commit();
            header('Location: dashboard.php?success='.urlencode('Profile updated successfully.'));
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            error_log('Profile update error: '.$e->getMessage());
            $error_message = 'Failed to update profile. Please try again.';
        }
    }
}

include "../includes/header.php"; 
include "../includes/navbar.php"; 
?>
<main>
  <div class="container">
    <div class="content-wrapper">
      <h1>Edit Profile</h1>
      <p>Update your public provider information. Leave password blank to keep current password.</p>
      <?php if($success_message): ?><div class="alert alert-success"><?=htmlspecialchars($success_message)?></div><?php endif; ?>
      <?php if($error_message): ?><div class="alert alert-danger"><?=htmlspecialchars($error_message)?></div><?php endif; ?>
      <div class="row">
        <div class="col-half" style="margin:0 auto;">
          <form method="POST" enctype="multipart/form-data" id="editForm" novalidate>
            <div class="form-group">
              <label class="form-label" for="name">Full Name</label>
              <input class="form-control" type="text" id="name" name="name" value="<?=htmlspecialchars($current['name'])?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email (read-only)</label>
              <input class="form-control" type="email" value="<?=htmlspecialchars($current['email'])?>" disabled>
            </div>
            <div class="form-group">
              <label class="form-label" for="phone">Phone <span style="font-size:0.75rem;color:#6b7280;">(10-15 digits)</span></label>
              <input class="form-control" type="text" id="phone" name="phone" value="<?=htmlspecialchars($current['phone'])?>" pattern="^[0-9]{10,15}$">
            </div>
            <div class="form-group">
              <label class="form-label" for="photo">Profile Photo</label>
              <?php if($current['photo']): ?>
                <div style="margin-bottom:0.5rem;">
                  <img src="../assets/uploads/<?=htmlspecialchars($current['photo'])?>" alt="Current Photo" style="max-width:120px;border-radius:8px;display:block;">
                  <small class="form-text">Upload a new image to replace current (max 2MB).</small>
                </div>
              <?php endif; ?>
              <input class="form-control" type="file" id="photo" name="photo" accept="image/*">
            </div>
            <div class="form-group">
              <label class="form-label" for="password">New Password</label>
              <input class="form-control" type="password" id="password" name="password" placeholder="Leave blank to keep existing">
            </div>
            <div class="form-group">
              <label class="form-label" for="confirm_password">Confirm New Password</label>
              <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password">
            </div>
            <!-- Leaflet CSS -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
            <div class="card" style="margin:1rem 0;">
              <div class="card-header"><h3 class="card-title" style="margin:0;">Business Location</h3></div>
              <div class="card-body">
                <p id="geoStatus" class="text-secondary" style="margin-bottom:0.75rem;">Update your service location (improves nearby customer visibility).</p>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                  <button type="button" class="btn btn-primary btn-small" onclick="enableGeo()">Use Current Location</button>
                  <button type="button" class="btn btn-outline btn-small" onclick="clearGeo()">Clear</button>
                  <button type="button" id="improveBtn" class="btn btn-outline btn-small" style="display:none;" onclick="improveAccuracy()">Improve Accuracy</button>
                  <span id="geoCoords" style="font-size:0.85rem;color:#64748b;"></span>
                  <span id="geoAccuracy" style="font-size:0.75rem;color:#999;"></span>
                </div>
                <div id="map" style="height:300px;margin-top:1rem;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;"></div>
                <input type="hidden" id="latitude" name="latitude" value="<?=htmlspecialchars($current['latitude'])?>">
                <input type="hidden" id="longitude" name="longitude" value="<?=htmlspecialchars($current['longitude'])?>">
              </div>
            </div>
            <div class="alert alert-info" id="geoHint" style="display:none;margin-top:0.5rem;">Allow location access to auto-fill coordinates, or drag the marker manually.</div>
            <div class="text-center" style="margin-top:1.25rem;">
              <button type="submit" class="btn btn-primary" style="width:100%;">Save Changes</button>
              <a href="dashboard.php" class="btn btn-outline" style="margin-top:0.75rem;width:100%;">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include "../includes/footer.php"; ?>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
let mapInstance=null; let marker=null; let mapInitialized=false; let lastAccuracy=null;
function updateGeoStatus(msg, isError=false){ const s=document.getElementById('geoStatus'); if(!s) return; s.textContent=msg; s.style.color=isError?'#b91c1c':'#64748b'; }
function setCoords(lat,lng,accuracy=null){
  document.getElementById('latitude').value=lat; document.getElementById('longitude').value=lng;
  document.getElementById('geoCoords').textContent='Lat: '+parseFloat(lat).toFixed(4)+' | Lng: '+parseFloat(lng).toFixed(4);
  if(accuracy!==null){ lastAccuracy=accuracy; document.getElementById('geoAccuracy').textContent='±'+Math.round(accuracy)+'m'; document.getElementById('improveBtn').style.display = accuracy>50? 'inline-block':'none'; }
  if(mapInstance){ const ll=L.latLng(lat,lng); marker.setLatLng(ll); if(mapInstance.getZoom()<13) mapInstance.setView(ll,13); }
}
function initMap(lat=20.5937,lng=78.9629){ if(mapInitialized) return; mapInitialized=true; mapInstance=L.map('map').setView([lat,lng], lat===20.5937 && lng===78.9629?5:13); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap contributors'}).addTo(mapInstance); marker=L.marker([lat,lng],{draggable:true}).addTo(mapInstance); marker.on('dragend',()=>{ const pos=marker.getLatLng(); setCoords(pos.lat,pos.lng); updateGeoStatus('Marker moved. Location updated.'); }); }
function enableGeo(){ if(!navigator.geolocation){ updateGeoStatus('Geolocation not supported',true); return; } document.getElementById('geoHint').style.display='block'; updateGeoStatus('Requesting precise location...'); navigator.geolocation.getCurrentPosition(pos=>{ setCoords(pos.coords.latitude,pos.coords.longitude,pos.coords.accuracy); document.getElementById('geoHint').style.display='none'; if(!mapInitialized) initMap(pos.coords.latitude,pos.coords.longitude); }, err=>{ if(err.code===err.TIMEOUT){ updateGeoStatus('High accuracy timed out, trying approximate...'); navigator.geolocation.getCurrentPosition(p2=>{ setCoords(p2.coords.latitude,p2.coords.longitude,p2.coords.accuracy); document.getElementById('geoHint').style.display='none'; if(!mapInitialized) initMap(p2.coords.latitude,p2.coords.longitude); }, err2=>{ updateGeoStatus('Failed: '+err2.message,true); }, {enableHighAccuracy:false, timeout:8000, maximumAge:300000}); } else { updateGeoStatus('Failed: '+err.message,true); } }, {enableHighAccuracy:true, timeout:12000, maximumAge:0}); }
function improveAccuracy(){ if(!navigator.geolocation) return; updateGeoStatus('Improving accuracy...'); navigator.geolocation.getCurrentPosition(pos=>{ if(!mapInitialized) initMap(pos.coords.latitude,pos.coords.longitude); setCoords(pos.coords.latitude,pos.coords.longitude,pos.coords.accuracy); updateGeoStatus('Improved accuracy '+Math.round(pos.coords.accuracy)+'m.'); }, err=>{ updateGeoStatus('Could not improve: '+err.message,true); }, {enableHighAccuracy:true, timeout:15000, maximumAge:0}); }
function clearGeo(){ document.getElementById('latitude').value=''; document.getElementById('longitude').value=''; document.getElementById('geoCoords').textContent=''; updateGeoStatus('Location cleared.'); }
// Initialize
 document.addEventListener('DOMContentLoaded',()=>{ const lat=document.getElementById('latitude').value; const lng=document.getElementById('longitude').value; if(lat && lng){ initMap(lat,lng); setCoords(lat,lng); updateGeoStatus('Loaded saved location.'); } else { initMap(); updateGeoStatus('Set or update your business location.'); } });
// Client-side validation
 document.getElementById('editForm').addEventListener('submit', e=>{ const phoneEl=document.getElementById('phone'); if(phoneEl.value.trim()!=='' && !/^\d{10,15}$/.test(phoneEl.value.trim())){ e.preventDefault(); alert('Phone must be 10-15 digits.'); phoneEl.focus(); return false; } const pwd=document.getElementById('password').value; const conf=document.getElementById('confirm_password').value; if(pwd!=='' && (pwd.length<8 || !/[0-9]/.test(pwd))){ e.preventDefault(); alert('New password must be at least 8 chars and include a number.'); return false; } if(pwd!=='' && pwd!==conf){ e.preventDefault(); alert('Password confirmation does not match.'); return false; } });
</script>
