<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/db.php';
}
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = $pageTitle ?? 'Online Complaint Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Submit, track, and resolve citizen and customer complaints seamlessly in real-time.">
    <title><?= htmlspecialchars($pageTitle) ?> | CMS Portal</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    
    <!-- SVG Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%234f46e5'><path d='M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'/></svg>">
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="main-content">
    <div class="container">
        <?php renderFlash(); ?>
