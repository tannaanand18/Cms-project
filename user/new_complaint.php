<?php
$pageTitle = 'Submit New Complaint';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$user = currentUser();
$db = getDB();

// Fetch categories
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$errors = [];
$title = '';
$categoryId = '';
$priority = 'Medium';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $priority = $_POST['priority'] ?? 'Medium';
    $description = trim($_POST['description'] ?? '');

    if (empty($title)) {
        $errors[] = 'Please enter a clear title / subject for your complaint.';
    }
    if (empty($description)) {
        $errors[] = 'Please provide detailed information describing the grievance.';
    }

    // Handle File Attachment Upload
    $attachmentPath = null;
    $attachmentName = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        $origName = basename($file['name']);
        $fileSize = $file['size'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
        $maxSizeBytes = 10 * 1024 * 1024; // 10 MB

        if (!in_array($ext, $allowedExts)) {
            $errors[] = 'Invalid file type. Allowed formats: JPG, PNG, PDF, DOC, DOCX, TXT.';
        } elseif ($fileSize > $maxSizeBytes) {
            $errors[] = 'Attachment size exceeds maximum limit of 10MB.';
        } else {
            $isVercel = !empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL']) || getenv('VERCEL') !== false;
            $uploadsDir = $isVercel ? '/tmp/uploads/' : __DIR__ . '/../uploads/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }

            $uniqueFilename = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetPath = $uploadsDir . $uniqueFilename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $attachmentPath = 'uploads/' . $uniqueFilename;
                $attachmentName = $origName;
            } else {
                $errors[] = 'Failed to upload attachment. Please check directory permissions.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $trackingCode = generateTrackingCode();

            $insertStmt = $db->prepare("INSERT INTO complaints 
                (tracking_code, user_id, category_id, title, description, priority, status, attachment_path, attachment_name) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?)");
            $insertStmt->execute([
                $trackingCode,
                $user['id'],
                $categoryId,
                $title,
                $description,
                $priority,
                $attachmentPath,
                $attachmentName
            ]);

            $complaintId = $db->lastInsertId();

            // Insert initial log
            $logStmt = $db->prepare("INSERT INTO complaint_logs (complaint_id, action_by, new_status, remark) VALUES (?, ?, 'Pending', 'Complaint submitted by citizen.')");
            $logStmt->execute([$complaintId, $user['id']]);

            setFlash('success', "Your complaint has been lodged successfully! Tracking Code: {$trackingCode}");
            header('Location: ' . BASE_URL . 'user/view_complaint.php?id=' . $complaintId);
            exit;
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 840px; margin: 1rem auto 3rem;">
    <div style="margin-bottom: 2rem;">
        <a href="<?= BASE_URL ?>user/dashboard.php" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.35rem; margin-bottom: 0.5rem;">
            &larr; Back to Dashboard
        </a>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-main);">File a New Complaint</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Provide complete and accurate details to expedite the investigation and resolution process.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="display: block;">
            <div style="font-weight: 700; margin-bottom: 0.25rem;">Please review the following errors:</div>
            <ul style="margin-left: 1.25rem; font-size: 0.875rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="<?= BASE_URL ?>user/new_complaint.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="title" class="form-label">Subject / Title <span class="required">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="Brief summary of the issue (e.g. Broken street lamp on Elm Street)" value="<?= htmlspecialchars($title) ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id" class="form-label">Department / Category <span class="required">*</span></label>
                        <select name="category_id" id="category_id" class="form-control" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($categoryId == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority" class="form-label">Urgency / Priority Level <span class="required">*</span></label>
                        <select name="priority" id="priority" class="form-control" required>
                            <option value="Low" <?= ($priority === 'Low') ? 'selected' : '' ?>>Low - Minor inconvenience</option>
                            <option value="Medium" <?= ($priority === 'Medium') ? 'selected' : '' ?>>Medium - Routine matter</option>
                            <option value="High" <?= ($priority === 'High') ? 'selected' : '' ?>>High - Significant impact</option>
                            <option value="Urgent" <?= ($priority === 'Urgent') ? 'selected' : '' ?>>Urgent - Safety hazard / Emergency</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Detailed Description <span class="required">*</span></label>
                    <textarea name="description" id="description" class="form-control" placeholder="Please describe what happened, the specific location, date/time noticed, and any other relevant background..." style="min-height: 150px;" required><?= htmlspecialchars($description) ?></textarea>
                    <div class="form-hint">Be as specific as possible with landmarks, street numbers, or reference IDs.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Upload Evidence / Supporting Attachment (Optional)</label>
                    <div class="file-dropzone" id="dropzone">
                        <input type="file" name="attachment" id="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt">
                        <svg class="dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <div class="dropzone-text">Click or drag & drop files here to upload</div>
                        <div class="dropzone-subtext">Supports JPG, PNG, PDF, DOC, DOCX up to 10MB</div>
                        
                        <div class="file-preview" id="filePreview">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span id="fileName">No file selected</span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                    <a href="<?= BASE_URL ?>user/dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        Submit Grievance
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
