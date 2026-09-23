<?php
/**
 * Admin authentication / authorization guard.
 * Admin sessions are kept separate from regular user sessions.
 */
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

function currentAdmin(): ?array
{
    if (!isAdminLoggedIn()) return null;
    static $admin = null;
    if ($admin === null) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: null;
    }
    return $admin;
}

function requireAdmin(): array
{
    $admin = currentAdmin();
    if (!$admin || $admin['status'] !== 'active') {
        session_unset();
        redirect('/admin/login.php');
    }
    return $admin;
}
