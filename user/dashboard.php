<?php
$pageTitle = 'Citizen Dashboard';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$user = currentUser();
$db = getDB();

// Fetch metrics for this user
$statsStmt = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved
    FROM complaints WHERE user_id = ?");
$statsStmt->execute([$user['id']]);
$stats = $statsStmt->fetch() ?: ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0];

// Fetch recent 5 complaints
$recentStmt = $db->prepare("SELECT c.*, cat.name as category_name 
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
    LIMIT 5");
$recentStmt->execute([$user['id']]);
$recentComplaints = $recentStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-main);">Welcome, <?= htmlspecialchars($user['name']) ?></h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Manage your submitted grievances and track ongoing redressal actions.</p>
    </div>
    <a href="<?= BASE_URL ?>user/new_complaint.php" class="btn btn-primary btn-lg">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Submit New Complaint
    </a>
</div>

<!-- Stats Counter Grid -->
<div class="cards-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Lodged</h3>
            <div class="stat-number"><?= (int)$stats['total'] ?></div>
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
            <h3>Pending Review</h3>
            <div class="stat-number" style="color: var(--warning);"><?= (int)$stats['pending'] ?></div>
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
            <h3>In Progress</h3>
            <div class="stat-number" style="color: var(--info);"><?= (int)$stats['in_progress'] ?></div>
        </div>
        <div class="stat-icon icon-info">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
            </svg>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Resolved</h3>
            <div class="stat-number" style="color: var(--success);"><?= (int)$stats['resolved'] ?></div>
        </div>
        <div class="stat-icon icon-success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
    </div>
</div>

<!-- Recent Complaints Section -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            Recent Complaints
        </div>
        <a href="<?= BASE_URL ?>user/my_complaints.php" class="btn btn-secondary btn-sm">
            View All Complaints &rarr;
        </a>
    </div>

    <div class="card-body" style="padding: 0;">
        <?php if (empty($recentComplaints)): ?>
            <div class="empty-state">
                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <h4>No complaints filed yet</h4>
                <p>Have an issue to report? Click the button below to register your first grievance.</p>
                <a href="<?= BASE_URL ?>user/new_complaint.php" class="btn btn-primary">File a Complaint</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tracking ID</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date Lodged</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentComplaints as $c): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--primary);"><?= htmlspecialchars($c['tracking_code']) ?></strong>
                                </td>
                                <td>
                                    <div style="font-weight: 600; max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($c['title']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($c['category_name'] ?? 'General') ?></td>
                                <td><?= getPriorityBadge($c['priority']) ?></td>
                                <td><?= getStatusBadge($c['status']) ?></td>
                                <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>user/view_complaint.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
