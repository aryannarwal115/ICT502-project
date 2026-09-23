<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/trips.php');
csrf_require();

$tripId = (int)($_POST['trip_id'] ?? 0);
$action = $_POST['action'] ?? '';
$trip = getOwnedTrip($tripId, $user['id']);
if (!$trip) { flash('error', 'Trip not found.'); redirect('/trips.php'); }

$pdo = getDB();

if ($action === 'enable') {
    $stmt = $pdo->prepare('SELECT id FROM trip_shares WHERE trip_id = ?');
    $stmt->execute([$tripId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE trip_shares SET is_active = 1 WHERE trip_id = ?');
        $stmt->execute([$tripId]);
    } else {
        $token = generateShareToken();
        $stmt = $pdo->prepare('INSERT INTO trip_shares (trip_id, token, is_active) VALUES (?, ?, 1)');
        $stmt->execute([$tripId, $token]);
    }
    flash('success', 'Sharing enabled. Anyone with the link can now view this itinerary.');
} elseif ($action === 'disable') {
    $stmt = $pdo->prepare('UPDATE trip_shares SET is_active = 0 WHERE trip_id = ?');
    $stmt->execute([$tripId]);
    flash('success', 'Sharing disabled.');
}

redirect('/trip-details.php?id=' . $tripId);
