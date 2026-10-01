<?php
$pageTitle = 'Citizen Registration';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'user/dashboard.php');
    exit;
}

$errors = [];
$name = '';
$email = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Full name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Password and confirmation do not match.';
    }

    if (empty($errors)) {
        try {
            $db = getDB();
            
            // Check if email already registered
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $errors[] = 'An account with this email address already exists.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $db->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, 'user')");
                $insertStmt->execute([$name, $email, $passwordHash, $phone, $address]);

                $newUserId = $db->lastInsertId();

                // Auto login user
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'user';

                setFlash('success', 'Registration successful! Welcome to the Complaint Management System.');
                header('Location: ' . BASE_URL . 'user/dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper" style="max-width: 580px;">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-icon" style="margin: 0 auto 1rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <h2>Create an Account</h2>
            <p>Register as a citizen to file complaints and receive resolution notifications</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="display: block;">
                <div style="font-weight: 700; margin-bottom: 0.25rem;">Please address the following:</div>
                <ul style="margin-left: 1.25rem; font-size: 0.875rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>register.php" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="John Doe" value="<?= htmlspecialchars($name) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="john@example.com" value="<?= htmlspecialchars($email) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone" class="form-label">Contact Phone</label>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="e.g. 9876543210" value="<?= htmlspecialchars($phone) ?>">
                </div>

                <div class="form-group">
                    <label for="address" class="form-label">Residential Address / Ward</label>
                    <input type="text" name="address" id="address" class="form-control" placeholder="Street, Ward, Area" value="<?= htmlspecialchars($address) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">Create Password <span class="required">*</span></label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1rem;">
                Create Account & Proceed
            </button>
        </form>

        <div class="auth-footer">
            Already registered? <a href="<?= BASE_URL ?>login.php" style="font-weight: 600;">Sign In Here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
