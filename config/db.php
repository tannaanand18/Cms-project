<?php
/**
 * Database Configuration & Helper Utilities
 * Online Complaint Management System
 * Supports MySQL (Local XAMPP & Cloud MySQL on Vercel) with seamless SQLite fallback
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (Supports Cloud Environment Variables & Local XAMPP)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'complaint_db');
define('DB_PORT', getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306);

// App Base Path helper
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Find root path relative to current script
$projectRoot = rtrim(preg_replace('/(\/(admin|user|config|includes|assets|uploads))(\/.*)?$/', '', $scriptDir), '/');
define('BASE_URL', $protocol . $host . $projectRoot . '/');

/**
 * Returns active PDO database connection.
 * Tries MySQL first. If MySQL is not running/configured, falls back to embedded SQLite.
 */
function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Detect if running on Vercel or Cloud
    $isVercel = !empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL']) || getenv('VERCEL') !== false;

    // 1. Try MySQL Connection if configured or local
    $hasCustomMysql = (getenv('DB_HOST') !== false);
    if ($hasCustomMysql || !$isVercel) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 2,
            ];
            $tmpPdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $tmpPdo->exec("USE `" . DB_NAME . "`;");

            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (Throwable $mysqlEx) {
            if ($hasCustomMysql) {
                die("MySQL Connection Error: " . htmlspecialchars($mysqlEx->getMessage()));
            }
        }
    }

    // 2. Fallback to SQLite (Writable in /tmp on Vercel)
    try {
        $sourceDb = __DIR__ . '/complaint_db.sqlite';
        if ($isVercel) {
            $sqlitePath = '/tmp/complaint_db.sqlite';
            if (!file_exists($sqlitePath) && file_exists($sourceDb)) {
                copy($sourceDb, $sqlitePath);
            }
        } else {
            $sqlitePath = $sourceDb;
        }

        $isNew = !file_exists($sqlitePath);
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");

        if ($isNew || filesize($sqlitePath) < 100) {
            initSqliteSchema($pdo);
        }
        return $pdo;
    } catch (Throwable $sqliteEx) {
        die('<div style="font-family:sans-serif;max-width:600px;margin:50px auto;padding:25px;border-radius:12px;background:#fef2f2;border:1px solid #f87171;color:#991b1b;">
            <h2 style="margin-top:0;">Database Connection Notice</h2>
            <p>' . htmlspecialchars($sqliteEx->getMessage()) . '</p>
        </div>');
    }
}

/**
 * Initializes SQLite schema and seeds demo data if running in fallback mode
 */
