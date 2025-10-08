<?php 
session_start();
include "../config/db.php";
include "../includes/header.php"; 
include "../includes/navbar.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = preg_replace('/\D+/','', $_POST['phone']); // keep digits only
    $password = $_POST['password'];
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $lat = isset($_POST['latitude']) ? trim($_POST['latitude']) : null;
    $lng = isset($_POST['longitude']) ? trim($_POST['longitude']) : null;
    // Aadhaar inputs
    $aadhaar_number = isset($_POST['aadhaar_number']) ? preg_replace('/\D+/', '', $_POST['aadhaar_number']) : '';
 
    // Phone validation (10-15 digits allow international growth; require at least 10)
    if(strlen($phone) < 10 || strlen($phone) > 15){
        $error_message = "Enter a valid phone number (10-15 digits).";
    }

    // Password policy
    if(!isset($error_message) && (strlen($password) < 8 || !preg_match('/[0-9]/',$password))){
        $error_message = "Password must be at least 8 characters and contain a number.";
    }

    // Confirm password match
    if(!isset($error_message) && $password !== $confirm_password){
        $error_message = "Passwords do not match.";
    }

    // Aadhaar number validation (12 digits)
    if(!isset($error_message)){
        if(strlen($aadhaar_number)!==12){
            $error_message = "Enter a valid 12-digit Aadhaar number.";
        }
    }

    // Photo required
    $photo_filename = null;
    $aadhaar_file_name = null;
    if(!isset($error_message)){
        if(!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK){
            $error_message = "Profile photo is required.";
        } else {
            $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'];
            $mime = mime_content_type($_FILES['photo']['tmp_name']);
            if(!isset($allowed[$mime])){
                $error_message = "Photo must be JPG, PNG, or GIF.";
            } else if($_FILES['photo']['size'] > 2*1024*1024){
                $error_message = "Photo must be under 2MB.";
            } else {
                $ext = $allowed[$mime];
                $photo_filename = 'prov_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
                $upload_dir = realpath(__DIR__.'/../assets/uploads');
                if(!$upload_dir){ $upload_dir = __DIR__.'/../assets/uploads'; }
                if(!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
                $dest = $upload_dir.DIRECTORY_SEPARATOR.$photo_filename;
                if(!move_uploaded_file($_FILES['photo']['tmp_name'],$dest)){
                    $error_message = "Failed to save uploaded photo.";
                }
            }
        }
        // Aadhaar document required (image or PDF)
        if(!isset($error_message)){
            if(!isset($_FILES['aadhaar_file']) || $_FILES['aadhaar_file']['error'] !== UPLOAD_ERR_OK){
                $error_message = "Aadhaar document is required (image or PDF).";
            } else {
                $allowed2 = [
                    'image/jpeg'=>'jpg',
                    'image/png'=>'png',
                    'application/pdf'=>'pdf'
                ];
                $mime2 = mime_content_type($_FILES['aadhaar_file']['tmp_name']);
                if(!isset($allowed2[$mime2])){
                    $error_message = "Aadhaar file must be JPG, PNG, or PDF.";
                } else if($_FILES['aadhaar_file']['size'] > 5*1024*1024){
                    $error_message = "Aadhaar file must be under 5MB.";
                } else {
                    $ext2 = $allowed2[$mime2];
                    $aadhaar_file_name = 'aadhaar_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext2;
                    $kyc_dir = realpath(__DIR__.'/../assets/uploads/kyc');
                    if(!$kyc_dir){ $kyc_dir = __DIR__.'/../assets/uploads/kyc'; }
                    if(!is_dir($kyc_dir)) mkdir($kyc_dir, 0775, true);
                    $dest2 = $kyc_dir.DIRECTORY_SEPARATOR.$aadhaar_file_name;
                    if(!move_uploaded_file($_FILES['aadhaar_file']['tmp_name'],$dest2)){
                        $error_message = "Failed to save Aadhaar document.";
                    }
                }
            }
        }
    }

    // Basic validation for coordinates
    if ($lat === null || $lng === null || $lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
        $error_message = "Location is required. Please enable browser location to register.";
    }
    
    // Ensure DB columns exist for Aadhaar fields
    if(!isset($error_message)){
        if($conn){
            if($res = $conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'aadhaar_number'")){
                $row = $res->fetch_row();
                if((int)$row[0] === 0){
                    $conn->query("ALTER TABLE users ADD COLUMN aadhaar_number VARCHAR(20) NULL AFTER phone");
                }
            }
            if($res2 = $conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'aadhaar_file'")){
                $row2 = $res2->fetch_row();
                if((int)$row2[0] === 0){
                    $conn->query("ALTER TABLE users ADD COLUMN aadhaar_file VARCHAR(255) NULL AFTER photo");
                }
            }
        }
    }

    // Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();
    
    if (!isset($error_message) && $result->num_rows > 0) {
        $error_message = "An account with this email address already exists.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Ensure email verification columns exist (avoid IF NOT EXISTS for older MySQL)
        $resCol1 = $conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'email_verified'");
        if ($resCol1) {
            $exists1 = (int)($resCol1->fetch_row()[0] ?? 0);
            if ($exists1 === 0) {
                $conn->query("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER password");
            }
        }
        $resCol2 = $conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'email_verified_at'");
        if ($resCol2) {
            $exists2 = (int)($resCol2->fetch_row()[0] ?? 0);
            if ($exists2 === 0) {
                $conn->query("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email_verified");
            }
        }

        // Insert user with prepared statement (email_verified=0)
        if (!isset($error_message)) {
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, latitude, longitude, photo, aadhaar_number, aadhaar_file, email_verified) VALUES (?, ?, ?, ?, 'provider', ?, ?, ?, ?, ?, 0)");
        $latf = is_numeric($lat) ? (float)$lat : null; $lngf = is_numeric($lng) ? (float)$lng : null;
        $stmt->bind_param("ssssddsss", $name, $email, $phone, $hashed_password, $latf, $lngf, $photo_filename, $aadhaar_number, $aadhaar_file_name);
        
        if ($stmt->execute()) {
            // Create email verification token and send email
            $uid = $conn->insert_id;
            $conn->query("CREATE TABLE IF NOT EXISTS email_verification_tokens (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token VARCHAR(128) NOT NULL, expires_at DATETIME NOT NULL, used TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_token (token), INDEX idx_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time()+60*60*24);
            $ins = $conn->prepare("INSERT INTO email_verification_tokens (user_id, token, expires_at) VALUES (?,?,?)");
            if ($ins) {
                $ins->bind_param('iss', $uid, $token, $expires);
                if ($ins->execute()) {
                    require_once __DIR__ . '/../includes/email_functions.php';
                    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                    $base = function_exists('getBaseUrl') ? rtrim(getBaseUrl(), '/') : ('http://' . $host);
                    $link = $base . '/Auth/verify_email.php?token=' . urlencode($token);
                    $msg = createEmailVerificationEmailTemplate(['name'=>$name,'verify_link'=>$link]);
                    @sendEmail($email, 'Confirm your HomeAid account', $msg);
                }
            }
            $success_message = "Registration successful. Please check your email to confirm your account.";
        } else if (!isset($error_message)) {
            $error_message = "Registration failed. Please try again.";
        }
    }
    }
}
?>

