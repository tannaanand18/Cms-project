<?php
$pageTitle = 'My Complaints';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$user = currentUser();
$db = getDB();

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'ALL');
$priorityFilter = trim($_GET['priority'] ?? 'ALL');

$query = "SELECT c.*, cat.name as category_name 
          FROM complaints c
          LEFT JOIN categories cat ON c.category_id = cat.id
          WHERE c.user_id = ?";
$params = [$user['id']];

if (!empty($search)) {
    $query .= " AND (c.title LIKE ? OR c.tracking_code LIKE ? OR c.description LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($statusFilter !== 'ALL' && !empty($statusFilter)) {
    $query .= " AND c.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter !== 'ALL' && !empty($priorityFilter)) {
    $query .= " AND c.priority = ?";
    $params[] = $priorityFilter;
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-main);">My Grievances</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Review status updates and official responses for all your lodged tickets.</p>
    </div>
    <a href="<?= BASE_URL ?>user/new_complaint.php" class="btn btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        New Complaint
    </a>
</div>

<!-- Filter & Search Toolbar -->
<form action="<?= BASE_URL ?>user/my_complaints.php" method="GET" class="filter-toolbar">
    <div class="search-input-group">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="liveTableSearch" name="q" placeholder="Instant search by title, ID or keyword..." value="<?= htmlspecialchars($search) ?>">
    </div>

    <div class="filter-selects">
        <select name="status" id="statusFilter" onchange="this.form.submit()">
            <option value="ALL" <?= $statusFilter === 'ALL' ? 'selected' : '' ?>>All Statuses</option>
            <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="In Progress" <?= $statusFilter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="Resolved" <?= $statusFilter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
            <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>

        <select name="priority" id="priorityFilter" onchange="this.form.submit()">
            <option value="ALL" <?= $priorityFilter === 'ALL' ? 'selected' : '' ?>>All Priorities</option>
            <option value="Low" <?= $priorityFilter === 'Low' ? 'selected' : '' ?>>Low</option>
            <option value="Medium" <?= $priorityFilter === 'Medium' ? 'selected' : '' ?>>Medium</option>
            <option value="High" <?= $priorityFilter === 'High' ? 'selected' : '' ?>>High</option>
            <option value="Urgent" <?= $priorityFilter === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
        </select>

        <?php if (!empty($search) || $statusFilter !== 'ALL' || $priorityFilter !== 'ALL'): ?>
            <a href="<?= BASE_URL ?>user/my_complaints.php" class="btn btn-secondary btn-sm" style="height: 42px;">Reset Filters</a>
        <?php endif; ?>
    </div>
</form>

<!-- Table Card -->
<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($complaints)): ?>
            <div class="empty-state">
                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <h4>No complaints found</h4>
                <p>No complaints match your current filter or search criteria.</p>
                <a href="<?= BASE_URL ?>user/new_complaint.php" class="btn btn-primary">Submit a New Complaint</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table" id="complaintsTable">
                    <thead>
                        <tr>
                            <th>Tracking ID</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Submitted On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $c): ?>
                            <tr data-status="<?= htmlspecialchars($c['status']) ?>" data-priority="<?= htmlspecialchars($c['priority']) ?>">
                                <td>
                                    <strong style="color: var(--primary);"><?= htmlspecialchars($c['tracking_code']) ?></strong>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-main); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($c['title']) ?>
                                    </div>
                                    <?php if (!empty($c['attachment_path'])): ?>
                                        <small style="color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                            </svg>
                                            Attachment included
                                        </small>
                                    <?php endif; ?>
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
