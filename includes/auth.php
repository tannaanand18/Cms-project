<?php
/**
 * Authentication and Access Control
 */

require_once __DIR__ . '/../config/db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'user'
    ];
}

function requireUser() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please login to access your portal.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Admin authentication required.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
    if (!isAdmin()) {
        setFlash('danger', 'Access denied. Administrator privileges required.');
        header('Location: ' . BASE_URL . 'user/dashboard.php');
        exit;
    }
}
