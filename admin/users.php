<?php
$pageTitle = 'Registered Users & Staff';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$search = trim($_GET['q'] ?? '');
$roleFilter = trim($_GET['role'] ?? 'ALL');

$query = "SELECT u.*, COUNT(c.id) as complaint_count 
          FROM users u
          LEFT JOIN complaints c ON u.id = c.user_id
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like];
}

if ($roleFilter !== 'ALL' && !empty($roleFilter)) {
    $query .= " AND u.role = ?";
    $params[] = $roleFilter;
}

$query .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-main);">Registered Accounts</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Directory of citizens, municipal complainants, and administrative staff.</p>
    </div>
    <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">
        Total: <span style="color: var(--primary); font-size: 1.1rem;"><?= count($users) ?></span> users
    </div>
</div>

<!-- Search & Role Filter -->
<form action="<?= BASE_URL ?>admin/users.php" method="GET" class="filter-toolbar">
    <div class="search-input-group">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" name="q" placeholder="Search by name, email, or phone number..." value="<?= htmlspecialchars($search) ?>">
    </div>

    <div class="filter-selects">
        <select name="role" onchange="this.form.submit()">
            <option value="ALL" <?= $roleFilter === 'ALL' ? 'selected' : '' ?>>All Roles</option>
            <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>Citizens Only</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admins Only</option>
        </select>

        <?php if (!empty($search) || $roleFilter !== 'ALL'): ?>
            <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-secondary btn-sm" style="height: 42px;">Reset</a>
        <?php endif; ?>
    </div>
</form>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Email Contact</th>
                        <th>Phone</th>
                        <th>Address / Area</th>
                        <th>Role</th>
                        <th>Lodged Complaints</th>
                        <th>Joined On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="avatar-badge" style="width: 32px; height: 32px; font-size: 0.8rem; <?= ($u['role'] === 'admin') ? 'background: linear-gradient(135deg, #f59e0b, #ef4444);' : '' ?>">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <strong><?= htmlspecialchars($u['name']) ?></strong>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['phone'] ?: 'N/A') ?></td>
                            <td>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($u['address'] ?: 'Not provided') ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= ($u['role'] === 'admin') ? 'badge-progress' : 'badge-neutral' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= (int)$u['complaint_count'] ?></strong> tickets
                            </td>
                            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
