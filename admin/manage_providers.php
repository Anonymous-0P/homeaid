<?php
require_once "../includes/session_manager.php";

// Check authentication with session timeout
if (!SessionManager::checkAuth('admin')) {
    header("Location: login.php");
    exit();
}

// Include DB and layout pieces
include "../config/db.php";
include "../includes/header.php";
include "../includes/navbar.php";

// Initialize feedback variables early to avoid undefined warnings
$success_message = '';
$error_message   = '';

// Ensure verification_status column exists (safeguard to avoid duplicate alter errors)
if ($conn) {
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS verification_status ENUM('pending','approved','rejected') DEFAULT 'pending' AFTER role");
}

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['provider_id'])) {
    $provider_id  = (int)$_POST['provider_id'];
    $action       = $_POST['action'];
    $admin_notes  = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';

    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        if ($conn) {
            $stmt = $conn->prepare("UPDATE users SET verification_status = ? WHERE id = ? AND role = 'provider'");
            if ($stmt) {
                $stmt->bind_param("si", $status, $provider_id);
                if ($stmt->execute()) {
                    // Notification message
                    $message = ($action === 'approve')
                        ? "🎉 Congratulations! Your provider account has been approved. You can now receive booking requests."
                        : "❌ Your provider verification has been rejected. Please contact support for more information.";
                    if ($admin_notes) {
                        $message .= "\n\nAdmin Notes: " . $admin_notes;
                    }
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, FALSE, NOW())");
                    if ($notif_stmt) {
                        $notif_stmt->bind_param("is", $provider_id, $message);
                        $notif_stmt->execute();
                    }
                    $success_message = "Provider " . ucfirst($status) . " successfully!";
                } else {
                    $error_message = "Failed to update provider status.";
                }
            } else {
                $error_message = "Database error preparing statement.";
            }
        } else {
            $error_message = "Database connection not available.";
        }
    }
}

// Queries
$result = $conn ? $conn->query("SELECT id, name, email, phone, created_at, verification_status, photo FROM users WHERE role='provider' ORDER BY verification_status = 'pending' DESC, created_at DESC") : false;

$provider_cards_query = "SELECT u.id, u.name, u.email, u.phone, u.photo, u.verification_status, u.created_at,
     u.aadhaar_number, u.aadhaar_file,
     GROUP_CONCAT(CONCAT(s.name, ':', ps.rate) SEPARATOR '|') as services_rates
 FROM users u
 LEFT JOIN provider_services ps ON u.id = ps.provider_id AND ps.is_active = 1
 LEFT JOIN services s ON ps.service_id = s.id
 WHERE u.role = 'provider'
 GROUP BY u.id, u.name, u.email, u.phone, u.photo, u.verification_status, u.created_at, u.aadhaar_number, u.aadhaar_file
 ORDER BY u.verification_status = 'pending' DESC, u.created_at DESC";
