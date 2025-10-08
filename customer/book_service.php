<?php
require_once "../includes/session_manager.php";
include "../config/db.php";

// Check authentication with session timeout
if (!SessionManager::checkAuth('customer')) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

include "../includes/header.php";
include "../includes/navbar.php";

// Handle adding service to cart
if (isset($_GET['add_to_cart'])) {
    $service_id = intval($_GET['add_to_cart']);
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (!in_array($service_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $service_id;
        $success_message = "Service added to cart successfully!";
    } else {
        $info_message = "Service is already in your cart.";
    }
}

// Handle direct booking
$direct_book_service = null;
$direct_book_providers = null;
if (isset($_GET['service_id'])) {
    $service_id = intval($_GET['service_id']);
    $direct_book_service = $conn->query("SELECT * FROM services WHERE id=$service_id")->fetch_assoc();
    if ($direct_book_service) {
        $direct_book_providers = $conn->query("
            SELECT u.id, u.name, u.email, u.phone, u.photo, ps.rate
            FROM provider_services ps
            JOIN users u ON ps.provider_id = u.id
            WHERE ps.service_id = $service_id AND u.role = 'provider'
            ORDER BY ps.rate ASC
        ");
    }
}
?>

<main>
    <div class="container">
        <div class="content-wrapper">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($info_message)): ?>
                <div class="alert alert-info"><?php echo $info_message; ?></div>
            <?php endif; ?>
            
            <?php if ($direct_book_service): ?>
                <!-- Direct Service Booking -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Book <?php echo htmlspecialchars($direct_book_service['name']); ?> Service</h1>
                    <a href="book_service.php" class="btn btn-outline">Browse All Services</a>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <?php 
                            $service_name = strtolower($direct_book_service['name']);
                            if (strpos($service_name, 'plumb') !== false) {
                                echo '🔧 ';
                            } elseif (strpos($service_name, 'electric') !== false) {
                                echo '⚡ ';
                            } elseif (strpos($service_name, 'clean') !== false) {
                                echo '🧹 ';
                            } else {
                                echo '🏠 ';
                            }
                            echo htmlspecialchars($direct_book_service['name']); 
                            ?>
                        </h3>
                        <?php if ($direct_book_service['description']): ?>
                            <p class="text-secondary"><?php echo htmlspecialchars($direct_book_service['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h4>Available Providers</h4>
                        <?php if ($direct_book_providers && $direct_book_providers->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($provider = $direct_book_providers->fetch_assoc()): ?>
                                    <div class="col-third">
                                        <div class="card" style="border: 2px solid #e5e7eb; transition: all 0.3s ease;">
                                            <div class="text-center p-3">
                                                <?php if ($provider['photo']): ?>
                                                    <img src="../assets/uploads/<?php echo htmlspecialchars($provider['photo']); ?>" 
                                                         alt="Provider Photo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                                                <?php else: ?>
                                                    <div style="width: 80px; height: 80px; border-radius: 50%; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem;">👤</div>
                                                <?php endif; ?>
                                                
                                                <h4><?php echo htmlspecialchars($provider['name']); ?></h4>
                                                <p><strong>Rate:</strong> ₹<?php echo number_format($provider['rate'], 2); ?>/hour</p>
                                                <?php if ($provider['phone']): ?>
                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($provider['phone']); ?></p>
                                                <?php endif; ?>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($provider['email']); ?></p>
                                                
                                                <a href="confirm_booking.php?provider_id=<?php echo $provider['id']; ?>&service_id=<?php echo $direct_book_service['id']; ?>" 
                                                   class="btn btn-primary" style="width: 100%;">
                                                    Book Now - ₹<?php echo number_format($provider['rate'], 2); ?>/hr
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-4">
                                <h4>No Providers Available</h4>
                                <p class="text-secondary">Sorry, no providers are currently offering this service. Please check back later.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Browse All Services -->
                <div class="d-flex justify-content-between align-items-center mb-4" style="gap:35rem;">
                    <h1 style="margin:0;">Book a Service</h1>
                    <div style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
                        <a href="cart.php" class="btn btn-secondary" style="margin:0;">
                            View Cart 
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                (<?php echo count($_SESSION['cart']); ?>)
                            <?php endif; ?>
                        </a>
                        <a href="dashboard.php" class="btn btn-outline" style="margin:0;">Dashboard</a>
                    </div>
                </div>
                
                <p class="text-center mb-2">Choose from our available services. Click "Book Now" to see providers, or "Add to Cart" to book multiple services together.</p>
                <div class="card mb-4" id="locationCard">
                    <div class="card-header"><h3 class="card-title">Location Based Search</h3></div>
                    <div class="card-body">
                        <p id="locationStatus" class="text-secondary" style="margin-bottom:0.75rem;">Location not enabled. Enable to sort providers by distance.</p>
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                            <button type="button" class="btn btn-primary btn-small" onclick="requestLocation()">Enable Location</button>
                            <button type="button" class="btn btn-outline btn-small" onclick="clearLocation()">Clear</button>
                            <button type="button" id="improveLocBtn" class="btn btn-outline btn-small" style="display:none;" onclick="improveUserAccuracy()">Improve Accuracy</button>
                            <select id="radiusSelect" class="form-control" style="max-width:140px;">
                                <option value="5">5 km</option>
                                <option value="10" selected>10 km</option>
                                <option value="25">25 km</option>
                                <option value="50">50 km</option>
                            </select>
                            <span id="locationAccuracy" style="font-size:0.7rem;color:#999;"></span>
                        </div>
                        <input type="hidden" id="userLat" value="">
                        <input type="hidden" id="userLng" value="">
                    </div>
                </div>
                
                <!-- Services Grid -->
                <div class="service-grid">
                    <?php
                    $services = $conn->query("SELECT * FROM services ORDER BY name");
                    while ($service = $services->fetch_assoc()):
                        // Count available providers for this service
                        $provider_count = $conn->query("
                            SELECT COUNT(DISTINCT ps.provider_id) as count 
                            FROM provider_services ps 
                            JOIN users u ON ps.provider_id = u.id 
                            WHERE ps.service_id = " . $service['id'] . " AND u.role = 'provider'
                        ");
                        $providers = $provider_count->fetch_assoc()['count'];
                        
                        // Get lowest rate for this service
                        $rate_query = $conn->query("
                            SELECT MIN(ps.rate) as min_rate 
                            FROM provider_services ps 
                            WHERE ps.service_id = " . $service['id']
                        );
                        $min_rate = $rate_query->fetch_assoc()['min_rate'];
                    ?>
                        <div class="service-card" 
                             data-category="<?php echo strtolower(str_replace(' ', '-', $service['category'] ?? 'general')); ?>"
                             data-min-price="<?php echo $min_rate ?: 0; ?>">
                            <div class="service-icon">
                                <?php 
                                $service_name = strtolower($service['name']);
                                if (strpos($service_name, 'plumb') !== false) {
                                    echo '🔧';
                                } elseif (strpos($service_name, 'electric') !== false) {
                                    echo '⚡';
                                } elseif (strpos($service_name, 'clean') !== false) {
                                    echo '🧹';
                                } elseif (strpos($service_name, 'repair') !== false) {
                                    echo '🔨';
                                } else {
                                    echo '🏠';
                                }
                                ?>
                            </div>
                            <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p class="service-description"><?php echo htmlspecialchars($service['description'] ?? 'Professional service tailored to your needs'); ?></p>
                            
                            <div class="service-stats">
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo $providers; ?></span>
                                    <span class="stat-label">Provider<?php echo $providers != 1 ? 's' : ''; ?></span>
                                </div>
                                <?php if ($min_rate): ?>
                                <div class="stat-item">
                                    <span class="stat-value">₹<?php echo number_format($min_rate, 2); ?></span>
                                    <span class="stat-label">Starting From</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($providers > 0): ?>
                                <div class="btn-group">
                                    <button type="button" 
                                            onclick="openProviderModal(<?php echo $service['id']; ?>, '<?php echo addslashes($service['name']); ?>')" 
                                            class="btn btn-primary">
                                        Book Now
                                    </button>
                                    <button type="button" 
                                            onclick="addToCart(<?php echo $service['id']; ?>, '<?php echo addslashes($service['name']); ?>')" 
                                            class="btn btn-outline">
                                        Add to Cart
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="service-actions">
                                    <button class="btn btn-outline" disabled style="width: 100%;">
                                        No Providers Available
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <?php 
                // Check if no services exist
                $services_count = $conn->query("SELECT COUNT(*) as count FROM services")->fetch_assoc()['count'];
                if ($services_count == 0):
                ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>No Services Available</h3>
                            <p class="text-secondary">Services will appear here as they are added to the platform.</p>
                            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Cart Summary (if items in cart) -->
                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Your Cart (<?php echo count($_SESSION['cart']); ?> services)</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-half">
                                    <?php
                                    foreach ($_SESSION['cart'] as $cart_service_id) {
                                        $cart_service = $conn->query("SELECT name FROM services WHERE id=$cart_service_id")->fetch_assoc();
                                        if ($cart_service) {
                                            echo "<p>• " . htmlspecialchars($cart_service['name']) . "</p>";
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="col-half">
                                    <a href="cart.php" class="btn btn-primary" style="width: 100%;">Proceed to Cart</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <?php if (!$direct_book_service): ?>
                    <a href="../index.php" class="btn btn-secondary">Browse Homepage</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Provider Selection Modal -->
<div id="providerModal" class="provider-modal">
    <div class="provider-modal-content">
        <span class="close" onclick="closeProviderModal()">&times;</span>
        <h2>Select Provider for <span id="modalServiceName"></span></h2>
        
        <form id="bookingForm" method="POST" action="confirm_booking.php">
            <input type="hidden" id="selectedServiceId" name="service_id" value="">
            <input type="hidden" id="selectedProviderId" name="provider_id" value="">
            <input type="hidden" id="selectedProviderName" name="provider_name" value="">
            <input type="hidden" id="selectedProviderRate" name="provider_rate" value="">
            
            <div id="providerList" class="provider-list">
                <!-- Providers will be loaded here via JavaScript -->
            </div>
            
            <div class="text-center mt-3">
                <button type="button" id="bookNowBtn" onclick="bookNow()" class="btn btn-primary" disabled>
                    Select a Provider
                </button>
                <button type="button" onclick="closeProviderModal()" class="btn btn-outline ml-2">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Search and Filter Bar (if showing all services) -->
<?php if (!$direct_book_service): ?>
<script>
// Add search and filter functionality to the page
document.addEventListener('DOMContentLoaded', function() {
    // Create search bar
    const searchBar = document.createElement('div');
    searchBar.className = 'search-filter-bar';
    searchBar.innerHTML = `
        <div class="form-row">
            <div class="form-group">
                <label for="searchInput">Search Services</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Search by service name...">
            </div>
            <div class="form-group">
                <label for="categoryFilter">Category</label>
                <select id="categoryFilter" class="form-control">
                    <option value="">All Categories</option>
                    <option value="home-maintenance">Home Maintenance</option>
                    <option value="electrical">Electrical</option>
                    <option value="plumbing">Plumbing</option>
                    <option value="cleaning">Cleaning</option>
                    <option value="repair">Repair</option>
                </select>
            </div>
            <div class="form-group">
                <label for="priceSort">Sort by Price</label>
                <select id="priceSort" class="form-control">
                    <option value="">Default</option>
                    <option value="asc">Price: Low to High</option>
                    <option value="desc">Price: High to Low</option>
                </select>
            </div>
        </div>
    `;
    
    // Insert search bar before service grid
    const serviceGrid = document.querySelector('.service-grid');
    if (serviceGrid) {
        serviceGrid.parentNode.insertBefore(searchBar, serviceGrid);
    }
});
</script>
<?php endif; ?>

<script src="../assets/js/booking.js"></script>
<script>
// Geolocation handling
let lastLocAccuracy=null; let locWatchId=null; let bestLocAccuracy=Infinity; let locWatchTimer=null; let askedLocThisSession=false;
const isLocalhost = /^(localhost|127\.0\.0\.1|\[::1\])$/.test(location.hostname);
async function getGeoPermissionState(){
    if(navigator.permissions && navigator.permissions.query){
        try{
            const res = await navigator.permissions.query({ name: 'geolocation' });
            return res.state; // 'granted' | 'prompt' | 'denied'
        }catch(e){ return 'unknown'; }
    }
    return 'unknown';
}
function stopLocationAccuracyWatch(){
    if(locWatchId!==null){ navigator.geolocation.clearWatch(locWatchId); locWatchId=null; }
    if(locWatchTimer){ clearTimeout(locWatchTimer); locWatchTimer=null; }
    const btn=document.getElementById('improveLocBtn'); if(btn) btn.disabled=false;
}
function startLocationAccuracyWatch(){
    if(!navigator.geolocation) return;
    if(locWatchId!==null){ updateLocStatus('Already refining location...', false); return; }
    const btn=document.getElementById('improveLocBtn'); if(btn) btn.disabled=true;
    bestLocAccuracy = (typeof lastLocAccuracy==='number' && !isNaN(lastLocAccuracy)) ? lastLocAccuracy : Infinity;
    updateLocStatus('Refining location for better accuracy...', false);
    try{
        locWatchId = navigator.geolocation.watchPosition(pos=>{
            const acc = pos.coords.accuracy;
            if(bestLocAccuracy===Infinity || (acc+5) < bestLocAccuracy){
                bestLocAccuracy = acc;
                setCustomerLocation(pos.coords.latitude,pos.coords.longitude,acc);
            }
            if(acc <= 25){
                updateLocStatus('High accuracy achieved (~'+Math.round(acc)+'m).', false);
                stopLocationAccuracyWatch();
            }
        }, err=>{
            updateLocStatus('Accuracy watch ended: '+err.message, true);
            stopLocationAccuracyWatch();
        }, {enableHighAccuracy:true, maximumAge:0});
        // Safety stop after 25s
        locWatchTimer = setTimeout(()=>{
            stopLocationAccuracyWatch();
            if(typeof bestLocAccuracy==='number' && isFinite(bestLocAccuracy)){
                updateLocStatus('Used best available fix (~'+Math.round(bestLocAccuracy)+'m).', false);
            } else {
                updateLocStatus('Couldn\'t improve accuracy further. You can still search; results may be less precise.', true);
            }
        }, 25000);
    }catch(e){ updateLocStatus('Could not start accuracy watch.', true); }
}
function setCustomerLocation(lat,lng,accuracy=null){
    document.getElementById('userLat').value=lat;
    document.getElementById('userLng').value=lng;
    localStorage.setItem('homeaid_lat', lat);
    localStorage.setItem('homeaid_lng', lng);
    if(accuracy!==null){
        lastLocAccuracy=accuracy;
        const accEl=document.getElementById('locationAccuracy');
        if(accEl) accEl.textContent='±'+Math.round(accuracy)+'m';
        document.getElementById('improveLocBtn').style.display = accuracy>50? 'inline-block':'none';
    }
    updateLocStatus('Location enabled: '+parseFloat(lat).toFixed(3)+', '+parseFloat(lng).toFixed(3), false);
    // If provider modal is open, refresh provider list with new location
    const modal = document.getElementById('providerModal');
    const isOpen = modal && window.getComputedStyle(modal).display !== 'none';
    const svcEl = document.getElementById('selectedServiceId');
    if(isOpen && svcEl && svcEl.value){ fetchProviders(svcEl.value); }
}
function requestLocation(){
    if(!navigator.geolocation){ updateLocStatus('Geolocation not supported by this browser.', true); return; }
    updateLocStatus('Requesting precise location...', false);
    navigator.geolocation.getCurrentPosition(pos=>{
        setCustomerLocation(pos.coords.latitude,pos.coords.longitude,pos.coords.accuracy);
        // Begin short-lived refinement
        startLocationAccuracyWatch();
    }, err=>{
        if(err.code===err.TIMEOUT){
            updateLocStatus('High accuracy timeout, trying approximate...', false);
            navigator.geolocation.getCurrentPosition(p2=>{
                setCustomerLocation(p2.coords.latitude,p2.coords.longitude,p2.coords.accuracy);
                startLocationAccuracyWatch();
            }, err2=>{
                updateLocStatus('Location error: '+err2.message, true);
            }, {enableHighAccuracy:false, timeout:8000, maximumAge:300000});
        } else {
            updateLocStatus('Location denied or unavailable ('+err.message+'). Using non-location search.', true);
        }
    }, {enableHighAccuracy:true, timeout:12000, maximumAge:0});
}
function improveUserAccuracy(){
    if(!navigator.geolocation) return;
    if(locWatchId!==null){ updateLocStatus('Already refining location...', false); return; }
    getGeoPermissionState().then(state=>{
        if(state==='denied'){
            updateLocStatus('Location permission denied. Please enable it in your browser\'s Site settings.', true);
            return;
        }
        if(state==='prompt' && askedLocThisSession){
            updateLocStatus('To avoid repeated prompts and get better accuracy, set Location permission to Allow in Site settings.', false);
        }
        startLocationAccuracyWatch();
    });
}
function clearLocation(){
    document.getElementById('userLat').value='';
    document.getElementById('userLng').value='';
    localStorage.removeItem('homeaid_lat');
    localStorage.removeItem('homeaid_lng');
    document.getElementById('locationAccuracy').textContent='';
    document.getElementById('improveLocBtn').style.display='none';
    updateLocStatus('Location cleared. Enable to sort/filter by distance.', false);
}
function updateLocStatus(msg,isError){
    const el=document.getElementById('locationStatus');
    if(!el) return; el.textContent=msg; el.style.color=isError?'#b91c1c':'#64748b';
}
document.addEventListener('DOMContentLoaded', () => {
    const sLat = localStorage.getItem('homeaid_lat');
    const sLng = localStorage.getItem('homeaid_lng');
    if(sLat && sLng){
        setCustomerLocation(sLat,sLng,null);
    }
    if(location.protocol!=='https:' && !isLocalhost){
        updateLocStatus('Tip: Use HTTPS for better location accuracy and fewer prompts. On HTTP, browsers often reduce accuracy.', false);
    }
});
</script>

<?php include "../includes/footer.php"; ?>
