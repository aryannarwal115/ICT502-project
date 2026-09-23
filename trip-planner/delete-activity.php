<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/trips.php');
csrf_require();

$actId = (int)($_POST['id'] ?? 0);
$tripId = (int)($_POST['trip_id'] ?? 0);

$pdo = getDB();
$stmt = $pdo->prepare('SELECT a.id, t.user_id FROM activities a JOIN trips t ON t.id = a.trip_id WHERE a.id = ?');
$stmt->execute([$actId]);
$row = $stmt->fetch();

if (!$row || (int)$row['user_id'] !== (int)$user['id']) {
    flash('error', 'Activity not found.');
    redirect('/trip-details.php?id=' . $tripId);
}

$stmt = $pdo->prepare('DELETE FROM activities WHERE id = ?');
$stmt->execute([$actId]);
flash('success', 'Activity deleted.');
redirect('/trip-details.php?id=' . $tripId);
