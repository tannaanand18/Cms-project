<?php
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="container nav-container">
        <a href="<?= BASE_URL ?>" class="brand">
            <div class="brand-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <span>Resolve<span class="highlight">CMS</span></span>
        </a>

        <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle navigation">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <ul class="nav-links" id="navLinks">
            <li><a href="<?= BASE_URL ?>" class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>">Home</a></li>
            <li><a href="<?= BASE_URL ?>track.php" class="nav-link <?= ($currentPage === 'track.php') ? 'active' : '' ?>">Track Status</a></li>

            <?php if (!$user): ?>
                <li><a href="<?= BASE_URL ?>login.php" class="nav-link <?= ($currentPage === 'login.php') ? 'active' : '' ?>">Sign In</a></li>
                <li><a href="<?= BASE_URL ?>register.php" class="btn btn-primary btn-sm">Get Started</a></li>
            <?php elseif ($user['role'] === 'admin'): ?>
                <li><a href="<?= BASE_URL ?>admin/dashboard.php" class="nav-link <?= ($currentPage === 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'admin') !== false) ? 'active' : '' ?>">Admin Overview</a></li>
                <li><a href="<?= BASE_URL ?>admin/complaints.php" class="nav-link <?= ($currentPage === 'complaints.php') ? 'active' : '' ?>">All Complaints</a></li>
                <li><a href="<?= BASE_URL ?>admin/categories.php" class="nav-link <?= ($currentPage === 'categories.php') ? 'active' : '' ?>">Categories</a></li>
                <li><a href="<?= BASE_URL ?>admin/users.php" class="nav-link <?= ($currentPage === 'users.php') ? 'active' : '' ?>">Users</a></li>
                <li class="nav-user">
                    <div class="avatar-badge" style="background: linear-gradient(135deg, #f59e0b, #ef4444);">
                        A
                    </div>
                    <div class="user-info-text">
                        <div class="user-info-name"><?= htmlspecialchars($user['name']) ?></div>
                        <div class="user-info-role">Admin</div>
                    </div>
                    <a href="<?= BASE_URL ?>logout.php" class="btn btn-secondary btn-sm" style="margin-left: 0.5rem;" title="Logout">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </a>
                </li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>user/dashboard.php" class="nav-link <?= ($currentPage === 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'user') !== false) ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>user/my_complaints.php" class="nav-link <?= ($currentPage === 'my_complaints.php') ? 'active' : '' ?>">My Complaints</a></li>
                <li><a href="<?= BASE_URL ?>user/new_complaint.php" class="btn btn-primary btn-sm">+ New Complaint</a></li>
                <li class="nav-user">
                    <div class="avatar-badge">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div class="user-info-text">
                        <div class="user-info-name"><?= htmlspecialchars($user['name']) ?></div>
                        <div class="user-info-role">Citizen / User</div>
                    </div>
                    <a href="<?= BASE_URL ?>logout.php" class="btn btn-secondary btn-sm" style="margin-left: 0.5rem;" title="Logout">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