<main>
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="content-wrapper">
                    <h1 class="text-center">Provider Registration</h1>
                    <p class="text-center">Join HomeAid as a service provider and grow your business.</p>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                            <div class="text-center mt-2" style="display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap;">
                                <a href="login.php" class="btn btn-primary">Login Now</a>
                                <?php 
                                  // Offer resend link using the submitted email
                                  if (!empty($email)) {
                                      $resendUrl = "../Auth/resend_verification.php?role=provider&email=" . urlencode($email);
                                      echo '<a class="btn btn-outline" href="' . htmlspecialchars($resendUrl) . '">Resend verification email</a>';
                                  }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-half" style="margin: 0 auto;">
                            <form method="POST" enctype="multipart/form-data" novalidate>
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone Number <span style="font-size:0.75rem; color:#6b7280;">(10-15 digits)</span></label>
                                    <input type="text" id="phone" name="phone" class="form-control" pattern="^[0-9]{10,15}$" placeholder="e.g. 9876543210" required>
                                </div>
                                <div class="form-group">
                                    <label for="photo" class="form-label">Profile Photo (Required)</label>
                                    <input type="file" id="photo" name="photo" class="form-control" accept="image/*" required>
                                    <small class="form-text">JPG/PNG/GIF up to 2MB.</small>
                                </div>
                                <div class="form-group">
                                    <label for="aadhaar_number" class="form-label">Aadhaar Number</label>
                                    <input type="text" id="aadhaar_number" name="aadhaar_number" class="form-control" pattern="^\d{12}$" placeholder="12-digit Aadhaar" required>
                                    <small class="form-text">We store it securely for verification. Only last digits may be shown to admins.</small>
                                </div>
                                <div class="form-group">
                                    <label for="aadhaar_file" class="form-label">Aadhaar Document (Image/PDF)</label>
                                    <input type="file" id="aadhaar_file" name="aadhaar_file" class="form-control" accept="image/*,application/pdf" required>
                                    <small class="form-text">JPG/PNG/PDF up to 5MB.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter your password" required>
                                </div>
                                
                      <!-- Leaflet CSS (correct SRI hash). Previous incorrect hash caused blocking. -->
                      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
                          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
                <div class="card" style="margin:1rem 0;">
                                    <div class="card-header"><h3 class="card-title" style="margin:0;">Business Location</h3></div>
                                    <div class="card-body">
                                        <p id="geoStatus" class="text-secondary" style="margin-bottom:0.75rem;">Location required to appear in nearby searches. Click Enable Location.</p>
                                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                                            <button type="button" class="btn btn-primary btn-small" onclick="enableGeo()">Enable Location</button>
                                            <button type="button" class="btn btn-outline btn-small" onclick="clearGeo()">Clear</button>
                                            <button type="button" id="improveBtn" class="btn btn-outline btn-small" style="display:none;" onclick="improveAccuracy()">Improve Accuracy</button>
                                            <span id="geoCoords" style="font-size:0.85rem;color:#64748b;"></span>
                                            <span id="geoAccuracy" style="font-size:0.75rem;color:#999;"></span>
                                        </div>
                    <div id="map" style="height:300px;margin-top:1rem;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;"></div>
                                        <input type="hidden" id="latitude" name="latitude" value="">
                                        <input type="hidden" id="longitude" name="longitude" value="">
                                    </div>
                                </div>
                                <div class="alert alert-info" id="geoHint" style="display:none; margin-top:0.5rem;">
                                    If prompted by your browser, allow location access. We only store approximate coordinates for distance-based customer searches.
                                </div>
                                
                                <button type="submit" id="submitBtn" class="btn btn-primary" style="width: 100%;" disabled>Register as Provider</button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <p>Already have an account? <a href="login.php">Login here</a></p>
                                <p>Looking for services? <a href="../customer/register.php">Register as Customer</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
