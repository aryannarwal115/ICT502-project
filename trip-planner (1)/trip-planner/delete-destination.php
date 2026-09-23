<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/trips.php');
csrf_require();

$destId = (int)($_POST['id'] ?? 0);
$tripId = (int)($_POST['trip_id'] ?? 0);

$pdo = getDB();
$stmt = $pdo->prepare('
  SELECT d.id, t.user_id FROM destinations d JOIN trips t ON t.id = d.trip_id WHERE d.id = ?
');
$stmt->execute([$destId]);
$row = $stmt->fetch();

if (!$row || (int)$row['user_id'] !== (int)$user['id']) {
    flash('error', 'Destination not found.');
    redirect('/trip-details.php?id=' . $tripId);
}

$stmt = $pdo->prepare('DELETE FROM destinations WHERE id = ?');
$stmt->execute([$destId]);
flash('success', 'Destination deleted.');
redirect('/trip-details.php?id=' . $tripId);
