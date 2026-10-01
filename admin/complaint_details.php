<?php
$pageTitle = 'Review & Update Grievance';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$admin = currentUser();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('danger', 'Complaint ID missing.');
    header('Location: ' . BASE_URL . 'admin/complaints.php');
    exit;
}

$db = getDB();

// Handle Status Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_status') {
    $newStatus = $_POST['status'] ?? '';
    $remarks = trim($_POST['admin_remarks'] ?? '');
    $newCategory = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $newPriority = $_POST['priority'] ?? null;

    $validStatuses = ['Pending', 'In Progress', 'Resolved', 'Rejected'];
    if (!in_array($newStatus, $validStatuses)) {
        setFlash('danger', 'Invalid status selected.');
    } else {
        try {
            // Get current status for log
            $cur = $db->prepare("SELECT status FROM complaints WHERE id = ?");
            $cur->execute([$id]);
            $oldStatus = $cur->fetchColumn();

            // Update complaint
            $now = date('Y-m-d H:i:s');
            $upStmt = $db->prepare("UPDATE complaints SET 
                status = ?, 
                admin_remarks = ?, 
                category_id = COALESCE(?, category_id),
                priority = COALESCE(?, priority),
                updated_at = ? 
                WHERE id = ?");
            $upStmt->execute([$newStatus, $remarks, $newCategory, $newPriority, $now, $id]);

            // Insert into logs
            $logStmt = $db->prepare("INSERT INTO complaint_logs (complaint_id, action_by, old_status, new_status, remark) VALUES (?, ?, ?, ?, ?)");
            $logStmt->execute([$id, $admin['id'], $oldStatus, $newStatus, $remarks ?: "Status updated from {$oldStatus} to {$newStatus}"]);

            setFlash('success', "Complaint status successfully updated to '{$newStatus}'.");
            header('Location: ' . BASE_URL . 'admin/complaint_details.php?id=' . $id);
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Error updating complaint: ' . $e->getMessage());
        }
    }
}

