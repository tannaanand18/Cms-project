<?php
$pageTitle = 'Administrator Dashboard';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$admin = currentUser();
$db = getDB();

// Global Stats
$stats = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM complaints")->fetch();

$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

$resolutionRate = ($stats['total'] > 0) ? round(($stats['resolved'] / $stats['total']) * 100, 1) : 0;

// High priority pending/in progress complaints
$urgentStmt = $db->query("SELECT c.*, cat.name as category_name, u.name as user_name 
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.priority IN ('Urgent', 'High') AND c.status IN ('Pending', 'In Progress')
    ORDER BY c.created_at ASC
    LIMIT 6");
$urgentComplaints = $urgentStmt->fetchAll();

// Recent 5 complaints overall
$recentStmt = $db->query("SELECT c.*, cat.name as category_name, u.name as user_name 
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 5");
$recentComplaints = $recentStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-main);">Admin Command Center</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Monitor civic issues, assign departments, and track resolution metrics.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>admin/complaints.php" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            Manage All Grievances
        </a>
    </div>
</div>

<!-- Key Performance Indicators -->
<div class="cards-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Complaints</h3>
            <div class="stat-number"><?= number_format($stats['total'] ?? 0) ?></div>
        </div>
        <div class="stat-icon icon-primary">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Action Required (Pending)</h3>
            <div class="stat-number" style="color: var(--warning);"><?= number_format($stats['pending'] ?? 0) ?></div>
        </div>
        <div class="stat-icon icon-warning">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Under Investigation</h3>
            <div class="stat-number" style="color: var(--info);"><?= number_format($stats['in_progress'] ?? 0) ?></div>
        </div>
        <div class="stat-icon icon-info">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
            </svg>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Resolved (<?= $resolutionRate ?>%)</h3>
            <div class="stat-number" style="color: var(--success);"><?= number_format($stats['resolved'] ?? 0) ?></div>
        </div>
        <div class="stat-icon icon-success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
    </div>
</div>

<!-- Priority Alert Queue -->
<?php if (!empty($urgentComplaints)): ?>
    <div class="card" style="border-left: 4px solid var(--danger);">
        <div class="card-header" style="background: #fff5f5;">
            <div class="card-title" style="color: #991b1b;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                High & Urgent Priority Queue (Awaiting Resolution)
            </div>
            <span class="status-badge badge-rejected"><?= count($urgentComplaints) ?> Critical Tickets</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Subject</th>
                            <th>Complainant</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($urgentComplaints as $u): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($u['tracking_code']) ?></strong></td>
                                <td>
                                    <div style="font-weight: 600; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($u['title']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($u['user_name']) ?></td>
                                <td><?= getPriorityBadge($u['priority']) ?></td>
                                <td><?= getStatusBadge($u['status']) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>admin/complaint_details.php?id=<?= $u['id'] ?>" class="btn btn-primary btn-sm">
                                        Update Status &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Recent Submissions Overview -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
            Recent Complaint Influx
        </div>
        <a href="<?= BASE_URL ?>admin/complaints.php" class="btn btn-secondary btn-sm">
            View All Complaints &rarr;
        </a>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tracking ID</th>
                        <th>Subject</th>
                        <th>Citizen</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Lodged</th>
                        <th>Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentComplaints as $c): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--primary);"><?= htmlspecialchars($c['tracking_code']) ?></strong>
                            </td>
                            <td>
                                <div style="font-weight: 600; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($c['title']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($c['user_name']) ?></td>
                            <td><?= htmlspecialchars($c['category_name'] ?? 'General') ?></td>
                            <td><?= getPriorityBadge($c['priority']) ?></td>
                            <td><?= getStatusBadge($c['status']) ?></td>
                            <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>admin/complaint_details.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">
                                    Review
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
