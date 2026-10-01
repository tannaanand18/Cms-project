<?php
$pageTitle = 'Complaint Details';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$user = currentUser();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    setFlash('danger', 'Complaint ID missing.');
    header('Location: ' . BASE_URL . 'user/my_complaints.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT c.*, cat.name as category_name 
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE c.id = ? AND c.user_id = ? LIMIT 1");
$stmt->execute([$id, $user['id']]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlash('danger', 'Complaint not found or unauthorized access.');
    header('Location: ' . BASE_URL . 'user/my_complaints.php');
    exit;
}

// Fetch logs
$logStmt = $db->prepare("SELECT l.*, u.name as actor_name, u.role as actor_role 
    FROM complaint_logs l
    LEFT JOIN users u ON l.action_by = u.id
    WHERE l.complaint_id = ?
    ORDER BY l.created_at ASC");
$logStmt->execute([$id]);
$logs = $logStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';

$status = $complaint['status'];
$step1 = 'completed';
$step2 = ($status === 'In Progress' || $status === 'Resolved') ? 'completed' : (($status === 'Pending') ? 'active' : '');
$step3 = ($status === 'In Progress') ? 'active' : (($status === 'Resolved') ? 'completed' : '');
$step4 = ($status === 'Resolved') ? 'completed' : (($status === 'Rejected') ? 'rejected' : '');
?>

<div style="max-width: 900px; margin: 1rem auto 3rem;">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <a href="<?= BASE_URL ?>user/my_complaints.php" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.35rem;">
            &larr; Back to My Complaints
        </a>
        <a href="<?= BASE_URL ?>track.php?tracking_code=<?= urlencode($complaint['tracking_code']) ?>" target="_blank" class="btn btn-secondary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            Public Tracker Link
        </a>
    </div>

    <!-- Stepper Component -->
    <div class="card" style="padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
        <div class="tracking-stepper">
            <div class="step-item <?= $step1 ?>">
                <div class="step-circle">1</div>
                <div class="step-title">Lodged</div>
                <small style="color: var(--text-light);"><?= date('M d, Y', strtotime($complaint['created_at'])) ?></small>
            </div>
            <div class="step-item <?= $step2 ?>">
                <div class="step-circle">2</div>
                <div class="step-title">In Review</div>
                <small style="color: var(--text-light);"><?= ($status !== 'Pending') ? 'Accepted' : 'Queued' ?></small>
            </div>
            <div class="step-item <?= $step3 ?>">
                <div class="step-circle">3</div>
                <div class="step-title">In Progress</div>
                <small style="color: var(--text-light);"><?= ($status === 'In Progress') ? 'Investigating' : ($status === 'Resolved' ? 'Done' : 'Awaiting') ?></small>
            </div>
            <div class="step-item <?= $step4 ?>">
                <div class="step-circle"><?= ($status === 'Rejected') ? '✕' : '4' ?></div>
                <div class="step-title"><?= ($status === 'Rejected') ? 'Rejected' : 'Resolved' ?></div>
                <small style="color: var(--text-light);"><?= ($status === 'Resolved' || $status === 'Rejected') ? date('M d, Y', strtotime($complaint['updated_at'])) : 'Pending' ?></small>
            </div>
        </div>
    </div>

    <!-- Detail Box -->
    <div class="card">
        <div class="card-header">
            <div>
                <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary); letter-spacing: 0.05em; text-transform: uppercase;">
                    Tracking Code: <?= htmlspecialchars($complaint['tracking_code']) ?>
                </span>
                <h2 style="font-size: 1.45rem; font-weight: 800; margin-top: 0.25rem;">
                    <?= htmlspecialchars($complaint['title']) ?>
                </h2>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <?= getPriorityBadge($complaint['priority']) ?>
                <?= getStatusBadge($complaint['status']) ?>
            </div>
        </div>

        <div class="card-body">
            <!-- Metadata summary bar -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; background: #f8fafc; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Department</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($complaint['category_name'] ?? 'General') ?></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Filed Date</div>
                    <div style="font-weight: 600;"><?= formatDatetime($complaint['created_at']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Last Status Update</div>
                    <div style="font-weight: 600;"><?= formatDatetime($complaint['updated_at']) ?></div>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">Complaint Information</h4>
                <div style="line-height: 1.7; background: #fff; border: 1px solid var(--border-subtle); padding: 1.25rem; border-radius: var(--radius-md);">
                    <?= nl2br(htmlspecialchars($complaint['description'])) ?>
                </div>
            </div>

            <!-- Evidence Attachment -->
            <?php if (!empty($complaint['attachment_path'])): ?>
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">Uploaded Attachment</h4>
                    <div style="display: inline-flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: #f1f5f9; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                        </svg>
                        <span style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($complaint['attachment_name'] ?? 'View Attachment') ?></span>
                        <a href="<?= BASE_URL . htmlspecialchars($complaint['attachment_path']) ?>" target="_blank" class="btn btn-secondary btn-sm" download>
                            Download / Open
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Authority Official Remarks -->
            <?php if (!empty($complaint['admin_remarks'])): ?>
                <div style="margin-bottom: 1.5rem; background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: var(--radius-md); padding: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: #1e40af; font-weight: 700; margin-bottom: 0.4rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        Official Authority Remarks
                    </div>
                    <div style="color: #1e3a8a; font-size: 0.95rem; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($complaint['admin_remarks'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Audit Trail -->
            <?php if (!empty($logs)): ?>
                <div style="margin-top: 2rem;">
                    <h4 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.75rem;">Timeline of Action</h4>
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
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