// Fetch complaint with category and complainant information
$stmt = $db->prepare("SELECT c.*, cat.name as category_name, u.name as user_name, u.email as user_email, u.phone as user_phone, u.address as user_address 
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.id = ? LIMIT 1");
$stmt->execute([$id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint record not found.');
    header('Location: ' . BASE_URL . 'admin/complaints.php');
    exit;
}

// Fetch categories for reassignment dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Fetch audit logs
$logStmt = $db->prepare("SELECT l.*, u.name as actor_name, u.role as actor_role 
    FROM complaint_logs l
    LEFT JOIN users u ON l.action_by = u.id
    WHERE l.complaint_id = ?
    ORDER BY l.created_at ASC");
$logStmt->execute([$id]);
$logs = $logStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 1040px; margin: 1rem auto 3rem;">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <a href="<?= BASE_URL ?>admin/complaints.php" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.35rem;">
            &larr; Back to Complaints Roster
        </a>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= BASE_URL ?>track.php?tracking_code=<?= urlencode($complaint['tracking_code']) ?>" target="_blank" class="btn btn-secondary btn-sm">
                View Public Tracking View
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.75rem; align-items: start;">
        
        <!-- Left Main Column: Complaint & Update Form -->
        <div>
            <!-- Complaint Overview Card -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary); letter-spacing: 0.05em; text-transform: uppercase;">
                            Tracking ID: <?= htmlspecialchars($complaint['tracking_code']) ?>
                        </span>
                        <h2 style="font-size: 1.35rem; font-weight: 800; margin-top: 0.25rem;">
                            <?= htmlspecialchars($complaint['title']) ?>
                        </h2>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <?= getPriorityBadge($complaint['priority']) ?>
                        <?= getStatusBadge($complaint['status']) ?>
                    </div>
                </div>

                <div class="card-body">
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">Description of Issue</h4>
                        <div style="line-height: 1.7; background: #f8fafc; border: 1px solid var(--border-subtle); padding: 1.25rem; border-radius: var(--radius-md);">
                            <?= nl2br(htmlspecialchars($complaint['description'])) ?>
                        </div>
                    </div>

                    <!-- Uploaded Evidence / Attachment -->
                    <?php if (!empty($complaint['attachment_path'])): ?>
                        <div style="margin-bottom: 1.5rem;">
                            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">Complainant Supporting Document</h4>
                            <div style="display: inline-flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: #f1f5f9; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                </svg>
                                <div>
                                    <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($complaint['attachment_name'] ?? 'View Attachment') ?></div>
                                </div>
                                <a href="<?= BASE_URL . htmlspecialchars($complaint['attachment_path']) ?>" target="_blank" class="btn btn-secondary btn-sm" download>
                                    Download File
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Current Remarks if any -->
                    <?php if (!empty($complaint['admin_remarks'])): ?>
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem;">
                            <strong style="color: #1e40af; font-size: 0.85rem; text-transform: uppercase;">Current Official Notes:</strong>
                            <p style="margin-top: 0.25rem; color: #1e3a8a; font-size: 0.95rem;"><?= nl2br(htmlspecialchars($complaint['admin_remarks'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Administrative Action / Status Update Card -->
            <div class="card" style="border: 2px solid #cbd5e1;">
                <div class="card-header" style="background: #f8fafc;">
                    <div class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Update Complaint Status & Directives
                    </div>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>admin/complaint_details.php?id=<?= $complaint['id'] ?>" method="POST">
                        <input type="hidden" name="action_type" value="update_status">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="status" class="form-label">Set Resolution Status <span class="required">*</span></label>
                                <select name="status" id="status" class="form-control" required style="font-weight: 700;">
                                    <option value="Pending" <?= ($complaint['status'] === 'Pending') ? 'selected' : '' ?>>Pending (Under Review)</option>
                                    <option value="In Progress" <?= ($complaint['status'] === 'In Progress') ? 'selected' : '' ?>>In Progress (Work Assigned)</option>
                                    <option value="Resolved" <?= ($complaint['status'] === 'Resolved') ? 'selected' : '' ?>>Resolved (Issue Fixed)</option>
                                    <option value="Rejected" <?= ($complaint['status'] === 'Rejected') ? 'selected' : '' ?>>Rejected (Invalid / Duplicate)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="priority" class="form-label">Re-evaluate Priority</label>
                                <select name="priority" id="priority" class="form-control">
                                    <option value="Low" <?= ($complaint['priority'] === 'Low') ? 'selected' : '' ?>>Low</option>
                                    <option value="Medium" <?= ($complaint['priority'] === 'Medium') ? 'selected' : '' ?>>Medium</option>
                                    <option value="High" <?= ($complaint['priority'] === 'High') ? 'selected' : '' ?>>High</option>
                                    <option value="Urgent" <?= ($complaint['priority'] === 'Urgent') ? 'selected' : '' ?>>Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="category_id" class="form-label">Re-assign Department</label>
                            <select name="category_id" id="category_id" class="form-control">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($complaint['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="admin_remarks" class="form-label">Official Department Remark / Action Report <span class="required">*</span></label>
                            <textarea name="admin_remarks" id="admin_remarks" class="form-control" placeholder="Specify instructions, inspection findings, contractor assignment, or resolution notes visible to the citizen..." style="min-height: 110px;" required><?= htmlspecialchars($complaint['admin_remarks'] ?? '') ?></textarea>
                            <div class="form-hint">This remark will be recorded in the audit history and displayed on the citizen's tracker.</div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Save Status & Publish Official Remarks
                        </button>
                    </form>
                </div>
            </div>

            <!-- Complete Audit Trail -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Complete Action History (Audit Log)
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($logs)): ?>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">No historical records found.</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($logs as $log): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-header">
                                        <span class="timeline-actor"><?= htmlspecialchars($log['actor_name']) ?> (<?= ucfirst($log['actor_role']) ?>)</span>
                                        <?= getStatusBadge($log['new_status']) ?>
                                        <span class="timeline-time"><?= formatDatetime($log['created_at']) ?></span>
                                    </div>
                                    <?php if (!empty($log['remark'])): ?>
                                        <div class="timeline-body">
                                            <?= nl2br(htmlspecialchars($log['remark'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Sidebar: Complainant Profile & Ticket Meta -->
        <div>
            <!-- Complainant Profile Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title" style="font-size: 1rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Citizen Profile
                    </div>
                </div>
                <div class="card-body" style="font-size: 0.9rem;">
                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Full Name</div>
                        <div style="font-weight: 700; color: var(--text-main); font-size: 1rem;"><?= htmlspecialchars($complaint['user_name']) ?></div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Email Contact</div>
                        <div><a href="mailto:<?= htmlspecialchars($complaint['user_email']) ?>"><?= htmlspecialchars($complaint['user_email']) ?></a></div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Phone Number</div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($complaint['user_phone'] ?? 'Not provided') ?></div>
                    </div>

                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Address / Ward</div>
                        <div><?= nl2br(htmlspecialchars($complaint['user_address'] ?? 'Not provided')) ?></div>
                    </div>
                </div>
            </div>

            <!-- Complaint Meta Timestamps Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title" style="font-size: 1rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                        </svg>
                        Ticket Info
                    </div>
                </div>
                <div class="card-body" style="font-size: 0.875rem;">
                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Current Department:</span>
                        <strong><?= htmlspecialchars($complaint['category_name'] ?? 'General') ?></strong>
                    </div>
                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Registered On:</span>
                        <strong><?= date('M d, Y h:i A', strtotime($complaint['created_at'])) ?></strong>
                    </div>
                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Last Updated:</span>
                        <strong><?= date('M d, Y h:i A', strtotime($complaint['updated_at'])) ?></strong>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