$provider_cards_result = $conn ? $conn->query($provider_cards_query) : false;
?>
<main>
    <div class="container">
        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4" style="gap:1.5rem;">
                <h1>Manage Providers</h1> <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            </div> <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div> <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div> <?php endif; ?>
            <!-- Summary Cards -->
            <div class="dashboard-grid" style="margin-bottom:2rem;">
                <?php $pending_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='provider' AND verification_status='pending'")->fetch_assoc()['count'];
                $approved_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='provider' AND verification_status='approved'")->fetch_assoc()['count'];
                $rejected_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='provider' AND verification_status='rejected'")->fetch_assoc()['count']; ?>
                <div class="dashboard-card"
                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                    <h3>Pending Verification</h3>
                    <p style="font-size:2.5rem;font-weight:700;margin:0.5rem 0;color:white;"><?= $pending_count ?></p>
                    <p style="color:white;"><small>Awaiting admin approval</small></p>
                </div>
                <div class="dashboard-card"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                    <h3>Approved Providers</h3>
                    <p style="font-size:2.5rem;font-weight:700;margin:0.5rem 0;color:white;"><?= $approved_count ?></p>
                    <p style="color:white;"><small>Active and verified</small></p>
                </div>
                <div class="dashboard-card"
                    style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                    <h3>Rejected</h3>
                    <p style="font-size:2.5rem;font-weight:700;margin:0.5rem 0;color:white;"><?= $rejected_count ?></p>
                    <p style="color:white;"><small>Verification denied</small></p>
                </div>
            </div> <!-- Unified Provider Management Center -->
            <div class="card" style="border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div class="card-header"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 0.5rem 0.5rem 0 0; padding: 1.5rem;">
                    <h2 class="card-title"
                        style="color: white; margin: 0; display: flex; align-items: center; gap: 0.75rem; font-size: 1.4rem;">
                        <span style="font-size: 1.5rem;">👥</span> Provider Management Center </h2>
                    <p style="margin: 0.5rem 0 0 0; color: rgba(255,255,255,0.9); font-size: 0.9rem;">Complete provider
                        information and management in one place</p>
                </div>
                <div class="card-body" style="padding: 2rem; background: #f8fafc;">
                    <?php if ($provider_cards_result && $provider_cards_result->num_rows > 0): ?>
                        <div style="display:flex; flex-direction:column; gap:2rem;">
                            <?php while ($provider = $provider_cards_result->fetch_assoc()):
                                $status = isset($provider['verification_status']) ? $provider['verification_status'] : 'pending'; ?>
                                <div class="provider-card"
                                    style="border: 1px solid #e2e8f0; border-radius: 1rem; padding: 2rem; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s ease; position: relative; overflow: hidden;">
                                    <!-- Provider ID Badge (Top Left) -->
                                    <div
                                        style="position: absolute; top: 1rem; left: 1rem; padding: 0.4rem 0.8rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600; background: #e0e7ff; color: #4f46e5; border: 1px solid #c7d2fe;">
                                        ID: #<?= htmlspecialchars($provider['id']) ?> </div> <!-- Status Badge (Top Right) -->
                                    <?php $badge_styles = ['pending' => 'background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #92400e; box-shadow: 0 2px 4px rgba(251,191,36,0.3);', 'approved' => 'background: linear-gradient(135deg, #10b981, #059669); color: white; box-shadow: 0 2px 4px rgba(16,185,129,0.3);', 'rejected' => 'background: linear-gradient(135deg, #ef4444, #dc2626); color: white; box-shadow: 0 2px 4px rgba(239,68,68,0.3);']; ?>
                                    <div
                                        style="position: absolute; top: 1rem; right: 1rem; padding: 0.5rem 1rem; border-radius: 1.5rem; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; <?= $badge_styles[$status] ?>">
                                        <?= ucfirst($status) ?> </div> <!-- Layout Wrapper for Info / Services / Actions -->
                                    <!-- Provider Info Section -->
                                    <div style="display:flex; align-items:flex-start; gap:1.25rem; margin:2.2rem 0 1.5rem 0;">
                                        <div style="width: 90px; height: 90px; border-radius: 50%; overflow: hidden; background: #f1f5f9; flex-shrink: 0; border: 4px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                            <?php if ($provider['photo']): ?>
                                                <img src="../assets/uploads/<?= htmlspecialchars($provider['photo']) ?>" 
                                                     alt="<?= htmlspecialchars($provider['name']) ?>" 
                                                     style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold; font-size: 2rem;">
                                                    <?= strtoupper(substr($provider['name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="flex:1; min-width:0; padding-top:0.5rem;">
                                            <h4 style="margin:0 0 1rem 0; font-size:1.4rem; color:#1e293b; font-weight:700; line-height:1.2; word-wrap:break-word;">
                                                <?= htmlspecialchars($provider['name']) ?>
                                            </h4>
                                            <div style="display:flex; flex-direction:column; gap:0.6rem;">
                                                <p style="margin:0; color:#64748b; font-size:0.9rem; display:flex; align-items:center; gap:0.6rem;">
                                                    <span style="color:#3b82f6; font-size:1rem;">📧</span>
                                                    <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($provider['email']) ?></span>
                                                </p>
                                                <p style="margin:0; color:#64748b; font-size:0.9rem; display:flex; align-items:center; gap:0.6rem;">
                                                    <span style="color:#10b981; font-size:1rem;">📞</span>
                                                    <?= htmlspecialchars(isset($provider['phone']) ? $provider['phone'] : 'No phone provided') ?>
                                                </p>
                                                <p style="margin:0; color:#64748b; font-size:0.85rem; display:flex; align-items:center; gap:0.6rem;">
                                                    <span style="color:#8b5cf6; font-size:1rem;">📅</span>
                                                    Joined: <?= date('M j, Y', strtotime(isset($provider['created_at']) ? $provider['created_at'] : 'now')) ?>
                                                </p>
                                                <?php 
                                                $aadhaarMasked = '';
                                                if (!empty($provider['aadhaar_number'])) {
                                                    $aad = preg_replace('/\D+/', '', $provider['aadhaar_number']);
                                                    if (strlen($aad) >= 4) {
                                                        $aadhaarMasked = str_repeat('•', max(0, strlen($aad) - 4)) . substr($aad, -4);
                                                    }
                                                }
                                                if ($aadhaarMasked || !empty($provider['aadhaar_file'])): ?>
                                                <div style="margin-top:0.25rem; display:flex; align-items:center; gap:0.6rem; color:#475569;">
                                                    <span style="color:#0ea5e9;">🪪</span>
                                                    <span style="font-size:0.9rem;">Aadhaar: <?= htmlspecialchars($aadhaarMasked ?: '—') ?></span>
                                                    <?php if (!empty($provider['aadhaar_file'])): ?>
                                                        <a href="view_aadhaar.php?uid=<?= (int)$provider['id'] ?>" target="_blank" style="font-size:0.85rem; color:#2563eb; text-decoration:underline;">View Document</a>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Lower Section: Service Charges and Actions Side by Side -->
                                    <div style="display:flex; gap:3rem; align-items:flex-start;">
                                        <!-- Service Charges Section -->
                                        <div style="flex:1; padding:1.25rem; background:#f8fafc; border-radius:0.75rem; border:1px solid #e2e8f0;">
                                            <h5 style="margin:0 0 1rem 0; color:#374151; font-size:1rem; font-weight:600; display:flex; align-items:center; gap:0.6rem;">
                                                <span style="color:#059669; font-size:1.1rem;">💰</span> Service Charges
                                            </h5>
                                            <?php if ($provider['services_rates']) {
                                                $services = explode('|', $provider['services_rates']);
                                                foreach ($services as $service_rate) {
                                                    if (strpos($service_rate, ':') !== false) {
                                                        list($service_name, $rate) = explode(':', $service_rate, 2);
                                                        echo '<div style="display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0; border-bottom:1px solid #e2e8f0;">';
                                                        echo '<span style="color:#6b7280; font-size:0.95rem; font-weight:500;">' . htmlspecialchars($service_name) . '</span>';
                                                        echo '<span style="font-weight:700; color:#059669; font-size:1.05rem; background:#ecfdf5; padding:0.4rem 1rem; border-radius:0.6rem; border:1px solid #d1fae5;">₹' . number_format($rate, 2) . '/hr</span>';
                                                        echo '</div>';
                                                    }
                                                }
                                            } else {
                                                echo '<div style="text-align:center; padding:1.5rem; color:#9ca3af; font-style:italic; background:#f3f4f6; border-radius:0.6rem; border:2px dashed #d1d5db;">';
                                                echo '<span style="font-size:2.5rem; display:block; margin-bottom:0.5rem;">💼</span>';
                                                echo '<p style="margin:0; font-size:0.95rem;">No services configured yet</p>';
                                                echo '</div>';
                                            } ?>
                                        </div>
                                        
                                        <!-- Management Actions -->
                                        <div style="flex:0 0 180px; display:flex; flex-direction:column; gap:0.6rem; align-items:stretch; padding:1rem; background:#ffffff; border-radius:0.75rem; border:1px solid #e2e8f0;">
                                            <?php if ($status === 'pending'): ?>
                                            <button class="btn btn-success"
                                                    style="width:100%; padding:0.8rem 1.2rem; font-weight:600; border-radius:0.65rem; border:none; background:linear-gradient(135deg, #10b981, #059669); color:white; transition:all 0.25s ease; box-shadow:0 2px 4px rgba(16,185,129,0.3);"
                                                    onclick="showVerificationModal(<?= $provider['id'] ?>, 'approve', '<?= htmlspecialchars($provider['name']) ?>')">
                                                    ✓ Approve </button> <button class="btn btn-danger"
                                                    style="width:100%; padding:0.8rem 1.2rem; font-weight:600; border-radius:0.65rem; border:none; background:linear-gradient(135deg, #ef4444, #dc2626); color:white; transition:all 0.25s ease; box-shadow:0 2px 4px rgba(239,68,68,0.3);"
                                                    onclick="showVerificationModal(<?= $provider['id'] ?>, 'reject', '<?= htmlspecialchars($provider['name']) ?>')">
                                                    ✗ Reject </button> <?php elseif ($status === 'approved'): ?> <button
                                                    class="btn btn-warning"
                                                    style="width:100%; padding:0.8rem 1.2rem; font-weight:600; border-radius:0.65rem; border:none; background:linear-gradient(135deg, #f59e0b, #d97706); color:white; transition:all 0.25s ease; box-shadow:0 2px 4px rgba(245,158,11,0.3);"
                                                    onclick="showVerificationModal(<?= $provider['id'] ?>, 'reject', '<?= htmlspecialchars($provider['name']) ?>')">
                                                    🔒 Revoke Access </button> <?php elseif ($status === 'rejected'): ?> <button
                                                    class="btn btn-success"
                                                    style="width:100%; padding:0.8rem 1.2rem; font-weight:600; border-radius:0.65rem; border:none; background:linear-gradient(135deg, #10b981, #059669); color:white; transition:all 0.25s ease; box-shadow:0 2px 4px rgba(16,185,129,0.3);"
                                                    onclick="showVerificationModal(<?= $provider['id'] ?>, 'approve', '<?= htmlspecialchars($provider['name']) ?>')">
                                                    🔄 Re-approve </button> <?php endif; ?> <a
                                                href="delete_provider.php?id=<?= $provider['id'] ?>"
                                                style="width:100%; padding:0.85rem 1rem; font-weight:600; border-radius:0.65rem; border:none; background:linear-gradient(135deg, #dc2626, #b91c1c); color:white; text-decoration:none; display:flex; align-items:center; justify-content:center; transition:all 0.25s ease; box-shadow:0 2px 4px rgba(220,38,38,0.3);"
                                                onclick="return confirm('Are you sure you want to delete this provider? This action cannot be undone.')">
                                                🗑️ Delete </a>
                                        </div>
                                    </div>
                                </div> <?php endwhile; ?>
                        </div> <?php else: ?>
                        <div class="text-center"
                            style="padding: 4rem 2rem; background: white; border-radius: 1rem; border: 2px dashed #d1d5db;">
                            <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;">�</div>
                            <h3 style="color: #6b7280; margin-bottom: 0.5rem;">No Providers Found</h3>
                            <p style="color: #9ca3af; margin: 0;">No providers have registered yet. Once providers register,
                                their information will appear here.</p>
                        </div> <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main> <!-- Verification Modal -->
<div id="verificationModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; backdrop-filter: blur(4px);">
    <div
        style="position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); background: white; padding: 2.5rem; border-radius: 1rem; max-width: 500px; width: 90%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e2e8f0;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div
                style="width: 80px; height: 80px; margin: 0 auto 1rem; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                ⚠️ </div>
            <h3 id="modalTitle" style="margin: 0 0 0.5rem 0; color: #1e293b; font-size: 1.5rem; font-weight: 700;">
                Verify Provider</h3>
            <p id="modalText" style="margin: 0; color: #64748b; font-size: 1rem;">Are you sure you want to perform this
                action?</p>
        </div>
        <form method="POST" id="verificationForm" style="margin-top: 1rem;"> <input type="hidden" id="providerId"
                name="provider_id"> <input type="hidden" id="actionType" name="action">
            <div style="margin-bottom: 1.5rem;"> <label for="adminNotes"
                    style="display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 600; font-size: 0.9rem;">Admin
                    Notes (Optional)</label> <textarea id="adminNotes" name="admin_notes"
                    style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.9rem; color: #374151; resize: vertical; min-height: 80px; transition: border-color 0.2s ease;"
                    rows="3" placeholder="Add any notes for the provider..."
                    onfocus="this.style.borderColor='#667eea'; this.style.outline='none';"
                    onblur="this.style.borderColor='#e2e8f0';"></textarea> </div>
            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;"> <button type="button"
                    style="padding: 0.75rem 2rem; border: 2px solid #e2e8f0; background: white; color: #6b7280; border-radius: 0.75rem; font-weight: 600; transition: all 0.2s ease; cursor: pointer;"
                    onclick="closeVerificationModal()"
                    onmouseover="this.style.borderColor='#d1d5db'; this.style.color='#374151';"
                    onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#6b7280';"> Cancel </button> <button
                    type="submit"
                    style="padding: 0.75rem 2rem; border: none; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 0.75rem; font-weight: 600; transition: all 0.2s ease; cursor: pointer; box-shadow: 0 4px 12px rgba(102,126,234,0.4);"
                    id="confirmBtn"
                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(102,126,234,0.5)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(102,126,234,0.4)';">
                    Confirm </button> </div>
        </form>
    </div>
</div>
<script>
// Modal open/close and wiring for approve/reject
function showVerificationModal(providerId, action, providerName) {
    const modal = document.getElementById('verificationModal');
    const title = document.getElementById('modalTitle');
    const text = document.getElementById('modalText');
    const confirmBtn = document.getElementById('confirmBtn');
    document.getElementById('providerId').value = providerId;
    document.getElementById('actionType').value = action;
    document.getElementById('adminNotes').value = '';

    if (action === 'approve') {
        title.textContent = 'Approve Provider';
        text.textContent = `Approve ${providerName} as a verified provider? They will be able to receive booking requests.`;
        confirmBtn.textContent = 'Approve';
        confirmBtn.className = 'btn btn-success';
    } else {
        title.textContent = 'Reject Provider';
        text.textContent = `Reject ${providerName}'s provider verification? They will not be able to receive bookings.`;
        confirmBtn.textContent = 'Reject';
        confirmBtn.className = 'btn btn-danger';
    }
    modal.style.display = 'block';
}
function closeVerificationModal() {
    document.getElementById('verificationModal').style.display = 'none';
}
// Close modal when clicking backdrop
document.getElementById('verificationModal').addEventListener('click', function(e) {
    if (e.target === this) closeVerificationModal();
});
// Enhance provider card hover
document.addEventListener('DOMContentLoaded', function() {
    const providerCards = document.querySelectorAll('.provider-card');
    providerCards.forEach(card => {
        card.addEventListener('mouseenter', function(){
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
        });
        card.addEventListener('mouseleave', function(){
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1)';
        });
    });
});
</script>
<style>
    /* Additional responsive and enhancement styles */
    @media (max-width: 768px) {
        .provider-cards-grid {
            grid-template-columns: 1fr !important;
        }

        .card-header h2 {
            font-size: 1.2rem !important;
        }

        .table-responsive {
            font-size: 0.85rem;
        }

        .btn {
            padding: 0.4rem 0.8rem !important;
            font-size: 0.75rem !important;
        }
    }

    @media (max-width: 480px) {
        .card-body {
            padding: 1rem !important;
        }

        .provider-card {
            padding: 1rem !important;
        }

        .dashboard-grid {
            grid-template-columns: 1fr !important;
        }
    }

    /* Button hover effects */
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    }

    /* Table row hover animation */
    tbody tr {
        transition: all 0.2s ease !important;
    }

    /* Card hover animations */
    .provider-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    /* Modal animation */
    #verificationModal {
        animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    #verificationModal>div {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translate(-50%, -60%);
            opacity: 0;
        }

        to {
            transform: translate(-50%, -50%);
            opacity: 1;
        }
    }
</style> <?php include "../includes/footer.php"; ?>