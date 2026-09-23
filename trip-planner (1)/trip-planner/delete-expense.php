<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/trips.php');
csrf_require();

$expId = (int)($_POST['id'] ?? 0);
$tripId = (int)($_POST['trip_id'] ?? 0);

$pdo = getDB();
$stmt = $pdo->prepare('SELECT e.id, t.user_id FROM expenses e JOIN trips t ON t.id = e.trip_id WHERE e.id = ?');
$stmt->execute([$expId]);
$row = $stmt->fetch();

if (!$row || (int)$row['user_id'] !== (int)$user['id']) {
    flash('error', 'Expense not found.');
    redirect('/expenses.php?trip_id=' . $tripId);
}

$stmt = $pdo->prepare('DELETE FROM expenses WHERE id = ?');
$stmt->execute([$expId]);
flash('success', 'Expense deleted.');
redirect('/expenses.php?trip_id=' . $tripId);