<!-- Leaflet JS (correct SRI hash). Previous hashes were swapped causing blocking & L undefined -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
// Provider registration geolocation
let mapInstance=null; let marker=null; let mapInitialized=false; let lastAccuracy=null;
let geoWatchId=null; let bestFixAccuracy=Infinity; let watchDeadlineTimer=null;
function updateGeoStatus(msg, isError=false){
    const s=document.getElementById('geoStatus'); if(!s) return; s.textContent=msg; s.style.color=isError?'#b91c1c':'#64748b';
}
function setCoords(lat,lng,accuracy=null){
    document.getElementById('latitude').value=lat;
    document.getElementById('longitude').value=lng;
    document.getElementById('geoCoords').textContent = 'Lat: '+parseFloat(lat).toFixed(4)+' | Lng: '+parseFloat(lng).toFixed(4);
    if(accuracy!==null){
        lastAccuracy=accuracy;
        document.getElementById('geoAccuracy').textContent='±'+Math.round(accuracy)+'m';
    // Show Improve Accuracy button if accuracy worse than 50m
    document.getElementById('improveBtn').style.display = accuracy>50? 'inline-block':'none';
    }
    localStorage.setItem('provider_lat', lat);
    localStorage.setItem('provider_lng', lng);
    document.getElementById('submitBtn').disabled = false;
    updateGeoStatus('Location captured successfully.', false);
    if(mapInstance){
        const ll = L.latLng(lat, lng);
        marker.setLatLng(ll);
        mapInstance.setView(ll, mapInstance.getZoom()<13?13:mapInstance.getZoom());
    }
}
function initMap(lat=20.5937, lng=78.9629){ // Default center: India
    if(mapInitialized) return; mapInitialized=true;
    mapInstance = L.map('map').setView([lat,lng], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapInstance);
    marker = L.marker([lat,lng], {draggable:true}).addTo(mapInstance);
    marker.on('dragend', e=>{
        const pos = marker.getLatLng();
        setCoords(pos.lat, pos.lng);
        updateGeoStatus('Marker moved. Location updated.', false);
    });
}
function enableGeo(){
    if(!navigator.geolocation){ updateGeoStatus('Geolocation not supported by this browser.', true); return; }
    document.getElementById('geoHint').style.display='block';
    updateGeoStatus('Requesting precise location...', false);
    navigator.geolocation.getCurrentPosition(pos=>{
        setCoords(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
        document.getElementById('geoHint').style.display='none';
        if(!mapInitialized) initMap(pos.coords.latitude, pos.coords.longitude);
        // Start a short watch to refine accuracy further
        startAccuracyWatch();
    }, err=>{
        // Fallback attempt with reduced accuracy
        if(err.code===err.TIMEOUT){
            updateGeoStatus('High accuracy timed out, trying approximate fix...', false);
            navigator.geolocation.getCurrentPosition(p2=>{
                setCoords(p2.coords.latitude, p2.coords.longitude, p2.coords.accuracy);
                document.getElementById('geoHint').style.display='none';
                if(!mapInitialized) initMap(p2.coords.latitude, p2.coords.longitude);
                // After coarse fix, still try to refine
                startAccuracyWatch();
            }, err2=>{
                updateGeoStatus('Failed to get location: '+err2.message+'. Permission is required to register.', true);
                document.getElementById('submitBtn').disabled = true;
            }, {enableHighAccuracy:false, timeout:8000, maximumAge:300000});
        } else {
            updateGeoStatus('Failed: '+err.message+'. Allow location access.', true);
            document.getElementById('submitBtn').disabled = true;
        }
    }, {enableHighAccuracy:true, timeout:12000, maximumAge:0});
}
function stopAccuracyWatch(){
    if(geoWatchId!==null){
        navigator.geolocation.clearWatch(geoWatchId);
        geoWatchId=null;
    }
    if(watchDeadlineTimer){
        clearTimeout(watchDeadlineTimer);
        watchDeadlineTimer=null;
    }
}
function startAccuracyWatch(){
    if(!navigator.geolocation){ return; }
    // Reset
    stopAccuracyWatch();
    bestFixAccuracy = (typeof lastAccuracy==='number' && !isNaN(lastAccuracy)) ? lastAccuracy : Infinity;
    updateGeoStatus('Refining location for better accuracy...', false);
    try{
        geoWatchId = navigator.geolocation.watchPosition(pos=>{
            const acc = pos.coords.accuracy;
            // Update if we have a meaningfully better accuracy (by > 5m) or first fix
            if(bestFixAccuracy===Infinity || (acc+5) < bestFixAccuracy){
                bestFixAccuracy = acc;
                setCoords(pos.coords.latitude, pos.coords.longitude, acc);
            }
            // Stop when accuracy is good enough
            if(acc <= 25){
                updateGeoStatus('High accuracy achieved (~'+Math.round(acc)+'m).', false);
                stopAccuracyWatch();
            }
        }, err=>{
            // If watch errors, let user know but keep current fix
            updateGeoStatus('Accuracy watch ended: '+err.message, true);
            stopAccuracyWatch();
        }, {enableHighAccuracy:true, maximumAge:0});
        // Safety stop after 25 seconds
        watchDeadlineTimer = setTimeout(()=>{
            stopAccuracyWatch();
            if(typeof bestFixAccuracy==='number' && isFinite(bestFixAccuracy)){
                updateGeoStatus('Used best available fix (~'+Math.round(bestFixAccuracy)+'m). You can drag the marker or try Improve Accuracy.', false);
            } else {
                updateGeoStatus('Couldn\'t improve accuracy further. Drag the map marker to fine-tune.', true);
            }
        }, 25000);
    } catch(e){
        updateGeoStatus('Could not start accuracy watch.', true);
    }
}
function improveAccuracy(){
    // Re-run the short-lived accuracy watch
    startAccuracyWatch();
}
function clearGeo(){
    document.getElementById('latitude').value='';
    document.getElementById('longitude').value='';
    document.getElementById('geoCoords').textContent='';
    localStorage.removeItem('provider_lat');
    localStorage.removeItem('provider_lng');
    document.getElementById('submitBtn').disabled = true;
    updateGeoStatus('Location cleared. Enable again to proceed.', false);
}
document.addEventListener('DOMContentLoaded',()=>{
    const sLat=localStorage.getItem('provider_lat');
    const sLng=localStorage.getItem('provider_lng');
    initMap(sLat && sLng ? sLat : 20.5937, sLng && sLat ? sLng : 78.9629);
    if(sLat && sLng){ setCoords(sLat, sLng); updateGeoStatus('Restored saved location. Ready to register.', false); }
    else { updateGeoStatus('Location required to appear in nearby searches. Click Enable Location.', false); }
});
// Prevent submit without coords
document.querySelector('form').addEventListener('submit', e=>{
    // Location check
    if(!document.getElementById('latitude').value || !document.getElementById('longitude').value){
         e.preventDefault(); updateGeoStatus('Please enable and capture your location before submitting.', true); return false;
    }
    // Phone pattern check
    const phoneEl = document.getElementById('phone');
    if(!/^\d{10,15}$/.test(phoneEl.value.trim())){
        e.preventDefault();
        alert('Enter a valid phone number (10-15 digits).');
        phoneEl.focus();
        return false;
    }
    // Password check
    const pwd = document.getElementById('password').value;
    if(pwd.length < 8 || !/[0-9]/.test(pwd)){
        e.preventDefault();
        alert('Password must be at least 8 characters and include a number.');
        return false;
    }
    // Confirm password match check
    const cpwd = document.getElementById('confirm_password').value;
    if(pwd !== cpwd){
        e.preventDefault();
        alert('Passwords do not match.');
        return false;
    }
    // Photo required
    const photoEl = document.getElementById('photo');
    if(!photoEl.files || photoEl.files.length===0){
        e.preventDefault(); alert('Please upload a profile photo.'); return false;
    }
    // Aadhaar number check
    const aadhaar = document.getElementById('aadhaar_number').value.replace(/\D+/g,'');
    if(aadhaar.length !== 12){ e.preventDefault(); alert('Enter a valid 12-digit Aadhaar number.'); return false; }
    // Aadhaar file check
    const aFile = document.getElementById('aadhaar_file');
    if(!aFile.files || aFile.files.length===0){ e.preventDefault(); alert('Please upload your Aadhaar document.'); return false; }
    const f = aFile.files[0];
    const okTypes = ['image/jpeg','image/png','application/pdf'];
    if(okTypes.indexOf(f.type)===-1){ e.preventDefault(); alert('Aadhaar file must be JPG, PNG, or PDF.'); return false; }
    if(f.size > 5*1024*1024){ e.preventDefault(); alert('Aadhaar file must be under 5MB.'); return false; }
});
</script>