function initSqliteSchema($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        phone TEXT,
        address TEXT,
        role TEXT DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS complaints (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tracking_code TEXT NOT NULL UNIQUE,
        user_id INTEGER NOT NULL,
        category_id INTEGER,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        priority TEXT DEFAULT 'Medium',
        status TEXT DEFAULT 'Pending',
        attachment_path TEXT,
        attachment_name TEXT,
        admin_remarks TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS complaint_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        complaint_id INTEGER NOT NULL,
        action_by INTEGER NOT NULL,
        old_status TEXT,
        new_status TEXT NOT NULL,
        remark TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
        FOREIGN KEY (action_by) REFERENCES users(id) ON DELETE CASCADE
    );");

    // Insert Default Categories
    $pdo->exec("INSERT INTO categories (name, description) VALUES
        ('Public Infrastructure', 'Issues related to roads, streetlights, bridges, and public works'),
        ('Sanitation & Waste Management', 'Garbage collection, sewage blockages, and public cleanliness'),
        ('Water & Electricity Supply', 'Water shortages, pipe leaks, power outages, and voltage issues'),
        ('Billing & Payments', 'Discrepancies in civic taxes, municipal utility bills, and payment errors'),
        ('Public Safety & Security', 'Traffic violations, vandalism, and public neighborhood hazards'),
        ('Other Services', 'General inquiries, feedback, and miscellaneous issues');");

    // Seed default admin and user
    $pdo->exec("INSERT INTO users (name, email, password, phone, address, role) VALUES
        ('System Administrator', 'admin@cms.com', '$2y$12$pexqbIRgn/3emxmhmFs1V.gHSCkQDSq1MO91yZNzL2A1UqaVF5ek.', '9876543210', 'Headquarters, Admin Block', 'admin'),
        ('John Citizen', 'user@cms.com', '$2y$12$DkJbdR7Ccto31aWSm.njeu/6V83SqyR7uoDYhllyC3dP2LpTJ2Fl.', '9123456780', '42 Maple Street, Sector 5', 'user');");

    // Sample complaints
    $pdo->exec("INSERT INTO complaints (tracking_code, user_id, category_id, title, description, priority, status, admin_remarks, created_at) VALUES
        ('CMP-2026-7841', 2, 1, 'Deep pothole on Main Avenue near Central Park', 'There is a dangerous deep pothole near the crossing of 4th street and Main Ave. It has caused traffic bottlenecks and nearly tripped two bikers.', 'High', 'In Progress', 'Dispatched municipal road repair team. Inspection completed on morning shift.', '2026-09-28 09:30:00'),
        ('CMP-2026-9210', 2, 3, 'Continuous low water pressure in Sector 5', 'For the past 4 days, pipeline water pressure has dropped significantly during peak morning hours (6am - 8am).', 'Medium', 'Pending', NULL, '2026-09-29 14:15:00'),
        ('CMP-2026-6432', 2, 2, 'Irregular garbage pickup in Residential Block B', 'Trash bins are overflowing since Sunday. Stray animals are scattering waste on the pedestrian sidewalk.', 'Urgent', 'Resolved', 'Sanitation truck deployed. Waste cleared and area disinfected. Service schedule restored.', '2026-09-25 11:00:00');");

    // Sample logs
    $pdo->exec("INSERT INTO complaint_logs (complaint_id, action_by, old_status, new_status, remark, created_at) VALUES
        (1, 2, NULL, 'Pending', 'Complaint submitted by user', '2026-09-28 09:30:00'),
        (1, 1, 'Pending', 'In Progress', 'Assigned road maintenance contractor team #4.', '2026-09-28 15:40:00'),
        (3, 2, NULL, 'Pending', 'Complaint submitted by user', '2026-09-25 11:00:00'),
        (3, 1, 'Pending', 'In Progress', 'Dispatched emergency sanitation vehicle.', '2026-09-25 13:20:00'),
        (3, 1, 'In Progress', 'Resolved', 'Waste collected, sanitized, and completed ticket.', '2026-09-26 10:00:00');");
}

/**
 * Clean & sanitize user string inputs
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate unique tracking code e.g. CMP-2026-48291
 */
function generateTrackingCode() {
    $year = date('Y');
    $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 5));
    return "CMP-{$year}-{$random}";
}

/**
 * Flash message helpers
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render Flash Alert HTML
 */
function renderFlash() {
    $flash = getFlash();
    if ($flash) {
        $icons = [
            'success' => '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
            'danger'  => '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
            'warning' => '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            'info'    => '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
        ];
        $type = $flash['type'];
        $icon = $icons[$type] ?? $icons['info'];
        echo '<div class="alert alert-' . htmlspecialchars($type) . '">
            ' . $icon . '
            <span>' . htmlspecialchars($flash['message']) . '</span>
            <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
        </div>';
    }
}

/**
 * Status HTML Badge
 */
function getStatusBadge($status) {
    $map = [
        'Pending'     => ['class' => 'badge-pending', 'label' => 'Pending', 'dot' => 'dot-amber'],
        'In Progress' => ['class' => 'badge-progress', 'label' => 'In Progress', 'dot' => 'dot-blue'],
        'Resolved'    => ['class' => 'badge-resolved', 'label' => 'Resolved', 'dot' => 'dot-emerald'],
        'Rejected'    => ['class' => 'badge-rejected', 'label' => 'Rejected', 'dot' => 'dot-rose'],
    ];

    $info = $map[$status] ?? ['class' => 'badge-neutral', 'label' => $status, 'dot' => 'dot-slate'];
    return '<span class="status-badge ' . $info['class'] . '"><span class="badge-dot ' . $info['dot'] . '"></span>' . htmlspecialchars($info['label']) . '</span>';
}

/**
 * Priority HTML Badge
 */
function getPriorityBadge($priority) {
    $map = [
        'Low'    => 'priority-low',
        'Medium' => 'priority-medium',
        'High'   => 'priority-high',
        'Urgent' => 'priority-urgent',
    ];
    $cls = $map[$priority] ?? 'priority-medium';
    return '<span class="priority-badge ' . $cls . '">' . htmlspecialchars($priority) . '</span>';
}

/**
 * Format Date helper
 */
function formatDatetime($timestamp) {
    if (!$timestamp) return 'N/A';
    return date('M d, Y h:i A', strtotime($timestamp));
}
