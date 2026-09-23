<?php
/**
 * Registered-user authentication / authorization guard.
 */
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id, name, email, role, status FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        if ($user && $user['status'] !== 'active') {
            // account deactivated mid-session
            session_unset();
            session_destroy();
            $user = null;
        }
    }
    return $user;
}

function requireLogin(): array
{
    $user = currentUser();
    if (!$user) {
        flash('error', 'Please log in to continue.');
        redirect('/login.php');
    }
    return $user;
}
