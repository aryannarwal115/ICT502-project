<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/trips.php');
csrf_require();

$tripId = (int)($_POST['trip_id'] ?? 0);
$docId = (int)($_POST['id'] ?? 0);
$trip = getOwnedTrip($tripId, $user['id']);
if (!$trip) { flash('error', 'Trip not found.'); redirect('/trips.php'); }

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM trip_documents WHERE id = ? AND trip_id = ?');
$stmt->execute([$docId, $tripId]);
$doc = $stmt->fetch();
if ($doc) {
    $path = __DIR__ . '/uploads/documents/' . $doc['stored_name'];
    if (is_file($path)) @unlink($path);
    $stmt = $pdo->prepare('DELETE FROM trip_documents WHERE id = ? AND trip_id = ?');
    $stmt->execute([$docId, $tripId]);
    flash('success', 'Document removed.');
}
redirect('/trip-details.php?id=' . $tripId);
