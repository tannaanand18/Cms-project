<?php
$pageTitle = 'Sign In to Portal';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'user/dashboard.php');
    }
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email and password.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && (password_verify($password, $user['password']) || $user['password'] === md5($password) || $user['password'] === $password)) {
                // If password was stored in plain text or legacy, rehash it automatically
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateStmt->execute([$newHash, $user['id']]);
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                setFlash('success', 'Welcome back, ' . $user['name'] . '!');

                if ($user['role'] === 'admin') {
                    header('Location: ' . BASE_URL . 'admin/dashboard.php');
                } else {
                    header('Location: ' . BASE_URL . 'user/dashboard.php');
                }
                exit;
            } else {
                $error = 'Invalid email address or password.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred during sign in. Please verify database setup.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-icon" style="margin: 0 auto 1rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h2>Portal Sign In</h2>
            <p>Access your complaints, track progress & manage tickets</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Quick Demo Credentials Box -->
        <div class="credentials-box">
            <div style="font-weight: 700; margin-bottom: 0.35rem; color: var(--text-main);">Demo Accounts (Click to Fill):</div>
            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                <button type="button" class="btn btn-secondary btn-sm" style="flex:1;" onclick="fillDemo('user@cms.com', 'user123')">
                    👤 Citizen User
                </button>
                <button type="button" class="btn btn-secondary btn-sm" style="flex:1;" onclick="fillDemo('admin@cms.com', 'admin123')">
                    🛡️ Administrator
                </button>
            </div>
        </div>

        <form action="<?= BASE_URL ?>login.php" method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label for="password" class="form-label" style="margin-bottom: 0;">Password <span class="required">*</span></label>
                </div>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                Sign In to Portal
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account yet? <a href="<?= BASE_URL ?>register.php" style="font-weight: 600;">Create an Account</a>
        </div>
    </div>
</div>

<script>
function fillDemo(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
