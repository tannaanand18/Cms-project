<?php
$pageTitle = 'Department & Category Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $catName = trim($_POST['name'] ?? '');
    $catDesc = trim($_POST['description'] ?? '');

    if (empty($catName)) {
        setFlash('danger', 'Category name cannot be empty.');
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$catName, $catDesc]);
            setFlash('success', "Category '{$catName}' created successfully.");
            header('Location: ' . BASE_URL . 'admin/categories.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Failed to add category: ' . $e->getMessage());
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    try {
        $delStmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $delStmt->execute([$delId]);
        setFlash('success', 'Category deleted successfully. Associated complaints re-assigned to General.');
        header('Location: ' . BASE_URL . 'admin/categories.php');
        exit;
    } catch (Exception $e) {
        setFlash('danger', 'Unable to delete category: ' . $e->getMessage());
    }
}

// Fetch categories with complaint counts
$categories = $db->query("SELECT cat.*, COUNT(c.id) as complaint_count 
    FROM categories cat
    LEFT JOIN complaints c ON cat.id = c.category_id
    GROUP BY cat.id
    ORDER BY cat.name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-main);">Complaint Categories</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Configure municipal departments and civic issue categories for ticket routing.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.75rem; align-items: start;">
    
    <!-- Add New Category Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="font-size: 1.05rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Category
            </div>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/categories.php" method="POST">
                <input type="hidden" name="action" value="add_category">

                <div class="form-group">
                    <label for="name" class="form-label">Category Name <span class="required">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Street Lighting & Electrical" required>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Brief Description</label>
                    <textarea name="description" id="description" class="form-control" placeholder="Scope of issues covered under this department..." style="min-height: 90px;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Save Category
                </button>
            </form>
        </div>
    </div>

    <!-- Existing Categories Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                Active Departments & Categories (<?= count($categories) ?>)
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th>Complaints</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($cat['name']) ?></strong>
                                </td>
                                <td>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">
                                        <?= htmlspecialchars($cat['description'] ?: 'None') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge badge-neutral">
                                        <?= (int)$cat['complaint_count'] ?> tickets
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>admin/categories.php?delete=<?= $cat['id'] ?>" class="btn btn-secondary btn-sm" style="color: var(--danger); border-color: #fecaca;" onclick="return confirm('Are you sure you want to remove this category?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
