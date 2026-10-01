<?php
/**
 * Vercel Serverless Single-Function Router
 * Dispatches all requests to the appropriate PHP script to stay within Vercel's 12-function Hobby limit
 */

// Fix CWD to project root
chdir(__DIR__ . '/..');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = trim($uri, '/');

// Default to index.php if root
if (empty($uri) || $uri === 'index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

$target = __DIR__ . '/../' . $uri;

// If URI doesn't end in .php, check if .php exists
if (!file_exists($target) && file_exists($target . '.php')) {
    $target .= '.php';
    $uri .= '.php';
}

if (file_exists($target) && is_file($target)) {
    $ext = pathinfo($target, PATHINFO_EXTENSION);
    
    if ($ext === 'php') {
        $_SERVER['SCRIPT_NAME'] = '/' . $uri;
        $_SERVER['PHP_SELF'] = '/' . $uri;
        require $target;
        exit;
    } else {
        // Serve static asset if requested through fallback
        $mimes = [
            'css' => 'text/css',
            'js'  => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'pdf' => 'application/pdf',
        ];
        $contentType = $mimes[$ext] ?? 'application/octet-stream';
        header("Content-Type: " . $contentType);
        readfile($target);
        exit;
    }
}

// 404 Handler
http_response_code(404);
echo '<div style="font-family:sans-serif;max-width:500px;margin:50px auto;text-align:center;">
    <h2>404 - Page Not Found</h2>
    <p>The requested page <code>/' . htmlspecialchars($uri) . '</code> does not exist.</p>
    <a href="/">Go to Home</a>
</div>';
