<?php
$pageTitle = 'Public Grievance & Complaint Redressal Portal';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Quick stats from DB if available
$totalComplaints = 0;
$resolvedComplaints = 0;
$inProgressComplaints = 0;

try {
    $db = getDB();
    $stats = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress
        FROM complaints")->fetch();
    if ($stats) {
        $totalComplaints = $stats['total'] ?? 0;
        $resolvedComplaints = $stats['resolved'] ?? 0;
        $inProgressComplaints = $stats['in_progress'] ?? 0;
    }
} catch (Exception $e) {
    // Fail gracefully if DB is not yet imported
}
?>

<section class="hero">
    <div class="hero-badge">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        <span>Official Redressal & Resolution System</span>
    </div>
    
    <h1>Transparent, Fast & Reliable Grievance Management</h1>
    <p>Submit your complaints, upload relevant supporting documents, and receive real-time status updates from civic and institutional authorities.</p>

    <div class="hero-actions">
        <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?><?= isAdmin() ? 'admin/dashboard.php' : 'user/new_complaint.php' ?>" class="btn btn-primary btn-lg">
                <?= isAdmin() ? 'Go to Admin Dashboard' : '+ Submit New Complaint' ?>
            </a>
            <a href="<?= BASE_URL ?><?= isAdmin() ? 'admin/complaints.php' : 'user/my_complaints.php' ?>" class="btn btn-secondary btn-lg">
                View All Grievances
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>register.php" class="btn btn-primary btn-lg">Register & Lodge Complaint</a>
            <a href="<?= BASE_URL ?>login.php" class="btn btn-secondary btn-lg">Sign In to Account</a>
        <?php endif; ?>
    </div>

    <!-- Quick Track Search Box -->
    <div class="track-card">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            Instant Complaint Tracker
        </h3>
        <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
            Have a tracking code? Enter it below to check current resolution status without logging in.
        </p>
        <form action="<?= BASE_URL ?>track.php" method="GET" class="track-form">
            <input type="text" name="tracking_code" placeholder="e.g. CMP-2026-7841" required autocomplete="off" style="text-transform: uppercase;">
            <button type="submit" class="btn btn-primary">Track Status</button>
        </form>
    </div>
</section>

<!-- Live Impact Stats -->
<div class="cards-grid" style="margin-top: 2rem;">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Registered Complaints</h3>
            <div class="stat-number"><?= number_format($totalComplaints) ?></div>
        </div>
        <div class="stat-icon icon-primary">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Under Investigation</h3>
            <div class="stat-number"><?= number_format($inProgressComplaints) ?></div>
        </div>
        <div class="stat-icon icon-info">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Successfully Resolved</h3>
            <div class="stat-number" style="color: var(--success);"><?= number_format($resolvedComplaints) ?></div>
        </div>
        <div class="stat-icon icon-success">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
    </div>
</div>

<!-- Workflow Steps -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <div class="card-title">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
            </svg>
            How The Redressal Process Works
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
            <div style="padding: 1rem; border-radius: var(--radius-md); background: #f8fafc; border: 1px solid var(--border-subtle);">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 0.75rem;">1</div>
                <h4 style="font-size: 1rem; margin-bottom: 0.35rem;">File Complaint</h4>
                <p style="font-size: 0.875rem; color: var(--text-muted);">Fill in grievance details, select category & priority, and upload photo/document proof.</p>
            </div>

            <div style="padding: 1rem; border-radius: var(--radius-md); background: #f8fafc; border: 1px solid var(--border-subtle);">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--info); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 0.75rem;">2</div>
                <h4 style="font-size: 1rem; margin-bottom: 0.35rem;">Get Tracking ID</h4>
                <p style="font-size: 0.875rem; color: var(--text-muted);">Receive an instant unique code (e.g. CMP-2026-XXXX) for 24/7 status tracking.</p>
            </div>

            <div style="padding: 1rem; border-radius: var(--radius-md); background: #f8fafc; border: 1px solid var(--border-subtle);">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--warning); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 0.75rem;">3</div>
                <h4 style="font-size: 1rem; margin-bottom: 0.35rem;">Department Action</h4>
                <p style="font-size: 0.875rem; color: var(--text-muted);">Admin officers inspect the report, assign staff, and update remarks in real-time.</p>
            </div>

            <div style="padding: 1rem; border-radius: var(--radius-md); background: #f8fafc; border: 1px solid var(--border-subtle);">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--success); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 0.75rem;">4</div>
                <h4 style="font-size: 1rem; margin-bottom: 0.35rem;">Resolution</h4>
                <p style="font-size: 0.875rem; color: var(--text-muted);">Issue is resolved with official feedback and recorded in transparent audit history.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
