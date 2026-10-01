<?php
$pageTitle = 'Manage Complaints';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Fetch all categories for filter
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'ALL');
$priorityFilter = trim($_GET['priority'] ?? 'ALL');
$categoryFilter = trim($_GET['category'] ?? 'ALL');

$query = "SELECT c.*, cat.name as category_name, u.name as user_name, u.email as user_email 
          FROM complaints c
          LEFT JOIN categories cat ON c.category_id = cat.id
          LEFT JOIN users u ON c.user_id = u.id
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (c.title LIKE ? OR c.tracking_code LIKE ? OR c.description LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

if ($statusFilter !== 'ALL' && !empty($statusFilter)) {
    $query .= " AND c.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter !== 'ALL' && !empty($priorityFilter)) {
    $query .= " AND c.priority = ?";
    $params[] = $priorityFilter;
}

if ($categoryFilter !== 'ALL' && !empty($categoryFilter)) {
    $query .= " AND c.category_id = ?";
    $params[] = $categoryFilter;
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-main);">Grievance Management</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Search, inspect evidence, and update resolution statuses across all civic departments.</p>
    </div>
    <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">
        Total: <span style="color: var(--primary); font-size: 1.1rem;"><?= count($complaints) ?></span> records
    </div>
</div>

<!-- Search & Filtering Bar -->
<form action="<?= BASE_URL ?>admin/complaints.php" method="GET" class="filter-toolbar">
    <div class="search-input-group">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="liveTableSearch" name="q" placeholder="Search by citizen, title, keyword or tracking code..." value="<?= htmlspecialchars($search) ?>">
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

        <select name="category" onchange="this.form.submit()">
            <option value="ALL" <?= $categoryFilter === 'ALL' ? 'selected' : '' ?>>All Departments</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($categoryFilter == $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if (!empty($search) || $statusFilter !== 'ALL' || $priorityFilter !== 'ALL' || $categoryFilter !== 'ALL'): ?>
            <a href="<?= BASE_URL ?>admin/complaints.php" class="btn btn-secondary btn-sm" style="height: 42px;">Reset</a>
        <?php endif; ?>
    </div>
</form>

<!-- Master Complaints Table -->
<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($complaints)): ?>
            <div class="empty-state">
                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <h4>No complaints found</h4>
                <p>No complaints matched your search filter parameters.</p>
                <a href="<?= BASE_URL ?>admin/complaints.php" class="btn btn-secondary">Clear Filters</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table" id="complaintsTable">
                    <thead>
                        <tr>
                            <th>Tracking ID</th>
                            <th>Subject</th>
                            <th>Complainant</th>
                            <th>Department</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date Lodged</th>
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
                                    <div style="font-weight: 600; color: var(--text-main); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($c['title']) ?>
                                    </div>
                                    <?php if (!empty($c['attachment_path'])): ?>
                                        <small style="color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                            </svg>
                                            Attachment Available
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($c['user_name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($c['user_email']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($c['category_name'] ?? 'General') ?></td>
                                <td><?= getPriorityBadge($c['priority']) ?></td>
                                <td><?= getStatusBadge($c['status']) ?></td>
                                <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>admin/complaint_details.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">
                                        Manage &rarr;
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
