<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/trips.php');
csrf_require();

$tripId = (int)($_POST['id'] ?? 0);
$trip = getOwnedTrip($tripId, $user['id']);
if (!$trip) {
    flash('error', 'Trip not found.');
    redirect('/trips.php');
}

$pdo = getDB();
$stmt = $pdo->prepare('DELETE FROM trips WHERE id = ? AND user_id = ?');
$stmt->execute([$tripId, $user['id']]);

if ($trip['cover_image']) {
    $path = __DIR__ . '/uploads/' . $trip['cover_image'];
    if (file_exists($path)) @unlink($path);
}

flash('success', 'Trip deleted.');
redirect('/trips.php');
