<?php 
require_once "../includes/session_manager.php";

// Check authentication with session timeout
if (!SessionManager::checkAuth('customer')) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
include "../includes/header.php"; 
include "../includes/navbar.php"; 
include "../config/db.php"; 
?>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary-color: #3b82f6;
        --primary-dark: #1e40af;
        --secondary-color: #64748b;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --background-color: #f8fafc;
        --card-background: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --border-color: #e2e8f0;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --gradient-success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --gradient-warning: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    body {
        background: var(--background-color);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        line-height: 1.6;
        color: var(--text-primary);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .content-wrapper {
        background: var(--card-background);
        border-radius: 20px;
        box-shadow: var(--shadow-lg);
        overflow: hidden;
        position: relative;
    }

    .content-wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient-primary);
    }

    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3rem 2rem 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .dashboard-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: repeating-linear-gradient(
            45deg,
            transparent,
            transparent 2px,
            rgba(255,255,255,0.03) 2px,
            rgba(255,255,255,0.03) 4px
        );
        animation: slide 20s linear infinite;
    }

    @keyframes slide {
        0% { transform: translateX(-50px) translateY(-50px); }
        100% { transform: translateX(0px) translateY(0px); }
    }

    .dashboard-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: relative;
        z-index: 1;
    }

    .dashboard-header p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin: 0;
        position: relative;
        z-index: 1;
        color: white;
    }

    .dashboard-content {
        padding: 2rem;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .dashboard-card {
        background: var(--card-background);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: var(--shadow-sm);
    }

    .dashboard-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-color);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .dashboard-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-color);
    }

    .dashboard-card:hover::before {
        transform: scaleX(1);
    }

    .dashboard-card:nth-child(1) { --accent-color: var(--primary-color); --gradient: var(--gradient-primary); }
    .dashboard-card:nth-child(2) { --accent-color: var(--warning-color); --gradient: var(--gradient-warning); }
    .dashboard-card:nth-child(3) { --accent-color: var(--success-color); --gradient: var(--gradient-success); }
    .dashboard-card:nth-child(4) { --accent-color: var(--secondary-color); --gradient: var(--gradient-secondary); }

    .dashboard-card::before {
        background: var(--gradient);
    }

    .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--gradient);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
        box-shadow: var(--shadow-md);
    }

    .dashboard-card h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.5rem 0;
        color: var(--text-primary);
    }

    .dashboard-card p {
        color: var(--text-secondary);
        margin: 0 0 1.5rem 0;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .notification-badge {
        background: var(--accent-color) !important;
        color: white !important;
        border-radius: 50% !important;
        width: 28px !important;
        height: 28px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        margin: 0.5rem auto !important;
        box-shadow: var(--shadow-md) !important;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .status-text {
        font-weight: 600 !important;
        margin: 0.5rem 0 1rem 0 !important;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        background: rgba(var(--accent-color), 0.1);
        border: 1px solid rgba(var(--accent-color), 0.2);
        display: inline-block;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        font-size: 0.95rem;
        font-weight: 600;
        text-decoration: none;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: none;
        letter-spacing: 0.025em;
        position: relative;
        overflow: hidden;
    }

    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .btn:hover::before {
        left: 100%;
    }

    .btn-primary {
        background: var(--gradient-primary);
        color: white;
        box-shadow: var(--shadow-md);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .btn-secondary {
        background: var(--gradient-warning);
        color: white;
        box-shadow: var(--shadow-md);
    }

    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .btn-outline {
        background: transparent;
        color: var(--primary-color);
        border: 2px solid var(--primary-color);
    }

    .btn-outline:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
    }

    .btn-danger {
        background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
        color: white;
        box-shadow: var(--shadow-md);
    }

    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .action-buttons {
        text-align: center;
        padding: 2rem 0 1rem;
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }
        
        .dashboard-header {
            padding: 2rem 1rem;
        }
        
        .dashboard-header h1 {
            font-size: 2rem;
        }
        
        .dashboard-content {
            padding: 1.5rem;
        }
        
        .dashboard-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .dashboard-card {
            padding: 1.5rem;
        }
        
        .action-buttons {
            flex-direction: column;
            align-items: center;
        }
        
        .btn {
            width: 100%;
            max-width: 300px;
        }
    }

    /* Loading Animation */
    .fade-in {
        animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--background-color);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-color);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }
</style>

<main>
    <div class="container">
        <div class="content-wrapper fade-in">
            
            
            <div class="dashboard-header">
                <h1><i class="fas fa-tachometer-alt"></i> Welcome to Your Dashboard!</h1>
                <p>Manage your bookings and find services with ease</p>
            </div>
            
            <div class="dashboard-content">
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <h3>Book a Service</h3>
                        <p>Find and book trusted service providers in your area with just a few clicks.</p>
                        <a href="book_service.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Book Now
                        </a>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3>My Cart</h3>
                        <p>Review your selected services before confirming your booking.</p>
                        <?php
                        $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                        if ($cart_count > 0): ?>
                            <div class="notification-badge">
                                <?php echo $cart_count; ?>
                            </div>
                            <div class="status-text" style="color: #f59e0b;">
                                <?php echo $cart_count; ?> item<?php echo $cart_count > 1 ? 's' : ''; ?> in cart
                            </div>
                        <?php else: ?>
                            <div style="margin: 1rem 0;">
                                <span style="color: var(--text-secondary);">Your cart is empty</span>
                            </div>
                        <?php endif; ?>
                        <a href="cart.php" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> View Cart
                        </a>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>Notifications</h3>
                        <p>Stay updated with the latest information about your bookings.</p>
                        <?php
                        // Get unread notification count
                        $notification_count = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $customer_id AND is_read = FALSE")->fetch_assoc()['count'];
                        if ($notification_count > 0): ?>
                            <div class="notification-badge">
                                <?php echo $notification_count; ?>
                            </div>
                            <div class="status-text" style="color: #10b981;">
                                <?php echo $notification_count; ?> new notification<?php echo $notification_count > 1 ? 's' : ''; ?>!
                            </div>
                        <?php else: ?>
                            <div style="margin: 1rem 0;">
                                <span style="color: var(--text-secondary);">All caught up!</span>
                            </div>
                        <?php endif; ?>
                        <a href="notifications.php" class="btn btn-secondary">
                            <i class="fas fa-inbox"></i> View Notifications
                        </a>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3>My Bookings</h3>
                        <p>Track and manage your current and past service bookings.</p>
                        <?php
                        // Get active bookings count
                        $active_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE customer_id = $customer_id AND status IN ('pending', 'accepted')")->fetch_assoc()['count'];
                        if ($active_bookings > 0): ?>
                            <div style="margin: 1rem 0;">
                                <div class="status-text" style="color: #3b82f6;">
                                    <?php echo $active_bookings; ?> active booking<?php echo $active_bookings > 1 ? 's' : ''; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="margin: 1rem 0;">
                                <span style="color: var(--text-secondary);">No active bookings</span>
                            </div>
                        <?php endif; ?>
                        <a href="my_bookings.php" class="btn btn-outline">
                            <i class="fas fa-list"></i> View Bookings
                        </a>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="../index.php" class="btn btn-outline">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                    <a href="../logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
