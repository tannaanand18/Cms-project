<?php
$pageTitle = 'Track Complaint Status';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$trackingCode = trim($_GET['tracking_code'] ?? '');
$complaint = null;
$logs = [];
$errorMsg = '';

if (!empty($trackingCode)) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT c.*, cat.name as category_name, u.name as user_name 
            FROM complaints c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.tracking_code = ? LIMIT 1");
        $stmt->execute([$trackingCode]);
        $complaint = $stmt->fetch();

        if ($complaint) {
            // Fetch logs for this complaint
            $logStmt = $db->prepare("SELECT l.*, u.name as actor_name, u.role as actor_role 
                FROM complaint_logs l
                LEFT JOIN users u ON l.action_by = u.id
                WHERE l.complaint_id = ?
                ORDER BY l.created_at ASC");
            $logStmt->execute([$complaint['id']]);
            $logs = $logStmt->fetchAll();
        } else {
            $errorMsg = "No complaint record found matching tracking code: " . htmlspecialchars($trackingCode);
        }
    } catch (Exception $e) {
        $errorMsg = "Unable to fetch tracking record: " . $e->getMessage();
    }
}
?>

<div style="max-width: 1200px; margin: 1rem auto 3.5rem;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h1 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 0.5rem;">Track Complaint Status</h1>
        <p style="color: var(--text-muted);">Enter your assigned tracking code to see real-time updates and department remarks.</p>
    </div>

    <!-- Search Form -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body">
            <form action="<?= BASE_URL ?>track.php" method="GET" class="track-form">
                <input type="text" name="tracking_code" placeholder="e.g. CMP-2026-7841" value="<?= htmlspecialchars($trackingCode) ?>" required style="text-transform: uppercase;">
                <button type="submit" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    Check Status
                </button>
            </form>
        </div>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($complaint): ?>
        <?php
        $status = $complaint['status'];
        $step1 = 'completed';
        $step2 = ($status === 'In Progress' || $status === 'Resolved') ? 'completed' : (($status === 'Pending') ? 'active' : '');
        $step3 = ($status === 'In Progress') ? 'active' : (($status === 'Resolved') ? 'completed' : '');
        $step4 = ($status === 'Resolved') ? 'completed' : (($status === 'Rejected') ? 'rejected' : '');
        ?>

        <!-- Stepper Component -->
        <div class="card" style="padding: 1rem 1.5rem;">
            <div class="tracking-stepper">
                <div class="step-item <?= $step1 ?>">
                    <div class="step-circle">1</div>
                    <div class="step-title">Submitted</div>
                    <small style="color: var(--text-light);"><?= date('M d, Y', strtotime($complaint['created_at'])) ?></small>
                </div>
                <div class="step-item <?= $step2 ?>">
                    <div class="step-circle">2</div>
                    <div class="step-title">Under Review</div>
                    <small style="color: var(--text-light);"><?= ($status !== 'Pending') ? 'Verified' : 'In Queue' ?></small>
                </div>
                <div class="step-item <?= $step3 ?>">
                    <div class="step-circle">3</div>
                    <div class="step-title">In Progress</div>
                    <small style="color: var(--text-light);"><?= ($status === 'In Progress') ? 'Team Assigned' : ($status === 'Resolved' ? 'Completed' : 'Pending') ?></small>
                </div>
                <div class="step-item <?= $step4 ?>">
                    <div class="step-circle"><?= ($status === 'Rejected') ? '✕' : '4' ?></div>
                    <div class="step-title"><?= ($status === 'Rejected') ? 'Rejected' : 'Resolved' ?></div>
                    <small style="color: var(--text-light);"><?= ($status === 'Resolved' || $status === 'Rejected') ? date('M d, Y', strtotime($complaint['updated_at'])) : 'Awaiting' ?></small>
                </div>
            </div>
        </div>

        <!-- Complaint Details Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary); letter-spacing: 0.05em; text-transform: uppercase;">
                        Complaint Record #<?= htmlspecialchars($complaint['tracking_code']) ?>
                    </span>
                    <h2 style="font-size: 1.35rem; font-weight: 700; margin-top: 0.25rem;">
                        <?= htmlspecialchars($complaint['title']) ?>
                    </h2>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <?= getPriorityBadge($complaint['priority']) ?>
                    <?= getStatusBadge($complaint['status']) ?>
                </div>
            </div>

            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; background: #f8fafc; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Category</div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($complaint['category_name'] ?? 'General') ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Complainant</div>
                        <div style="font-weight: 600;">
                            <?php
                            if (isLoggedIn()) {
                                echo htmlspecialchars($complaint['user_name']);
                            } else {
                                // Mask for privacy
                                $n = $complaint['user_name'];
                                echo htmlspecialchars(substr($n, 0, 1) . '*** ' . substr($n, -1));
                            }
                            ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Lodged On</div>
                        <div style="font-weight: 600;"><?= formatDatetime($complaint['created_at']) ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Last Updated</div>
                        <div style="font-weight: 600;"><?= formatDatetime($complaint['updated_at']) ?></div>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">Complaint Description</h4>
                    <div style="line-height: 1.7; background: #fff; border: 1px solid var(--border-subtle); padding: 1rem; border-radius: var(--radius-md);">
                        <?= nl2br(htmlspecialchars($complaint['description'])) ?>
                    </div>
                </div>

                <!-- Attached Proof/Document -->
                <?php if (!empty($complaint['attachment_path'])): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">Attached Evidence / Supporting Document</h4>
                        <div style="display: inline-flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: #f1f5f9; border-radius: var(--radius-md);">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                            </svg>
                            <span style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($complaint['attachment_name'] ?? 'View File') ?></span>
                            <a href="<?= BASE_URL . htmlspecialchars($complaint['attachment_path']) ?>" target="_blank" class="btn btn-secondary btn-sm" download>
                                Download
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Official Administrative Remarks -->
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

                <!-- Progress / Audit History Timeline -->
                <?php if (!empty($logs)): ?>
                    <div style="margin-top: 2rem;">
                        <h4 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.75rem;">Status Audit History</h4>
                        <div class="timeline">
                            <?php foreach ($logs as $log): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-header">
                                        <span class="timeline-actor"><?= htmlspecialchars($log['actor_name']) ?></span>
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
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
